<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Interfaces\MoneyTransfer;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Throwable;

/**
 * Simulates disbursing a cashback payment to a user's bank account.
 */
class PaymentService
{
    public function startPayment(User $user, ?array $data = []): Payment
    {
        $reference = strtoupper(Str::random(20));
        if (isset($data['reference'])) {
            if (Payment::where('reference', $data['reference'])->exists()) {
                throw new PreconditionFailedHttpException('Payment reference already exists. Please try again.');
            }
            $reference = $data['reference'];
        }
        DB::beginTransaction();
        try {
            $payment = $user->payments()->create([
                'reference' => $reference,
                'amount' => $data['amount'],
                'provider' => $data['provider'] ?? PaymentGateway::PAYSTACK->value,
                'ip_address' => request()->ip(),
                'description' => $data['description'] ?? null,
                'meta' => $data['meta'] ?? null,
            ]);
        } catch (Throwable $e) {
            DB::rollback();
            throw new PreconditionFailedHttpException('Payment could not be initiated. Please try again.');
        }
        DB::commit();

        return $payment->fresh();
    }

    /**
     * @return array{reference: string, status: string, amount: int, currency: string}
     */
    public function disburse(Payment $payment): array
    {
        $paymentProvider = app(MoneyTransfer::class);

        $user = $payment->user;
        $bank = $user->bank;

        if (! $bank) {
            throw new PreconditionFailedHttpException("User [{$user->id}] has no bank account on file to disburse to.");
        }

        $payload = $paymentProvider->prepareForTransfer([
            ...$payment->toArray(),
            ...$bank->toArray(),
            // The transfer API expects the amount in the currency's base denomination (kobo for NGN),
            // not the naira value the Payment model's accessor casts it to.
            'amount' => $payment->getRawOriginal('amount'),
            'recipient_type' => 'nuban',
        ]);

        try {
            $paymentProvider->transfer($payload);
        } catch (Throwable $e) {
            Log::error('[PaymentService] Disbursement failed', [
                'reference' => $payment->reference,
                'user_id' => $user->id,
                'amount' => $payment->getRawOriginal('amount'),
                'error' => $e->getMessage(),
            ]);

            $this->markAsFailed($payment);

            throw $e;
        }

        Log::info('[MockPaymentProvider] Cashback disbursed', [
            'reference' => $payment->reference,
            'user_id' => $user->id,
            'amount' => $payment->amount,
            'status' => 'success',
            'note' => 'Enjoy your weekend. Na you dey reign, boss!',
        ]);

        return [
            'reference' => $payment->reference,
            'status' => 'success',
        ];
    }

    public function markAsComplete(Payment|string $payment): void
    {
        $payment = $this->resolvePayment($payment);
        $payment->status = PaymentStatus::COMPLETED;

        $payment->save();
    }

    public function markAsFailed(Payment|string $payment): void
    {
        $payment = $this->resolvePayment($payment);
        $payment->status = PaymentStatus::FAILED;

        $payment->save();
    }

    private function resolvePayment(Payment|string $payment): Payment
    {
        return $payment instanceof Payment
            ? $payment
            : Payment::where('reference', $payment)->firstOrFail();
    }
}
