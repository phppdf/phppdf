<?php

declare(strict_types=1);

namespace PhpPdf\Serialization\PdfStreamSerializer;

use PhpPdf\Content\Operation\BeginText;
use PhpPdf\Object\PdfContentStream;
use PhpPdf\Object\PdfContentStreamData;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfContentStreamSerializer;
use PhpPdf\Serialization\PdfStreamSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfStreamSerializer::class)]
#[CoversMethod(PdfStreamSerializer::class, 'serializeContentStream')]
#[UsesClass(BeginText::class)]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(PdfContentStreamData::class)]
#[UsesClass(PdfContentStreamSerializer::class)]
#[UsesClass(PdfMemoryOutput::class)]
final class SerializeContentStreamTest extends TestCase
{
    #[Test]
    public function serializeContentStreamReturnsOperatorOutput(): void
    {
        // Arrange
        $stream = new PdfContentStream([new BeginText()]);
        $data = new PdfContentStreamData($stream);
        $serializer = new PdfStreamSerializer();

        // Act
        $result = $serializer->serializeContentStream($data);

        // Assert
        self::assertSame("BT\n", $result);
    }

    #[Test]
    public function serializeContentStreamWithNoOperationsReturnsEmptyString(): void
    {
        // Arrange
        $stream = new PdfContentStream([]);
        $data = new PdfContentStreamData($stream);
        $serializer = new PdfStreamSerializer();

        // Act
        $result = $serializer->serializeContentStream($data);

        // Assert
        self::assertSame('', $result);
    }
}
