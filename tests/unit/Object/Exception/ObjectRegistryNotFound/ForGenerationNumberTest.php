<?php

declare(strict_types=1);

namespace PhpPdf\Object\Exception\ObjectRegistryNotFound;

use PhpPdf\Object\Exception\ObjectRegistryNotFound;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ObjectRegistryNotFound::class)]
#[CoversMethod(ObjectRegistryNotFound::class, 'forGenerationNumber')]
final class ForGenerationNumberTest extends TestCase
{
    #[Test]
    public function forGenerationNumberReturnsException(): void
    {
        // Arrange / Act
        $ex = ObjectRegistryNotFound::forGenerationNumber(10, 2);

        // Assert
        self::assertInstanceOf(ObjectRegistryNotFound::class, $ex);
        self::assertStringContainsString('10', $ex->getMessage());
        self::assertStringContainsString('2', $ex->getMessage());
    }
}
