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
#[CoversMethod(PdfDocumentSerializer::class, 'writeIndirectObjects')]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfMemoryOutput::class)]
final class WriteIndirectObjectsTest extends TestCase
{
    #[Test]
    public function writeIndirectObjectsReturnsOffsetsByObjectNumber(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);
        $objects = [
            new PdfIndirectObject(1, 0, new PdfInteger(10)),
            new PdfIndirectObject(2, 0, new PdfInteger(20)),
        ];

        // Act
        $offsets = $serializer->writeIndirectObjects($objects);

        // Assert — object 1 starts at byte 0; object 2 starts after "1 0 obj\n10\nendobj\n" (18 bytes)
        self::assertSame([1 => 0, 2 => 18], $offsets);
    }

    #[Test]
    public function writeIndirectObjectsWithEmptyIterableReturnsEmptyArray(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $offsets = $serializer->writeIndirectObjects([]);

        // Assert
        self::assertSame([], $offsets);
    }
}
