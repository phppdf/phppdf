<?php

declare(strict_types=1);

namespace PhpPdf\Serialization\PdfDocumentSerializer;

use PhpPdf\Object\PdfInteger;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentSerializer::class)]
#[CoversMethod(PdfDocumentSerializer::class, 'writeInteger')]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfMemoryOutput::class)]
final class WriteIntegerTest extends TestCase
{
    #[Test]
    public function writeIntegerPositive(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeInteger(new PdfInteger(42));

        // Assert
        self::assertSame('42', $output->getContent());
    }

    #[Test]
    public function writeIntegerZero(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeInteger(new PdfInteger(0));

        // Assert
        self::assertSame('0', $output->getContent());
    }

    #[Test]
    public function writeIntegerNegative(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeInteger(new PdfInteger(-7));

        // Assert
        self::assertSame('-7', $output->getContent());
    }
}
