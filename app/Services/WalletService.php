<?php

declare(strict_types=1);

namespace App\Services;

// use App\Enums\TransactionType;
use App\Enums\TransactionType;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class WalletService
{
    public function getClassAndId(int|array|User|Wallet $model): object
    {
        $class = (is_array($model) || is_int($model)) ? ($model['class'] ?? User::class) : get_class($model);

        $id = (is_array($model) || is_int($model)) ? ($model['id'] ?? $model) : $model->id;

        $obj = new \stdClass();
        $obj->class = $class;
        $obj->id = $id;

        return $obj;
    }
    
    public function getWalletBalance(?Wallet $wallet = null): int
    {
        $balance = ($wallet === null) ? 0 : $wallet->balance;
        return $balance;
    }

    public function getWalletByModelOrId(int|array|User|Wallet $model): Wallet
    {
        $obj = $this->getClassAndId($model);
        $wallet = null;
        DB::transaction(function () use ($obj, &$wallet, $model) {
            if($model instanceof Wallet){
                $wallet = Wallet::where("id", $model->id)->lockForUpdate()->first();
            }
            else{
                $wallet = Wallet::where([
                    ['owner_id', $obj->id],
                    ['owner_type', $obj->class]
                ])->lockForUpdate() //if the caller of this method has this method in a transaction, this will lock the wallet record
                    ->first();
            }
        }, 5); //this will retry 5 times, if there is a deadlock

        if ($wallet === null) {
            $wallet = $this->createEmptyWallet($model);
        }

        return $wallet;
    }

    public function createEmptyWallet(
        User $model,
        array $config = []
    ): Wallet {
        $description = 'New empty wallet created';

        sleep(1); //Important! Add little delay, to separate wallet timestamps

        $wallet = Wallet::create(array_merge($config, [
            'owner_id' => $model->id,
            'owner_type' => get_class($model),
        ]));

        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'amount' => 0,
            'opening_balance' => 0,
            'balance' => 0,
            'closing_balance' => 0,
            'currency' => $wallet->currency,
            'description' => $description,
        ]);
        return $wallet;
    }


    public function creditWallet(
        Wallet $wallet,
        int $amount,
        ?string $description = null,
        ?Order $payment = null,
        ?int $fee = null,
        ?string $currency = null
    ): void {
        DB::beginTransaction();
        try {
            // get the wallet again, so that we can lock it for update
            $wallet = $this->getWalletByModelOrId($wallet);
            $balance = $this->getWalletBalance($wallet);
            $newBalance = $balance + $amount;

            sleep(1); //Important! Add little delay, to separate wallet timestamps

            $data = [
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'opening_balance' => $balance,
                'balance' => $newBalance,
                'fee' => $fee,
                "net_balance" => $newBalance - $fee,
                'closing_balance' => $newBalance,
                'currency' => "NGN",
                'description' => $description ?? 'Credit in NGN',
                'transaction_type' => TransactionType::CREDIT->name,
            ];
            if ($payment !== null) {
                $data['order_id'] = $payment->id;
            }
            WalletTransaction::create($data);
        } catch (\Throwable $e) {
            DB::rollback();
            throw new BadRequestHttpException($e->getMessage());
        }

        DB::commit();
    }
}
