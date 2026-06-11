<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfFormFieldType;

use PhpPdf\Reader\PdfFormFieldType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfFormFieldType::class)]
final class CasesTest extends TestCase
{
    #[Test]
    public function fromPdfNameReturnsMatchingCase(): void
    {
        // Arrange / Act / Assert
        self::assertSame(PdfFormFieldType::Text, PdfFormFieldType::fromPdfName('Tx'));
        self::assertSame(PdfFormFieldType::Button, PdfFormFieldType::fromPdfName('Btn'));
        self::assertSame(PdfFormFieldType::Choice, PdfFormFieldType::fromPdfName('Ch'));
        self::assertSame(PdfFormFieldType::Signature, PdfFormFieldType::fromPdfName('Sig'));
    }

    #[Test]
    public function fromPdfNameReturnsUnknownForNull(): void
    {
        // Arrange / Act
        $result = PdfFormFieldType::fromPdfName(null);

        // Assert
        self::assertSame(PdfFormFieldType::Unknown, $result);
    }

    #[Test]
    public function fromPdfNameReturnsUnknownForUnrecognizedValue(): void
    {
        // Arrange / Act
        $result = PdfFormFieldType::fromPdfName('Unknown');

        // Assert
        self::assertSame(PdfFormFieldType::Unknown, $result);
    }
}
