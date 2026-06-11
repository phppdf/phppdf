<?php

declare(strict_types=1);

namespace PhpPdf\Object\Exception\ObjectRegistryNotFound;

use PhpPdf\Object\Exception\ObjectRegistryNotFound;
use PhpPdf\Object\PdfIndirectReference;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ObjectRegistryNotFound::class)]
#[CoversMethod(ObjectRegistryNotFound::class, 'forReference')]
#[UsesClass(PdfIndirectReference::class)]
final class ForReferenceTest extends TestCase
{
    #[Test]
    public function forReferenceReturnsException(): void
    {
        // Arrange
        $ref = new PdfIndirectReference(5, 0);

        // Act
        $ex = ObjectRegistryNotFound::forReference($ref);

        // Assert
        self::assertInstanceOf(ObjectRegistryNotFound::class, $ex);
        self::assertStringContainsString('5', $ex->getMessage());
    }
}
