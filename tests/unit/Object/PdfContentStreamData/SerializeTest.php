<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfContentStreamData;

use PhpPdf\Content\Operation\BeginText;
use PhpPdf\Content\Operation\EndText;
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

#[CoversClass(PdfContentStreamData::class)]
#[CoversMethod(PdfContentStreamData::class, 'serialize')]
#[UsesClass(PdfStreamSerializer::class)]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(PdfContentStreamSerializer::class)]
#[UsesClass(PdfMemoryOutput::class)]
#[UsesClass(BeginText::class)]
#[UsesClass(EndText::class)]
final class SerializeTest extends TestCase
{
    #[Test]
    public function serializeRendersOperationsToString(): void
    {
        // Arrange
        $stream = new PdfContentStream([new BeginText(), new EndText()]);
        $data = new PdfContentStreamData($stream);
        $serializer = new PdfStreamSerializer();

        // Act
        $result = $data->serialize($serializer);

        // Assert
        self::assertSame("BT\nET\n", $result);
    }
}
