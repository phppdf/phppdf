<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfPageSize;

use PhpPdf\Builder\PdfPageSize;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfPageSize::class)]
final class ConstantsTest extends TestCase
{
    #[Test]
    public function a4HasCorrectDimensions(): void
    {
        // Arrange / Act / Assert
        self::assertSame([595, 842], PdfPageSize::A4);
    }

    #[Test]
    public function letterHasCorrectDimensions(): void
    {
        // Arrange / Act / Assert
        self::assertSame([612, 792], PdfPageSize::LETTER);
    }

    #[Test]
    public function allPresetsArePortraitOriented(): void
    {
        // Arrange
        $presets = [
            PdfPageSize::A0, PdfPageSize::A1, PdfPageSize::A2, PdfPageSize::A3,
            PdfPageSize::A4, PdfPageSize::A5,
            PdfPageSize::LETTER, PdfPageSize::LEGAL, PdfPageSize::TABLOID,
        ];

        // Act / Assert
        foreach ($presets as $size) {
            self::assertLessThan($size[1], $size[0], 'Width must be less than height in portrait');
        }
    }

    #[Test]
    public function isoASeriesIncreasesInSize(): void
    {
        // Arrange / Act / Assert
        self::assertGreaterThan(PdfPageSize::A1[0], PdfPageSize::A0[0]);
        self::assertGreaterThan(PdfPageSize::A2[0], PdfPageSize::A1[0]);
        self::assertGreaterThan(PdfPageSize::A3[0], PdfPageSize::A2[0]);
        self::assertGreaterThan(PdfPageSize::A4[0], PdfPageSize::A3[0]);
        self::assertGreaterThan(PdfPageSize::A5[0], PdfPageSize::A4[0]);
    }
}
