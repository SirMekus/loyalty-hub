<?php

namespace App\Repositories;

use App\Interfaces\MoneyTransfer;
use App\Interfaces\ProvidesGatewayFixtures;
use App\Models\ResolvedBankAccount;
use App\Services\PaymentService;
use Emmy\Ego\Gateway\Paystack\Paystack;
use Illuminate\Support\Facades\Http;

class PaystackRepository extends Paystack implements MoneyTransfer, ProvidesGatewayFixtures
{
    public function getProvider(): string
    {
        return 'paystack';
    }

    /**
     * Sample responses for Paystack's transfer endpoints, captured from real test-mode
     * API calls, for tests that exercise the real gateway code path without hitting
     * the network. See ProvidesGatewayFixtures.
     */
    public static function fakeHttpResponses(): array
    {
        return [
            'api.paystack.co/transferrecipient*' => Http::response([
                'status' => true,
                'message' => 'Transfer recipient created successfully',
                'data' => [
                    'active' => true,
                    'currency' => 'NGN',
                    'domain' => 'test',
                    'id' => 123456,
                    'name' => 'Test',
                    'recipient_code' => 'RCP_test000000',
                    'type' => 'nuban',
                    'is_deleted' => false,
                    'details' => [
                        'account_number' => '0000000000',
                        'account_name' => null,
                        'bank_code' => '057',
                        'bank_name' => 'Zenith Bank',
                    ],
                ],
            ]),
            'api.paystack.co/transfer*' => Http::response([
                'status' => true,
                'message' => 'Transfer has been queued',
                'data' => [
                    'reference' => 'test-reference',
                    'domain' => 'test',
                    'amount' => config('business.cashback') * 100,
                    'currency' => 'NGN',
                    'source' => 'balance',
                    'reason' => 'Bonus',
                    'recipient' => 123456,
                    'status' => 'success',
                    'transfer_code' => 'TRF_test000000',
                ],
            ]),
        ];
    }

    /**
     * Paystack caps live account-resolution calls at 3 (in test mode) before it starts rejecting
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
