<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Ewallet;
use App\Models\BuyerOffer;
use App\Models\SellerOffer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Validator;

class OffersController extends Controller
{
    public function fetchEwallet()
    {
        $data = Ewallet::all();
        if ($data->isEmpty()) {
            return response()->json([
                'data' => 'ewallet not available. You can always request for this ewallet via... \'http://api\request-ewallet\' '
            ], 200);
        } else {
            return response()->json([
                'data' => $data->load(['paymentoption', 'paymentoption.requirement']),
                'request' => 'You can always request for an ewallet via... \'http://api\request-ewallet\''
            ], 200);
        }
    }


    public function createBuyerOffer(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'ewallet_id'            =>      ['required'],
            'payment_option_id'     =>      ['required'],
            'percentage'            =>      ['nullable'],
            "fixed_rate"            =>      ['nullable'],
            "min_amount"            =>      ['required'],
            "max_amount"            =>      ['required'],
            "duration"              =>      ['required'],
            "guide"                 =>      ['required', 'string'],
            "buyer_offer_requiremnet"   => ['json', 'required'],
            "buyer_terms_and_conditions" => ['json', 'required']
        ]);

        if ($validation->fails()) {
            return response()->json(['message' => $validation->errors()->first()], 400);
        } else {
            $fixedrate = null;
            $percentage = null;
            if ($request->fixed_rate !== null) {
                $fixedrate = $request->fixed_rate;
            }

            if ($request->percentage !== null) {
                $percentage = $request->percentage;
            }

            if ($request->percentage !== null && $request->fixed_rate !== null) {
                return response()->json(['message' => 'You can only choose either fixed rate or percentage'], 400);
            } else {
                $user = User::find(auth()->user()->id);
                $data = $user->buyeroffer()->create([
                    'guide'             =>  $request->guide,
                    'duration'          =>  $request->duration,
                    'min_amount'        =>  $request->min_amount,
                    'max_amount'        =>  $request->max_amount,
                    'percentage'        =>  $percentage,
                    'ratefyfee'         =>  2,
                    'fixed_rate'        =>  $fixedrate,
                    'ewallet_id'        =>  $request->ewallet_id,
                    'payment_option_id' =>  $request->payment_option_id,
                    'status'            =>  'active',
                    'approval'          =>  'pending'
                ]);

                foreach (json_decode($request->buyer_offer_requiremnet) as $requirement) {
                    $data->buyerofferrequirement()->create([
                        'requirement_id' =>  $requirement->id
                    ]);
                }

                foreach (json_decode($request->buyer_terms_and_conditions) as $terms) {
                    $data->buyerterm()->create([
                        'title' => $terms->title,
                        'condition' => $terms->condition
                    ]);
                }

                $this->notifyStaffs(direction: 'Buyer Offer', content: $data . ' created on >>> ', uuid: auth()->user()->uuid, id: $data->id);

                return response()->json([
                    'data' => $data->load(['buyerofferrequirement', 'buyerterm']),
                    'status'    => 200

                ]);
            }
        }
    }

    public function createSellerOffer(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'ewallet_id'            =>      ['required'],
            'payment_option_id'     =>      ['required'],
            'percentage'            =>      ['nullable'],
            "fixed_rate"            =>      ['nullable'],
            "min_amount"            =>      ['required'],
            "max_amount"            =>      ['required'],
            "duration"              =>      ['required'],
            "guide"                 =>      ['required', 'string'],
            "seller_offer_requiremnet"   => ['json', 'required'],
            'seller_terms_and_conditions' => ['json', 'required']
        ]);

        if ($validation->fails()) {
            return response()->json(['message' => $validation->errors()->first()], 400);
        } else {
            $fixedrate = null;
            $percentage = null;
            if ($request->fixed_rate !== null) {
                $fixedrate = $request->fixed_rate;
            }

            if ($request->percentage !== null) {
                $percentage = $request->percentage;
            }

            if ($request->percentage !== null && $request->fixed_rate !== null) {
                return response()->json(['message' => 'You can only choose either fixed rate or percentage'], 400);
            } else {
                $user = User::find(auth()->user()->id);
                $data = $user->selleroffer()->create([
                    'guide'             =>  $request->guide,
                    'duration'          =>  $request->duration,
                    'min_amount'        =>  $request->min_amount,
                    'max_amount'        =>  $request->max_amount,
                    'percentage'        =>  $percentage,
                    'ratefyfee'         =>  -2,
                    'fixed_rate'        =>  $fixedrate,
                    'ewallet_id'        =>  $request->ewallet_id,
                    'payment_option_id' =>  $request->payment_option_id,
                    'status'            =>  'active',
                    'approval'          =>  'pending'
                ]);

                foreach (json_decode($request->seller_offer_requiremnet) as $requirement) {
                    $data->sellerofferrequirement()->create([
                        'requirement_id' => $requirement->id
                    ]);
                }

                foreach (json_decode($request->seller_terms_and_conditions) as $terms) {
                    $data->sellerterm()->create([
                        'title' => $terms->title,
                        'condition' => $terms->condition
                    ]);
                }

                $this->notifyStaffs(direction: 'Seller Offer', content: $data . ' created on >>> ', uuid: auth()->user()->uuid, id: $data->id);
                return response()->json([
                    'data' => $data->load(['sellerofferrequirement', 'sellerterm']),
                    'status'    => 200
                ]);
            }
        }
    }

    public function getPaymentOption(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'uuid'    =>      ['required'],
        ]);

        if ($validation->fails()) {
            return response()->json(['message' => $validation->errors()->first()], 400);
        } else {
            $data = Ewallet::where('uuid', $request->uuid)->first()->paymentoption()->get();
            if ($data->isEmpty()) {
                return response()->json([
                    'data' => 'This ewallet options not available. You can always request for this ewallet via... \'http://api\request-payment-options\' '
                ], 200);
            } else {
                return response()->json([
                    'data' => $data,
                    'request' => 'You can always request for an ewallet options via... \'http://api\request-payment-options\''
                ], 200);
            }
        }
    }

    public function getPaymentOptionRequirement(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'uuid'    =>      ['required'],
            'option'  =>      ['required']
        ]);

        if ($validation->fails()) {
            return response()->json(['message' => $validation->errors()->first()], 400);
        } else {
            $data = Ewallet::where('uuid', $request->uuid)->first()->paymentoption()->where('option', $request->option)->first()->requirement()->get();
            if ($data->isEmpty()) {
                return response()->json([
                    'data' => 'This ewallet options requirement is not available. You can always request for this ewallet via... \'http://api\request-option-requirement\' '
                ], 200);
            } else {
                return response()->json([
                    'data' => $data,
                    'request' => 'You can always request for an ewallet options requirement via... \'http://api\request-option-requirement\''
                ], 200);
            }
        }
    }

    public function fetchBuyerOffer()
    {
        $user = User::find(auth()->user()->id);
        $data = $user->buyeroffer()->whereIn('status', ['active', 'paused'])->get();
        return response()->json(['data' => $data->load('buyerterm', 'buyerofferrequirement.requirement', 'ewallet')], 200);
    }


    public function pauseBuyerOffer(Request $request)
    {
        $user = User::find(auth()->user()->id);
        $user->buyeroffer()->where('id', $request->id)->update(['status' => 'paused']);
        return response()->json(['data' => 'paused', 'status' => 200], 200);
    }


    public function reactivateBuyerOffer(Request $request)
    {
        $user = User::find(auth()->user()->id);
        $user->buyeroffer()->where('id', $request->id)->update(['status' => 'active']);
        return response()->json(['data' => 'active', 'status' => 200], 200);
    }

    public function createBuyerOfferTerms(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id'      =>      ['required'],
            'title'   =>      ['required'],
            'condition' =>    ['required']
        ]);

        if ($validation->fails()) {
            return response()->json(['message' => $validation->errors()->first()], 400);
        } else {
            $user = User::find(auth()->user()->id);
            $data = $user->buyeroffer()->where('id', $request->id)->first()->buyerterm()->create([
                'title'     => $request->title,
                'condition' => $request->condition
            ]);
            return response()->json(['data' => $data], 200);
        }
    }

    public function deleteBuyerOfferTerms(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id'      =>      ['required'],
            'term_id' =>      ['required']
        ]);

        if ($validation->fails()) {
            return response()->json(['message' => $validation->errors()->first()], 400);
        } else {
            $user = User::find(auth()->user()->id);
            $data = $user->buyeroffer()->where('id', $request->id)->first()->buyerterm()
                ->where('id',  $request->term_id)->delete();
            return response()->json(['data' => $data], 200);
        }
    }

    public function fetchSellerOffer()
    {
        $user = User::find(auth()->user()->id);
        $data = $user->selleroffer()->whereIn('status', ['active', 'paused'])->get();
        return response()->json(['data' => $data->load('sellerterm', 'sellerofferrequirement.requirement', 'ewallet')], 200);
    }

    public function pauseSellerOffer(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id'      =>      ['required']
        ]);

        if ($validation->fails()) {
            return response()->json(['message' => $validation->errors()->first()], 400);
        } else {
            $user = User::find(auth()->user()->id);
            $user->selleroffer()->where('id', $request->id)->update(['status' => 'paused']);
            return response()->json(['data' => 'paused', 'status' => 200], 200);
        }
    }

    public function reactivateSellerOffer(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id'      =>      ['required']
        ]);

        if ($validation->fails()) {
            return response()->json(['message' => $validation->errors()->first()], 400);
        } else {
            $user = User::find(auth()->user()->id);
            $user->selleroffer()->where('id', $request->id)->update(['status' => 'active']);
            return response()->json(['data' => 'active', 'status' => 200], 200);
        }
    }

    public function createSellerOfferTerms(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id'      =>      ['required'],
            'title'   =>      ['required'],
            'condition' =>    ['required']
        ]);

        if ($validation->fails()) {
            return response()->json(['message' => $validation->errors()->first()], 400);
        } else {
            $user = User::find(auth()->user()->id);
            $data = $user->selleroffer()->where('id', $request->id)->first()->sellerterm()->create([
                'title'     => $request->title,
                'condition' => $request->condition
            ]);
            return response()->json(['data' => $data], 200);
        }
    }


    public function deleteSellerOfferTerms(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id'      =>      ['required'],
        ]);

        if ($validation->fails()) {
            return response()->json(['message' => $validation->errors()->first()], 400);
        } else {
            $user = User::find(auth()->user()->id);
            $data = $user->selleroffer()->where('id', $request->id)->first()->sellerterm()
                ->where('id',  $request->term_id)->delete();
            return response()->json(['data' => $data, 'status' => 200], 200);
        }
    }

    public function filterEwallet()
    {
        $data = Ewallet::where('status', 'active')->get();
        if ($data->isEmpty()) {
            return response()->json([
                'data' => 'ewallet not available. You can always request for this ewallet via... \'http://api\request-ewallet\' '
            ], 200);
        } else {
            return response()->json([
                'data' => $data->load(['paymentoption.requirement']),
                'request' => 'You can always request for an ewallet via... \'http://api\request-ewallet\''
            ], 200);
        }
    }


    public function fetchSingleBuyerTerm(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id'    =>      ['required'],
        ]);

        if ($validation->fails()) {
            return response()->json(['message' => $validation->errors()->first()], 400);
        } else {
            $user = User::find(auth()->user()->id);
            $data = $user->buyeroffer()->where('id', $request->id)->first();
            return response()->json([
                'data' => $data->load(['buyerterm', 'buyerofferrequirement.requirement', 'ewallet'])
            ]);
        }
    }


    public function fetchSingleSellerTerm(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id'      =>      ['required']
        ]);

        if ($validation->fails()) {
            return response()->json(['message' => $validation->errors()->first()], 400);
        } else {
            $user = User::find(auth()->user()->id);
            $data = $user->selleroffer()->where('id', $request->id)->first();
            return response()->json([
                'data' => $data->load(['sellerterm', 'sellerofferrequirement.requirement', 'ewallet'])
            ]);
        }
    }


    public function fetchSingleOfferSellerDetail(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id'    =>      ['required'],
        ]);

        if ($validation->fails()) {
            return response()->json(['message' => $validation->errors()->first()], 400);
        } else {
            $user = User::find(auth()->user()->id);
            $data = $user->buyeroffer()->where('id', $request->id)->where('status', 'active')->first();
            if (!$data) {
                $this->compareAndDeleteTradeRequest($request->id, $itemFor = 'sell');
                return response()->json([
                    'message' => 'The data here has been moved, edited or deleted',

                ]);
            } else {
                return response()->json([
                    'data' => $data->load(['paymentoption'])
                ]);
            }
        }
    }


    public function fetchSingleOfferBuyerDetail(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id'    =>      ['required'],
        ]);

        if ($validation->fails()) {
            return response()->json(['message' => $validation->errors()->first()], 400);
        } else {
            $user = User::find(auth()->user()->id);
            $data = $user->selleroffer()->where('id', $request->id)->where('status', 'active')->first();
            if (!$data) {
                $this->compareAndDeleteTradeRequest($request->id, $itemFor = 'buy');
                return response()->json([
                    'message' => 'The data here has been moved, edited or deleted',

                ]);
            } else {
                return response()->json([
                    'data' => $data->load(['paymentoption'])
                ]);
            }
        }
    }


    public function editBuyerOffer(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'offerId'               =>      ['required'],
            'ewallet_id'            =>      ['required'],
            'payment_option_id'     =>      ['required'],
            'percentage'            =>      ['nullable'],
            "fixed_rate"             =>      ['nullable'],
            "min_amount"            =>      ['required'],
            "max_amount"            =>      ['required'],
            "duration"              =>      ['required'],
            "guide"                 =>      ['required', 'string'],
            "buyer_offer_requiremnet"   => ['json', 'required'],
            "buyer_terms_and_conditions" => ['json', 'required']
        ]);

        if ($validation->fails()) {
            return response()->json(['message' => $validation->errors()->first()], 400);
        } else {
            $user = User::find(auth()->user()->id);
            $user->buyeroffer()->where('id', $request->offerId)->update([
                'guide'             =>  $request->guide,
                'duration'          =>  $request->duration,
                'min_amount'        =>  $request->min_amount,
                'max_amount'        =>  $request->max_amount,
                'percentage'        =>  $request->percentage,
                'fixed_rate'        =>  $request->fixed_rate,
                'ewallet_id'        =>  $request->ewallet_id,
                'payment_option_id' =>  $request->payment_option_id,
            ]);

            $state = $user->buyeroffer()->where('id', $request->offerId)->first();


            foreach (json_decode($request->buyer_terms_and_conditions) as $terms) {
                $state->buyerterm()->where('id', $terms->id)->update([
                    'title'         => $terms->title,
                    'condition'     => $terms->condition
                ]);
            }

            foreach (json_decode($request->buyer_offer_requiremnet) as $requirement) {
                $state->buyerofferrequirement()->where('id', $request->offerId)->update([
                    'requirement_id' =>  $requirement->id
                ]);
            }

            return response()->json([
                'status'    => 200,
                'message'   => $state
            ]);
        }
    }

    public function deleteBuyerOffer(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'offerId'    =>      ['required'],
        ]);

        if ($validation->fails()) {
            return response()->json(['message' => $validation->errors()->first()], 400);
        } else {
            $user = User::find(auth()->user()->id);
            $user->buyeroffer()->where('id', $request->offerId)->update([
                'status'    => 'deleted'
            ]);

            return response()->json(['data' => 'deleted', 'status' => 200]);
        }
    }


    public function editSellerOffer(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'offerId'               =>      ['required'],
            'ewallet_id'            =>      ['required'],
            'payment_option_id'     =>      ['required'],
            'percentage'            =>      ['nullable'],
            "fixed_rate"             =>     ['nullable'],
            "min_amount"            =>      ['required'],
            "max_amount"            =>      ['required'],
            "duration"              =>      ['required'],
            "guide"                 =>      ['required', 'string'],
            "seller_offer_requiremnet"   => ['json', 'required'],
            'seller_terms_and_conditions' => ['json', 'required']
        ]);

        if ($validation->fails()) {
            return response()->json(['message' => $validation->errors()->first()], 400);
        } else {
            $user = User::find(auth()->user()->id);
            $state = $user->selleroffer()->where('id', $request->offerId)->update([
                'guide'             =>  $request->guide,
                'duration'          =>  $request->duration,
                'min_amount'        =>  $request->min_amount,
                'max_amount'        =>  $request->max_amount,
                'percentage'        =>  $request->percentage,
                'fixed_rate'        =>  $request->fixed_rate,
                'ewallet_id'        =>  $request->ewallet_id,
                'payment_option_id' =>  $request->payment_option_id,
            ]);

            $state = SellerOffer::where('id', $request->offerId)->first();

            foreach (json_decode($request->seller_terms_and_conditions) as $terms) {
                $state->sellerterm()->where('id', $terms->id)->update([
                    'title'         => $terms->title,
                    'condition'     => $terms->condition
                ]);
            }

            foreach (json_decode($request->seller_offer_requiremnet) as $requirement) {
                $state->sellerofferrequirement()->where('id', $request->offerId)->update([
                    'requirement_id' =>  $requirement->id
                ]);
            }

            return response()->json([
                'status'    => 200,
                'message'   => $state
            ]);
        }
    }


    public function deleteSellerOffer(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'offerId'    =>      ['required'],
        ]);

        if ($validation->fails()) {
            return response()->json(['message' => $validation->errors()->first()], 400);
        } else {
            $user = User::find(auth()->user()->id);
            $user->selleroffer()->where('id', $request->offerId)->update([
                'status'    => 'deleted'
            ]);

            return response()->json(['data' => 'deleted', 'status' => 200]);
        }
    }

    public function fetchSingleBuyerOffer($id)
    {
        $data = BuyerOffer::find($id);
        return response()->json(['data' => $data->load('buyerterm', 'buyerofferrequirement.requirement', 'ewallet', 'user')], 200);
    }

    public function fetchSingleSellerOffer($id)
    {
        $data = SellerOffer::find($id);
        return response()->json(['data' => $data->load('sellerterm', 'sellerofferrequirement.requirement', 'ewallet', 'user')], 200);
    }


    public function fetchSingleBuyingOffer($id)
    {
        $data = SellerOffer::where('id', $id)->where('status', 'active')->first();
        if (!$data) {
            return response()->json([
                'message' => 'The data here has been moved, edited or deleted',

            ]);
        } else {
            return response()->json([
                'data' => $data->load(['sellerterm', 'ewallet', 'paymentoption', 'user']),


            ]);
        }
    }

    public function fetchSingleSellingOffer($id)
    {
        $data = BuyerOffer::where('id', $id)->where('status', 'active')->first();
        if (!$data) {
            return response()->json([
                'message' => 'The data here has been moved, edited or deleted',

            ]);
        } else {
            return response()->json([
                'data' => $data->load(['buyerterm', 'ewallet', 'paymentoption', 'user']),

            ]);
        }
    }

    public function fetchOnlySingleEwallet(Request $request)
    {
        $data = Ewallet::where('id', $request->id)->first();
        return response()->json([
            'data' => $data
        ]);
    }


    /* Comming back to this place */

    public function compareAndDeleteTradeRequest($id, $itemFor, InitiateRequestController $initiateRequestController)
    {
        $initiateRequestController->compareAndDeleteTrade_A_Request($id, $itemFor);
    }

    public function notifyStaffs($direction, $content, $uuid, $id)
    {
        $adminController = app(AdminController::class);
        $staffing = Http::get('https://staffbased.ratefy.co/api/admin-staff');
        $staffs = $staffing->object();
        $groupStaff = [];
        foreach ($staffs as $staff) {
            $groupStaff[] = $staff->email;
        }

        $adminController->staffsNotification($direction, $content, $uuid, $id, $groupStaff);
    }


    public function getOffer($direction, $id)
    {
        if ($direction === "buyeroffer") {
            $data = BuyerOffer::where('id', $id)->first();
            return $data;
        } else {
            $data = SellerOffer::where('id', $id)->first();
            return $data;
        }
    }
}
