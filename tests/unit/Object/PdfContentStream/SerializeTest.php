<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfContentStream;

use PhpPdf\Object\PdfContentStream;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use TypeError;

#[CoversClass(PdfContentStream::class)]
#[CoversMethod(PdfContentStream::class, 'serialize')]
#[UsesClass(PdfDocumentSerializer::class)]
#[UsesClass(PdfMemoryOutput::class)]
final class SerializeTest extends TestCase
{
    #[Test]
    public function serializeDelegatesToDocumentSerializer(): void
    {
        // Arrange
        $stream = new PdfContentStream([]);
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act / Assert
        $this->expectException(TypeError::class);
        $stream->serialize($serializer);
    }
}
