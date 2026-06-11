<?php

declare(strict_types=1);

namespace PhpPdf\Object\Exception\ObjectRegistryNotFound;

use PhpPdf\Object\Exception\ObjectRegistryNotFound;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ObjectRegistryNotFound::class)]
#[CoversMethod(ObjectRegistryNotFound::class, 'forObjectNumber')]
final class ForObjectNumberTest extends TestCase
{
    #[Test]
    public function forObjectNumberReturnsException(): void
    {
        // Arrange / Act
        $ex = ObjectRegistryNotFound::forObjectNumber(42);

        // Assert
        self::assertInstanceOf(ObjectRegistryNotFound::class, $ex);
        self::assertStringContainsString('42', $ex->getMessage());
    }
}
