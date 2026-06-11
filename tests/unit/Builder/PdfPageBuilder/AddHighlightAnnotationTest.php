<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfPageBuilder;

use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Color\Color;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfReal;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfPageBuilder::class)]
#[CoversMethod(PdfPageBuilder::class, 'addHighlightAnnotation')]
#[UsesClass(Color::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfReal::class)]
final class AddHighlightAnnotationTest extends TestCase
{
    #[Test]
    public function addHighlightAnnotationReturnsSelf(): void
    {
        $page = new PdfPageBuilder();

        $result = $page->addHighlightAnnotation(50, 700, 200, 20);

        self::assertSame($page, $result);
    }

    #[Test]
    public function addHighlightAnnotationWithExplicitColor(): void
    {
        // Covers passing an explicit $color (bypasses default)
        $page = new PdfPageBuilder();

        $result = $page->addHighlightAnnotation(50, 700, 200, 20, Color::fromHex('#00ff00'));

        self::assertSame($page, $result);
    }
}
