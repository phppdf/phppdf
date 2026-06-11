<?php

declare(strict_types=1);

namespace PhpPdf\Document\PdfDocumentEditor;

use PhpPdf\Content\Operation\SetFont;
use PhpPdf\Content\Operation\ShowText;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocument;
use PhpPdf\Document\PdfDocumentEditor;
use PhpPdf\Font\PdfFontCompiler;
use PhpPdf\Font\TrueTypeFont;
use PhpPdf\Font\TrueTypeSubsetter;
use PhpPdf\Object\Exception\ObjectRegistryNotFound;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfContentStream;
use PhpPdf\Object\PdfContentStreamData;
use PhpPdf\Object\PdfDictionary;
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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

use function assert;

#[CoversClass(PdfDocumentEditor::class)]
#[CoversMethod(PdfDocumentEditor::class, 'build')]
#[UsesClass(PdfDocument::class)]
#[UsesClass(PdfFontCompiler::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(PdfContentStreamBuilder::class)]
#[UsesClass(PdfContentStreamData::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfObjectRegistry::class)]
#[UsesClass(PdfReal::class)]
#[UsesClass(PdfRawStreamData::class)]
#[UsesClass(PdfStream::class)]
#[UsesClass(PdfToUnicodeCMap::class)]
#[UsesClass(TrueTypeFont::class)]
#[UsesClass(TrueTypeSubsetter::class)]
#[UsesClass(ObjectRegistryNotFound::class)]
#[UsesClass(SetFont::class)]
#[UsesClass(ShowText::class)]
#[UsesClass(PdfString::class)]
final class BuildTest extends TestCase
{
    use MinimalDocument;

    #[Test]
    public function buildReturnsPdfDocument(): void
    {
        // Arrange
        $editor = new PdfDocumentEditor(self::buildDocument(pageCount: 1));

        // Act
        $result = $editor->build();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $result);
    }

    #[Test]
    public function buildOutputHasCorrectPageCount(): void
    {
        // Arrange
        $editor = new PdfDocumentEditor(self::buildDocument(pageCount: 2));

        // Act
        $result = $editor->build();

        // Assert
        $catalog = self::asDictionary($result->getObjects()->get($result->getCatalog()));
        $pages = self::asDictionary($result->getObjects()->get(self::asReference($catalog->get('Pages'))));
        self::assertSame(2, self::asInteger($pages->get('Count'))->getValue());
    }

    #[Test]
    public function buildReflectsRemovePage(): void
    {
        // Arrange
        $editor = new PdfDocumentEditor(self::buildDocument(pageCount: 3));
        $editor->removePage(1);

        // Act
        $result = $editor->build();

        // Assert
        $catalog = self::asDictionary($result->getObjects()->get($result->getCatalog()));
        $pages = self::asDictionary($result->getObjects()->get(self::asReference($catalog->get('Pages'))));
        self::assertSame(2, self::asInteger($pages->get('Count'))->getValue());
    }

    #[Test]
    public function buildUsesHighestVersionFromPlan(): void
    {
        // Arrange
        $docA = self::buildDocument(version: PdfVersion::PDF_1_4);
        $docB = self::buildDocument(version: PdfVersion::PDF_2_0);
        $editor = new PdfDocumentEditor($docA);
        $editor->insertPagesFrom($docB, before: $editor->getPageCount());

        // Act
        $result = $editor->build();

        // Assert
        self::assertSame(PdfVersion::PDF_2_0, $result->getVersion());
    }

    #[Test]
    public function buildAppliesRotationToPage(): void
    {
        // Arrange
        $editor = new PdfDocumentEditor(self::buildDocument(pageCount: 1));
        $editor->rotatePage(0, 90);

        // Act
        $result = $editor->build();

        // Assert — page dict should have Rotate entry
        $catalog = self::asDictionary($result->getObjects()->get($result->getCatalog()));
        $pages = self::asDictionary($result->getObjects()->get(self::asReference($catalog->get('Pages'))));
        $pageRef = self::asReference(self::asArray($pages->get('Kids'))->getItems()[0]);
        $page = self::asDictionary($result->getObjects()->get($pageRef));
        self::assertSame(90, self::asInteger($page->get('Rotate'))->getValue());
    }

    #[Test]
    public function buildAppliesCropBoxToPage(): void
    {
        // Arrange
        $editor = new PdfDocumentEditor(self::buildDocument(pageCount: 1));
        $editor->cropPage(0, 10, 20, 200, 300);

        // Act
        $result = $editor->build();

        // Assert — page dict should have CropBox entry
        $catalog = self::asDictionary($result->getObjects()->get($result->getCatalog()));
        $pages = self::asDictionary($result->getObjects()->get(self::asReference($catalog->get('Pages'))));
        $pageRef = self::asReference(self::asArray($pages->get('Kids'))->getItems()[0]);
        $page = self::asDictionary($result->getObjects()->get($pageRef));
        self::assertNotNull($page->get('CropBox'));
    }

    #[Test]
    public function buildWithContentStreamCoversStreamCloning(): void
    {
        // Arrange — page has a content stream so cloneObject(PdfStream) is exercised
        $registry = new PdfObjectRegistry();
        $streamRef = $registry->register(new PdfStream(new PdfDictionary([]), new PdfRawStreamData('BT ET')));
        $pageRef = $registry->register(new PdfDictionary([
            'Contents' => $streamRef,
            'MediaBox' => new PdfArray([
                new PdfReal(0), new PdfReal(0), new PdfReal(595.28), new PdfReal(841.89),
            ]),
            'Type' => new PdfName('Page'),
        ]));
        $pagesRef = $registry->register(new PdfDictionary([
            'Count' => new PdfInteger(1),
            'Kids' => new PdfArray([$pageRef]),
            'Type' => new PdfName('Pages'),
        ]));
        self::asDictionary($registry->get($pageRef))->set('Parent', $pagesRef);
        $catalogRef = $registry->register(new PdfDictionary([
            'Pages' => $pagesRef,
            'Type' => new PdfName('Catalog'),
        ]));
        $doc = new PdfDocument($registry, PdfVersion::PDF_1_7, $catalogRef, null);
        $editor = new PdfDocumentEditor($doc);

        // Act
        $result = $editor->build();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $result);
    }

    #[Test]
    public function buildWithHeaderTemplateInvokesApplyHeaderFooter(): void
    {
        // Arrange
        $invoked = false;
        $editor = new PdfDocumentEditor(self::buildDocument(pageCount: 1));
        $editor->header(static function (
            PdfContentStreamBuilder $s,
            int $page,
            int $total,
            float $w,
            float $h,
        ) use (&$invoked): void {
            $invoked = true;
        });

        // Act
        $result = $editor->build();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $result);
        self::assertTrue($invoked);
    }

    #[Test]
    public function buildWithFooterTemplateInvokesApplyHeaderFooter(): void
    {
        // Arrange
        $invoked = false;
        $editor = new PdfDocumentEditor(self::buildDocument(pageCount: 1));
        $editor->footer(static function (
            PdfContentStreamBuilder $s,
            int $page,
            int $total,
            float $w,
            float $h,
        ) use (&$invoked): void {
            $invoked = true;
        });

        // Act
        $result = $editor->build();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $result);
        self::assertTrue($invoked);
    }

    #[Test]
    public function buildWithType1FontInHeaderCoversCompileType1(): void
    {
        // Arrange
        $editor = new PdfDocumentEditor(self::buildDocument(pageCount: 1));
        $editor->useType1Font('F1', 'Helvetica');
        $editor->header(static function (PdfContentStreamBuilder $s): void {
            // no ops needed
        });

        // Act
        $result = $editor->build();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $result);
    }

    #[Test]
    public function buildPageWithExistingContentsArrayMergesHeader(): void
    {
        // Arrange — page already has a Contents array (not a single ref)
        $registry = new PdfObjectRegistry();
        $streamRef = $registry->register(new PdfStream(new PdfDictionary([]), new PdfRawStreamData('q Q')));
        $pageRef = $registry->register(new PdfDictionary([
            'Contents' => new PdfArray([$streamRef]),
            'MediaBox' => new PdfArray([
                new PdfReal(0), new PdfReal(0), new PdfReal(595.28), new PdfReal(841.89),
            ]),
            'Type' => new PdfName('Page'),
        ]));
        $pagesRef = $registry->register(new PdfDictionary([
            'Count' => new PdfInteger(1),
            'Kids' => new PdfArray([$pageRef]),
            'Type' => new PdfName('Pages'),
        ]));
        self::asDictionary($registry->get($pageRef))->set('Parent', $pagesRef);
        $catalogRef = $registry->register(new PdfDictionary([
            'Pages' => $pagesRef,
            'Type' => new PdfName('Catalog'),
        ]));

        $doc = new PdfDocument($registry, PdfVersion::PDF_1_7, $catalogRef, null);
        $editor = new PdfDocumentEditor($doc);
        $editor->header(static fn () => null);

        // Act
        $result = $editor->build();

        // Assert — header merged; page's Contents is now an array with header + original
        $outCatalog = self::asDictionary($result->getObjects()->get($result->getCatalog()));
        $outPages = self::asDictionary($result->getObjects()->get(self::asReference($outCatalog->get('Pages'))));
        $outPageRef = self::asReference(self::asArray($outPages->get('Kids'))->getItems()[0]);
        $outPage = self::asDictionary($result->getObjects()->get($outPageRef));
        self::assertInstanceOf(PdfArray::class, $outPage->get('Contents'));
    }

    #[Test]
    public function buildCoversContentsAsDirectIndirectReference(): void
    {
        // Page Contents is a single PdfIndirectReference (not wrapped in an array)
        // — exercises the `elseif ($existing instanceof PdfIndirectReference)` branch.
        $registry = new PdfObjectRegistry();
        $streamRef = $registry->register(new PdfStream(new PdfDictionary([]), new PdfRawStreamData('q Q')));
        $pageRef = $registry->register(new PdfDictionary([
            'Contents' => $streamRef,
            'MediaBox' => new PdfArray([
                new PdfReal(0), new PdfReal(0), new PdfReal(595.28), new PdfReal(841.89),
            ]),
            'Type' => new PdfName('Page'),
        ]));
        $pagesRef = $registry->register(new PdfDictionary([
            'Count' => new PdfInteger(1),
            'Kids' => new PdfArray([$pageRef]),
            'Type' => new PdfName('Pages'),
        ]));
        self::asDictionary($registry->get($pageRef))->set('Parent', $pagesRef);
        $catalogRef = $registry->register(new PdfDictionary([
            'Pages' => $pagesRef,
            'Type' => new PdfName('Catalog'),
        ]));
        $doc = new PdfDocument($registry, PdfVersion::PDF_1_7, $catalogRef, null);
        $editor = new PdfDocumentEditor($doc);
        $editor->header(static fn () => null);

        // Act
        $result = $editor->build();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $result);
    }

    #[Test]
    public function buildCoversReadPageDimensionsWithNoMediaBox(): void
    {
        // Page has no /MediaBox — readPageDimensions returns default [595.28, 841.89].
        $registry = new PdfObjectRegistry();
        $pageRef = $registry->register(new PdfDictionary([
            'Type' => new PdfName('Page'),
        ]));
        $pagesRef = $registry->register(new PdfDictionary([
            'Count' => new PdfInteger(1),
            'Kids' => new PdfArray([$pageRef]),
            'Type' => new PdfName('Pages'),
        ]));
        self::asDictionary($registry->get($pageRef))->set('Parent', $pagesRef);
        $catalogRef = $registry->register(new PdfDictionary([
            'Pages' => $pagesRef,
            'Type' => new PdfName('Catalog'),
        ]));
        $doc = new PdfDocument($registry, PdfVersion::PDF_1_7, $catalogRef, null);
        $editor = new PdfDocumentEditor($doc);
        $editor->header(static fn () => null);

        // Act / Assert — no exception even with missing MediaBox
        self::assertInstanceOf(PdfDocument::class, $editor->build());
    }

    #[Test]
    public function buildCoversNumValueWithPdfInteger(): void
    {
        // MediaBox uses PdfInteger values — exercises the `instanceof PdfInteger` branch.
        $registry = new PdfObjectRegistry();
        $pageRef = $registry->register(new PdfDictionary([
            'MediaBox' => new PdfArray([
                new PdfInteger(0), new PdfInteger(0),
                new PdfInteger(595), new PdfInteger(842),
            ]),
            'Type' => new PdfName('Page'),
        ]));
        $pagesRef = $registry->register(new PdfDictionary([
            'Count' => new PdfInteger(1),
            'Kids' => new PdfArray([$pageRef]),
            'Type' => new PdfName('Pages'),
        ]));
        self::asDictionary($registry->get($pageRef))->set('Parent', $pagesRef);
        $catalogRef = $registry->register(new PdfDictionary([
            'Pages' => $pagesRef,
            'Type' => new PdfName('Catalog'),
        ]));
        $doc = new PdfDocument($registry, PdfVersion::PDF_1_7, $catalogRef, null);
        $editor = new PdfDocumentEditor($doc);
        $editor->header(static fn () => null);

        // Act / Assert
        self::assertInstanceOf(PdfDocument::class, $editor->build());
    }

    #[Test]
    public function buildCoversCollectReachableAlreadySeen(): void
    {
        // Two pages share the same resource dict — collectReachable hits the
        // "already seen" guard on the second traversal (L557).
        $registry = new PdfObjectRegistry();
        $sharedFont = $registry->register(new PdfDictionary(['Type' => new PdfName('Font')]));
        $resourcesRef = $registry->register(new PdfDictionary([
            'Font' => new PdfDictionary(['F1' => $sharedFont]),
        ]));
        $pageRefs = [];

        for ($i = 0; $i < 2; $i++) {
            $pageRefs[] = $registry->register(new PdfDictionary([
                'MediaBox' => new PdfArray([
                    new PdfReal(0), new PdfReal(0), new PdfReal(595.28), new PdfReal(841.89),
                ]),
                'Resources' => $resourcesRef,
                'Type' => new PdfName('Page'),
            ]));
        }

        $pagesRef = $registry->register(new PdfDictionary([
            'Count' => new PdfInteger(2),
            'Kids' => new PdfArray($pageRefs),
            'Type' => new PdfName('Pages'),
        ]));

        foreach ($pageRefs as $r) {
            self::asDictionary($registry->get($r))->set('Parent', $pagesRef);
        }

        $catalogRef = $registry->register(new PdfDictionary([
            'Pages' => $pagesRef,
            'Type' => new PdfName('Catalog'),
        ]));
        $doc = new PdfDocument($registry, PdfVersion::PDF_1_7, $catalogRef, null);
        $editor = new PdfDocumentEditor($doc);

        // Act / Assert
        self::assertInstanceOf(PdfDocument::class, $editor->build());
    }

    #[Test]
    public function buildCoversNumValueWithNullFromPartialMediaBox(): void
    {
        // MediaBox has only 3 items — $items[3] ?? null gives null → numValue(null) → 0.0
        $registry = new PdfObjectRegistry();
        $pageRef = $registry->register(new PdfDictionary([
            'MediaBox' => new PdfArray([
                new PdfReal(0), new PdfReal(0), new PdfReal(595.28),
            ]),
            'Type' => new PdfName('Page'),
        ]));
        $pagesRef = $registry->register(new PdfDictionary([
            'Count' => new PdfInteger(1),
            'Kids' => new PdfArray([$pageRef]),
            'Type' => new PdfName('Pages'),
        ]));
        self::asDictionary($registry->get($pageRef))->set('Parent', $pagesRef);
        $catalogRef = $registry->register(new PdfDictionary([
            'Pages' => $pagesRef,
            'Type' => new PdfName('Catalog'),
        ]));
        $doc = new PdfDocument($registry, PdfVersion::PDF_1_7, $catalogRef, null);
        $editor = new PdfDocumentEditor($doc);
        $editor->header(static fn () => null);

        // Act / Assert
        self::assertInstanceOf(PdfDocument::class, $editor->build());
    }

    #[Test]
    public function buildCoversIndirectResourcesOnPage(): void
    {
        // Page /Resources and /Font are stored as indirect references
        // — exercises both indirect-resolve branches in getOrCreateFontDict.
        $registry = new PdfObjectRegistry();
        $fontDictRef = $registry->register(new PdfDictionary([]));
        $resourcesRef = $registry->register(new PdfDictionary([
            'Font' => $fontDictRef,
        ]));
        $pageRef = $registry->register(new PdfDictionary([
            'MediaBox' => new PdfArray([
                new PdfReal(0), new PdfReal(0), new PdfReal(595.28), new PdfReal(841.89),
            ]),
            'Resources' => $resourcesRef,
            'Type' => new PdfName('Page'),
        ]));
        $pagesRef = $registry->register(new PdfDictionary([
            'Count' => new PdfInteger(1),
            'Kids' => new PdfArray([$pageRef]),
            'Type' => new PdfName('Pages'),
        ]));
        self::asDictionary($registry->get($pageRef))->set('Parent', $pagesRef);
        $catalogRef = $registry->register(new PdfDictionary([
            'Pages' => $pagesRef,
            'Type' => new PdfName('Catalog'),
        ]));
        $doc = new PdfDocument($registry, PdfVersion::PDF_1_7, $catalogRef, null);
        $editor = new PdfDocumentEditor($doc);
        $editor->header(static fn () => null);

        // Act / Assert
        self::assertInstanceOf(PdfDocument::class, $editor->build());
    }

    #[Test]
    public function readPageDimensionsThrowsWhenPageRefResolvesToNonDictionary(): void
    {
        // Arrange — register a PdfInteger under a ref so the page-dict guard fires.
        $registry = new PdfObjectRegistry();
        $ref = $registry->register(new PdfInteger(0));

        $editor = new PdfDocumentEditor(self::buildDocument());
        $method = new ReflectionMethod($editor, 'readPageDimensions');

        // Act / Assert
        $this->expectException(RuntimeException::class);
        $method->invoke($editor, $registry, $ref);
    }

    #[Test]
    public function buildSkipsDanglingIndirectReferenceFromInsertedDocument(): void
    {
        // Arrange — source page has a /Resources entry pointing to object 9999,
        // which is not registered; collectReachable must skip it gracefully (L564).
        $registry = new PdfObjectRegistry();
        $danglingRef = new PdfIndirectReference(9999, 0);
        $pageRef = $registry->register(new PdfDictionary([
            'MediaBox' => new PdfArray([
                new PdfReal(0), new PdfReal(0), new PdfReal(595.28), new PdfReal(841.89),
            ]),
            'Resources' => $danglingRef,
            'Type' => new PdfName('Page'),
        ]));
        $pagesRef = $registry->register(new PdfDictionary([
            'Count' => new PdfInteger(1),
            'Kids' => new PdfArray([$pageRef]),
            'Type' => new PdfName('Pages'),
        ]));
        self::asDictionary($registry->get($pageRef))->set('Parent', $pagesRef);
        $catalogRef = $registry->register(new PdfDictionary([
            'Pages' => $pagesRef,
            'Type' => new PdfName('Catalog'),
        ]));
        $srcDoc = new PdfDocument($registry, PdfVersion::PDF_1_7, $catalogRef, null);

        $editor = new PdfDocumentEditor(self::buildDocument(pageCount: 1));
        $editor->insertPagesFrom($srcDoc, before: 0);

        // Act / Assert — dangling reference skipped, build succeeds
        self::assertInstanceOf(PdfDocument::class, $editor->build());
    }

    #[Test]
    public function buildWithEmbeddedFontInHeaderCompilesFont(): void
    {
        // Arrange — useEmbeddedFont + header that draws text exercises:
        //   - line 682: allUsedGlyphs accumulation from getUsedGlyphs()
        //   - line 693: PdfFontCompiler::compileEmbedded() call
        $editor = new PdfDocumentEditor(self::buildDocument(pageCount: 1));
        $font = self::makeEmbeddedFont();
        $editor->useEmbeddedFont('F1', $font);
        $editor->header(static function (PdfContentStreamBuilder $s): void {
            $s->setFont('F1', 12)->showText('A');
        });

        // Act
        $result = $editor->build();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $result);
    }

    private static function makeEmbeddedFont(): TrueTypeFont
    {
        $refClass = new ReflectionClass(TrueTypeFont::class);
        $font = $refClass->newInstanceWithoutConstructor();
        assert($font instanceof TrueTypeFont);
        $set = static function (string $prop, mixed $value) use ($refClass, $font): void {
            $refClass->getProperty($prop)->setValue($font, $value);
        };

        $set('rawData', 'fake_cff_binary');
        $set('fontName', 'TestFont-Regular');
        $set('unitsPerEm', 1000);
        $set('numGlyphs', 2);
        $set('fontIndex', 0);
        $set('cmap', [65 => 1]); // 'A' (U+0041) → glyph 1
        $set('advanceWidths', [0 => 500, 1 => 600]);
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
}
