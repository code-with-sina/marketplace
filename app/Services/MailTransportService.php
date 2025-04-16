<?php

namespace App\Services;

class MailTransportService
{

    private $errorState = false;
    private $success;
    private $error;

    protected $subject;
    protected $recipinet;
    protected $content;

    public function subject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    public function isUser($recipinet) {}


    public function isAdmin($recipient) {}


    public function content()
    {
        $this->content = $this->createNaration();
    }

    public function channel()
    {
        $this->isQueue();
        $this->isDirect();
    }


    public function send()
    {
        Mail::send(new AllPurposeMail());
    }


    public function isQueue()
    {
        return dispatch()->delay(5);
    }

    public function isDirect() {
        
    }



    public function setErrorState() {}


    public function setSuccessState() {}


    public function createNaration()
    {
        /* This is a sample naration. we are coming to it later */


        $isHtml = true;
        return __(
            "An administrative fee has been charged on the total withdrawal sum of :amount " . ($isHtml ? "<br>" : "\n") .
                "Your transaction reference is :reference " . ($isHtml ? "<br>" : "\n") .
                "Your transaction status is :status " . ($isHtml ? "<br>" : "\n") .
                "Your transaction failure reason is :failureReason " . ($isHtml ? "<br>" : "\n") .
                "Your transaction transfer id is :transferId " . ($isHtml ? "<br>" : "\n"),
            [
                'amount' => self::WITHDRAWAL_FEE,
                'reference' => $this->reference,
                'status' => $this->apiRef->status,
                'failureReason' => $this->apiRef->failureReason,
                'transferId' => $this->apiRef->transferId,
            ]
        );
    }
}
