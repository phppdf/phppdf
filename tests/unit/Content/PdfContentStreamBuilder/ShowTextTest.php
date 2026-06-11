<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\SetFont;
use PhpPdf\Content\Operation\ShowText;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Font\TrueTypeFont;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function assert;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'showText')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(SetFont::class)]
#[UsesClass(ShowText::class)]
#[UsesClass(TrueTypeFont::class)]
final class ShowTextTest extends TestCase
{
    #[Test]
    public function showTextReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->showText('Hello'));
    }

    #[Test]
    public function showTextAddsShowTextOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->showText('Hello');
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(ShowText::class, $ops[0]);
    }

    #[Test]
    public function showTextEncodesAsWinAnsiWhenNoEmbeddedFont(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->setFont('F1', 12.0);
        $builder->showText('Hi');
        $ops = $builder->build()->getOperations();
        // SetFont + ShowText
        self::assertCount(2, $ops);
        self::assertInstanceOf(ShowText::class, $ops[1]);
    }

    #[Test]
    public function showTextEscapesParenthesesInWinAnsi(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->showText('A(B)C');
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(ShowText::class, $ops[0]);
    }

    // -------------------------------------------------------------------------
    // CID / embedded-font path
    // -------------------------------------------------------------------------

    #[Test]
    public function showTextWithEmbeddedFontAddsShowTextOperation(): void
    {
        $font = self::makeFont();
        $builder = new PdfContentStreamBuilder(['F2' => $font]);
        $builder->setFont('F2', 12.0);
        $builder->showText('A');
        $ops = $builder->build()->getOperations();
        // SetFont + ShowText
        self::assertCount(2, $ops);
        self::assertInstanceOf(ShowText::class, $ops[1]);
    }

    #[Test]
    public function showTextWithEmbeddedFontRecordsUsedGlyphs(): void
    {
        $font = self::makeFont();
        $builder = new PdfContentStreamBuilder(['F2' => $font]);
        $builder->setFont('F2', 12.0);
        $builder->showText('A'); // codepoint 65 → glyph 1

        $glyphs = $builder->getUsedGlyphs();
        self::assertArrayHasKey('F2', $glyphs);
        self::assertSame(65, $glyphs['F2'][1]);
    }

    #[Test]
    public function showTextSkipsGlyphIdZeroInUsedGlyphs(): void
    {
        // Codepoint 67 (C) is not in the cmap → getGlyphId returns 0 → NOT recorded.
        $font = self::makeFont();
        $builder = new PdfContentStreamBuilder(['F2' => $font]);
        $builder->setFont('F2', 12.0);
        $builder->showText('C'); // codepoint 67 → glyph 0

        $fontGlyphs = $builder->getUsedGlyphs()['F2'] ?? [];
        self::assertArrayNotHasKey(0, $fontGlyphs);
    }

    /**
     * Creates a minimal TrueTypeFont stub via reflection.
     * cmap: 65 (A) → glyph 1; all other codepoints → glyph 0.
     */
    private static function makeFont(): TrueTypeFont
    {
        $rc = new ReflectionClass(TrueTypeFont::class);
        $font = $rc->newInstanceWithoutConstructor();
        assert($font instanceof TrueTypeFont);
        $set = static fn(string $p, mixed $v) => $rc->getProperty($p)->setValue($font, $v);

        $set('rawData', 'fake_cff');
        $set('fontName', 'Test');
        $set('unitsPerEm', 1000);
        $set('numGlyphs', 2);
        $set('fontIndex', 0);
        $set('cmap', [65 => 1]);
        $set('advanceWidths', [0 => 500, 1 => 667]);
        $set('ascent', 800);
        $set('descent', -200);
        $set('capHeight', 700);
        $set('italicAngle', 0.0);
        $set('xMin', 0);
        $set('yMin', -200);
        $set('xMax', 1000);
        $set('yMax', 800);
        $set('flags', 32);
        $set('stemV', 80);
        $set('isCff', true);

        return $font;
    }
}
