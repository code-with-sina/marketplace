<?php 

namespace App\UserFacades;

use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Validator;

trait HasRegistrationValidation 
{
    protected $validateParams;

    public function params($parameter) {
        $this->validateParams = $parameter;
        return $this;
    }

    public function validateUser() {
        $validation = Validator::make($this->validateParams, [
            'firstname'     =>  'required|string|max:255',
            'lastname'      =>  'required|string|max:255',
            'username'      =>  'required|string|unique:users,username',
            'email'         =>  'required|email|unique:users,email',
            'mobile'        =>  'required|string|max:14|unique:users,mobile',
            'password'      =>  ['required', 'confirmed', Rules\Password::defaults()],
            'ip'            =>  'required|string',
            'device'        =>  'required|string'
        ]);


        if ($validation->fails()) {
            return ['data' => $validation->errors(), 'status' => 422];
           
        }else{
            return ['data' => (object)$this->validateParams, 'status' => 200];
           
        }
    }
}