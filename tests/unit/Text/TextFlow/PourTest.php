<?php

declare(strict_types=1);

namespace PhpPdf\Text\TextFlow;

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Font\Type1FontMetrics;
use PhpPdf\Text\TextBox;
use PhpPdf\Text\TextFlow;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TextFlow::class)]
#[CoversMethod(TextFlow::class, 'pour')]
#[UsesClass(TextBox::class)]
#[UsesClass(Type1FontMetrics::class)]
#[UsesClass(PdfDocumentBuilder::class)]
#[UsesClass(PdfPageBuilder::class)]
final class PourTest extends TestCase
{
    #[Test]
    public function pourCreatesOnePageForTextBoxWithOnlyEmptyLine(): void
    {
        // Arrange — empty string produces one blank line (paragraph-spacing
        // preservation), so pour adds exactly one page.
        $metrics = Type1FontMetrics::helvetica();
        $box = TextBox::create('', $metrics, fontSize: 10, maxWidth: 400);
        $document = new PdfDocumentBuilder();
        $pageCount = 0;

        // Act
        TextFlow::pour(
            box: $box,
            document: $document,
            configure: static function (PdfPageBuilder $p) use (&$pageCount): void {
                $pageCount++;
                $p->useType1Font('F1', 'Helvetica');
            },
            fontName: 'F1',
            x: 72.0,
            y: 700.0,
            maxHeight: 600.0,
        );

        // Assert — one page for the single blank line
        self::assertSame(1, $pageCount);
    }

    #[Test]
    public function pourAddsOnePageForShortText(): void // phpcs:ignore
    {
        // Arrange
        $metrics = Type1FontMetrics::helvetica();
        $box = TextBox::create(
            'Short text that fits on one page.',
            $metrics,
            fontSize: 10,
            maxWidth: 400,
            lineHeight: 14,
        );
        $document = new PdfDocumentBuilder();
        $pageCount = 0;

        // Act
        TextFlow::pour(
            box: $box,
            document: $document,
            configure: static function (PdfPageBuilder $p) use (&$pageCount): void {
                $pageCount++;
                $p->useType1Font('F1', 'Helvetica');
            },
            fontName: 'F1',
            x: 72.0,
            y: 700.0,
            maxHeight: 600.0,
        );

        // Assert
        self::assertSame(1, $pageCount);
    }

    #[Test]
    public function pourAddsMultiplePagesForLongText(): void
    {
        // Arrange — generate enough text to require 2 pages
        // lineHeight=14, maxHeight=28 → 2 lines per page
        // 5 lines of text → 3 pages (2+2+1)
        $metrics = Type1FontMetrics::helvetica();
        $text = implode("\n", ['Line A', 'Line B', 'Line C', 'Line D', 'Line E']);
        $box = TextBox::create($text, $metrics, fontSize: 10, maxWidth: 400, lineHeight: 14);
        $document = new PdfDocumentBuilder();
        $pageCount = 0;

        // Act
        TextFlow::pour(
            box: $box,
            document: $document,
            configure: static function (PdfPageBuilder $p) use (&$pageCount): void {
                $pageCount++;
                $p->useType1Font('F1', 'Helvetica');
            },
            fontName: 'F1',
            x: 72.0,
            y: 700.0,
            maxHeight: 28.0,
        );

        // Assert — ceil(5/2) = 3 pages
        self::assertSame(3, $pageCount);
    }
}
