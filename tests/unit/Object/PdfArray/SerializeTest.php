<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfArray;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfArray::class)]
#[CoversMethod(PdfArray::class, 'serialize')]
#[UsesClass(PdfDocumentSerializer::class)]
#[UsesClass(PdfMemoryOutput::class)]
#[UsesClass(PdfInteger::class)]
final class SerializeTest extends TestCase
{
    #[Test]
    public function serializeWritesBracketedItems(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        (new PdfArray([new PdfInteger(1), new PdfInteger(2)]))->serialize($serializer);

        // Assert
        self::assertSame('[1 2]', $output->getContent());
    }

    #[Test]
    public function serializeEmptyArrayWritesEmptyBrackets(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        (new PdfArray([]))->serialize($serializer);

        // Assert
        self::assertSame('[]', $output->getContent());
    }
}
