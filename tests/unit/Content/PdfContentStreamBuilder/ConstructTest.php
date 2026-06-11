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
#[CoversMethod(PdfContentStreamBuilder::class, '__construct')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(SetFont::class)]
#[UsesClass(ShowText::class)]
#[UsesClass(TrueTypeFont::class)]
final class ConstructTest extends TestCase
{
    #[Test]
    public function defaultConstructorProducesBuilderWithNoEmbeddedFonts(): void
    {
        $builder = new PdfContentStreamBuilder();

        // Without embedded fonts, showText uses the WinAnsi (Type1) path.
        $builder->showText('A');
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(ShowText::class, $ops[0]);
    }

    #[Test]
    public function constructorAcceptsEmbeddedFontsMap(): void
    {
        $font = self::makeFont();
        $builder = new PdfContentStreamBuilder(['F1' => $font]);

        // With an embedded font, showText uses the CID (hex-string) path.
        $builder->setFont('F1', 12.0);
        $builder->showText('A');

        $glyphs = $builder->getUsedGlyphs();
        self::assertArrayHasKey('F1', $glyphs);
    }

    private static function makeFont(): TrueTypeFont
    {
        $rc = new ReflectionClass(TrueTypeFont::class);
        $font = $rc->newInstanceWithoutConstructor();
        assert($font instanceof TrueTypeFont);
        $set = static fn (string $p, mixed $v) => $rc->getProperty($p)->setValue($font, $v);

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
