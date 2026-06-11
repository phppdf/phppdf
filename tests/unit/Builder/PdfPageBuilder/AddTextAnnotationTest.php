<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfPageBuilder;

use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Color\Color;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfBoolean;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfReal;
use PhpPdf\Object\PdfString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfPageBuilder::class)]
#[CoversMethod(PdfPageBuilder::class, 'addTextAnnotation')]
#[UsesClass(Color::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfBoolean::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfReal::class)]
#[UsesClass(PdfString::class)]
final class AddTextAnnotationTest extends TestCase
{
    #[Test]
    public function addTextAnnotationReturnsSelf(): void
    {
        $page = new PdfPageBuilder();

        $result = $page->addTextAnnotation(50, 700, 'A sticky note');

        self::assertSame($page, $result);
    }

    #[Test]
    public function addTextAnnotationWithTitleSetsTEntry(): void
    {
        // Covers the `if ($title !== null)` branch
        $page = new PdfPageBuilder();

        $result = $page->addTextAnnotation(50, 700, 'Note text', title: 'Author Name');

        self::assertSame($page, $result);
    }

    #[Test]
    public function addTextAnnotationWithCustomColorAndOpenFlag(): void
    {
        // Covers the $color parameter path and $open=true
        $page = new PdfPageBuilder();

        $result = $page->addTextAnnotation(
            x: 10,
            y: 10,
            text: 'Custom',
            title: null,
            open: true,
            color: Color::fromHex('#ff0000'),
        );

        self::assertSame($page, $result);
    }
}
