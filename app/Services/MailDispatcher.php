<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use App\Mail\TransactionInvoiceMail;

class MailDispatcher
{


    public function send($recipient, $content): void
    {
        Mail::to($recipient->email)->send(new TransactionInvoiceMail($content));
    }
}