<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfPageBuilder;

use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Image\PdfImage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfPageBuilder::class)]
#[CoversMethod(PdfPageBuilder::class, 'useImage')]
#[UsesClass(PdfImage::class)]
final class UseImageTest extends TestCase
{
    #[Test]
    public function useImageReturnsSelf(): void
    {
        $page = new PdfPageBuilder();
        $image = PdfImage::fromData(self::makeJpegData());

        $result = $page->useImage('Img1', $image);

        self::assertSame($page, $result);
    }

    /** Minimal 1×1 RGB JPEG (SOI + SOF0 + EOI). */
    private static function makeJpegData(): string
    {
        return "\xFF\xD8" // SOI
            . "\xFF\xC0" // SOF0 marker
            . "\x00\x11" // segment length = 17
            . "\x08" // precision = 8 bits
            . "\x00\x01" // height = 1
            . "\x00\x01" // width  = 1
            . "\x03" // components = 3 → DeviceRGB
            . "\x01\x11\x00" // component 1 spec
            . "\x02\x11\x01" // component 2 spec
            . "\x03\x11\x01" // component 3 spec
            . "\xFF\xD9"; // EOI
    }
}
