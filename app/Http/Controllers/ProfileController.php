<?php

namespace App\Http\Controllers;


use App\Models\User;
use App\Enums\Gender;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;


class ProfileController extends Controller
{
    public function ProfileCreate(Request $request)
    {
        $request->validate([
            'sex'           => ['required', 'string', Rule::enum(Gender::class)],
            'dob'           => ['required', 'string'],
            'address'       => ['required', 'string'],
            'home_number'   => ['required', 'string'],
            'city'          => ['required', 'string'],
            'state'         => ['required', 'string'],
            'country'       => ['required', 'string'],
            'zip_code'      => ['required', 'string'],
        ]);

        $user = User::find(auth()->user()->id);
        $verify = $user->profile()->first();
        if ($verify === null) {
            $profile = $user->profile()->create([
                'sex'           => $request->sex,
                'dob'           => $request->dob,
                'address'       => $request->address,
                'city'          => $request->city,
                'state'         =>  trim($request->state, 'State'),
                'country'       => $request->country,
                'zip_code'      => $request->zip_code,
                'home_number'   => $request->home_number
            ]);

            $user->authorization()->update([
                'profile' => 'has_profile'
            ]);

            if ($profile !== null) {
                return response()->json([
                    'status'    => 200,
                    'data'  => $profile
                ], 200);
            } else {
                return response()->json([
                    'status'    => 400,
                    'title'  => 'something went wrong'
                ], 400);
            }
        } else {
            return response()->json([
                'status'    => 400,
                'title'  => 'sorry you already have a profile'
            ], 400);
        }
    }


    public function updateProfile(Request $request)
    {
        $request->validate([
            'sex'           => ['required', 'string', Rule::enum(Gender::class)],
            'dob'           => ['required', 'string'],
            'address'       => ['required', 'string'],
            'home_number'   => ['required', 'string'],
            'city'          => ['required', 'string'],
            'state'         => ['required', 'string'],
            'country'       => ['required', 'string'],
            'zip_code'      => ['required', 'string'],
        ]);

        $user = User::find(auth()->user()->id);
        $verify = $user->profile()->first();
        if ($verify !== null) {
            $profile = $user->profile()->update([
                'sex'           => $request->sex,
                'dob'           => $request->dob,
                'address'       => $request->address,
                'city'          => $request->city,
                'state'         =>  trim($request->state, 'State'),
                'country'       => $request->country,
                'zip_code'      => $request->zip_code,
                'home_number'   => $request->home_number
            ]);

            $user->authorization()->update([
                'profile' => 'has_profile'
            ]);

            if ($profile !== null) {
                return response()->json([
                    'status'    => 200,
                    'data'  => $profile
                ], 200);
            } else {
                return response()->json([
                    'status'    => 400,
                    'title'  => 'something went wrong'
                ], 400);
            }
        } else {
            return response()->json([
                'status'    => 400,
                'title'  => 'sorry you have no profile yet'
            ], 400);
        }
    }

    public function singleUpdateProfile(Request $request)
    {
        $user = User::find(auth()->user()->id);
        if ($user->profile()->update([$request->single_column => $request->data])) {
            return response()->json([
                'status' => 200,
                'data'  => $user->load('freelance', 'kyc', 'shoppermigrant')
            ], 200);
        } else {
            return response()->json([
                'status' => 400,
                'title'  => 'unable to update. try again later'
            ], 400);
        }
    }

    public function multipleUpdateProfile(Request $request)
    {
        $request->validate([
            'sex'           => ['required', 'string', Rule::enum(Gender::class)],
            'dob'           => ['required', 'string'],
            'address'       => ['required', 'string'],
            'city'          => ['required', 'string'],
            'state'         => ['required', 'string'],
            'country'       => ['required', 'string'],
            'zip_code'      => ['required', 'string'],
        ]);

        $user = User::find(auth()->user()->id);

        if ($user->profile()->update([
            'sex'       => $request->sex,
            'dob'       => $request->dob,
            'address'   => $request->address,
            'city'      => $request->city,
            'state'     => $request->state,
            'country'   => $request->country,
            'zip_code'  => $request->zip_code
        ])) {
            return response()->json([
                'data'  => $user->load('freelance', 'kyc', 'shoppermigrant')
            ], 200);
        } else {
            return response()->json([
                'status' => 200,
                'title'  => 'unable to update'
            ], 400);
        }
    }

    public function FreelanceCreate(Request $request)
    {
        $request->validate([
            "options"       => ['required', 'string'],
            "service_offer" => ['required', 'string'],
            "work_history"  => ['required', 'string'],
            "purpose"       => ['required', 'string'],
            "experience"    => ['required', 'string']
        ]);
        $user = User::find(auth()->user()->id);
        if ($user->profile()->first()->freelance()->first() == null) {
            $user->profile()->first()->freelance()->create([
                'options'           => $request->options,
                'service_offer'     => $request->service_offer,
                'portfolio'         => $request->portfolio ?? null,
                'work_history'      => $request->experience,
                'purpose'           => $request->purpose
            ]);

            if ($user->profile()->first()->freelance()->first() !== null) {

                $user->authorization()->first()->type === 'none' ? $user->authorization()->first()->update(['type' => 'freelance']) : $user->authorization()->first()->update(['type' => 'both']);
                return response()->json([
                    'status' => 200,
                    'data'  => $user->profile()->first()->freelance()->first()
                ], 200);
            }
        } else {
            return response()->json([
                'status'    => 400,
                'title' => ['bad request, use edit or update route', 'alt' => '/single-update-freelance'],
                'data'  => $user->profile()->first()->freelance()->first()
            ], 400);
        }
    }

    public function ShopperMigrantCreate(Request $request)
    {

        $request->validate([
            "option"       => ['required', 'string'],
            "purpose"       => ['required', 'string'],
            "experience"    => ['required', 'string']
        ]);


        $user = User::find(auth()->user()->id);
        if ($user->profile()->first()->shoppermigrant()->first() == null) {
            $user->profile()->first()->shoppermigrant()->create([
                'option'        => $request->option,
                'experience'    => $request->experience,
                'purpose'       => $request->purpose
            ]);
            if ($user->profile()->first()->freelance()->first() !== null) {

                $user->authorization()->first()->type === 'none' ? $user->authorization()->first()->update(['type' => 'shopper-migrant']) : $user->authorization()->first()->update(['type' => 'both']);
                return response()->json([
                    'status' => 200,
                    'data'  => $user->profile()->first()->shoppermigrant()->first()
                ], 200);
            }
        } else {
            return response()->json([
                'status'    => 400,
                'title' => ['bad request, use edit or update route', 'alt' => '/single-update-shoppermigrant'],
                'data'  => $user->profile()->first()->shoppermigrant()->first()
            ], 400);
        }
    }

    public function KYCCreate(Request $request)
    {

        $request->validate([
            "uuid"              => ['required', 'string'],
            "bvn"               => ['required', 'string'],
            "document_id"       => ['required', 'string'],
            "document_type"     => ['required', 'string'],

        ]);


        $user = User::find(auth()->user()->id);

        if ($user->profile()->first()->kyc()->first() === null) {
            $profile->kyc()->create([
                'bvn'           => $request->bvn,
                'selfie'        => $request->document_id,
                'dateOfBirth'   => $request->document_type,
                'gender'        => $request->document_type,
                'idNumber'      => $request->document_type,
                'idType'        => $request->document_type,
                'expiryDate'    => $request->document_type,
                'document_id'   => $request->document_type,
                'company'       => $request->document_type,
            ]);

            if ($user->profile()->first()->kyc()->first() !== null) {
                $user->authorization()->first()->update(['kyc' => 'approved']);
                return response()->json([
                    'status'    => 200,
                    'data'  => $user->profile()->first()->kyc()->first()
                ], 200);
            }
        } else {
            return response()->json([
                'status'    => 400,
                'title' => ['bad request, use edit or update route', 'alt' => '/single-update-kyc'],
                'data'  => $user->profile()->first()->kyc()->first()
            ], 400);
        }
    }

    public function singleUpdateFreelance(Request $request)
    {

        $request->validate([
            "data"          =>  ['required', 'string'],
            "single_column" =>  ['required', 'string']
        ]);

        $user = User::find(auth()->user()->id);

        if ($user->profile()->first()->freelance()->update([$request->single_column => $request->data])) {
            return response()->json([
                'status' => 200,
                'data'  => $user->load('freelance', 'kyc', 'shoppermigrant')
            ], 200);
        } else {
            return response()->json([
                'status' => 400,
                'title'  => 'unable to update'
            ], 400);
        }
    }

    public function singleUpdateShopperMigrant(Request $request)
    {
        $request->validate([
            "data"          => ['required', 'string'],
            "single_column" =>  ['required', 'string']
        ]);

        $user = User::find(auth()->user()->id);
        if ($user->profile()->first()->shoppermigrant()->update([$request->single_column => $request->data])) {
            return response()->json([
                'status' => 200,
                'data'  => $user->profile()->first()->load('freelance', 'kyc', 'shoppermigrant')
            ], 200);
        } else {
            return response()->json([
                'status' => 400,
                'title'  => 'unable to update online shopper or emigrant'
            ], 400);
        }
    }

    public function singleUpdateKYC(Request $request)
    {
        $request->validate([
            "data"          => ['required', 'string'],
            "single_column" =>  ['required', 'string']
        ]);

        $user = User::find(auth()->user()->id);
        if ($user->profile()->first()->kyc()->update([$request->single_column => $request->data])) {
            return response()->json([
                'status'    => 200,
                'data'  => $user->profile()->first()->load('freelance', 'kyc', 'shoppermigrant')
            ], 200);
        } else {
            return response()->json([
                'status'    => 400,
                'title'  => 'unable to update kyc'
            ], 200);
        }
    }

    public function multipleUpdateFreelance(Request $request)
    {

        $request->validate([
            "options"       => ['required', 'string'],
            "service_offer" => ['required', 'string'],
            "work_history"  => ['required', 'string'],
            "purpose"       => ['required', 'string'],
            "experience"    => ['required', 'string']
        ]);

        $user = User::find(auth()->user()->id);
        if ($user->profile()->first()->freelance()->update([
            'options'           => $request->options,
            'service_offer'     => $request->service_offer,
            'portfolio'         => $request->portfolio ?? null,
            'work_history'      => $request->experience,
            'purpose'           => $request->purpose
        ])) {
            return response()->json([
                'status'    => 200,
                'data'  => $user->profile()->first()->load('freelance', 'kyc', 'shoppermigrant')
            ], 200);
        } else {
            return response()->json([
                'status'    => 400,
                'title'  => 'unable to update'
            ], 400);
        }
    }

    public function multipleUpdateShopperMigrant(Request $request)
    {
        $request->validate([
            "options"       => ['required', 'string'],
            "purpose"       => ['required', 'string'],
            "experience"    => ['required', 'string']
        ]);

        $user = User::find(auth()->user()->id);
        if ($user->profile()->first()->shoppermigrant()->update([
            'option'        => $request->option,
            'experience'    => $request->experience,
            'purpose'       => $request->purpose
        ])) {
            return response()->json([
                'status'    => 200,
                'data'  => $user->profile()->first()->load('freelance', 'kyc', 'shoppermigrant')
            ], 200);
        } else {
            return response()->json([
                'status' => 400,
                'title'  => 'unable to update'
            ], 400);
        }
    }

    public function multipleUpdateKYC(Request $request)
    {
        $request->validate([
            "uuid"              => ['required', 'string'],
            "bvn"               => ['required', 'string'],
            "document_id"       => ['required', 'string'],
            "document_type"     => ['required', 'string'],

        ]);

        $user = User::find(auth()->user()->id);
        if ($user->profile()->first()->kyc()->update([
            'bvn'           => $request->bvn,
            'document_id'   => $request->document_id,
            'document_type' => $request->document_type
        ])) {
            return response()->json([
                'status'    => 200,
                'data'  => $user->profile()->first()->load('freelance', 'kyc', 'shoppermigrant')
            ], 200);
        } else {
            return response()->json([
                'status'    => 400,
                'title'  => 'unable to update'
            ], 400);
        }
    }

    public function getFullProfile()
    {
        $user = User::find(auth()->user()->id);
        $profile = $user->profile()->first();
        if ($profile !== null) {
            return response()->json([
                'status'    => 200,
                'data'  => $user->profile()->first()->load('freelance', 'kyc', 'shoppermigrant')
            ], 200);
        } else {
            return response()->json([
                'status'    => 400,
                'title'      => 'user record not found'
            ], 400);
        }
    }

    public function getWorkOptions()
    {

        $user = User::find(auth()->user()->id);
        $profile = $user->profile()->first();
        if ($profile !== null) {
            return response()->json([
                'status'    => 200,
                'data'  => $user->profile()->first()->load('freelance',  'shoppermigrant')
            ], 200);
        } else {
            return response()->json([
                'status'    => 400,
                'title'      => 'user record not found'
            ], 400);
        }
    }


    public function profileVerified()
    {

        $user = User::find(auth()->user()->id);

        return response()->json([
            'count'  => $user->profile()->first()
        ], 200);
    }
}
