<?php

declare(strict_types=1);

namespace PhpPdf\Serialization\PdfDocumentSerializer;

use PhpPdf\Object\PdfReal;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentSerializer::class)]
#[CoversMethod(PdfDocumentSerializer::class, 'writeReal')]
#[UsesClass(PdfReal::class)]
#[UsesClass(PdfMemoryOutput::class)]
final class WriteRealTest extends TestCase
{
    #[Test]
    public function writeRealFractionalValue(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeReal(new PdfReal(1.5));

        // Assert
        self::assertSame('1.5', $output->getContent());
    }

    #[Test]
    public function writeRealWholeNumberStripsTrailingDecimalPoint(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeReal(new PdfReal(2.0));

        // Assert
        self::assertSame('2', $output->getContent());
    }
}
