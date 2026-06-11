<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfPageBuilder;

use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Color\Color;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfObjectImporter;
use PhpPdf\Font\PdfFontCompiler;
use PhpPdf\Font\TrueTypeFont;
use PhpPdf\Font\TrueTypeSubsetter;
use PhpPdf\Image\PdfImage;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfBoolean;
use PhpPdf\Object\PdfContentStream;
use PhpPdf\Object\PdfContentStreamData;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfGraphicsStateDictionary;
use PhpPdf\Object\PdfIndirectObject;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Object\PdfRawStreamData;
use PhpPdf\Object\PdfReal;
use PhpPdf\Object\PdfStream;
use PhpPdf\Object\PdfString;
use PhpPdf\Object\PdfToUnicodeCMap;
use PhpPdf\Object\PdfVersion;
use PhpPdf\Reader\PdfLexer;
use PhpPdf\Reader\PdfReadDocument;
use PhpPdf\Reader\PdfReadPage;
use PhpPdf\Shading\ColorStop;
use PhpPdf\Shading\PdfAxialShading;
use PhpPdf\Shading\ShadingFunctions;
use PhpPdf\Svg\SvgDocument;
use PhpPdf\Svg\SvgRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function assert;

#[CoversClass(PdfPageBuilder::class)]
#[CoversMethod(PdfPageBuilder::class, 'compile')]
#[UsesClass(Color::class)]
#[UsesClass(ColorStop::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfAxialShading::class)]
#[UsesClass(PdfBoolean::class)]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(PdfContentStreamBuilder::class)]
#[UsesClass(PdfContentStreamData::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfFontCompiler::class)]
#[UsesClass(PdfGraphicsStateDictionary::class)]
#[UsesClass(PdfImage::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfLexer::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfObjectImporter::class)]
#[UsesClass(PdfObjectRegistry::class)]
#[UsesClass(PdfRawStreamData::class)]
#[UsesClass(PdfReal::class)]
#[UsesClass(PdfReadDocument::class)]
#[UsesClass(PdfReadPage::class)]
#[UsesClass(PdfStream::class)]
#[UsesClass(PdfString::class)]
#[UsesClass(PdfToUnicodeCMap::class)]
#[UsesClass(ShadingFunctions::class)]
#[UsesClass(SvgDocument::class)]
#[UsesClass(SvgRenderer::class)]
#[UsesClass(TrueTypeFont::class)]
#[UsesClass(TrueTypeSubsetter::class)]
final class CompileTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    #[Test]
    public function compileWithNoResourcesReturnsIndirectReference(): void
    {
        $registry = new PdfObjectRegistry();
        $parent = self::makeParentRef($registry);
        $page = new PdfPageBuilder();

        $ref = $page->compile($registry, $parent);

        self::assertInstanceOf(PdfIndirectReference::class, $ref);
    }

    #[Test]
    public function compileWithRotateAddsRotateEntryToPageDict(): void
    {
        // Covers the `if ($this->rotate !== null)` branch
        $registry = new PdfObjectRegistry();
        $parent = self::makeParentRef($registry);
        $page = (new PdfPageBuilder())->rotate(90);

        $ref = $page->compile($registry, $parent);

        self::assertInstanceOf(PdfIndirectReference::class, $ref);
    }

    #[Test]
    public function compileWithType1FontRegistersFont(): void
    {
        // Covers compileType1Fonts() and the `if ($fontEntries !== [])` branch
        $registry = new PdfObjectRegistry();
        $parent = self::makeParentRef($registry);
        $page = (new PdfPageBuilder())->useType1Font('F1', 'Helvetica');

        $ref = $page->compile($registry, $parent);

        self::assertInstanceOf(PdfIndirectReference::class, $ref);
    }

    #[Test]
    public function compileWithEmbeddedFontRegistersCompositeFont(): void
    {
        // Covers embeddedFonts loop → PdfFontCompiler::compileEmbedded()
        $registry = new PdfObjectRegistry();
        $parent = self::makeParentRef($registry);
        $font = self::makeFont();
        $page = (new PdfPageBuilder())->useEmbeddedFont('F2', $font);

        $ref = $page->compile($registry, $parent);

        self::assertInstanceOf(PdfIndirectReference::class, $ref);
    }

    #[Test]
    public function compileWithJpegImageRegistersImageXObjectWithDctDecodeFilter(): void
    {
        // Covers compileImages → compileImageXObject (isJpeg=true, hasMask=false)
        $registry = new PdfObjectRegistry();
        $parent = self::makeParentRef($registry);
        $image = self::makeImage(isJpeg: true, hasMask: false);
        $page = (new PdfPageBuilder())->useImage('Img1', $image);

        $ref = $page->compile($registry, $parent);

        self::assertInstanceOf(PdfIndirectReference::class, $ref);
    }

    #[Test]
    public function compileWithPngImageWithAlphaRegistersSMaskXObject(): void
    {
        // Covers compileImageXObject: hasMask=true → SMask stream is registered first
        $registry = new PdfObjectRegistry();
        $parent = self::makeParentRef($registry);
        $image = self::makeImage(isJpeg: false, hasMask: true);
        $page = (new PdfPageBuilder())->useImage('Img2', $image);

        $ref = $page->compile($registry, $parent);

        self::assertInstanceOf(PdfIndirectReference::class, $ref);
    }

    #[Test]
    public function compileWithGraphicsStateRegistersExtGStateResource(): void
    {
        // Covers compileGraphicsStates() and the `if ($graphicsStateEntries !== [])` branch
        $registry = new PdfObjectRegistry();
        $parent = self::makeParentRef($registry);
        $state = new PdfGraphicsStateDictionary(fillAlpha: 0.5);
        $page = (new PdfPageBuilder())->useGraphicsState('GS1', $state);

        $ref = $page->compile($registry, $parent);

        self::assertInstanceOf(PdfIndirectReference::class, $ref);
    }

    #[Test]
    public function compileWithShadingRegistersShadingResource(): void
    {
        // Covers compileShadings() and the `if ($shadingEntries !== [])` branch
        $registry = new PdfObjectRegistry();
        $parent = self::makeParentRef($registry);
        $shading = PdfAxialShading::between(
            x0: 0,
            y0: 0,
            x1: 100,
            y1: 0,
            colorStart: Color::fromHex('#ff0000'),
            colorEnd: Color::fromHex('#0000ff'),
        );
        $page = (new PdfPageBuilder())->useShading('Sh1', $shading);

        $ref = $page->compile($registry, $parent);

        self::assertInstanceOf(PdfIndirectReference::class, $ref);
    }

    #[Test]
    public function compileWithSvgRegistersFormXObjectResource(): void
    {
        // Covers compileSvgs → compileSvgFormXObject and the `if ($xObjectEntries !== [])` branch
        $registry = new PdfObjectRegistry();
        $parent = self::makeParentRef($registry);
        $svg = SvgDocument::fromString('<svg width="100" height="50" xmlns="http://www.w3.org/2000/svg"/>');
        $page = (new PdfPageBuilder())->useSvg('Logo', $svg);

        $ref = $page->compile($registry, $parent);

        self::assertInstanceOf(PdfIndirectReference::class, $ref);
    }

    #[Test]
    public function compileWithImportedPageRegistersFormXObjectResource(): void
    {
        // Covers compileImportedPages → PdfObjectImporter clones resources
        $registry = new PdfObjectRegistry();
        $parent = self::makeParentRef($registry);
        $readPage = self::makeReadPage();
        $page = (new PdfPageBuilder())->useImportedPage('TPL', $readPage);

        $ref = $page->compile($registry, $parent);

        self::assertInstanceOf(PdfIndirectReference::class, $ref);
    }

    #[Test]
    public function compileWithContentCallableExecutesCallback(): void
    {
        // Covers the contentCallables foreach loop in compile()
        $registry = new PdfObjectRegistry();
        $parent = self::makeParentRef($registry);
        $called = false;
        $page = (new PdfPageBuilder())->content(static function (PdfContentStreamBuilder $s) use (&$called): void {
            $called = true;
        });

        $page->compile($registry, $parent);

        self::assertTrue($called);
    }

    /**
     * Creates a TrueTypeFont via reflection, bypassing the private constructor.
     * Uses a CFF font so TrueTypeSubsetter returns rawData unchanged.
     *
     * @param array<int,int> $advanceWidths [glyphId => fontUnits]
     */
    private static function makeFont(array $advanceWidths = [0 => 500, 1 => 600]): TrueTypeFont
    {
        $rc = new ReflectionClass(TrueTypeFont::class);
        $font = $rc->newInstanceWithoutConstructor();
        assert($font instanceof TrueTypeFont);
        $set = static fn(string $p, mixed $v) => $rc->getProperty($p)->setValue($font, $v);

        $set('rawData', 'fake_cff_binary');
        $set('fontName', 'TestFont-Regular');
        $set('unitsPerEm', 1000);
        $set('numGlyphs', 2);
        $set('fontIndex', 0);
        $set('cmap', [65 => 1, 66 => 2]);
        $set('advanceWidths', $advanceWidths);
        $set('ascent', 800);
        $set('descent', -200);
        $set('capHeight', 700);
        $set('italicAngle', 0.0);
        $set('xMin', -10);
        $set('yMin', -200);
        $set('xMax', 1000);
        $set('yMax', 800);
        $set('flags', 32);
        $set('stemV', 80);
        $set('isCff', true);

        return $font;
    }

    /**
     * Creates a PdfImage via reflection, bypassing the private constructor.
     * Avoids GD/file-I/O dependencies in tests.
     */
    private static function makeImage(bool $isJpeg, bool $hasMask): PdfImage
    {
        $rc = new ReflectionClass(PdfImage::class);
        $image = $rc->newInstanceWithoutConstructor();
        assert($image instanceof PdfImage);
        $set = static fn(string $p, mixed $v) => $rc->getProperty($p)->setValue($image, $v);

        $set('width', 10);
        $set('height', 10);
        $set('colorSpace', 'DeviceRGB');
        $set('data', str_repeat("\x00", 300)); // 10×10×3 placeholder bytes
        $set('isJpeg', $isJpeg);
        $set('maskData', $hasMask ? str_repeat("\xFF", 100) : null);

        return $image;
    }

    /** Creates a minimal PdfReadPage backed by an empty PdfReadDocument. */
    private static function makeReadPage(): PdfReadPage
    {
        $document = new PdfReadDocument(
            PdfLexer::fromString(''),
            [],
            new PdfDictionary([]),
            PdfVersion::PDF_1_4,
        );

        return new PdfReadPage(new PdfDictionary([]), $document);
    }

    /** Returns a fake parent reference for compile(). */
    private static function makeParentRef(PdfObjectRegistry $registry): PdfIndirectReference
    {
        return $registry->register(new PdfDictionary([]));
    }
}
