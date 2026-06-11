<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfObjectRegistry;

use PhpPdf\Object\PdfIndirectObject;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfObjectRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfObjectRegistry::class)]
#[CoversMethod(PdfObjectRegistry::class, 'getLatestGeneration')]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
final class GetLatestGenerationTest extends TestCase
{
    #[Test]
    public function getLatestGenerationReturnsZeroAfterRegister(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $ref = $registry->register(new PdfInteger(1));

        // Act
        $gen = $registry->getLatestGeneration($ref->getObjectNumber());

        // Assert
        self::assertSame(0, $gen);
    }

    #[Test]
    public function getLatestGenerationReturnsOneAfterUpdate(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();
        $ref = $registry->register(new PdfInteger(1));
        $registry->update($ref, new PdfInteger(2));

        // Act
        $gen = $registry->getLatestGeneration($ref->getObjectNumber());

        // Assert
        self::assertSame(1, $gen);
    }
}
