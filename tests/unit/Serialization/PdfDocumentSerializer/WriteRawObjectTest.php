<?php

declare(strict_types=1);

namespace PhpPdf\Serialization\PdfDocumentSerializer;

use PhpPdf\Object\PdfRawObject;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentSerializer::class)]
#[CoversMethod(PdfDocumentSerializer::class, 'writeRawObject')]
#[UsesClass(PdfRawObject::class)]
#[UsesClass(PdfMemoryOutput::class)]
final class WriteRawObjectTest extends TestCase
{
    #[Test]
    public function writeRawObjectOutputsValueVerbatim(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeRawObject(new PdfRawObject('null'));

        // Assert
        self::assertSame('null', $output->getContent());
    }

    #[Test]
    public function writeRawObjectPreservesArbitraryContent(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeRawObject(new PdfRawObject('0.75 g'));

        // Assert
        self::assertSame('0.75 g', $output->getContent());
    }
}
