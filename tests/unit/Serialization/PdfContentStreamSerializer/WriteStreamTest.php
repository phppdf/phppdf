<?php

declare(strict_types=1);

namespace PhpPdf\Serialization\PdfContentStreamSerializer;

use PhpPdf\Content\Operation\BeginText;
use PhpPdf\Content\Operation\EndText;
use PhpPdf\Object\PdfContentStream;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfContentStreamSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamSerializer::class)]
#[CoversMethod(PdfContentStreamSerializer::class, 'writeStream')]
#[UsesClass(BeginText::class)]
#[UsesClass(EndText::class)]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(PdfMemoryOutput::class)]
final class WriteStreamTest extends TestCase
{
    #[Test]
    public function writeStreamSerializesAllOperations(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfContentStreamSerializer($output);
        $stream = new PdfContentStream([new BeginText(), new EndText()]);

        // Act
        $serializer->writeStream($stream);

        // Assert
        self::assertSame("BT\nET\n", $output->getContent());
    }

    #[Test]
    public function writeStreamWithNoOperationsWritesNothing(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfContentStreamSerializer($output);
        $stream = new PdfContentStream([]);

        // Act
        $serializer->writeStream($stream);

        // Assert
        self::assertSame('', $output->getContent());
    }
}
