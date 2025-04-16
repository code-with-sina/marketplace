<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\CustomerStatus;
use App\Models\WithdrawalJournal;
use Illuminate\Support\Facades\Log;
use App\Services\SubAccountService;
use App\Services\WalletFeeService;
use App\Http\Controllers\MessengerController;

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
            // Log::info(['data' => $request->data]);

            $users = CustomerStatus::where('customerId', $request->data['relationships']['customer']['data']['id'])->first();
            $this->user = $users->user()->first();
            $this->updateApprovalSuccessStatus();
            $this->eventLogger(user: $this->user);
            Log::info(['approved', $this->user->authorization()->first()->kyc]);
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
}
