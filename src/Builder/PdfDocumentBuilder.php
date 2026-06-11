<?php

declare(strict_types=1);

namespace PhpPdf\Builder;

use DateTimeImmutable;
use InvalidArgumentException;
use OutOfBoundsException;
use PhpPdf\Compliance\PdfAConformance;
use PhpPdf\Compliance\PdfAMetadataBuilder;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocument;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Encryption\PdfEncryptionConfig;
use PhpPdf\Encryption\PdfStandardSecurityHandler;
use PhpPdf\Font\TrueTypeFont;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfBoolean;
use PhpPdf\Object\PdfContentStreamData;
use PhpPdf\Object\PdfDate;
use PhpPdf\Object\PdfDestination;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfHexString;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Object\PdfOutline;
use PhpPdf\Object\PdfOutlineItem;
use PhpPdf\Object\PdfRawObject;
use PhpPdf\Object\PdfRawStreamData;
use PhpPdf\Object\PdfReal;
use PhpPdf\Object\PdfRectangle;
use PhpPdf\Object\PdfStream;
use PhpPdf\Object\PdfString;
use PhpPdf\Object\PdfVersion;
use PhpPdf\Object\PdfXmpMetadataStream;
use PhpPdf\Signing\PdfSignatureConfig;

use function assert;

/**
 * Fluent builder for complete PDF documents.
 *
 * Manages the object registry, page tree, catalog, and optional document
 * information dictionary. After configuration, call build() to obtain a
 * PdfDocument that can be passed directly to PdfDocumentSerializer.
 *
 * Typical usage:
 *
 *   $document = (new PdfDocumentBuilder())
 *       ->version(PdfVersion::PDF_1_7)
 *       ->info(
 *           (new PdfDocumentInfo())
 *               ->title('My Report')
 *               ->author('Jane Smith')
 *       )
 *       ->page(function (PdfPageBuilder $page): void {
 *           $page
 *               ->size(...PdfPageSize::A4)
 *               ->useType1Font('F1', 'Helvetica')
 *               ->content(function (PdfContentStreamBuilder $stream): void {
 *                   $stream
 *                       ->beginText()
 *                       ->setFont('F1', 12)
 *                       ->setTextMatrix(1, 0, 0, 1, 72, 720)
 *                       ->showText('Hello World')
 *                       ->endText();
 *               });
 *       })
 *       ->build();
 *
 *   $output = new PdfMemoryOutput();
 *   (new PdfDocumentSerializer($output))->writeDocument($document);
 *
 * @phpstan-import-type FieldSpec from PdfFormBuilder
 */
final class PdfDocumentBuilder
{
    private PdfVersion $version;
    private ?PdfDocumentInfo $info = null;
    private ?PdfSignatureConfig $signatureConfig = null;
    private ?PdfEncryptionConfig $encryptionConfig = null;
    private ?PdfAConformance $conformance = null;
    private bool $compressionEnabled = false;
    private ?PdfOutlineBuilder $outlineBuilder = null;
    private ?PdfFormBuilder $formBuilder = null;

    /** @var array<string, string> Type 1 font registrations applied to every page. */
    private array $globalType1Fonts = [];

    /** @var array<string, \PhpPdf\Font\TrueTypeFont> Embedded font registrations applied to every page. */
    private array $globalEmbeddedFonts = [];

    /** @var (callable(\PhpPdf\Content\PdfContentStreamBuilder, int, int): void)|null */
    private mixed $headerTemplate = null;

    /** @var (callable(\PhpPdf\Content\PdfContentStreamBuilder, int, int): void)|null */
    private mixed $footerTemplate = null;

    /** @var list<\PhpPdf\Builder\PdfPageBuilder> */
    private array $pageBuilders = [];

    public function __construct()
    {
        $this->version = PdfVersion::PDF_1_7;
    }

    /**
     * Sets the PDF version declared in the file header.
     *
     * Defaults to PDF 1.7 when not called. Set a lower version only when
     * targeting environments with strict version requirements; use a higher
     * version when the document uses features introduced in later specs
     * (e.g. transparency requires at least PDF 1.4).
     */
    public function version(PdfVersion $version): self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Attaches a document information dictionary to the document.
     *
     * The supplied PdfDocumentInfo object is compiled and registered as an
     * indirect object when build() is called. When not provided no Info entry
     * is written to the trailer.
     */
    public function info(PdfDocumentInfo $info): self
    {
        $this->info = $info;

        return $this;
    }

    /**
     * Registers a standard Type 1 font on every page of the document.
     *
     * Use this for fonts needed by header/footer templates, so you don't have
     * to repeat useType1Font() on every page. Page-level registrations with
     * the same name override the global one for that page.
     */
    public function globalFont(string $localName, string $baseFont): self
    {
        $this->globalType1Fonts[$localName] = $baseFont;

        return $this;
    }

    /**
     * Registers an embedded TrueType/OpenType font on every page.
     *
     * Use this when header/footer templates need text in a custom font.
     * Page-level registrations with the same name override this for that page.
     */
    public function globalEmbeddedFont(string $localName, TrueTypeFont $font): self
    {
        $this->globalEmbeddedFonts[$localName] = $font;

        return $this;
    }

    /**
     * Sets a header template drawn at the top of every page.
     *
     * The callable receives the content stream for the header layer, the
     * 1-based page number, and the total page count. It is free to draw
     * anything — text, lines, images — using coordinates relative to the
     * page origin (bottom-left).
     *
     * Font names used here must be registered with globalFont() (or on each
     * page individually). Skipping a particular page is as simple as checking
     * $pageNumber inside the callback:
     *
     *   ->header(function ($s, $n, $total): void {
     *       if ($n === 1) return; // skip title page
     *       $s->beginText()->setFont('F1', 9)->...->endText();
     *   })
     *
     * @param callable(\PhpPdf\Content\PdfContentStreamBuilder, int $pageNumber, int $totalPages): void $template
     */
    public function header(callable $template): self
    {
        $this->headerTemplate = $template;

        return $this;
    }

    /**
     * Sets a footer template drawn at the bottom of every page.
     *
     * Same contract as header() — see its documentation for details.
     *
     * @param callable(\PhpPdf\Content\PdfContentStreamBuilder, int $pageNumber, int $totalPages): void $template
     */
    public function footer(callable $template): self
    {
        $this->footerTemplate = $template;

        return $this;
    }

    /**
     * Enables FlateDecode (zlib) compression for all stream bodies.
     *
     * The serializer compresses each stream's content with gzcompress() and
     * adds /Filter /FlateDecode to the stream dictionary. Streams that already
     * declare a /Filter and XMP metadata streams are left uncompressed.
     *
     * Compression and encryption compose correctly: content is compressed
     * first, then encrypted, so the PDF viewer decrypts then decompresses.
     *
     * Typical size reduction is 60–80% for content streams.
     */
    public function compress(): self
    {
        $this->compressionEnabled = true;

        return $this;
    }

    /**
     * Adds a document outline (bookmarks panel) to the document.
     *
     * The callable receives a PdfOutlineBuilder and should add top-level
     * bookmark items. Items reference pages by their 0-based index in the
     * order they were added to this builder. Nesting is supported to any
     * depth via the optional $configure callback on PdfOutlineBuilder::item().
     *
     * Sets /PageMode /UseOutlines on the catalog so viewers open the
     * bookmarks panel automatically.
     *
     * @param callable(\PhpPdf\Builder\PdfOutlineBuilder): void $configure
     */
    public function outline(callable $configure): self
    {
        $this->outlineBuilder = new PdfOutlineBuilder();
        $configure($this->outlineBuilder);

        return $this;
    }

    /**
     * Adds an interactive AcroForm to the document.
     *
     * The callable receives a PdfFormBuilder and should register text fields,
     * text areas, checkboxes, and combo boxes. All field positions use PDF
     * page coordinates (origin at bottom-left, y increases upward).
     *
     * Fields reference pages by 0-based index. The AcroForm is compiled
     * during build() and added to the document catalog automatically.
     *
     * @param callable(\PhpPdf\Builder\PdfFormBuilder): void $configure
     */
    public function form(callable $configure): self
    {
        $this->formBuilder = new PdfFormBuilder();
        $configure($this->formBuilder);

        return $this;
    }

    /**
     * Marks the document for PDF/A archival conformance.
     *
     * Inserts an XMP metadata stream into the catalog (/Metadata) and ensures
     * /ID is present in the trailer. The XMP includes the pdfaid namespace
     * declaring the requested part and conformance level, plus dc/xmp/pdf
     * properties sourced from the info dictionary when one is provided.
     *
     * PDF/A forbids encryption — calling both conformTo() and encrypt() on the
     * same builder will throw an InvalidArgumentException at build() time.
     *
     * Known limitations: the standard 14 Type 1 fonts used by useType1Font()
     * are not embedded (font programs are not included in this library yet),
     * which strict PDF/A validators will flag. All other structural requirements
     * for the 'b' conformance levels are satisfied.
     */
    public function conformTo(PdfAConformance $conformance): self
    {
        $this->conformance = $conformance;

        return $this;
    }

    /**
     * Enables AES-128 encryption (Standard Security Handler, V=4, R=4).
     *
     * The builder derives the O/U entries and encryption key during build().
     * The serializer then encrypts every string and stream in the document.
     * The trailer receives /ID and /Encrypt automatically.
     */
    public function encrypt(PdfEncryptionConfig $config): self
    {
        $this->encryptionConfig = $config;

        return $this;
    }

    /**
     * Prepares the document for digital signing.
     *
     * Embeds an AcroForm, an invisible signature field, and a signature
     * dictionary with fixed-size ByteRange and Contents placeholders. After
     * serializing the document, pass the raw bytes to PdfDocumentSigner::sign()
     * to compute and embed the PKCS#7 signature.
     *
     * Requires at least one page to be added before build() is called.
     */
    public function prepareForSigning(PdfSignatureConfig $config): self
    {
        $this->signatureConfig = $config;

        return $this;
    }

    /**
     * Adds a page to the document.
     *
     * $configure receives a PdfPageBuilder and should configure its size,
     * fonts, and content. Pages appear in the document in the order they
     * are added.
     *
     * @param callable(\PhpPdf\Builder\PdfPageBuilder): void $configure
     */
    public function page(callable $configure): self
    {
        return $this->insertPage(count($this->pageBuilders), $configure);
    }

    /**
     * Inserts a new page at the given 0-based position.
     *
     * Pass getPageCount() as $before to append (same as page()). All pages
     * at or after $before shift one position toward the end. Global fonts
     * registered with globalFont() / globalEmbeddedFont() are injected into
     * the new page before the configure callback runs.
     *
     * @param callable(\PhpPdf\Builder\PdfPageBuilder): void $configure
     * @throws \OutOfBoundsException when $before is outside [0, getPageCount()].
     */
    public function insertPage(int $before, callable $configure): self
    {
        $count = count($this->pageBuilders);

        if ($before < 0 || $before > $count) {
            throw new OutOfBoundsException("Insert position $before is out of range [0, $count].");
        }

        $pageBuilder = new PdfPageBuilder();

        foreach ($this->globalType1Fonts as $name => $baseFont) {
            $pageBuilder->useType1Font($name, $baseFont);
        }

        foreach ($this->globalEmbeddedFonts as $name => $font) {
            $pageBuilder->useEmbeddedFont($name, $font);
        }

        $configure($pageBuilder);
        array_splice($this->pageBuilders, $before, 0, [$pageBuilder]);

        return $this;
    }

    /**
     * Removes the page at the given 0-based index.
     *
     * Pages after $index shift one position toward the front. The removed
     * page builder is discarded.
     *
     * @throws \OutOfBoundsException when $index is outside [0, getPageCount()-1].
     */
    public function removePage(int $index): self
    {
        $count = count($this->pageBuilders);

        if ($index < 0 || $index >= $count) {
            throw new OutOfBoundsException("Page index $index is out of range [0, " . ($count - 1) . "].");
        }

        array_splice($this->pageBuilders, $index, 1);

        return $this;
    }

    /**
     * Moves the page currently at $from to position $to.
     *
     * $to is the index the page will occupy in the final order. Both values
     * are 0-based indices in the range [0, getPageCount()-1]. Moving a page
     * to its own position is a no-op.
     *
     * Example — bring the last page to the front:
     *   $doc->movePage($doc->getPageCount() - 1, 0)
     *
     * @throws \OutOfBoundsException when either index is out of range.
     */
    public function movePage(int $from, int $to): self
    {
        $count = count($this->pageBuilders);

        if ($from < 0 || $from >= $count) {
            throw new OutOfBoundsException("Source index $from is out of range [0, " . ($count - 1) . "].");
        }

        if ($to < 0 || $to >= $count) {
            throw new OutOfBoundsException("Target index $to is out of range [0, " . ($count - 1) . "].");
        }

        [$entry] = array_splice($this->pageBuilders, $from, 1);
        array_splice($this->pageBuilders, $to, 0, [$entry]);

        return $this;
    }

    /**
     * Returns the number of pages added so far.
     */
    public function getPageCount(): int
    {
        return count($this->pageBuilders);
    }

    /**
     * Compiles all pages and produces the final PdfDocument.
     *
     * Registers all indirect objects (fonts, streams, pages, the page tree,
     * the catalog, and optionally the info dictionary) into a fresh
     * PdfObjectRegistry, then returns the assembled PdfDocument.
     */
    public function build(): PdfDocument
    {
        $registry = new PdfObjectRegistry();

        // Register an empty page tree node first so we have a reference to
        // pass as the parent to each page during compilation.
        $pagesDict = new PdfDictionary([
            'Count' => new PdfInteger(0),
            'Kids' => new PdfArray([]),
            'Type' => new PdfName('Pages'),
        ]);
        $pagesRef = $registry->register($pagesDict);

        // Compile pages; each receives the parent reference.
        $pageRefs = [];

        foreach ($this->pageBuilders as $pageBuilder) {
            $pageRefs[] = $pageBuilder->compile($registry, $pagesRef);
        }

        // Back-fill the page tree with the real kids list and count.
        $pagesDict->set('Kids', new PdfArray($pageRefs));
        $pagesDict->set('Count', new PdfInteger(count($pageRefs)));

        // Catalog
        $catalogDict = new PdfDictionary([
            'Pages' => $pagesRef,
            'Type' => new PdfName('Catalog'),
        ]);
        $catalogRef = $registry->register($catalogDict);

        $infoRef = null;

        if ($this->info !== null) {
            $infoRef = $registry->register($this->info->compile());
        }

        if ($this->conformance !== null) {
            if ($this->encryptionConfig !== null) {
                throw new InvalidArgumentException(
                    'PDF/A documents cannot be encrypted (ISO 19005, §6.1.2). '
                    . 'Remove either conformTo() or encrypt() from the builder.',
                );
            }

            $xml = (new PdfAMetadataBuilder())->build($this->conformance, $this->info);
            $metadataRef = $registry->register(new PdfXmpMetadataStream($xml));
            $catalogDict->set('Metadata', $metadataRef);
        }

        // Collect all annotations per page so signatures and links coexist correctly.
        /** @var array<int, list<\PhpPdf\Object\PdfIndirectReference>> $pageAnnotations */
        $pageAnnotations = array_fill(0, count($pageRefs), []);

        if ($this->signatureConfig !== null && count($pageRefs) > 0) {
            $sigFieldRef = $this->buildSignatureStructure(
                $registry,
                $catalogDict,
                $this->signatureConfig,
                $pageRefs[0],
            );

            $pageAnnotations[0][] = $sigFieldRef;
        }

        foreach ($this->pageBuilders as $i => $pageBuilder) {
            foreach ($pageBuilder->compileAnnotations($registry) as $annotRef) {
                $pageAnnotations[$i][] = $annotRef;
            }

            foreach ($pageBuilder->compileLinks($registry, $pageRefs) as $annotRef) {
                $pageAnnotations[$i][] = $annotRef;
            }
        }

        if ($this->formBuilder !== null) {
            $this->compileForm($registry, $this->formBuilder, $pageRefs, $pageAnnotations, $catalogDict);
        }

        foreach ($pageAnnotations as $i => $annots) {
            if ($annots === []) {
                continue;
            }

            $pageDict = $registry->get($pageRefs[$i]);
            assert($pageDict instanceof PdfDictionary);
            $pageDict->set('Annots', new PdfArray($annots));
        }

        // PDF/A requires /ID in the trailer (§6.7.2); generate one even without encryption.
        $documentId = $this->conformance !== null
            ? random_bytes(16)
            : null;
        $encryptDictRef = null;
        $encryptionContext = null;

        if ($this->encryptionConfig !== null) {
            $documentId = random_bytes(16);
            $handler = new PdfStandardSecurityHandler($this->encryptionConfig, $documentId);
            $encryptDictRef = $registry->register($handler->buildEncryptionDictionary());
            $encryptionContext = $handler->createEncryptionContext();
            $encryptionContext->setEncryptDictObjectNumber($encryptDictRef->getObjectNumber());
        }

        if ($this->outlineBuilder !== null && count($pageRefs) > 0) {
            $this->buildOutline($registry, $this->outlineBuilder, $pageRefs, $catalogDict);
        }

        if ($this->headerTemplate !== null || $this->footerTemplate !== null) {
            $totalPages = count($pageRefs);

            foreach ($pageRefs as $i => $pageRef) {
                $this->applyPageTemplate($registry, $pageRef, $i + 1, $totalPages);
            }
        }

        return new PdfDocument(
            $registry,
            $this->version,
            $catalogRef,
            $infoRef,
            $documentId,
            $encryptDictRef,
            $encryptionContext,
            $this->compressionEnabled,
        );
    }

    private function buildSignatureStructure(
        PdfObjectRegistry $registry,
        PdfDictionary $catalogDict,
        PdfSignatureConfig $config,
        PdfIndirectReference $firstPageRef,
    ): PdfIndirectReference {

        // Signature dictionary with fixed-width placeholders for ByteRange and
        // Contents. PdfDocumentSigner will locate these by pattern and patch
        // them in after the full byte offsets are known.
        $sigEntries = [
            'ByteRange' => new PdfRawObject('[0 0000000000 0000000000 0000000000]'),
            'Contents' => new PdfHexString(str_repeat("\0", PdfSignatureConfig::RESERVED_BYTES)),
            'Filter' => new PdfName('Adobe.PPKLite'),
            'M' => new PdfDate(new DateTimeImmutable()),
            'SubFilter' => new PdfName('adbe.pkcs7.detached'),
            'Type' => new PdfName('Sig'),
        ];

        if ($config->getName() !== null) {
            $sigEntries['Name'] = new PdfString($config->getName());
        }

        if ($config->getReason() !== null) {
            $sigEntries['Reason'] = new PdfString($config->getReason());
        }

        if ($config->getLocation() !== null) {
            $sigEntries['Location'] = new PdfString($config->getLocation());
        }

        if ($config->getContactInfo() !== null) {
            $sigEntries['ContactInfo'] = new PdfString($config->getContactInfo());
        }

        $sigDictRef = $registry->register(new PdfDictionary($sigEntries));

        // Invisible signature field widget annotation on the first page.
        $sigFieldDict = new PdfDictionary([
            'F' => new PdfInteger(4),
            'FT' => new PdfName('Sig'),
            'P' => $firstPageRef,
            'Rect' => new PdfRectangle(0, 0, 0, 0),
            'Subtype' => new PdfName('Widget'),
            'T' => new PdfString($config->getFieldName()),
            'Type' => new PdfName('Annot'),
            'V' => $sigDictRef,
        ]);
        $sigFieldRef = $registry->register($sigFieldDict);

        // AcroForm with SigFlags=3: SignaturesExist | AppendOnly.
        $acroFormDict = new PdfDictionary([
            'Fields' => new PdfArray([$sigFieldRef]),
            'SigFlags' => new PdfInteger(3),
        ]);
        $acroFormRef = $registry->register($acroFormDict);

        $catalogDict->set('AcroForm', $acroFormRef);

        return $sigFieldRef;
    }

    /**
     * Registers the outline root and all items, sets /Outlines and /PageMode
     * on the catalog.
     *
     * @param list<\PhpPdf\Object\PdfIndirectReference> $pageRefs
     */
    private function buildOutline(
        PdfObjectRegistry $registry,
        PdfOutlineBuilder $builder,
        array $pageRefs,
        PdfDictionary $catalogDict,
    ): void {
        $items = $builder->getItems();

        if ($items === []) {
            return;
        }

        $outlineDict = new PdfOutline();
        $outlineRef = $registry->register($outlineDict);

        [$firstRef, $lastRef, $totalCount] = $this->buildOutlineLevel($registry, $items, $pageRefs, $outlineRef);

        $outlineDict->set('First', $firstRef);
        $outlineDict->set('Last', $lastRef);
        $outlineDict->set('Count', new PdfInteger($totalCount));

        $catalogDict->set('Outlines', $outlineRef);
        $catalogDict->set('PageMode', new PdfName('UseOutlines'));
    }

    /**
     * Recursively registers one level of outline items and links their
     * Prev/Next/Parent/First/Last/Count entries.
     *
     * @param list<\PhpPdf\Builder\PdfOutlineItemSpec> $items
     * @param list<\PhpPdf\Object\PdfIndirectReference> $pageRefs
     * @return array{\PhpPdf\Object\PdfIndirectReference, \PhpPdf\Object\PdfIndirectReference, int}
     */
    private function buildOutlineLevel(
        PdfObjectRegistry $registry,
        array $items,
        array $pageRefs,
        PdfIndirectReference $parentRef,
    ): array {
        $refs = [];
        $dicts = [];

        // First pass: register all items at this level.
        foreach ($items as $item) {
            $pageRef = $pageRefs[min($item->pageIndex, count($pageRefs) - 1)];
            $dict = new PdfOutlineItem($item->title, new PdfDestination($pageRef));
            $dict->set('Parent', $parentRef);
            $refs[] = $registry->register($dict);
            $dicts[] = $dict;
        }

        $totalCount = count($items);

        // Second pass: link siblings and recurse into children.
        foreach ($items as $i => $item) {
            if ($i > 0) {
                $dicts[$i]->set('Prev', $refs[$i - 1]);
            }

            if ($i < count($items) - 1) {
                $dicts[$i]->set('Next', $refs[$i + 1]);
            }

            $children = $item->children->getItems();

            if ($children === []) {
                continue;
            }

            [$firstChild, $lastChild, $childCount] = $this->buildOutlineLevel(
                $registry,
                $children,
                $pageRefs,
                $refs[$i],
            );
            $dicts[$i]->set('First', $firstChild);
            $dicts[$i]->set('Last', $lastChild);
            $dicts[$i]->set('Count', new PdfInteger($childCount));
            $totalCount += $childCount;
        }

        return [$refs[0], $refs[count($refs) - 1], $totalCount];
    }

    /**
     * Wraps the page body stream with header and footer content streams.
     *
     * Each template is compiled into its own stream object. The page's
     * /Contents entry is updated from a single reference to an array:
     * [header, body, footer]. This means all three streams share the same
     * page resource dictionary, so font names registered via globalFont()
     * or useType1Font() are available inside the templates.
     *
     * Header and footer streams are compiled with the global embedded-font
     * map so that TrueType fonts registered via globalEmbeddedFont() encode
     * text correctly. Type 1 fonts work without any additional configuration.
     */
    private function applyPageTemplate(
        PdfObjectRegistry $registry,
        PdfIndirectReference $pageRef,
        int $pageNumber,
        int $totalPages,
    ): void {
        $pageDict = $registry->get($pageRef);
        assert($pageDict instanceof PdfDictionary);

        // Extract the existing body stream reference.
        $bodyRef = $pageDict->get('Contents');

        $contentRefs = [];

        if ($this->headerTemplate !== null) {
            $builder = new PdfContentStreamBuilder($this->globalEmbeddedFonts);
            ($this->headerTemplate)($builder, $pageNumber, $totalPages);
            $contentRefs[] = $registry->register(new PdfStream(
                new PdfDictionary(),
                new PdfContentStreamData($builder->build()),
            ));
        }

        if ($bodyRef !== null) {
            $contentRefs[] = $bodyRef;
        }

        if ($this->footerTemplate !== null) {
            $builder = new PdfContentStreamBuilder($this->globalEmbeddedFonts);
            ($this->footerTemplate)($builder, $pageNumber, $totalPages);
            $contentRefs[] = $registry->register(new PdfStream(
                new PdfDictionary(),
                new PdfContentStreamData($builder->build()),
            ));
        }

        // Write back: single ref stays a ref; multiple refs become an array.
        if (count($contentRefs) > 1) {
            $pageDict->set('Contents', new PdfArray($contentRefs));
        } elseif (count($contentRefs) === 1) { // @codeCoverageIgnore
            $pageDict->set('Contents', $contentRefs[0]); // @codeCoverageIgnore
        }
    }

    /**
     * Compiles all form fields into Widget annotations and an AcroForm dictionary.
     *
     * Registers a standard Helvetica Type1 font as /Helv in the AcroForm's
     * default resource dictionary so that /DA strings ("…Tf 0 g") are valid.
     * Text fields and combo boxes rely on NeedAppearances true so the viewer
     * generates text-rendering appearances. Checkboxes carry explicit /AP
     * streams so they render correctly in all viewers.
     *
     * @param list<\PhpPdf\Object\PdfIndirectReference> $pageRefs
     * @param array<int, list<\PhpPdf\Object\PdfIndirectReference>> $pageAnnotations
     */
    private function compileForm(
        PdfObjectRegistry $registry,
        PdfFormBuilder $formBuilder,
        array $pageRefs,
        array &$pageAnnotations,
        PdfDictionary $catalogDict,
    ): void {
        // Register /Helv (Helvetica) for use in /DA default-appearance strings.
        $helveticaRef = $registry->register(new PdfDictionary([
            'BaseFont' => new PdfName('Helvetica'),
            'Encoding' => new PdfName('WinAnsiEncoding'),
            'Subtype' => new PdfName('Type1'),
            'Type' => new PdfName('Font'),
        ]));

        $drFonts = new PdfDictionary([
            'Helv' => $helveticaRef,
        ]);
        $dr = new PdfDictionary([
            'Font' => $drFonts,
        ]);

        $fieldRefs = [];

        foreach ($formBuilder->getFields() as $spec) {
            $pageIndex = min($spec['page'], count($pageRefs) - 1);
            $pageRef = $pageRefs[$pageIndex];

            $x = $spec['x'];
            $y = $spec['y'];
            $w = $spec['width'];
            $h = $spec['height'];

            $rect = new PdfArray([
                new PdfReal($x), new PdfReal($y),
                new PdfReal($x + $w), new PdfReal($y + $h),
            ]);

            $widgetRef = match ($spec['type']) {
                'text' => $this->compileTextField($registry, $spec, $rect, $pageRef),
                'checkbox' => $this->compileCheckbox($registry, $spec, $rect, $pageRef),
                'combo' => $this->compileComboBox($registry, $spec, $rect, $pageRef),
                default => null, // @codeCoverageIgnore
            };

            if ($widgetRef === null) { // @codeCoverageIgnore
                continue; // @codeCoverageIgnore
            }

            $fieldRefs[] = $widgetRef;
            $pageAnnotations[$pageIndex][] = $widgetRef;
        }

        if ($fieldRefs === []) {
            return;
        }

        $acroForm = new PdfDictionary([
            'DA' => new PdfString('/Helv 10 Tf 0 g'),
            'DR' => $dr,
            'Fields' => new PdfArray($fieldRefs),
            'NeedAppearances' => new PdfBoolean(true),
        ]);

        $catalogDict->set('AcroForm', $registry->register($acroForm));
    }

    /** @param FieldSpec $spec */
    private function compileTextField(
        PdfObjectRegistry $registry,
        array $spec,
        PdfArray $rect,
        PdfIndirectReference $pageRef,
    ): PdfIndirectReference {
        $fontSize = $spec['fontSize'];
        $da = "/Helv {$fontSize} Tf 0 g";

        // Ff flags: bit 13 (4096) = multiline, bit 1 (1) = read-only.
        $ff = 0;

        if ($spec['multi']) {
            $ff |= 4096;
        }

        if ($spec['readOnly']) {
            $ff |= 1;
        }

        $entries = [
            'DA' => new PdfString($da),
            'F' => new PdfInteger(4),
            'Ff' => new PdfInteger($ff),
            'FT' => new PdfName('Tx'),
            'P' => $pageRef,
            'Rect' => $rect,
            'Subtype' => new PdfName('Widget'),
            'T' => new PdfString($spec['name']),
            'Type' => new PdfName('Annot'),
        ];

        if ($spec['value'] !== '') {
            $entries['V'] = new PdfString($spec['value']);
            $entries['DV'] = new PdfString($spec['value']);
        }

        if ($spec['tooltip'] !== '') {
            $entries['TU'] = new PdfString($spec['tooltip']);
        }

        return $registry->register(new PdfDictionary($entries));
    }

    /** @param FieldSpec $spec */
    private function compileCheckbox(
        PdfObjectRegistry $registry,
        array $spec,
        PdfArray $rect,
        PdfIndirectReference $pageRef,
    ): PdfIndirectReference {
        $checked = $spec['checked'];
        $w = $spec['width'];
        $h = $spec['height'];
        $state = $checked
            ? 'Yes'
            : 'Off';

        // Off appearance — plain border.
        $offStream = $registry->register(new PdfStream(
            new PdfDictionary([
                'BBox' => new PdfArray([
                    new PdfReal(0), new PdfReal(0), new PdfReal($w), new PdfReal($h),
                ]),
                'Subtype' => new PdfName('Form'),
                'Type' => new PdfName('XObject'),
            ]),
            new PdfRawStreamData(
                "q\n0.9 g\n0 0 {$w} {$h} re\nf\n0 G\n0.5 w\n0 0 {$w} {$h} re\nS\nQ\n",
            ),
        ));

        // Yes appearance — border plus a drawn tick mark.
        $m1x = round($w * 0.15, 2);
        $m1y = round($h * 0.45, 2);
        $m2x = round($w * 0.40, 2);
        $m2y = round($h * 0.20, 2);
        $m3x = round($w * 0.85, 2);
        $m3y = round($h * 0.75, 2);
        $yesContent = "q\n0.9 g\n0 0 {$w} {$h} re\nf\n0 G\n0.5 w\n0 0 {$w} {$h} re\nS\n"
            . "0.1 0.35 0.75 RG\n2 w\n"
            . "{$m1x} {$m1y} m\n{$m2x} {$m2y} l\n{$m3x} {$m3y} l\nS\nQ\n";
        $yesStream = $registry->register(new PdfStream(
            new PdfDictionary([
                'BBox' => new PdfArray([
                    new PdfReal(0), new PdfReal(0), new PdfReal($w), new PdfReal($h),
                ]),
                'Subtype' => new PdfName('Form'),
                'Type' => new PdfName('XObject'),
            ]),
            new PdfRawStreamData($yesContent),
        ));

        $apN = new PdfDictionary([
            'Off' => $offStream,
            'Yes' => $yesStream,
        ]);

        $entries = [
            'AP' => new PdfDictionary([
                'N' => $apN,
            ]),
            'AS' => new PdfName($state),
            'DV' => new PdfName('Off'),
            'F' => new PdfInteger(4),
            'Ff' => new PdfInteger(0),
            'FT' => new PdfName('Btn'),
            'P' => $pageRef,
            'Rect' => $rect,
            'Subtype' => new PdfName('Widget'),
            'T' => new PdfString($spec['name']),
            'Type' => new PdfName('Annot'),
            'V' => new PdfName($state),
        ];

        if ($spec['tooltip'] !== '') {
            $entries['TU'] = new PdfString($spec['tooltip']);
        }

        return $registry->register(new PdfDictionary($entries));
    }

    /** @param FieldSpec $spec */
    private function compileComboBox(
        PdfObjectRegistry $registry,
        array $spec,
        PdfArray $rect,
        PdfIndirectReference $pageRef,
    ): PdfIndirectReference {
        $fontSize = $spec['fontSize'];
        $da = "/Helv {$fontSize} Tf 0 g";

        $optArray = new PdfArray(array_values(array_map(
            static fn (string $o): PdfString => new PdfString($o),
            $spec['options'],
        )));

        $ff = 131072; // Combo flag

        if ($spec['readOnly']) {
            $ff |= 1;
        }

        $entries = [
            'DA' => new PdfString($da),
            'F' => new PdfInteger(4),
            'Ff' => new PdfInteger($ff),
            'FT' => new PdfName('Ch'),
            'Opt' => $optArray,
            'P' => $pageRef,
            'Rect' => $rect,
            'Subtype' => new PdfName('Widget'),
            'T' => new PdfString($spec['name']),
            'Type' => new PdfName('Annot'),
        ];

        if ($spec['value'] !== '') {
            $entries['V'] = new PdfString($spec['value']);
            $entries['DV'] = new PdfString($spec['value']);
        }

        if ($spec['tooltip'] !== '') {
            $entries['TU'] = new PdfString($spec['tooltip']);
        }

        return $registry->register(new PdfDictionary($entries));
    }
}
