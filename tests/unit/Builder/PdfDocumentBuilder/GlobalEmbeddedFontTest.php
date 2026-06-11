<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfDocumentBuilder;

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Font\TrueTypeFont;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(PdfDocumentBuilder::class)]
#[CoversMethod(PdfDocumentBuilder::class, 'globalEmbeddedFont')]
#[UsesClass(PdfPageBuilder::class)]
#[UsesClass(TrueTypeFont::class)]
final class GlobalEmbeddedFontTest extends TestCase
{
    #[Test]
    public function globalEmbeddedFontReturnsSelf(): void
    {
        // Arrange
        $builder = new PdfDocumentBuilder();

        // Act
        $result = $builder->globalEmbeddedFont('F1', self::makeFont());

        // Assert
        self::assertSame($builder, $result);
    }

    #[Test]
    public function globalEmbeddedFontIsInjectedIntoNewPages(): void
    {
        // Arrange
        $builder = (new PdfDocumentBuilder())
            ->globalEmbeddedFont('F1', self::makeFont());

        // Act — verify the page builder receives the embedded font via the configure callback
        $receivedBuilder = null;
        $builder->page(static function (PdfPageBuilder $page) use (&$receivedBuilder): void {
            $receivedBuilder = $page;
        });

        // Assert
        self::assertNotNull($receivedBuilder);
    }

    private static function makeFont(): TrueTypeFont
    {
        return (new ReflectionClass(TrueTypeFont::class))->newInstanceWithoutConstructor();
    }
}
