<?php

namespace App\Http\Controllers;



use App\Models\Rate;
use App\Models\Ewallet;
use App\Models\BuyerOffer;
use App\Models\Requirement;
use App\Models\SellerOffer;
use Illuminate\Http\Request;
use App\Models\PaymentOption;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


class SearchController extends Controller
{
    public function buyerEwallet($ewallet_id, $payment_option_id)
    {
        $validateParam = [
            'ewallet_id' => $ewallet_id,
            'payment_option_id' => $payment_option_id
        ];

        $validation = Validator::make($validateParam, [
            'ewallet_id' => 'required|string',
            'payment_option_id' => 'required|string'
        ]);

        if ($validation->fails()) {
            return response()->json(['message' => $validation->errors()->first()], 400);
        } else {

            $authUserId = Auth::id();
            $rate = Rate::latest()->first();
            $data = Ewallet::find($ewallet_id);
            $result = $data->selleroffer()
                ->where('payment_option_id', $payment_option_id)
                ->where('approval', 'pending')
                ->where('status', 'active')
                ->when(
                    $data->id == '22' && in_array($payment_option_id, ['48', '52']), 
                    function ($query) use ($authUserId) {
                        if ($authUserId != 7919) {
                            $query->where('user_id', '!=', ['7919']);
                        }
                    }
                ) 
                ->selectRaw("*, COALESCE(percentage * ?, fixed_rate) as ranking_score", [$rate->rate_normal])
                ->orderBy('ranking_score', 'asc')
                ->with(['sellerofferrequirement.requirement', 'sellerterm', 'user'])
                ->paginate(20);

            return response()->json([
                'data'  => $result
            ]);
        }
    }

    public function sellerEwallet($ewallet_id = null, $payment_option_id = null)
    {

        if (!$ewallet_id || !$payment_option_id || $ewallet_id === 'undefined' || $payment_option_id === 'undefined') {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or missing parameters: ewallet_id and payment_option_id are required.',
            ], 400);
        }



        $validateParam = [
            'ewallet_id' => $ewallet_id,
            'payment_option_id' => $payment_option_id
        ];

        $validation = Validator::make($validateParam, [
            'ewallet_id' => 'required|string',
            'payment_option_id' => 'required|string'
        ]);

        if ($validation->fails()) {
            return response()->json(['message' => $validation->errors()->first()], 400);
        } else {
            $rate = Rate::latest()->first();
            $data = Ewallet::find($ewallet_id);

            $result = $data->buyeroffer()
                ->where('payment_option_id', $payment_option_id)
                ->where('approval', 'pending')
                ->where('status', 'active')
                ->selectRaw("*, COALESCE(percentage * ?, fixed_rate) as ranking_score", [$rate->rate_normal])
                ->orderBy('ranking_score', 'desc')
                ->with(['buyerofferrequirement.requirement', 'buyerterm', 'user'])
                ->paginate(20);

            return response()->json([
                'data'  => $result
            ]);
        }
    }

    public function buyerPaymentOption(Request $request)
    {
        $validateParam = [
            'query' => $request->input('query'),
        ];

        $validation = Validator::make($validateParam, [
            'query' => 'required|string',
        ]);

        if ($validation->fails()) {
            return response()->json(['message' => $validation->errors()->first()], 400);
        } else {
            $data = PaymentOption::search($request->input('query'))->get();
            return response()->json(['data' => $data->load('requirement', 'selleroffer', 'selleroffer.sellerterm', 'selleroffer.sellerofferrequirement.requirement')], 200);
        }
    }

    public function sellerPaymentOption(Request $request)
    {
        $validateParam = [
            'query' => $request->input('query'),
        ];

        $validation = Validator::make($validateParam, [
            'query' => 'required|string',
        ]);

        if ($validation->fails()) {
            return response()->json(['message' => $validation->errors()->first()], 400);
        } else {
            $data = PaymentOption::search($request->input('query'))->get();
            return response()->json(['data' => $data->load('requirement', 'buyeroffer', 'buyeroffer.buyerterm', 'buyeroffer.buyerofferrequirement.requirement')], 200);
        }
    }


    public function buyerRequirement(Request $request)
    {
        $validateParam = [
            'query' => $request->input('query'),
        ];

        $validation = Validator::make($validateParam, [
            'query' => 'required|string',
        ]);

        if ($validation->fails()) {
            return response()->json(['message' => $validation->errors()->first()], 400);
        } else {
            $data = Requirement::search($request->input('query'))->get();
            $getActualData = array();
            $collateData = array();
            foreach ($data as $dale) {
                $collateData[] = PaymentOption::find($dale->payment_option_id);
                foreach ($collateData as $extract) {
                    $getActualData[] = $extract->load('selleroffer');
                }
            }
            return response()->json(['data' => $getActualData], 200);
        }
    }

    public function sellerRequirement(Request $request)
    {
        $validateParam = [
            'query' => $request->input('query'),
        ];

        $validation = Validator::make($validateParam, [
            'query' => 'required|string',
        ]);

        if ($validation->fails()) {
            return response()->json(['message' => $validation->errors()->first()], 400);
        } else {
            $data = Requirement::search($request->input('query'))->get();
            $getActualData = array();
            $collateData = array();
            foreach ($data as $dale) {
                $collateData[] = PaymentOption::find($dale->payment_option_id);
                foreach ($collateData as $extract) {
                    $getActualData[] = $extract->load('buyeroffer');
                }
            }
            return response()->json(['data' => $getActualData], 200);
        }
    }

    public function buyeroffer(Request $request)
    {
        $validateParam = [
            'query' => $request->input('query'),
        ];

        $validation = Validator::make($validateParam, [
            'query' => 'required|string',
        ]);

        if ($validation->fails()) {
            return response()->json(['message' => $validation->errors()->first()], 400);
        } else {
            $data = BuyerOffer::search($request->input('query'))->get();
            return response()->json(['data' => $data], 200);
        }
    }


    public function selleroffer(Request $request)
    {
        $validateParam = [
            'query' => $request->input('query'),
        ];

        $validation = Validator::make($validateParam, [
            'query' => 'required|string',
        ]);

        if ($validation->fails()) {
            return response()->json(['message' => $validation->errors()->first()], 400);
        } else {
            $data = SellerOffer::search($request->input('query'))->get();
            return response()->json(['data' => $data], 200);
        }
    }
}
