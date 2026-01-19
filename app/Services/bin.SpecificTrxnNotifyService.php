<?php

namespace App\Services;
use App\Models\AdminAuth;
use Illuminate\Support\Facades\Mail;


class SpecificTrxnNotifyService {
    
    protected $adminStaffs;
    protected $trxnDetails = [];
    protected $contentConstruct;
    protected $postMail;


    public function getAdminStaff() {
        $this->adminStaffs = AdminAuth::get();
    }


    public function getTransactionDetails($reference, $timeAt, $reason, $amount, $currency, $status) {

        $this->trxnDetails = (object)[
            'reference' => $reference,
            'createdAt' => $timeAt,
            'reason' => $reason,
            'amount' => $amount,
            'currency' => $currency,
            'status' => $status,

        ];
    }


    public function sendMail($user, $content) {
        return $this->postMail = Mail::to($user)->send($content);
    }


    public function messageContentSetter($transactionDetails) {
        $isHtml = true;

        $content = __(
            "An administrative fee has been charged on the total withdrawal sum of :amount " . ($isHtml ? "<br>" : "\n") .
            "Your transaction reference is :reference " . ($isHtml ? "<br>" : "\n") .
            "Your transaction status is :status " . ($isHtml ? "<br>" : "\n") .
            "Your transaction failure reason is :reason " . ($isHtml ? "<br>" : "\n"),
            [
                'amount' => $transactionDetails->amount,
                'reference' => $transactionDetails->reference,
                'status' => $transactionDetails->status,
                'reason' => $transactionDetails->reason,
                'currency' => $transactionDetails->currency,
            ]
        );

        $this->contentConstruct = $content;
    }


    public function dispatchMail($staffs, callable $mail, $contnet) {
        foreach($staffs as $user) {
            $mail($user, $content);
        }
    } 
    

    public function processService($reference, $timeAt, $reason, $amount, $currency, $status) {
        $this->getAdminStaff();
        $this->getTransactionDetails($reference, $timeAt, $reason, $amount, $currency, $status);
        $this->messageContentSetter($this->trxnDetails);
        $this->dispatchMail($this->adminStaffs, $this->postMail, $this->contentConstruct);
    }
    


}