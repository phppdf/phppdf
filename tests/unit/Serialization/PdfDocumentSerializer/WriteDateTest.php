<?php

declare(strict_types=1);

namespace PhpPdf\Serialization\PdfDocumentSerializer;

use DateTimeImmutable;
use DateTimeZone;
use PhpPdf\Object\PdfDate;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentSerializer::class)]
#[CoversMethod(PdfDocumentSerializer::class, 'writeDate')]
#[UsesClass(PdfDate::class)]
#[UsesClass(PdfMemoryOutput::class)]
final class WriteDateTest extends TestCase
{
    #[Test]
    public function writeDateFormatsAsLiteralPdfString(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);
        $date = new PdfDate(new DateTimeImmutable('2023-01-15 12:30:00', new DateTimeZone('UTC')));

        // Act
        $serializer->writeDate($date);

        // Assert
        self::assertSame('(D:20230115123000+0000)', $output->getContent());
    }
}
