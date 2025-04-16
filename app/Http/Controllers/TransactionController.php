<?php

namespace App\Http\Controllers;

use App\Models\PToP;
use App\Models\TradeRequest;
use App\Models\Image;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
// use App\Jobs\ReleasePaymentJob;


class TransactionController extends Controller
{
    public function getPTOP()
    {
        $data = PToP::all();
        return response()->json([
            'data'  => $data
        ]);
    }

    public function getSession($id, $session)
    {
        //first authenticate the parties invloved

        $parties = PToP::where([
            'acceptance_id' => $id,
            'session_id' => $session
        ])->first();


        $authenticated = in_array(auth()->user()->uuid, [$parties->owner_id, $parties->recipient_id]);
        if ($authenticated) {
            $ptop = PToP::where([
                'acceptance_id' => $id,
                'session_id' => $session
            ])
                ->with(['ownerDetail', 'recipientDetail', 'trade.charge', 'trade']) // Load trade
                ->first();


            if ($ptop) {

                if ($ptop->item_for === 'sell') {
                    $offer = $ptop->buyerOffer()->with(['ewallet', 'paymentoption'])->first();
                } else {
                    $offer = $ptop->sellerOffer()->with(['ewallet', 'paymentoption'])->first();
                }



                return response()->json([
                    'data'  => $ptop->setRelation('offer', $offer)
                ]);
            }
        } else {
            return response()->json([
                'status'    => 400,
                'title'     => "You are not authrized to see this transaction"
            ], 400);
        }
    }


    public function fetchActtiveTransactions()
    {
        $data = PToP::where(function ($query) {
            $query->where('owner_id', auth()->user()->uuid)
                ->orWhere('recipient_id', auth()->user()->uuid);
        })->where('session_status', 'open')->with(['ownerDetail', 'recipientDetail'])->paginate(5);
        return response()->json([
            'data'  => $data
        ]);
    }


    public function fetchPastTransactions()
    {
        $data = PToP::where(function ($query) {
            $query->where('owner_id', auth()->user()->uuid)
                ->orWhere('recipient_id', auth()->user()->uuid);
        })->where('session_status', 'closed')->with(['ownerDetail', 'recipientDetail'])->paginate(5);
        return response()->json([
            'data'  => $data
        ]);
    }

    public function fetchListofP2PsByLastest()
    {
        $data = PToP::latest()->take(5)->get();
        return response()->json([
            'data'  => $data
        ]);
    }


    public function getListOfTicketsByLatest()
    {
        $data = PToP::where('reportage', 'open_ticket')->latest()->take(5)->get();
        return response()->json([
            'data'  => $data
        ]);
    }


    public function getP2PData()
    {
        $p2p = PToP::paginate(10);
        return response()->json($p2p);
    }


    public function getPendingP2PData()
    {
        $p2p = PToP::where('payment_status', 'pending')->paginate(10);
        return response()->json($p2p);
    }

    public function getProcessingP2PData()
    {
        $p2p = PToP::where('session_status', 'open')->paginate(10);
        return response()->json($p2p);
    }

    public function getCompleteP2PData()
    {
        $p2p = PToP::where('proof_of_payment_status', 'accept')->where('session_status', 'closed')->paginate(10);
        return response()->json($p2p);
    }

    public function getCanceledP2PData()
    {
        $p2p = PToP::where('session_status', 'closed')->paginate(10);
        return response()->json($p2p);
    }

    public function getDisputedP2PData()
    {
        $p2p = PToP::where('reportage', 'open_ticket')->paginate(10);
        return response()->json($p2p);
    }



    public function getFullORderDetails($session)
    {

        $peertopeer = PToP::where('session_id', $session)->first();
        $order = TradeRequest::where('item_id', $peertopeer->item_id)->first();
        $offer = $peertopeer->item_for == 'sell' ? $this->getSellerOfferDetails($peertopeer->item_id) : $this->getBuyerOfferDetails($peertopeer->item_id);
        $buyer = $peertopeer->owner == 'buyer' ?  $this->userDetail($peertopeer->owner_id) : $this->userDetail($peertopeer->recipient_id);
        $seller = $peertopeer->owner == 'seller' ?  $this->userDetail($peertopeer->owner_id) : $this->userDetail($peertopeer->recipient_id);

        return response()->json([
            'Peer'      => $peertopeer,
            'Order'     => $order,
            'Offer'     => $offer,
            'Buyer'     => $buyer,
            'Seller'    => $seller
        ]);
    }


    public function getBuyerOfferDetails($id)
    {
        $offer = Http::post("https://offerbased.ratefy.co/api/fetch-single-offer-seller-detail", [
            'id' => $id
        ]);
        return $offer->body();
    }

    public function getSellerOfferDetails($id)
    {
        $offer = Http::post("https://offerbased.ratefy.co/api/fetch-single-offer-buyer-detail", [
            'id' => $id
        ]);
        return $offer->body();
    }

    public function userDetail($uuid)
    {
        $user = Http::post("https://userbased.ratefy.co/api/detail", [
            'uuid' => $uuid
        ]);
        return $user->body();
    }


    public function getTrade($reg)
    {
        $data = PToP::where('fund_reg', $reg)->first();
        return response()->json($data);
    }
}
