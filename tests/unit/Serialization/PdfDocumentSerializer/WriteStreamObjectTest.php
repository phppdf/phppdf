<?php

declare(strict_types=1);

namespace PhpPdf\Serialization\PdfDocumentSerializer;

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

#[CoversClass(PdfDocumentSerializer::class)]
#[CoversMethod(PdfDocumentSerializer::class, 'writeStreamObject')]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfMemoryOutput::class)]
#[UsesClass(PdfRawStreamData::class)]
#[UsesClass(PdfStream::class)]
#[UsesClass(PdfStreamSerializer::class)]
final class WriteStreamObjectTest extends TestCase
{
    #[Test]
    public function writeStreamObjectWritesDictionaryAndContent(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);
        $stream = new PdfStream(new PdfDictionary(), new PdfRawStreamData('hello'));

        // Act (compressionEnabled=false and encryptionContext=null by default)
        $serializer->writeStreamObject($stream);

        // Assert
        self::assertSame("<<\n/Length 5\n>>\nstream\nhello\nendstream", $output->getContent());
    }

    #[Test]
    public function writeStreamObjectSetsLengthFromContent(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);
        $stream = new PdfStream(new PdfDictionary(), new PdfRawStreamData('longer content here'));

        // Act
        $serializer->writeStreamObject($stream);

        // Assert
        self::assertStringContainsString('/Length 19', $output->getContent());
    }
}
