<?php

declare(strict_types=1);

namespace PhpPdf\Serialization\PdfDocumentSerializer;

use PhpPdf\Object\PdfIndirectObject;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentSerializer::class)]
#[CoversMethod(PdfDocumentSerializer::class, 'writeIndirectObject')]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfMemoryOutput::class)]
final class WriteIndirectObjectTest extends TestCase
{
    #[Test]
    public function writeIndirectObjectWrapsObjectInObjEndobj(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);
        $object = new PdfIndirectObject(3, 0, new PdfInteger(42));

        // Act
        $serializer->writeIndirectObject($object);

        // Assert
        self::assertSame("3 0 obj\n42\nendobj\n", $output->getContent());
    }

    #[Test]
    public function writeIndirectObjectIncludesObjectAndGenerationNumbers(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);
        $object = new PdfIndirectObject(7, 1, new PdfInteger(0));

        // Act
        $serializer->writeIndirectObject($object);

        // Assert
        self::assertStringStartsWith("7 1 obj\n", $output->getContent());
    }
}
