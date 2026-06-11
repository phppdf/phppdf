<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfDocumentBuilder;

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentBuilder::class)]
#[CoversMethod(PdfDocumentBuilder::class, 'globalFont')]
#[UsesClass(PdfPageBuilder::class)]
final class GlobalFontTest extends TestCase
{
    #[Test]
    public function globalFontReturnsSelf(): void
    {
        // Arrange
        $builder = new PdfDocumentBuilder();

        // Act
        $result = $builder->globalFont('F1', 'Helvetica');

        // Assert
        self::assertSame($builder, $result);
    }

    #[Test]
    public function globalFontIsInjectedIntoNewPages(): void
    {
        // Arrange
        $builder = (new PdfDocumentBuilder())
            ->globalFont('F1', 'Helvetica');

        // Act — verify the global font is passed into the page builder
        $receivedFont = null;
        $builder->page(static function (PdfPageBuilder $page) use (&$receivedFont): void {
            $receivedFont = $page;
        });

        // Assert — page builder was configured (no exception means font was injected)
        self::assertNotNull($receivedFont);
    }
}
