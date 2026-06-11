<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfReal;

use PhpPdf\Object\PdfReal;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfReal::class)]
#[CoversMethod(PdfReal::class, 'serialize')]
#[UsesClass(PdfDocumentSerializer::class)]
#[UsesClass(PdfMemoryOutput::class)]
final class SerializeTest extends TestCase
{
    #[Test]
    public function serializeWritesFormattedFloat(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        (new PdfReal(1.5))->serialize($serializer);

        // Assert
        self::assertSame('1.5', $output->getContent());
    }
}
