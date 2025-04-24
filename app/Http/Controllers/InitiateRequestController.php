<?php

namespace App\Http\Controllers;


use App\Models\User;
use App\Models\PToP;
use App\Models\TradeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TradeController;
use App\Services\BuyApprovalService;
use App\Services\SellApprovalService;
use App\Services\RejectTradeService;
use App\Services\SellRequestService;
use App\Services\BuyRequestService;
use App\Services\CancelTradeService;
use App\TradeFacades\HasCreatePeerToPeer;

use Illuminate\Support\Facades\Log;

class InitiateRequestController extends Controller
{
    use HasCreatePeerToPeer;

    public function sellerRequest(Request $request)
    {
        $response = app(SellRequestService::class)
            ->validate($request->all())
            ->DeterminantToolKit()
            ->prepareCharge()
            ->createTradeRequest()
            ->sendAdminNotification()
            ->notifyRecipient()
            ->autoCancelTradeRequest()
            ->successState()
            ->throwStatus();
        return response()->json($response, $response->status);
    }


    public function buyerRequest(Request $request)
    {
        $response = app(BuyRequestService::class)
            ->validate($request->all())
            ->validateBalance()
            ->DeterminantToolKit()
            ->prepareCharge()
            ->debit()
            ->createTempTradeData()
            ->successState()
            ->throwStatus();

        return response()->json($response, $response->status);
    }

    public function buyerAcceptRequest(Request $request)
    {
        $response = app(BuyApprovalService::class)
            ->validate($request->all())
            ->validateNoCancelledRequest()
            ->getTradeInvoice()
            ->validateNoPreviousTrade()
            ->validateBalance()
            ->debit()
            // ->processPeerToPeer()
            ->throwStatus();

        return response()->json($response, $response->status);
    }


    public function rejectRequest(Request $request)
    {
        $response = app(RejectTradeService::class)
            ->validate($request->all())
            ->validateTradeExists()
            ->ReverseDebit()
            ->updateTrade()
            ->informStakeholder()
            ->throwState();

        return response()->json($response, $response->status);
    }


    public function sellerAcceptRequest(Request $request)
    {
        $response = app(SellApprovalService::class)
            ->validate($request->all())
            ->validateNoCancelledRequest()
            ->validateNoPreviousTrade()
            ->processPeerToPeer()
            ->throwStatus();

        return response()->json($response, $response->status);
    }


    public function fetchInitiatedOrder()
    {
        $trades = TradeRequest::where('recipient', auth()->user()->uuid)
            ->where('status', 'active')
            ->with(['wallet', 'charge', 'owner', 'recipient'])
            ->get()
            ->map(function ($trade) {
                return [
                    'trade' => $trade,
                    'offer' => $trade->item_for === 'sell'
                        ? $trade->buyerOffer()->with(['ewallet', 'paymentoption'])->first()
                        : $trade->sellerOffer()->with(['ewallet', 'paymentoption'])->first(),
                ];
            });


        return response()->json([
            'data'  => $trades
        ]);
    }


    public function cancelTradeRequest(Request $request)
    {

        $response = app(CancelTradeService::class)
            ->validate($request->all())
            ->validateTradeExists()
            ->validateTradeHasNoAction()
            ->ReverseDebit()
            ->updateTrade()
            ->informStakeholder()
            ->throwState();

        return response()->json($response, $response->status);
    }

    public function cancelBuyingTradeRequest(Request $request)
    {

        $response = app(CancelTradeService::class)
            ->validate($request->all())
            ->validateTradeExists()
            ->validateTradeHasNoAction()
            ->ReverseDebit()
            ->updateTrade()
            ->informStakeholder()
            ->throwState();

        return response()->json($response, $response->status);
    }


    public function compareAndDeleteTradeRequest(Request $request)
    {
        TradeRequest::where('item_id', $request->id)->where('item_for', $request->item_for)->update(['status' => 'cancelled']);
        return response()->json('ok');
    }

    /* This is for a controller injection  */
    public function compareAndDeleteTrade_A_Request($id, $item_for)
    {
        TradeRequest::where('item_id', $id)->where('item_for', $item_for)->update(['status' => 'cancelled']);
        return response()->json('ok');
    }


    public function holdFundAtTradeRequestAccepted($ledgerKey, $amount, $walletName, $sellerId, $itemFor, $acceptance)
    {
        $trade = app(TradeController::class);

        $trade->holdFundAtTradeRequestAccepted(
            regs: $ledgerKey,
            amount: $amount,
            walletName: $walletName,
            sellerId: $sellerId,
            item_for: $itemFor,
            state: 'withhold',
            direction: 'incoming',
            acceptance_id: $acceptance
        );
    }


    /* Don't know where this was used but will figure it out latter */
    public function returnFundToBuyerAccount($ledgerKey, $authorizer, $amount, $offerRate, $walletName, $sellerId, $itemFor)
    {
        try {
            Log::info([$ledgerKey, $authorizer, $amount, $offerRate, $walletName, $sellerId, $itemFor]);
            Http::post('https://walletbased.ratefy.co/api/customer/trade/return-fund-to-buyer-account', [
                'uuid'          => $authorizer,
                'amount'        => ($amount * $offerRate),
                'regs'          => $ledgerKey,
                'state'         => 'withhold',
                'direction'     => 'incoming',
                'walletName'    => $walletName,
                'sellerId'      => $sellerId,
                'item_for'      => $itemFor

            ])->throw();
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }



    public function fetchTrade(Request $request)
    {
        $data = PToP::where('session_id', $request->session_id)->first();
        return response()->json($data);
    }

    public function returnBackFund($reg)
    {
        $trade = app(TradeController::class);
        $trade->returnBackFundFromACancelledTradeRequest(reg: $reg);
    }

    public function returnBackFromRejectedTrade($reg)
    {
        $trade = app(TradeController::class);
        return $trade->returnBackFundFromACancelledTradeRequest(reg: $reg);
    }

    public function notifyStaffs($direction, $content, $uuid, $id)
    {

        $admin = app(AdminController::class);
        $staffing = Http::get('https://staffbased.ratefy.co/api/admin-staff');
        $staffs = $staffing->object();
        $groupStaff = [];
        foreach ($staffs as $staff) {
            $groupStaff[] = $staff->email;
        }

        $admin->staffsNotification(direction: $direction, content: $content, groupStaff: $groupStaff, uuid: $uuid, id: $id);
    }


    public function fetchBalance()
    {
        $user = User::find(auth()->user()->id);
        $balance = Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'x-anchor-key' => env('ANCHOR_KEY'),
        ])->get(env('ANCHOR_SANDBOX') . "accounts/balance/" . $user->customerstatus()->first()->customer()->first()->personalaccount()->first()->personalId . "?include=DepositAccount%2CIndividualCustomer%2CBusinessCustomer");
        if ($balance->status() == 200) {
            $bals = $balance->object();
            return  ['status' => 200, 'data' => $bals->data->availableBalance];
        } else {
            $bals = $balance->object();
            return ['status' => 400, 'data' =>  $bals->errors[0]->detail];
        }
    }


    public function holdFundAtTradeInitiated($ledgerKey, $amount, $offerRate, $walletName, $sellerId, $itemFor)
    {

        $trade = app(TradeController::class);
        $data = $trade->holdFundAtTradeRequestInitiatedForInjection(amount: ($amount * $offerRate), regs: $ledgerKey,  walletName: $walletName, sellerId: $sellerId, itemFor: $itemFor, direction: 'incoming', state: 'withhold');
        return $data;
    }
}
