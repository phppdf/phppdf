<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfStream;

use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfRawStreamData;
use PhpPdf\Object\PdfStream;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PhpPdf\Serialization\PdfStreamSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfStream::class)]
#[CoversMethod(PdfStream::class, 'serialize')]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfDocumentSerializer::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfMemoryOutput::class)]
#[UsesClass(PdfRawStreamData::class)]
#[UsesClass(PdfStreamSerializer::class)]
final class SerializeTest extends TestCase
{
    #[Test]
    public function serializeWritesStreamToOutput(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);
        $stream = new PdfStream(new PdfDictionary([]), new PdfRawStreamData('hello'));

        // Act
        $stream->serialize($serializer);

        // Assert
        $content = $output->getContent();
        self::assertStringContainsString('stream', $content);
        self::assertStringContainsString('hello', $content);
        self::assertStringContainsString('endstream', $content);
    }
}
