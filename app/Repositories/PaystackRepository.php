<?php

namespace App\Repositories;

use App\Interfaces\MoneyTransfer;
use App\Models\ResolvedBankAccount;
use App\Services\PaymentService;
use Emmy\Ego\Gateway\Paystack\Paystack;

class PaystackRepository extends Paystack implements MoneyTransfer
{
    public function getProvider(): string
    {
        return 'paystack';
    }

    /**
     * Paystack caps live account-resolution calls at 3 before it starts rejecting
     * further requests, so a successfully resolved account_number/bank_code pair is
     * cached and never re-verified against the API.
     */
    public function verifyAccountNumber(array $request = []): array
    {
        $accountNumber = $request['account_number'] ?? null;
        $bankCode = $request['bank_code'] ?? null;

        $cached = ResolvedBankAccount::where('account_number', $accountNumber)
            ->where('bank_code', $bankCode)
            ->first();

        if ($cached) {
            return [
                'status' => true,
                'data' => [
                    'account_number' => $cached->account_number,
                    'account_name' => $cached->account_name,
                ],
            ];
        }

        $response = parent::verifyAccountNumber($request);

        ResolvedBankAccount::updateOrCreate(
            ['account_number' => $accountNumber, 'bank_code' => $bankCode],
            ['account_name' => $response['data']['account_name']],
        );

        return $response;
    }

    public function listBanks(): array
    {
        $response = $this->getBanks();

        $banks = $response['data'];
        $formattedBanks = [];
        for ($i = 0; $i < count($banks); $i++) {
            $formattedBanks[$i] = [
                'bank_name' => $banks[$i]['name'],
                'bank_slug' => $banks[$i]['slug'],
                'bank_id' => $banks[$i]['id'],
                'bank_code' => $banks[$i]['code'],
            ];
        }

        return $formattedBanks;
    }

    /**
     * Takes the webhook payload, verifies that the webhook reference is legit then returns a different
     * payload (for those payments on transactions like deposits) structure gotten via the payment
     * verification endpoint.
     */
    public function handleWebhook(array $payload): array
    {
        $gatewayData = [];

        switch ($payload['event']) {
            case 'transfer.success':
                $gatewayData = $this->verifyTransaction($payload['data']['reference']);
                app(PaymentService::class)->markAsComplete($payload['data']['reference']);
            default:
                throw new \Exception('Paystack Webhook verification failed. ');
        }

        return $gatewayData;
    }
}
