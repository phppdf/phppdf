<?php

declare(strict_types=1);

namespace PhpPdf\Serialization\PdfDocumentSerializer;

use PhpPdf\Object\PdfInteger;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentSerializer::class)]
#[CoversMethod(PdfDocumentSerializer::class, 'write')]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfMemoryOutput::class)]
final class WriteTest extends TestCase
{
    #[Test]
    public function writeDispatchesToObjectSerializer(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->write(new PdfInteger(42));

        // Assert
        self::assertSame('42', $output->getContent());
    }
}
