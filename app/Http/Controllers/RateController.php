<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rate;

class RateController extends Controller
{

    public function createRate(Request $request) {
        Rate::create([
                'rate_decimal' =>  $request->rate_decimal,
                'rate_normal' => $request->rate_normal,
                'assets_id_from' => $request->assets_id_from,
                'assets_id_to' => $request->assets_id_to
        ]);
    }


    public function fetchRate() {
        $rate = Rate::latest()->first();
        return response()->json([
            'data' => $rate
        ], 200);
    }
}
