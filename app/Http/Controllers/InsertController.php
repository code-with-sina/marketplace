<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;


class InsertController extends Controller
{
    protected $filePath;
    protected $content;
    public function insert(Request $request)
    {
        $data = $this->data();

        if ($data === false) {
            return response()->json(['error' => 'Unable to read the file'], 500);
        }

        $loopFinished = false;

        foreach ($data as $item) {

            $name = $this->splitName($item['name']);
            $this->creatingUser(
                uuid: $item['uuid'],
                email: $item['email'],
                firstname: $name['firstname'],
                lastname: $name['lastname'],
                mobile: $this->modifyMobile($item['mobile_number']),
                username: $item['username'],
                password: $item['password'],
                email_verified_at: $item['email_verified_at'],
                remember_token: $item['remember_token'],
                exp_id: $item["id"],
                created_at: $item['created_at'],
                updated_at: $item['updated_at'],
                priviledge: $item['activate'] == 0 ? "blocked" : "activated",
                emailVerification: $item['email_verified_at'] == null ? "unverified" : "verified"
            );
        }

        $loopFinished = true;

        if ($loopFinished) {
            return response()->json('ok');
        }
    }

    public function splitName($name)
    {
        $parts = explode(' ', $name, 2);
        return ['firstname' => $parts[0],  'lastname' => $parts[1]  ?? ''];
    }

    public function modifyMobile($mobile)
    {
        if (is_null($mobile) || !is_numeric($mobile)) {
            return "null";
        } else {
            $phoneNumber = preg_replace('/\D/', '', $mobile);

            if (Str::startsWith($phoneNumber, '0')) {
                $phoneNumber = '+234' . substr($phoneNumber, 1);
            }

            return $phoneNumber;
        }
    }

    public function data()
    {
        $this->filePath = public_path('datas.json');

        // Check if the file exists and is readable
        if (!file_exists($this->filePath) || !is_readable($this->filePath)) {
            return false;
        }

        // Read the file content
        $this->content = file_get_contents($this->filePath);

        // Decode the JSON content into an associative array
        return json_decode($this->content, true);
    }


    public function creatingUser($uuid, $email, $firstname, $lastname, $mobile, $username, $password, $email_verified_at, $remember_token, $created_at, $updated_at, $exp_id, $priviledge, $emailVerification)
    {
        $state = $this->checkIfExists(uuid: $uuid);

        if($state == 'insert') {
            $createUser = new User();
            $createUser->create([
                "uuid"  =>  $uuid,
                "email" =>  $email,
                "firstname" =>  $firstname,
                "lastname"  =>  $lastname,
                "mobile"    =>  $mobile,
                "username"  =>  $username,
                "password"  =>  $password,
                "email_verified_at" =>  $email_verified_at,
                "remember_token"    =>  $remember_token,
                "created_at"    =>  $created_at,
                "updated_at"    =>  $updated_at,
                "exp_id"    =>  $exp_id,
                "circulated"    =>  "express",
            ]);

            $user = User::where('exp_id', $exp_id)->first();
            $this->appendAuthorization(user: $user, priviledge: $priviledge, emailVerification: $emailVerification);
        }
        
    }

    public function appendAuthorization($user, $priviledge, $emailVerification)
    {
        $user->authorization()->create([
            "priviledge"    =>  $priviledge,
            "email"         =>  $emailVerification,
            "type"          =>  "none",
            "wallet_status" =>  "no_wallet",
            "kyc"           =>  "unapproved",
            "internal_kyc"  =>  "unapproved",
            "profile"       =>  "unchecked",
        ]);
    }


    public function checkIfExists($uuid)
    {
        $checks = User::where('uuid', $uuid)->first();
        if ($checks !== null) {
            return 'taken';
        } else {
            return 'insert';
        }
    }
}
