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
#[CoversMethod(PdfObjectRegistry::class, 'register')]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
final class RegisterTest extends TestCase
{
    #[Test]
    public function registerReturnsPdfIndirectReference(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();

        // Act
        $ref = $registry->register(new PdfInteger(1));

        // Assert
        self::assertInstanceOf(PdfIndirectReference::class, $ref);
        self::assertSame(1, $ref->getObjectNumber());
        self::assertSame(0, $ref->getGenerationNumber());
    }

    #[Test]
    public function registerAssignsIncrementingObjectNumbers(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();

        // Act
        $ref1 = $registry->register(new PdfInteger(1));
        $ref2 = $registry->register(new PdfInteger(2));

        // Assert
        self::assertSame(1, $ref1->getObjectNumber());
        self::assertSame(2, $ref2->getObjectNumber());
    }
}
