<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfPageBuilder;

use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Color\Color;
use PhpPdf\Shading\ColorStop;
use PhpPdf\Shading\PdfAxialShading;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfPageBuilder::class)]
#[CoversMethod(PdfPageBuilder::class, 'useShading')]
#[UsesClass(Color::class)]
#[UsesClass(ColorStop::class)]
#[UsesClass(PdfAxialShading::class)]
final class UseShadingTest extends TestCase
{
    #[Test]
    public function useShadingReturnsSelf(): void
    {
        $page = new PdfPageBuilder();
        $shading = PdfAxialShading::between(
            x0: 0,
            y0: 0,
            x1: 100,
            y1: 0,
            colorStart: Color::fromHex('#ff0000'),
            colorEnd: Color::fromHex('#0000ff'),
        );

        $result = $page->useShading('Sh1', $shading);

        self::assertSame($page, $result);
    }
}
