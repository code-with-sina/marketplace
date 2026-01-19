<?php

namespace App\Services;

use App\Data\TransactionData;

class InvoiceMessageBuilder
{
    public function build(TransactionData $transaction): string
    {
        return __(
            "A new trade payment of the total sum of :amount :currency<br>
             Transaction Reference: :reference<br>
             Status: :status<br>
             Description: :description<br>
             Currency: :currency<br>
             Name: :sourceaccountname<br>
             Number: :sourceaccountnumber<br>
             Bank: :sourceaccountbank<br>
             Status: :status<br>
             Session ID: :session<br>
             
             
            ",
            [
                'reference'     =>  $transaction->reference,
                'time'          =>  $transaction->createdAt,
                'amount'        =>  $transaction->amount,
                'description'   =>  $transaction->description,
                'currency'      =>  $transaction->currency,
                'session'       =>  $transaction->session,
                'sourceaccountnumber'       =>  $transaction->sourceaccountnumber,
                'sourceaccountname'         =>  $transaction->sourceaccountname,
                'sourceaccountbank'         =>  $transaction->sourceaccountbank,
                'status'        =>  $transaction->status
            ]
        );
    }
}