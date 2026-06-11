<?php

declare(strict_types=1);

namespace PhpPdf\Imposition\NUpImposer;

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Color\Color;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\Operation\ConcatenateMatrix;
use PhpPdf\Content\Operation\InvokeXObject;
use PhpPdf\Content\Operation\RestoreGraphicsState;
use PhpPdf\Content\Operation\SaveGraphicsState;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocument;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Document\PdfObjectImporter;
use PhpPdf\Font\PdfFontCompiler;
use PhpPdf\Font\TrueTypeFont;
use PhpPdf\Font\TrueTypeSubsetter;
use PhpPdf\Image\PdfImage;
use PhpPdf\Imposition\NUpConfig;
use PhpPdf\Imposition\NUpImposer;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfBoolean;
use PhpPdf\Object\PdfContentStream;
use PhpPdf\Object\PdfContentStreamData;
use PhpPdf\Object\PdfDate;
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

#[CoversClass(NUpImposer::class)]
#[CoversMethod(NUpImposer::class, 'impose')]
#[UsesClass(NUpConfig::class)]
#[UsesClass(PdfDocumentBuilder::class)]
#[UsesClass(PdfDocumentInfo::class)]
#[UsesClass(PdfFontCompiler::class)]
#[UsesClass(PdfPageBuilder::class)]
#[UsesClass(PdfImage::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfBoolean::class)]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(PdfContentStreamData::class)]
#[UsesClass(PdfDate::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfDocument::class)]
#[UsesClass(PdfGraphicsStateDictionary::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfObjectRegistry::class)]
#[UsesClass(PdfRawStreamData::class)]
#[UsesClass(PdfReal::class)]
#[UsesClass(PdfStream::class)]
#[UsesClass(PdfString::class)]
#[UsesClass(Matrix::class)]
#[UsesClass(ConcatenateMatrix::class)]
#[UsesClass(InvokeXObject::class)]
#[UsesClass(RestoreGraphicsState::class)]
#[UsesClass(SaveGraphicsState::class)]
#[UsesClass(PdfContentStreamBuilder::class)]
#[UsesClass(PdfLexer::class)]
#[UsesClass(PdfObjectImporter::class)]
#[UsesClass(PdfReadDocument::class)]
#[UsesClass(PdfReadPage::class)]
#[UsesClass(Color::class)]
#[UsesClass(ColorStop::class)]
#[UsesClass(PdfAxialShading::class)]
#[UsesClass(ShadingFunctions::class)]
#[UsesClass(SvgDocument::class)]
#[UsesClass(SvgRenderer::class)]
#[UsesClass(TrueTypeFont::class)]
#[UsesClass(TrueTypeSubsetter::class)]
final class ImposeTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    #[Test]
    public function imposeReturnsPdfDocument(): void
    {
        // Arrange
        $source = self::makeSourceDoc(1);
        $config = new NUpConfig(1, 1, 595, 842);
        $imposer = new NUpImposer($source, $config);

        // Act
        $result = $imposer->impose();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $result);
    }

    #[Test]
    public function imposeWithDocumentInfoIncludesInfoInOutput(): void
    {
        // Arrange
        $source = self::makeSourceDoc(1);
        $config = new NUpConfig(1, 1, 595, 842);
        $imposer = new NUpImposer($source, $config);
        $info = (new PdfDocumentInfo())->title('Test');

        // Act
        $result = $imposer->impose($info);

        // Assert — info !== null branch was taken; the document has an /Info entry
        self::assertInstanceOf(PdfDocument::class, $result);
        self::assertNotNull($result->getInfo());
    }

    #[Test]
    public function imposeDistributesSourcePagesAcrossSheets(): void
    {
        // Arrange — 3 source pages with 2-up config produces 2 output sheets
        $source = self::makeSourceDoc(3);
        $config = NUpConfig::twoUp(842, 595);
        $imposer = new NUpImposer($source, $config);

        // Act
        $result = $imposer->impose();

        // Assert — no exception means all pages were processed including the
        // partial last sheet (only 1 of 2 cells filled)
        self::assertInstanceOf(PdfDocument::class, $result);
    }

    /**
     * Creates a minimal PdfReadDocument with $pageCount pages, each with a
     * 595×842 MediaBox. Uses reflection to pre-populate the internal object
     * cache so no lexer parsing is needed during the test.
     */
    private static function makeSourceDoc(int $pageCount): PdfReadDocument // phpcs:ignore
    {
        $kids = [];
        $pageDicts = [];

        for ($i = 0; $i < $pageCount; $i++) {
            $objNum = $i + 3; // obj 1 = catalog, 2 = pages tree, 3+ = pages
            $pageDicts[$objNum] = new PdfDictionary([
                'MediaBox' => new PdfArray([
                    new PdfInteger(0),
                    new PdfInteger(0),
                    new PdfInteger(595),
                    new PdfInteger(842),
                ]),
                'Type' => new PdfName('Page'),
            ]);
            $kids[] = new PdfIndirectReference($objNum, 0);
        }

        $pagesDict = new PdfDictionary([
            'Count' => new PdfInteger($pageCount),
            'Kids' => new PdfArray($kids),
            'Type' => new PdfName('Pages'),
        ]);

        $catalogDict = new PdfDictionary([
            'Pages' => new PdfIndirectReference(2, 0),
            'Type' => new PdfName('Catalog'),
        ]);

        $trailer = new PdfDictionary([
            'Root' => new PdfIndirectReference(1, 0),
        ]);

        $document = new PdfReadDocument(
            PdfLexer::fromString(''),
            [],
            $trailer,
            PdfVersion::PDF_1_4,
        );

        // Pre-populate the internal cache so objects are resolved without
        // invoking the lexer or object parser.
        $cache = [1 => $catalogDict, 2 => $pagesDict];

        foreach ($pageDicts as $num => $dict) {
            $cache[$num] = $dict;
        }

        $rc = new ReflectionClass(PdfReadDocument::class);
        $prop = $rc->getProperty('cache');
        $prop->setValue($document, $cache);

        return $document;
    }
}
