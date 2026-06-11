<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfVersion;

use PhpPdf\Object\PdfVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfVersion::class)]
final class BackingValuesTest extends TestCase
{
    #[Test]
    public function pdf10HasExpectedValue(): void
    {
        self::assertSame('1.0', PdfVersion::PDF_1_0->value);
    }

    #[Test]
    public function pdf17HasExpectedValue(): void
    {
        self::assertSame('1.7', PdfVersion::PDF_1_7->value);
    }

    #[Test]
    public function pdf20HasExpectedValue(): void
    {
        self::assertSame('2.0', PdfVersion::PDF_2_0->value);
    }

    #[Test]
    public function pdf11HasExpectedValue(): void
    {
        // Arrange / Act / Assert
        self::assertSame('1.1', PdfVersion::PDF_1_1->value);
    }

    #[Test]
    public function pdf12HasExpectedValue(): void
    {
        // Arrange / Act / Assert
        self::assertSame('1.2', PdfVersion::PDF_1_2->value);
    }

    #[Test]
    public function pdf13HasExpectedValue(): void
    {
        // Arrange / Act / Assert
        self::assertSame('1.3', PdfVersion::PDF_1_3->value);
    }

    #[Test]
    public function pdf14HasExpectedValue(): void
    {
        // Arrange / Act / Assert
        self::assertSame('1.4', PdfVersion::PDF_1_4->value);
    }

    #[Test]
    public function pdf15HasExpectedValue(): void
    {
        // Arrange / Act / Assert
        self::assertSame('1.5', PdfVersion::PDF_1_5->value);
    }

    #[Test]
    public function pdf16HasExpectedValue(): void
    {
        // Arrange / Act / Assert
        self::assertSame('1.6', PdfVersion::PDF_1_6->value);
    }
}
