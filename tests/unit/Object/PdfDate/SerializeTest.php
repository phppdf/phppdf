<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfDate;

use DateTimeImmutable;
use PhpPdf\Object\PdfDate;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDate::class)]
#[CoversMethod(PdfDate::class, 'serialize')]
#[UsesClass(PdfDocumentSerializer::class)]
#[UsesClass(PdfMemoryOutput::class)]
final class SerializeTest extends TestCase
{
    #[Test]
    public function serializeWritesDateInPdfFormat(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);
        $date = new DateTimeImmutable('2024-06-15T10:30:00+00:00');

        // Act
        (new PdfDate($date))->serialize($serializer);

        // Assert
        self::assertStringStartsWith('(D:', $output->getContent());
    }
}
