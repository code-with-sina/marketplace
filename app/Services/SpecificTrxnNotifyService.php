<?php

namespace App\Services;

use App\Data\TransactionData;
use App\Repositories\AdminRepository;
use App\Services\InvoiceMessageBuilder;
use App\Services\MailDispatcher;


class SpecificTrxnNotifyService
{
    public function __construct(
        protected AdminRepository $adminRepository,
        protected InvoiceMessageBuilder $messageBuilder,
        protected MailDispatcher $mailDispatcher
    ) {}

    public function process(
        string $reference,
        string $createdAt,
        float $amount,
        string $description,
        string $currency,
        string $session,
        string $sourceaccountnumber,
        string $sourceaccountname,
        string $sourceaccountbank,
        string $status
    ): void {
        // 1. Create system data
        $transaction = new TransactionData(
            $reference,
            $createdAt,
            $amount,
            $description,
            $currency,
            $session,
            $sourceaccountnumber,
            $sourceaccountname,
            $sourceaccountbank,
            $status
        );

        // 2. Build invoice-like message
        $message = $this->messageBuilder->build($transaction);

        // 3. Get recipients
        $admins = $this->adminRepository->all();
        $allowedEmails = [
            'ratefytechnology@gmail.com',
            // 'gafaromolabakesoliat171@gmail.com',
            // 'judithmbama6@gmail.com',
        ];
        // 4. Dispatch emails
        foreach ($admins as $admin) {
            if (!in_array($admin->email, $allowedEmails)) {
                continue;
            }
            
            $this->mailDispatcher->send($admin, $message);
        }
    }
}