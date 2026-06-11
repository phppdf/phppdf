<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfPageBuilder;

use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Font\TrueTypeFont;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(PdfPageBuilder::class)]
#[CoversMethod(PdfPageBuilder::class, 'useEmbeddedFont')]
#[UsesClass(TrueTypeFont::class)]
final class UseEmbeddedFontTest extends TestCase
{
    #[Test]
    public function useEmbeddedFontReturnsSelf(): void
    {
        $page = new PdfPageBuilder();
        $font = self::makeFont();

        $result = $page->useEmbeddedFont('F1', $font);

        self::assertSame($page, $result);
    }

    private static function makeFont(): TrueTypeFont
    {
        return (new ReflectionClass(TrueTypeFont::class))->newInstanceWithoutConstructor();
    }
}
