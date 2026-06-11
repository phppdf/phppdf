<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfAnnotationType;

use PhpPdf\Reader\PdfAnnotationType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfAnnotationType::class)]
final class CasesTest extends TestCase
{
    #[Test]
    public function fromPdfNameReturnsMatchingCase(): void
    {
        // Arrange / Act / Assert
        self::assertSame(PdfAnnotationType::Text, PdfAnnotationType::fromPdfName('Text'));
        self::assertSame(PdfAnnotationType::Link, PdfAnnotationType::fromPdfName('Link'));
        self::assertSame(PdfAnnotationType::Highlight, PdfAnnotationType::fromPdfName('Highlight'));
    }

    #[Test]
    public function fromPdfNameReturnsUnknownForNull(): void
    {
        // Arrange / Act
        $result = PdfAnnotationType::fromPdfName(null);

        // Assert
        self::assertSame(PdfAnnotationType::Unknown, $result);
    }

    #[Test]
    public function fromPdfNameReturnsUnknownForUnrecognizedValue(): void
    {
        // Arrange / Act
        $result = PdfAnnotationType::fromPdfName('NotAType');

        // Assert
        self::assertSame(PdfAnnotationType::Unknown, $result);
    }
}
