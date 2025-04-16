<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdminController extends Model
{
    use HasFactory;

    public function allUsers(){
        $user = User::orderBy('id', 'DESC')->with(['authorization', 'activity', 'tag', 'miniprofile'])->paginate(10);
        return response()->json($user);
    }

    public function usersType() {
            $user = User::all();
                return response()->json([
                            'data'      => $user->load('authorization')
                        ], 200);
    }

    public function singleUser(Request $request) {
        $user = User::where('uuid', $request->uuid)->first();
        return response()->json([
            'data'  => $user->load(['authorization', 'activity', 'tag', 'miniprofile'])
        ], 200);
    }

    public function searchSingleUser(Request $request) {
        if($request->determinant  === "email") {
            $user = User::where('email', $request->search)->first();
            return response()->json([
                'data'  => $user->load(['authorization', 'activity', 'tag', 'miniprofile'])
            ], 200);
        }else {
            $user = User::where('username', $request->search)->first();
            return response()->json([
                'data'  => $user->load(['authorization', 'activity', 'tag', 'miniprofile'])
            ], 200);
        }
    }


    public function getUserWithoutDetail($uuid) {
        $user = User::where('uuid', $uuid)->first();
        return response()->json($user, 200);
    }
}
