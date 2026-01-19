<?php

namespace App\Data;

class TransactionData 
{
    public function __construct(
        public string $reference,
        public string $createdAt,
        public float $amount,
        public string $description,
        public string $currency,
        public string $session,
        public string $sourceaccountnumber,
        public string $sourceaccountname,
        public string $sourceaccountbank,
        public string $status
    ) {

    }
}


