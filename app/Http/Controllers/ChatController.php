<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\PToP;
use App\Events\Update;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Events\Chat as Dialogue;
use Illuminate\Http\UploadedFile;
use App\Otp\ReleasePaymentOtp;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\TradeController;
use App\Services\CancelTransactionService;
use App\Services\PeerPayment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\File\File;


class ChatController extends Controller
{
    public function sendChat(Request $request)
    {

        if (!auth()->check()) {
            return response()->json([
                'status' => 401,
                'title' => 'Unauthorized: User not authenticated'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'acceptance' => 'required|string',
            'session' => 'required|string',
            'sender' => 'required|string',
            'receiver' => 'required|string|different:sender',
            'message' => 'nullable|string',
            'assets' => 'nullable|string',
            'contentType' => 'nullable|string',
        ]);

        // If validation fails, return a response with the errors
        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ], 422);
        }

        if (
            auth()->user()->uuid === $request->sender
            && auth()->user()->uuid !== $request->receiver
        ) {
            broadcast(
                new Dialogue(
                    acceptance: $request->acceptance,
                    session: $request->session,
                    sender: auth()->user()->uuid,
                    receiver: $request->receiver,
                    admin: null,
                    message: $request->message ?? null,
                    filename: $request->assets ?? null,
                    contentType: $request->contentType
                )
            )->toOthers();
        } else {
            return response()->json([
                'status'    => 400,
                'title'    => 'unauthorized'
            ], 400);
        }
    }


    public function uploadPOP(Request $request)
    {


        if (!auth()->check()) {
            return response()->json([
                'status' => 401,
                'title' => 'Unauthorized: User not authenticated'
            ], 401);
        }



        $validator = Validator::make($request->all(), [
            'acceptance' => 'required|string',
            'session' => 'required|string',
            'sender' => 'required|string',
            'receiver' => 'required|string|different:sender',
            'message' => 'nullable|string',
            'assets' => 'nullable|string',
            'contentType' => 'nullable|string',
            'photo' => 'required|mimes:jpg,jpeg,png|max:3072',
        ]);

        // If validation fails, return a response with the errors
        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ], 422);
        }


        if (
            auth()->user()->uuid === $request->sender
            && auth()->user()->uuid !== $request->receiver
        ) {


            if ($request->has('photo')) {

                $filename = time() . '.' . $request->photo->extension();
                $request->photo->move(public_path('storage/images/contracts'), $filename);

                broadcast(new Update(
                    acceptance: $request->acceptance,
                    session: $request->session,
                    updateState: '1'
                ))->toOthers();

                broadcast(new Dialogue(
                    acceptance: $request->acceptance,
                    session: $request->session,
                    sender: auth()->user()->uuid,
                    receiver: $request->receiver,
                    admin: null,
                    message: "I have fulfilled the order and here is the proof of fulfillment",
                    filename: "https://p2p.ratefy.co/storage/images/contracts/" . $filename,
                    contentType: $request->contentType
                ))->toOthers();

                if (PToP::where([
                    ['session_id', '=', $request->session],
                    ['proof_of_payment_status', '=', 'accept'],
                    ['payment_status', '=', 'released'],
                    ['session_status', '=', 'closed'],
                ])->exists()) {
                    PToP::where('session_id', $request->session)->update([
                        'proof_of_payment'  => "https://p2p.ratefy.co/storage/images/contracts/" . $filename
                    ]);
                    broadcast(new Update(
                        acceptance: $request->acceptance,
                        session: $request->session,
                        updateState: '2'
                    ))->toOthers();
                } else {
                    PToP::where('session_id', $request->session)->update([
                        'payment_status'    => "pending",
                        'proof_of_payment'  => "https://p2p.ratefy.co/storage/images/contracts/" . $filename
                    ]);
                }


                $data = PToP::where('session_id', $request->session)->first();
                return response()->json([
                    'response'   => $data,
                    'status'    => 200
                ]);
            }
        } else {
            return response()->json([
                'status'    => 400,
                'title'    => 'unauthorized'
            ], 400);
        }
    }


    public function sendImageChat(Request $request)
    {

        if (!auth()->check()) {
            return response()->json([
                'status' => 401,
                'title' => 'Unauthorized: User not authenticated'
            ], 401);
        }


        $validator = Validator::make($request->all(), [
            'acceptance' => 'required|string',
            'session' => 'required|string',
            'sender' => 'required|string',
            'receiver' => 'required|string|different:sender',
            'message' => 'nullable|string',
            'assets' => 'nullable|string',
            'contentType' => 'nullable|string',
            'photo' => 'required|mimes:jpg,jpeg,png|max:3072',
        ]);

        // If validation fails, return a response with the errors
        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ], 422);
        }


        if (
            auth()->user()->uuid === $request->sender
            && auth()->user()->uuid !== $request->receiver
        ) {

            if ($request->has('photo')) {

                $filename = time() . '.' . $request->photo->extension();
                $request->photo->move(public_path('storage/images/contracts'), $filename);


                broadcast(new Dialogue(
                    acceptance: $request->acceptance,
                    session: $request->session,
                    sender: auth()->user()->uuid,
                    receiver: $request->receiver,
                    admin: null,
                    message: $request->message ?? null,
                    filename: "https://p2p.ratefy.co/storage/images/contracts/" . $filename,
                    contentType: $request->contentType
                ))->toOthers();
            }
        } else {
            return response()->json([
                'status'    => 400,
                'title'    => 'unauthorized'
            ], 400);
        }
    }


    public function denyPayment(Request $request)
    {
        PToP::where('session_id', $request->session_id)->update([
            'proof_of_payment_status'   => 'denied'
        ]);

        $data = PToP::where('session_id', $request->session_id)->first();
        event(new Update(
            acceptance: $request->acceptance,
            session: $request->session_id,
            updateState: '3'
        ));
        return response()->json([
            'p2p'   => $data
        ]);
    }




    public function acceptPayment(Request $request)
    {
        $beforeOTP = User::where('uuid', auth()->user()->uuid)->first();

        $otp = $beforeOTP->peerpaymentotp()->where('session_id', $request->session_id)->latest()->first();


        if ($otp !== null && $otp->status === "waiting") {
            return response()->json([
                'status'    => 222,
                'message'   => "You have an OTP that is still valid, please use it to proceed", 
                "hash"  => $otp->tally
            ], 400);
        } elseif ($otp !== null && $otp->status === "destroyed") {
            return response()->json([
                'status'    => 222,
                'message'   => "You have an OTP that is still valid, please use it to proceed",
                "hash"  => $otp->tally
            ], 400);
        } else {
            $check = ReleasePaymentOtp::initProcess(session: $request->session_id, acceptance: $request->acceptance);
            return response()->json(['status' => 200, 'message' => $check], 200);
        }
    }

    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'hash' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ], 422);
        }

        $check = ReleasePaymentOtp::reProcess(tally: $request->hash);
        return response()->json(['status' => 200, 'message' => $check]);
    }

    public function confirmOtp(Request $request)
    {
        $check = ReleasePaymentOtp::confirmPassword(otp: $request->otp);

        if ($check['status'] == 200) {
            $account = [
                "session_id" => $check['message']->session_id,
                "acceptance" => $check['message']->acceptance,
            ];
            $response = app(PeerPayment::class)
                ->validate($account)
                ->allocate()
                ->validateIfCancellationOccured()
                ->validateIfTransactionExist()
                ->chargeFee()
                ->makePayment()
                // ->updateTransaction()
                // ->sendPaymentNotification()
                // ->broadcastUpdate()
                ->throwState();

            return response()->json($response, $response->status);
            // $this->processWithdrawal(amount: $check['message']->amount, accountId: $check['message']->accountId);
            // return response()->json(['status' => 200, 'message' => "Your withdrawal has been successfully initiated."], 200);
        } else {
            return response()->json($check);
        }
    }


    public function cancelSession(Request $request)
    {
        $response = app(CancelTransactionService::class)
            ->validate($request->all())
            ->validateTransactionExists()
            ->validateIfPaymentOccured()
            ->validateIfReversalOccurred()
            ->reFund()
            ->cancelCurrentTransaction()
            ->broadcastUpdate()
            ->informStakeholder()
            ->throwStatus();

        return response()->json($response, $response->status);
    }


    public function completeTransaction(Request $request)
    {
        PToP::where('session_id', $request->session_id)->update([
            'session_status'    => 'closed',
            'duration_status'   => 'expired'
        ]);
        $data = PToP::where('session_id', $request->session_id)->first();

        event(new Update(
            acceptance: $request->acceptance,
            session: $request->session_id,
            updateState: '4'
        ));
        return response()->json([
            'p2p'       => $data,
            'status'    => 200
        ]);
    }

    public function reinburseSeller(Request $request)
    {
        PToP::where('session_id', $request->session_id)->update([
            'payment_status' => 'released'
        ]);

        $data = PToP::where('session_id', $request->session_id)->first();
        event(new Update(
            acceptance: $request->acceptance,
            session: $request->session_id,
            updateState: '4'
        ));

        // write the business logic to transfer as an admin
        return response()->json([
            'p2p'   => $data
        ]);
    }

    public function getChat(Request $request)
    {
        $getSession = PToP::where([
            'acceptance_id' => $request->acceptance,
            'session_id' => $request->session
        ])->first();

        if ($getSession->owner_id === auth()->user()->uuid || $getSession->recipient_id === auth()->user()->uuid) {
            $chat = Chat::where('session', $request->session)->where('acceptance', $request->acceptance)->with(['sender', 'receiver'])->get();
            return response()->json($chat);
        } else {
            return response()->json([
                'status'    => 400,
                'title'     => "You are not authorized to see this transaction"
            ], 400);
        }
    }
}
