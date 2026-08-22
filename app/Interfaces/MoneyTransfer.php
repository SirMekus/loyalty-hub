<?php

declare(strict_types=1);

namespace App\Interfaces;

interface MoneyTransfer
{
    public function getProvider(): string;

    public function prepareForTransfer(array $data): array;

    public function listBanks(): array;

    public function verifyAccountNumber(array $data): array;

    public function transfer(array $payload): array;
}
