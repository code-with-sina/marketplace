<?php

namespace App\Messages;

use Illuminate\Support\Facades\Http;

class ShortMessageService
{
    public $from;
    public $to;
    public $message;

    public function __construct()
    {
        return $this;
    }

    public function from($from)
    {
        $this->from = $from;
        return $this;
    }

    public function to($to)
    {
        $this->to =  $to;
        return $this;
    }

    public function message($message)
    {
        $this->message = $message;
        return $this;
    }

    public function send()
    {
        if (!$this->to || !$this->message) {
            throw new \Exception('SMS not correct... ' . $this->to . '--' . $this->message);
        }

        return Http::post('https://api.ng.termii.com/api/sms/send', [
            'from'  => 'ratefy',
            'to'    => $this->to,
            'sms'   => $this->message,
            'type'  => 'plain',
            'channel' => 'generic',
            'api_key'   => 'TLN6WXNS4VtM5n08puP15RPhsZhDRfyH64Ybi47mEkG5dFyQQ7DtCnYpk4eNk4',
        ]);
    }
}
