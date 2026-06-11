<?php

declare(strict_types=1);

namespace PhpPdf\Serialization\PdfDocumentSerializer;

use PhpPdf\Object\PdfBoolean;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentSerializer::class)]
#[CoversMethod(PdfDocumentSerializer::class, 'writeBoolean')]
#[UsesClass(PdfBoolean::class)]
#[UsesClass(PdfMemoryOutput::class)]
final class WriteBooleanTest extends TestCase
{
    #[Test]
    public function writeBooleanTrue(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeBoolean(new PdfBoolean(true));

        // Assert
        self::assertSame('true', $output->getContent());
    }

    #[Test]
    public function writeBooleanFalse(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeBoolean(new PdfBoolean(false));

        // Assert
        self::assertSame('false', $output->getContent());
    }
}
