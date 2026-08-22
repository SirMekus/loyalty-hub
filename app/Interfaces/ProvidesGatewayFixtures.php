<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Optional, test-support-only contract. A MoneyTransfer implementation that also
 * implements this can supply its own Http::fake()-compatible sample responses,
 * so tests that exercise the real gateway code path (rather than mocking
 * MoneyTransfer itself) aren't hardcoded to one specific provider's URLs and
 * response shapes, and keep working if the bound provider changes.
 */
interface ProvidesGatewayFixtures
{
    /**
     * @return array<string, mixed> An Http::fake()-compatible URL pattern => response map.
     */
    public static function fakeHttpResponses(): array;
}
