<?php

declare(strict_types=1);

namespace PhpPdf\Builder;

use InvalidArgumentException;
use PhpPdf\Color\Color;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfObjectImporter;
use PhpPdf\Font\PdfFontCompiler;
use PhpPdf\Font\TrueTypeFont;
use PhpPdf\Image\PdfImage;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfBoolean;
use PhpPdf\Object\PdfContentStreamData;
use PhpPdf\Object\PdfDestination;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfGoToAction;
use PhpPdf\Object\PdfGraphicsStateDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfLinkAnnotation;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Object\PdfRawStreamData;
use PhpPdf\Object\PdfReal;
use PhpPdf\Object\PdfStream;
use PhpPdf\Object\PdfString;
use PhpPdf\Object\PdfUriAction;
use PhpPdf\Reader\PdfReadPage;
use PhpPdf\Shading\PdfShading;
use PhpPdf\Svg\SvgDocument;
use PhpPdf\Svg\SvgRenderer;

use function assert;

/**
 * Builds a single PDF page with its fonts, resources, and content stream.
 *
 * Obtain an instance from PdfDocumentBuilder::page() and configure it via
 * the fluent methods below. The page is compiled into the document's object
 * registry when PdfDocumentBuilder::build() is called.
 *
 * Typical usage:
 *
 *   $builder->page(function (PdfPageBuilder $page): void {
 *       $page
 *           ->size(...PdfPageSize::A4)
 *           ->useType1Font('F1', 'Helvetica')
 *           ->content(function (PdfContentStreamBuilder $stream): void {
 *               $stream
 *                   ->beginText()
 *                   ->setFont('F1', 12)
 *                   ->setTextMatrix(1, 0, 0, 1, 72, 720)
 *                   ->showText('Hello World')
 *                   ->endText();
 *           });
 *   });
 */
final class PdfPageBuilder
{
    private int $width;
    private int $height;
    private ?int $rotate = null;

    /** @var array<string, string> Maps local resource name to base font name. */
    private array $type1Fonts = [];

    /** @var array<string, \PhpPdf\Font\TrueTypeFont> Maps local resource name to embedded font. */
    private array $embeddedFonts = [];

    /** @var array<string, \PhpPdf\Image\PdfImage> Maps local resource name to image. */
    private array $images = [];

    /** @var array<string, \PhpPdf\Object\PdfGraphicsStateDictionary> Maps local resource name to ExtGState. */
    private array $graphicsStates = [];

    /** @var array<string, \PhpPdf\Shading\PdfShading> Maps local resource name to shading pattern. */
    private array $shadings = [];

    /** @var array<string, \PhpPdf\Svg\SvgDocument> Maps local resource name to SVG document. */
    private array $svgs = [];

    /** @var array<string, \PhpPdf\Reader\PdfReadPage> Maps local resource name to an imported external page. */
    private array $importedPages = [];

    /** @var list<callable(\PhpPdf\Content\PdfContentStreamBuilder): void> */
    private array $contentCallables = [];

    /** @var list<\PhpPdf\Builder\PdfLinkSpec> */
    private array $linkSpecs = [];

    /** @var list<\PhpPdf\Object\PdfDictionary> Simple annotations compiled during compile(). */
    private array $annotations = [];

    public function __construct()
    {
        [$this->width, $this->height] = PdfPageSize::A4;
    }

    /**
     * Sets the page dimensions in points.
     *
     * Use PdfPageSize constants for standard sizes:
     *   $page->size(...PdfPageSize::A4)
     *
     * Or supply custom dimensions directly:
     *   $page->size(595, 842)
     */
    public function size(int $width, int $height): self
    {
        $this->width = $width;
        $this->height = $height;

        return $this;
    }

    /**
     * Sets the clockwise display rotation for this page.
     *
     * The value must be 0, 90, 180, or 270 degrees. It is written as a /Rotate
     * entry in the page dictionary, which instructs the viewer to rotate the
     * page before displaying it. The content stream coordinate system is
     * unaffected — all drawing coordinates remain relative to the unrotated
     * page origin.
     *
     * @throws \InvalidArgumentException for values outside {0, 90, 180, 270}.
     */
    public function rotate(int $degrees): self
    {
        if (!in_array($degrees, [0, 90, 180, 270], true)) {
            throw new InvalidArgumentException("Rotation must be 0, 90, 180, or 270; got {$degrees}.");
        }

        $this->rotate = $degrees;

        return $this;
    }

    /**
     * Registers a standard Type 1 font for use in the page's content stream.
     *
     * $localName is the short name used in setFont() calls within the content
     * stream (e.g. 'F1'). $baseFont must be one of the 14 standard PDF fonts:
     * Helvetica, Helvetica-Bold, Helvetica-Oblique, Helvetica-BoldOblique,
     * Times-Roman, Times-Bold, Times-Italic, Times-BoldItalic,
     * Courier, Courier-Bold, Courier-Oblique, Courier-BoldOblique,
     * Symbol, ZapfDingbats.
     */
    public function useType1Font(string $localName, string $baseFont): self
    {
        $this->type1Fonts[$localName] = $baseFont;

        return $this;
    }

    /**
     * Adds a content drawing callback to the page.
     * O
     * The callable receives a PdfContentStreamBuilder and should add drawing
     * operations to it. Multiple calls are allowed; each callback appends
     * to the same content stream in the order it was registered.
     *
     * @param callable(\PhpPdf\Content\PdfContentStreamBuilder): void $configure
     */
    public function content(callable $configure): self
    {
        $this->contentCallables[] = $configure;

        return $this;
    }

    /**
     * Registers an embedded TrueType or OpenType font for use in the content stream.
     *
     * The font program is embedded verbatim in the PDF (no subsetting), and the
     * font is wired up as a composite Type0 font with Identity-H encoding and a
     * ToUnicode CMap. Pass any UTF-8 string to showText() as normal — the
     * builder transcodes to glyph IDs automatically.
     *
     * $localName is the short resource name used in setFont() calls, e.g. 'F1'.
     */
    public function useEmbeddedFont(string $localName, TrueTypeFont $font): self
    {
        $this->embeddedFonts[$localName] = $font;

        return $this;
    }

    /**
     * Registers an image for use in the content stream.
     *
     * $localName is the short resource name passed to drawImage(), e.g. 'Img1'.
     * The image is embedded as a PDF image XObject. JPEG images are stored
     * with DCTDecode; PNG images use raw pixel data (optionally compressed
     * with FlateDecode when PdfDocumentBuilder::compress() is enabled).
     * PNG images with transparency have their alpha channel preserved as a
     * /SMask soft-mask XObject.
     */
    public function useImage(string $localName, PdfImage $image): self
    {
        $this->images[$localName] = $image;

        return $this;
    }

    /**
     * Registers a named graphics state (ExtGState) for use in the content stream.
     *
     * $localName is the short resource name passed to setGraphicsStateParameters()
     * inside a content callback, e.g. 'GS1'. The dictionary sets fill opacity (ca),
     * stroke opacity (CA), and/or a blend mode (BM). Transparency requires PDF 1.4.
     *
     * Example:
     *
     *   $page->useGraphicsState('GSHalf', new PdfGraphicsStateDictionary(fillAlpha: 0.5))
     *        ->useGraphicsState('GSMul', new PdfGraphicsStateDictionary(blendMode: BlendMode::Multiply))
     *        ->content(function ($s): void {
     *            $s->setGraphicsStateParameters('GSHalf')
     *              ->fillColor(Color::rgb(1, 0, 0))
     *              ->rectangle(72, 600, 200, 100)->fill();
     *        });
     */
    public function useGraphicsState(string $localName, PdfGraphicsStateDictionary $state): self
    {
        $this->graphicsStates[$localName] = $state;

        return $this;
    }

    /**
     * Registers a named shading pattern for use in the content stream.
     *
     * $localName is the short resource name passed to paintShading() inside a
     * content callback. Paint the shading by clipping to a path first so only
     * the intended area is filled:
     *
     *   $page->useShading('HGrad', PdfAxialShading::between(...))
     *        ->content(function ($s) {
     *            $s->saveGraphicsState()
     *              ->rectangle($x, $y, $w, $h)->clip()->endPath()
     *              ->paintShading('HGrad')
     *              ->restoreGraphicsState();
     *        });
     */
    public function useShading(string $localName, PdfShading $shading): self
    {
        $this->shadings[$localName] = $shading;

        return $this;
    }

    /**
     * Registers an SVG document for use in the content stream.
     *
     * $localName is the short resource name passed to drawSvg(), e.g. 'Logo'.
     * The SVG is embedded as a PDF Form XObject, preserving vector quality at
     * any scale. Supported SVG elements: rect, circle, ellipse, line, polyline,
     * polygon, path, g. Supported properties: fill, stroke, stroke-width,
     * fill-rule, display, transforms.
     */
    public function useSvg(string $localName, SvgDocument $svg): self
    {
        $this->svgs[$localName] = $svg;

        return $this;
    }

    /**
     * Registers a page from an external PDF for use as a Form XObject background.
     *
     * The page is imported via PdfObjectImporter: its content streams and all
     * referenced resources (fonts, images, nested XObjects) are deep-cloned into
     * the target document's registry. The clone is registered as a Form XObject
     * whose /BBox matches the imported page's /MediaBox.
     *
     * $localName is the short resource name used in drawImportedPage() calls
     * inside a content callback:
     *
     *   $page->useImportedPage('TPL', $sourcePage)
     *        ->content(function (PdfContentStreamBuilder $stream): void {
     *            $stream->drawImportedPage('TPL'); // full size
     *            $stream->drawImportedPage('TPL', x: 0, y: 0, scale: 0.5); // 50%
     *        });
     */
    public function useImportedPage(string $localName, PdfReadPage $page): self
    {
        $this->importedPages[$localName] = $page;

        return $this;
    }

    /**
     * Adds a Text (sticky-note) annotation pinned at (x, y).
     *
     * The note appears as a small icon in the PDF viewer; clicking it opens a
     * popup displaying $text. $title is the author/subject shown in the popup
     * header. $open controls whether the popup opens automatically. $color sets
     * the icon tint (defaults to yellow).
     *
     * (x, y) is the bottom-left of the 20×20 pt icon rectangle in page
     * coordinates (origin at bottom-left, units in points).
     */
    public function addTextAnnotation(
        float $x,
        float $y,
        string $text,
        ?string $title = null,
        bool $open = false,
        ?Color $color = null,
    ): self {
        $color ??= Color::fromHex('#ffcc00');
        [$r, $g, $b] = $color->getComponents();

        $entries = [
            'C' => new PdfArray([
                new PdfReal($r), new PdfReal($g), new PdfReal($b),
            ]),
            'Contents' => new PdfString($text),
            'Open' => new PdfBoolean($open),
            'Rect' => $this->annotRect($x, $y, 20, 20),
            'Subtype' => new PdfName('Text'),
            'Type' => new PdfName('Annot'),
        ];

        if ($title !== null) {
            $entries['T'] = new PdfString($title);
        }

        $this->annotations[] = new PdfDictionary($entries);

        return $this;
    }

    /**
     * Adds a Highlight annotation over a rectangular text region.
     *
     * The highlight is drawn as a translucent coloured band by the viewer.
     * (x, y) is the bottom-left corner; (width, height) is the extent of the
     * highlighted area. $color defaults to yellow.
     */
    public function addHighlightAnnotation(float $x, float $y, float $width, float $height, ?Color $color = null,): self
    {
        return $this->addMarkupAnnotation('Highlight', $x, $y, $width, $height, $color ?? Color::fromHex('#ffff00'));
    }

    /**
     * Adds an Underline annotation under a rectangular text region.
     *
     * The viewer draws a coloured underline along the bottom of the rectangle.
     * $color defaults to red.
     */
    public function addUnderlineAnnotation(float $x, float $y, float $width, float $height, ?Color $color = null,): self
    {
        return $this->addMarkupAnnotation('Underline', $x, $y, $width, $height, $color ?? Color::fromHex('#cc0000'));
    }

    /**
     * Adds a Square annotation — a rectangular border drawn over the page.
     *
     * (x, y) is the bottom-left corner of the annotation rectangle. $borderColor
     * sets the stroke color; $fillColor (optional) sets the interior fill. Use
     * $borderWidth to control the stroke width in points (default 1.5).
     */
    public function addSquareAnnotation(
        float $x,
        float $y,
        float $width,
        float $height,
        ?Color $borderColor = null,
        ?Color $fillColor = null,
        float $borderWidth = 1.5,
    ): self {
        return $this->addShapeAnnotation('Square', $x, $y, $width, $height, $borderColor, $fillColor, $borderWidth);
    }

    /**
     * Adds a Circle annotation — an oval border drawn over the page.
     *
     * Same parameters as addSquareAnnotation(). The viewer draws an ellipse
     * inscribed in the annotation rectangle.
     */
    public function addCircleAnnotation(
        float $x,
        float $y,
        float $width,
        float $height,
        ?Color $borderColor = null,
        ?Color $fillColor = null,
        float $borderWidth = 1.5,
    ): self {
        return $this->addShapeAnnotation('Circle', $x, $y, $width, $height, $borderColor, $fillColor, $borderWidth);
    }

    // -------------------------------------------------------------------------
    // Internal annotation helpers
    // -------------------------------------------------------------------------

    /**
     * Compiles simple annotations (text, highlight, underline, square, circle)
     * into the registry and returns their indirect references.
     *
     * Called by PdfDocumentBuilder::build() alongside compileLinks().
     *
     * @return list<\PhpPdf\Object\PdfIndirectReference>
     */
    public function compileAnnotations(PdfObjectRegistry $registry): array
    {
        $refs = [];

        foreach ($this->annotations as $dict) {
            $refs[] = $registry->register($dict);
        }

        return $refs;
    }

    /**
     * Adds a clickable area that opens a URL in the user's browser.
     *
     * (x, y) is the bottom-left corner of the clickable rectangle in page
     * coordinates (origin at bottom-left of the page, units in points).
     */
    public function addUriLink(float $x, float $y, float $width, float $height, string $uri): self
    {
        $this->linkSpecs[] = PdfLinkSpec::uri($x, $y, $width, $height, $uri);

        return $this;
    }

    /**
     * Adds a clickable area that navigates to a page within the document.
     *
     * $pageIndex is 0-based and refers to the order pages were added to
     * PdfDocumentBuilder. The viewer fits the target page to the window.
     */
    public function addPageLink(float $x, float $y, float $width, float $height, int $pageIndex): self
    {
        $this->linkSpecs[] = PdfLinkSpec::page($x, $y, $width, $height, $pageIndex);

        return $this;
    }

    /**
     * Registers link annotations for this page and returns their references.
     *
     * Called by PdfDocumentBuilder after all pages have been compiled so that
     * internal GoTo destinations can reference any page, not just earlier ones.
     *
     * @param list<\PhpPdf\Object\PdfIndirectReference> $allPageRefs
     * @return list<\PhpPdf\Object\PdfIndirectReference>
     */
    public function compileLinks(PdfObjectRegistry $registry, array $allPageRefs): array
    {
        $refs = [];

        foreach ($this->linkSpecs as $spec) {
            $rect = new PdfArray([
                new PdfReal($spec->x),
                new PdfReal($spec->y),
                new PdfReal($spec->x + $spec->width),
                new PdfReal($spec->y + $spec->height),
            ]);

            if ($spec->uri !== null) {
                $action = new PdfUriAction($spec->uri);
            } else {
                $pageIndex = $spec->pageIndex ?? 0;
                $targetRef = $allPageRefs[min($pageIndex, count($allPageRefs) - 1)];
                $action = new PdfGoToAction(new PdfDestination($targetRef));
            }

            $refs[] = $registry->register(new PdfLinkAnnotation($rect, $action));
        }

        return $refs;
    }

    /**
     * Compiles the page into the registry and returns its indirect reference.
     *
     * Called internally by PdfDocumentBuilder::build(). Not intended for
     * direct use.
     */
    public function compile(PdfObjectRegistry $registry, PdfIndirectReference $parent): PdfIndirectReference
    {
        // Build content stream first; it accumulates glyph usage for embedded fonts.
        $streamBuilder = new PdfContentStreamBuilder($this->embeddedFonts);

        foreach ($this->contentCallables as $callable) {
            $callable($streamBuilder);
        }

        $contentsRef = $registry->register(new PdfStream(
            new PdfDictionary(),
            new PdfContentStreamData($streamBuilder->build()),
        ));

        $usedGlyphs = $streamBuilder->getUsedGlyphs();

        // Compile font resource entries.
        $fontEntries = $this->compileType1Fonts($registry);

        foreach ($this->embeddedFonts as $localName => $font) {
            $fontEntries[$localName] = PdfFontCompiler::compileEmbedded(
                $registry,
                $font,
                $usedGlyphs[$localName] ?? [],
            );
        }

        $imageEntries = $this->compileImages($registry);
        $svgEntries = $this->compileSvgs($registry);
        $importedPageEntries = $this->compileImportedPages($registry);
        $graphicsStateEntries = $this->compileGraphicsStates($registry);
        $shadingEntries = $this->compileShadings($registry);

        $xObjectEntries = array_merge($imageEntries, $svgEntries, $importedPageEntries);

        $resources = new PdfDictionary();

        if ($fontEntries !== []) {
            $resources->set('Font', new PdfDictionary($fontEntries));
        }

        if ($xObjectEntries !== []) {
            $resources->set('XObject', new PdfDictionary($xObjectEntries));
        }

        if ($graphicsStateEntries !== []) {
            $resources->set('ExtGState', new PdfDictionary($graphicsStateEntries));
        }

        if ($shadingEntries !== []) {
            $resources->set('Shading', new PdfDictionary($shadingEntries));
        }

        $page = new PdfDictionary([
            'Contents' => $contentsRef,
            'MediaBox' => new PdfArray([
                new PdfInteger(0),
                new PdfInteger(0),
                new PdfInteger($this->width),
                new PdfInteger($this->height),
            ]),
            'Parent' => $parent,
            'Resources' => $resources,
            'Type' => new PdfName('Page'),
        ]);

        if ($this->rotate !== null) {
            $page->set('Rotate', new PdfInteger($this->rotate));
        }

        return $registry->register($page);
    }

    private function addMarkupAnnotation(
        string $subtype,
        float $x,
        float $y,
        float $width,
        float $height,
        Color $color,
    ): self {
        [$r, $g, $b] = $color->getComponents();

        // QuadPoints: upper-left, upper-right, lower-left, lower-right of the quad.
        $quadPoints = new PdfArray([
            new PdfReal($x), new PdfReal($y + $height),
            new PdfReal($x + $width), new PdfReal($y + $height),
            new PdfReal($x), new PdfReal($y),
            new PdfReal($x + $width), new PdfReal($y),
        ]);

        $this->annotations[] = new PdfDictionary([
            'C' => new PdfArray([
                new PdfReal($r), new PdfReal($g), new PdfReal($b),
            ]),
            'QuadPoints' => $quadPoints,
            'Rect' => $this->annotRect($x, $y, $width, $height),
            'Subtype' => new PdfName($subtype),
            'Type' => new PdfName('Annot'),
        ]);

        return $this;
    }

    private function addShapeAnnotation(
        string $subtype,
        float $x,
        float $y,
        float $width,
        float $height,
        ?Color $borderColor,
        ?Color $fillColor,
        float $borderWidth,
    ): self {
        $borderColor ??= Color::fromHex('#cc0000');
        [$r, $g, $b] = $borderColor->getComponents();

        $entries = [
            'BS' => new PdfDictionary([
                'S' => new PdfName('S'),
                'W' => new PdfReal($borderWidth),
            ]),
            'C' => new PdfArray([
                new PdfReal($r), new PdfReal($g), new PdfReal($b),
            ]),
            'Rect' => $this->annotRect($x, $y, $width, $height),
            'Subtype' => new PdfName($subtype),
            'Type' => new PdfName('Annot'),
        ];

        if ($fillColor !== null) {
            [$fr, $fg, $fb] = $fillColor->getComponents();
            $entries['IC'] = new PdfArray([
                new PdfReal($fr), new PdfReal($fg), new PdfReal($fb),
            ]);
        }

        $this->annotations[] = new PdfDictionary($entries);

        return $this;
    }

    private function annotRect(float $x, float $y, float $width, float $height): PdfArray
    {
        return new PdfArray([
            new PdfReal($x),
            new PdfReal($y),
            new PdfReal($x + $width),
            new PdfReal($y + $height),
        ]);
    }

    /** @return array<string, \PhpPdf\Object\PdfObject> */
    private function compileType1Fonts(PdfObjectRegistry $registry): array
    {
        $entries = [];

        foreach ($this->type1Fonts as $localName => $baseFont) {
            $entries[$localName] = PdfFontCompiler::compileType1($registry, $baseFont);
        }

        return $entries;
    }

    /** @return array<string, \PhpPdf\Object\PdfObject> */
    private function compileImages(PdfObjectRegistry $registry): array
    {
        $entries = [];

        foreach ($this->images as $localName => $image) {
            $entries[$localName] = $this->compileImageXObject($registry, $image);
        }

        return $entries;
    }

    /** @return array<string, \PhpPdf\Object\PdfObject> */
    private function compileSvgs(PdfObjectRegistry $registry): array
    {
        $entries = [];
        $renderer = new SvgRenderer();

        foreach ($this->svgs as $localName => $svg) {
            $entries[$localName] = $this->compileSvgFormXObject($registry, $svg, $renderer);
        }

        return $entries;
    }

    private function compileSvgFormXObject(
        PdfObjectRegistry $registry,
        SvgDocument $svg,
        SvgRenderer $renderer,
    ): PdfIndirectReference {
        $w = $svg->getWidth();
        $h = $svg->getHeight();

        $dict = new PdfDictionary([
            'BBox' => new PdfArray([
                new PdfReal(0.0),
                new PdfReal(0.0),
                new PdfReal($w),
                new PdfReal($h),
            ]),
            // Matrix maps form space (SVG coords: origin top-left, y down)
            // to a 1×1 unit square with origin bottom-left, y up (PDF space).
            'Matrix' => new PdfArray([
                new PdfReal(1.0 / $w),
                new PdfReal(0.0),
                new PdfReal(0.0),
                new PdfReal(-1.0 / $h),
                new PdfReal(0.0),
                new PdfReal(1.0),
            ]),
            'Subtype' => new PdfName('Form'),
            'Type' => new PdfName('XObject'),
        ]);

        $streamData = $renderer->render($svg);

        return $registry->register(new PdfStream($dict, new PdfRawStreamData($streamData)));
    }

    /**
     * Imports each registered external page as a Form XObject.
     *
     * For each page, all referenced resources (fonts, images, nested XObjects)
     * are deep-cloned into $registry via PdfObjectImporter. The cloned content
     * streams are concatenated and wrapped in a Form XObject stream whose /BBox
     * matches the imported page's /MediaBox.
     *
     * @return array<string, \PhpPdf\Object\PdfObject>
     */
    private function compileImportedPages(PdfObjectRegistry $registry): array
    {
        $entries = [];

        foreach ($this->importedPages as $localName => $page) {
            $importer = new PdfObjectImporter($page->getDocument(), $registry);

            // Clone the /Resources dictionary — all indirect refs inside (fonts,
            // images, XObjects) are deep-copied into $registry with new numbers.
            $srcResources = $page->getResources();
            $clonedResources = $importer->importObject($srcResources);
            assert($clonedResources instanceof PdfDictionary);

            // Concatenate all content streams.
            $contentData = implode("\n", $page->getContentStreams());

            // BBox from the source page's media box [x y w h].
            [$mbX, $mbY, $mbW, $mbH] = $page->getMediaBox();

            $formDict = new PdfDictionary([
                'BBox' => new PdfArray([
                    new PdfReal($mbX),
                    new PdfReal($mbY),
                    new PdfReal($mbW),
                    new PdfReal($mbH),
                ]),
                'Resources' => $clonedResources,
                'Subtype' => new PdfName('Form'),
                'Type' => new PdfName('XObject'),
            ]);

            $formRef = $registry->register(new PdfStream($formDict, new PdfRawStreamData($contentData)));
            $entries[$localName] = $formRef;
        }

        return $entries;
    }

    /** @return array<string, \PhpPdf\Object\PdfObject> */
    private function compileGraphicsStates(PdfObjectRegistry $registry): array
    {
        $entries = [];

        foreach ($this->graphicsStates as $localName => $state) {
            $entries[$localName] = $registry->register($state);
        }

        return $entries;
    }

    /** @return array<string, \PhpPdf\Object\PdfObject> */
    private function compileShadings(PdfObjectRegistry $registry): array
    {
        $entries = [];

        foreach ($this->shadings as $localName => $shading) {
            $entries[$localName] = $shading->compile($registry);
        }

        return $entries;
    }

    private function compileImageXObject(PdfObjectRegistry $registry, PdfImage $image): PdfIndirectReference
    {
        // Optional soft-mask (alpha channel) — registered as its own indirect object.
        // It is referenced from the main image dict and must NOT appear in the page
        // /XObject resource dict.
        $smaskRef = null;

        if ($image->hasMask()) {
            $maskDict = new PdfDictionary([
                'BitsPerComponent' => new PdfInteger(8),
                'ColorSpace' => new PdfName('DeviceGray'),
                'Height' => new PdfInteger($image->getHeight()),
                'Subtype' => new PdfName('Image'),
                'Type' => new PdfName('XObject'),
                'Width' => new PdfInteger($image->getWidth()),
            ]);
            $smaskRef = $registry->register(
                new PdfStream($maskDict, new PdfRawStreamData($image->getMaskData() ?? '')),
            );
        }

        $imageDict = new PdfDictionary([
            'BitsPerComponent' => new PdfInteger(8),
            'ColorSpace' => new PdfName($image->getColorSpace()),
            'Height' => new PdfInteger($image->getHeight()),
            'Subtype' => new PdfName('Image'),
            'Type' => new PdfName('XObject'),
            'Width' => new PdfInteger($image->getWidth()),
        ]);

        if ($image->isJpeg()) {
            $imageDict->set('Filter', new PdfName('DCTDecode'));
        }

        if ($smaskRef !== null) {
            $imageDict->set('SMask', $smaskRef);
        }

        return $registry->register(
            new PdfStream($imageDict, new PdfRawStreamData($image->getData())),
        );
    }
}
