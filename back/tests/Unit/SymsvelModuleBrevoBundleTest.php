<?php

declare(strict_types=1);

namespace SymSvel\Module\SymsvelModuleBrevo\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SymSvel\Module\SymsvelModuleBrevo\SymsvelModuleBrevoBundle;

final class SymsvelModuleBrevoBundleTest extends TestCase
{
    public function testBundleCanBeInstantiated(): void
    {
        self::assertInstanceOf(SymsvelModuleBrevoBundle::class, new SymsvelModuleBrevoBundle());
    }
}
