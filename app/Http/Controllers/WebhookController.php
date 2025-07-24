<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\CustomerStatus;
use App\Models\TransactionEvent;
use App\Models\WithdrawalJournal;
use App\Services\UpdateAccountService;
use App\Services\WalletFeeService;
use Illuminate\Support\Facades\Log;
use App\Services\SubAccountService;
use App\Models\TransactionalJournal;
use App\Services\PostBuyRequestService;
use App\Http\Controllers\MessengerController;
use App\Services\PostBuyApprovalService;
use App\Services\PostPeerPaymentService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Sleep;

class WebhookController extends Controller
{

    protected User $user;
    protected $eventData;

    public function handle(Request $request)
    {
        
        $this->eventData = $request->all();
        $homeSecret = config('app.webhook_secret');
        $getAnchorSignature = $request->header('x-anchor-signature');
        $hmacSha1Hash = hash_hmac('sha1', $request->getContent(), $homeSecret, false);
        $base64EncodeHash = base64_encode($hmacSha1Hash);

        if ($getAnchorSignature !== $base64EncodeHash) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }


        if ($request->data['type'] === 'customer.identification.approved') {

            $users = CustomerStatus::where('customerId', $request->data['relationships']['customer']['data']['id'])->first();
            $this->user = $users->user()->first();
            $this->updateApprovalSuccessStatus();
            $this->eventLogger(user: $this->user);
            Log::info(['approved', $this->user->authorization()->first()->kyc]);
            $this->updateUserAccount($this->user->uuid);

        }

        if ($request->data['type'] === 'customer.identification.error') {
            $users = CustomerStatus::where('customerId', $request->data['relationships']['resource']['data']['id'])->first();
            $this->user = $users->user()->first();
            $this->eventLogger(user: $this->user);
            Log::info(['error', $this->user->authorization()->first()->kyc]);
        }

        if ($request->data['type'] === 'customer.identification.rejected') {
            $users = CustomerStatus::where('customerId', $request->data['relationships']['resource']['data']['id'])->first();
            $this->user = $users->user()->first();
            $this->updateApprovalFailureStatus();
            $this->eventLogger(user: $this->user);
            Log::info(['rejected', $this->user->authorization()->first()->kyc]);
        }


        if ($request->data['type'] === 'nip.transfer.successful') {

            $withdrawal = WithdrawalJournal::where('reference', $request->included[1]['attributes']['reference'])->where('status', 'pending')->first();
            if ($withdrawal !== null) {
                $withdrawal->update(['status' => 'success']);
                $uuid =  $withdrawal->user->uuid;
                if ($uuid) {
                    app(WalletFeeService::class)
                        ->getAuthorizer(uuid: $uuid, ref: $request->included[1]['attributes']['reference'])
                        ->getAdmin()
                        ->processTransaction()
                        ->createJournal()
                        ->throwState();

                    $messengerController = app(MessengerController::class);
                    $messengerController->sendWithdrawalSuccessNotification($uuid, $request->included[1]['attributes']['amount']);
                    $this->eventLogger(user: $withdrawal->user);
                }
            }
        }


        if ($request->data['type'] === 'nip.transfer.failed') {
            $withdrawal = WithdrawalJournal::where('reference', $request->included[1]['attributes']['reference'])->where('status', 'pending')->first();
            $withdrawal->update([
                'status' => 'failed',
                'reason_for_failure' => $request->included[0]['attributes']['failureReason'],
            ]);
            $this->eventLogger(user: $withdrawal->user);
        }





        if($request->data['type'] === 'book.transfer.failed') {
            $transaction = TransactionEvent::where('reference',  $request->included[1]['attributes']['reference'])->where('status', 'initiated')->first();
            $transaction->transactionevent()->update([
                 'status' => 'failed', 
                 'event_id' => $this->eventData['data']['id'],
                 'event_type' => $this->eventData['data']['type'],
                 'message'   => $this->eventData['data']['attributes']['failureEventData']['message'] ?? "null",
                 'payload'   => json_encode($this->eventData),
                 'event_time' => $this->eventData['data']['attributes']['createdAt']
             ]);
            
         }
         
         if($request->data['type'] === 'book.transfer.successful') {

            $payload = json_decode($request->getContent(), true);
            $transfer = collect($payload['included'])->firstWhere('type', 'BOOK_TRANSFER');
            Log::info(['transfer' => $transfer]);
            $reference = $transfer['attributes']['reference'];
            Log::info(['reference' => $reference]);
            $journal = TransactionalJournal::where('api_reference',  $reference)->first();
            Log::info(['journal' => $journal]);
            $transaction = TransactionEvent::where('reference',  $journal->source_reference)->where('status', 'initiated')->latest()->first();
            if ($transaction->type == "BuyerRequest") {
                TransactionEvent::where('reference',  $journal->source_reference)->update([
                    'status' => 'successful',
                    'event_id' => $payload['data']['id'],
                    'event_type' => $payload['data']['type'],
                    'message' => $payload['data']['attributes']['failureEventData']['message'] ?? "null",
                    'payload' => json_encode($payload),
                    'event_time' => $payload['data']['attributes']['createdAt']
                ]);

                app(PostBuyRequestService::class)
                ->retreiveTempTradeData($journal->source_reference)
                ->DeterminantToolKit()
                ->prepareCharge()
                ->createTradeRequest()
                ->sendAdminNotification()
                ->notifyRecipient()
                ->autoCancelTradeRequest()
                ->successState()
                ->throwStatus();
            }elseif ($transaction->type == "BuyerApproval") {
                TransactionEvent::where('reference',  $journal->source_reference)->update([
                    'status' => 'successful',
                    'event_id' => $payload['data']['id'],
                    'event_type' => $payload['data']['type'],
                    'message' => $payload['data']['attributes']['failureEventData']['message'] ?? "null",
                    'payload' => json_encode($payload),
                    'event_time' => $payload['data']['attributes']['createdAt']
                ]);

                app(PostBuyApprovalService::class)
                ->processPeerToPeer($journal->source_reference)
                ->broadcastPeerToPeer()
                ->throwStatus();
            }elseif ($transaction->type == "PeerPaymentFee") {
                TransactionEvent::where('reference',  $journal->source_reference)->update([
                    'status' => 'successful',
                    'event_id' => $payload['data']['id'],
                    'event_type' => $payload['data']['type'],
                    'message' => $payload['data']['attributes']['failureEventData']['message'] ?? "null",
                    'payload' => json_encode($payload),
                    'event_time' => $payload['data']['attributes']['createdAt']
                ]);

               
            }elseif ($transaction->type == "Disbursement") {
                TransactionEvent::where('reference',  $journal->source_reference)->update([
                    'status' => 'successful',
                    'event_id' => $payload['data']['id'],
                    'event_type' => $payload['data']['type'],
                    'message' => $payload['data']['attributes']['failureEventData']['message'] ?? "null",
                    'payload' => json_encode($payload),
                    'event_time' => $payload['data']['attributes']['createdAt']
                ]);

                $fee = TransactionEvent::where('reference',  $journal->source_reference)->where('type', 'PeerPaymentFee')->where('status', 'successful')->first();
                $disbursement = TransactionEvent::where('reference',  $journal->source_reference)->where('type', 'Disbursement')->where('status', 'successful')->first();
                Log::info(['disbursement' => $disbursement]);
                if($fee && $disbursement) {
                    Log::info(['data' => "I got here"]);
                    app(PostPeerPaymentService::class)
                    ->getData($journal->source_reference)
                    ->updateTransaction()
                    ->sendPaymentNotification()
                    ->broadcastUpdate()
                    ->throwStatus();
                } 
            }else {
                Log::info(['type' => $transaction->type]);
            }
            
         }
        return response()->json(['message' => 'Webhook processed successfully'], 200);
    }


    public function updateApprovalSuccessStatus()
    {
        $this->user->authorization()->update(['kyc' => 'approved']);
        $this->user->customerstatus()->update(['status' => 'fully-verified']);
        app(SubAccountService::class)->validateUser($this->user->uuid)
            ->validateUserKyc()
            ->processEscrow()
            ->processPersonal()
            ->createSubAccount()
            ->throwStatus();
    }


    public function updateApprovalFailureStatus()
    {
        $this->user->authorization()->update(['kyc' => 'rejected']);
        $this->user->customerstatus()->update(['status' => 'rejected']);
    }


    public function updateApprovalErrorStatus()
    {
        $this->user->authorization()->update(['kyc' => 'error']);
    }


    public function eventLogger($user)
    {


        $user->webhookevent()->create([
            'event_id' => $this->eventData['data']['id'],
            'event_type' => $this->eventData['data']['type'],
            'message'   => $this->eventData['data']['attributes']['failureEventData']['message'] ?? "null",
            'payload'   => json_encode($this->eventData),
            'event_time' => $this->eventData['data']['attributes']['createdAt']
        ]);
    }



    public function transactionJournal()
    {
        //for all transaction journal 
    }


    public function updateUserAccount($uuid)
    {
        Sleep(10);
        app(UpdateAccountService::class)
        ->getUser(uuid: $uuid)
        ->validateUserHasPersonalAccount()
        ->validateUserHasEscrowAccount()
        // ->fetchAccount()
        ->setState()
        ->updateAccount();
    }
}
