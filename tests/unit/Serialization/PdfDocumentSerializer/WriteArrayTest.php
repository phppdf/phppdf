<?php

declare(strict_types=1);

namespace PhpPdf\Serialization\PdfDocumentSerializer;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentSerializer::class)]
#[CoversMethod(PdfDocumentSerializer::class, 'writeArray')]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfMemoryOutput::class)]
final class WriteArrayTest extends TestCase
{
    #[Test]
    public function writeArrayEmpty(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeArray(new PdfArray([]));

        // Assert
        self::assertSame('[]', $output->getContent());
    }

    #[Test]
    public function writeArraySingleItem(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeArray(new PdfArray([new PdfInteger(1)]));

        // Assert
        self::assertSame('[1]', $output->getContent());
    }

    #[Test]
    public function writeArrayMultipleItemsSeparatedBySpaces(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeArray(new PdfArray([
            new PdfInteger(1),
            new PdfInteger(2),
            new PdfInteger(3),
        ]));

        // Assert
        self::assertSame('[1 2 3]', $output->getContent());
    }
}
