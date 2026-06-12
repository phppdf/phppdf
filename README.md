# phppdf

phppdf is a pure PHP library for creating and processing PDF files. It covers both sides of the PDF workflow: a fluent
builder API for generating documents from scratch, and a parser for reading, extracting content from, and modifying
existing files.

The library maps closely to the PDF specification. Page content is built through a content stream API that exposes the
full set of PDF graphics operators — paths, text, images, color spaces, gradients, transformations — while higher-level
helpers (text flow, tables, barcodes, QR codes, SVG rendering) sit on top for common tasks. You get fine-grained control
when you need it and convenience when you don't.

No composer dependencies are required beyond standard PHP extensions.

## Features

### Building PDFs

**Document structure**

- Fluent document builder with pages, headers, footers, outlines, forms, and metadata
- PDF versions 1.0 – 2.0
- Stream compression (Flate/deflate)
- Page sizes: A0 – A5, Letter, Legal, Tabloid, and arbitrary custom dimensions
- Page rotation
- Global fonts and templates shared across all pages
- Output to memory buffer, file path, or any PHP stream resource

**Fonts and text**

- Standard Type 1 fonts (Helvetica, Times, Courier, Symbol, ZapfDingbats) — no embedding required
- Embedded TrueType and OpenType fonts, including TTC font collections
- Automatic glyph subsetting — only the glyphs used are written to the file
- Full Unicode support for embedded fonts, including non-Latin scripts
- All PDF text operators: character spacing, word spacing, horizontal scaling, leading, rise
- Text rendering modes: fill, stroke, fill+stroke, invisible, and clipping variants
- `TextBox` — word-wrapped text block with left / centre / right / justify alignment, configurable leading, and overflow
  detection
- `TextFlow` — automatically paginates a `TextBox` across as many pages as needed
- TeX-based hyphenation
- Bulleted lists

**Graphics and color**

- Path construction: straight lines, cubic Bézier curves (all three operator variants)
- Path painting: stroke, fill (non-zero winding and even-odd), fill+stroke, clip
- Shape helpers: rectangle, circle, ellipse, rounded rectangle
- Full graphics state control: line width, cap, join, miter limit, dash pattern, flatness
- Grayscale, RGB, and CMYK colors for stroke and fill
- Named `Color` helper with hex input, named colors, and mix/lighten/darken
- Axial (linear) gradients with two-stop and multi-stop support
- Radial gradients
- Transparency and blend modes via graphics state dictionaries
- Transformation matrices: translate, rotate, scale, arbitrary

**Images and media**

- PNG images (RGB, RGBA, grayscale, indexed; alpha channel preserved as soft mask)
- JPEG images
- SVG subset: shapes, paths, groups, transforms, and fill/stroke
- QR codes (all versions, configurable error correction level and module size)
- Code 128 barcodes
- EAN-13 barcodes

**Tables**

- Arbitrary column widths
- Per-cell and default padding
- Outer and inner borders with configurable color and weight
- Background color per row or per cell
- Text color per cell
- Left / right / centre / justify text alignment per cell
- Top / middle / bottom vertical alignment per cell
- Column span and row span
- Per-cell font and size override

**Interactive features**

- URI links and internal page links
- Text, highlight, underline, square, and circle annotations
- Document outlines (bookmarks) with unlimited nesting depth
- AcroForms: single-line text fields, multi-line text areas, checkboxes, combo boxes

**Compliance and security**

- PDF/A archival conformance: levels 1b, 1a, 2b, 2a, 2u, 3b, 3a, 3u — XMP metadata written automatically
- AES-128 encryption with separate user and owner passwords
- Granular document permissions: printing, copying, modifying, annotations, and more
- Digital signatures (PKCS#7 / CMS, SHA-256)

**Marked content**

- Tagged content regions for structure and accessibility workflows
- Marked content points
- Compatibility sections (BX / EX)

### Reading PDFs

- Traditional cross-reference tables (PDF 1.0 – 1.4)
- Cross-reference streams (PDF 1.5+)
- Compressed object streams (ObjStm)
- Lazy object loading with in-memory cache
- Text extraction: CID/Type0 fonts via ToUnicode CMap; Type1 and TrueType via WinAnsiEncoding
- Image extraction for all XObject images on a page
- Annotation reading with URI extraction
- AcroForm field discovery: names, values, types, and option lists
- AcroForm filling via incremental update (leaves the original byte-range intact)
- Password-protected file opening (user password and owner password)

### Document operations

- Merge multiple documents into one
- Add, remove, and reorder pages — from a compiled document or directly from a file on disk
- Insert pages from one document into another
- Rotate and crop pages
- Inject headers and footers into an existing built document
- Import pages from existing PDFs as Form XObjects (reusable templates)
- N-up imposition (2-up, 4-up, 9-up presets; custom grid and sheet size)
- PDF/A compliance validation with per-issue severity reporting

### CLI

- `phppdf text` — extract plain text from a file
- `phppdf info` — print version, page count, and metadata
- `phppdf merge` — combine multiple files into one

---

## Requirements

- PHP 8.4+
- `ext-dom` (html to pdf)
- `ext-gd` (images)
- `ext-libxml` (svg and html)
- `ext-mbstring` (text encoding)
- `ext-openssl` (encryption and signing)
- `ext-zlib` (reading and writing)

## Installation

```bash
composer require phppdf/phppdf
```

## Quick start

```php
use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;

$document = (new PdfDocumentBuilder())
    ->page(function (PdfPageBuilder $page): void {
        $page
            ->size(...PdfPageSize::A4)
            ->useType1Font('F1', 'Helvetica')
            ->content(function (PdfContentStreamBuilder $s): void {
                $s->beginText()
                  ->setFont('F1', 24)
                  ->setTextMatrix(Matrix::translate(72, 720))
                  ->showText('Hello World')
                  ->endText();
            });
    })
    ->build();

$output = new PdfMemoryOutput();
(new PdfDocumentSerializer($output))->writeDocument($document);

header('Content-Type: application/pdf');
echo $output->getContent();
```

---

## Writing PDFs

### Page sizes

`PdfPageSize` provides named constants for standard page sizes. All values are in PDF user units (1/72 inch).

```php
$page->size(...PdfPageSize::A4);       // 595 × 842
$page->size(...PdfPageSize::Letter);   // 612 × 792
$page->size(200, 300);                 // custom width × height
```

### Fonts

**Standard Type 1 fonts** are referenced by name and require no embedding:

```php
$page->useType1Font('F1', 'Helvetica');
$page->useType1Font('F2', 'Helvetica-Bold');
$page->useType1Font('F3', 'Times-Roman');
$page->useType1Font('F4', 'Courier');
```

**TrueType / OpenType fonts** are fully embedded in the PDF. They support Unicode text including non-Latin scripts. Only
the glyphs actually used in the document are embedded (subsetting is automatic):

```php
use PhpPdf\Font\TrueTypeFont;

$font = TrueTypeFont::fromFile('/path/to/font.ttf');
$page->useEmbeddedFont('F1', $font);
```

**Global fonts** are registered once and available on every page, which is useful for headers and footers:

```php
(new PdfDocumentBuilder())
    ->globalFont('FH', 'Helvetica-Bold')
    ->header(function (PdfContentStreamBuilder $s, int $n, int $total): void {
        $s->beginText()->setFont('FH', 9)
          ->setTextMatrix(Matrix::translate(72, 822))
          ->showText("Page $n of $total")
          ->endText();
    })
```

### Text layout

`TextBox` measures and wraps a block of text within a fixed width. `TextFlow` automatically paginates overflowing text
across multiple pages.

```php


$metrics = Type1FontMetrics::helvetica();
$box = TextBox::create($longText, $metrics, 12, 451.0, 14.0, TextAlign::Justify);

// Draw on a single page
$s->drawTextBox($box, 'F1', x: 72, y: 770);

// Or paginate automatically
TextFlow::pour(
    box:       $box,
    document:  $doc,
    configure: fn(PdfPageBuilder $p) => $p->size(...PdfPageSize::A4)->useType1Font('F1', 'Helvetica'),
    fontName:  'F1',
    x:         72.0,
    y:         770.0,
    maxHeight: 698.0,
);
```

`TextAlign` supports `Left`, `Centre`, `Right`, and `Justify`. Hyphenation is available via `TeXHyphenator`:

```php


$box = TextBox::create($text, $metrics, 12, 200.0, hyphenator: new TeXHyphenator('en_US'));
```

### Lists

```php


$list = new ListBox([
    new ListItem('First item',  $metrics, 11, 400.0),
    new ListItem('Second item', $metrics, 11, 400.0),
], $metrics, 11);

$s->drawListBox($list, 'F1', x: 72, y: 700);
```

### Drawing

All drawing uses the PDF graphics model — paths, fills, strokes, and transforms. Coordinates are in user units with the
origin at the bottom-left of the page.

```php
// Rectangle
$s->setNonStrokingRgbColor(0.2, 0.4, 0.8)
  ->rectangle(72, 500, 200, 100)
  ->fill();

// Line
$s->setStrokingGray(0.5)
  ->setLineWidth(0.5)
  ->moveTo(72, 400)
  ->lineTo(523, 400)
  ->stroke();

// Bezier curve
$s->moveTo(100, 300)
  ->curveTo(150, 400, 200, 200, 250, 300)
  ->stroke();

// Graphics state save/restore
$s->saveGraphicsState()
  ->concatenateMatrix(Matrix::rotate(45))
  ->...
  ->restoreGraphicsState();
```

### Colors

Stream color methods (`setNonStrokingRgbColor`, `setStrokingRgbColor`, etc.) take plain float values. The `Color` class
is used by table cells and other higher-level helpers.

```php
// RGB — fill and stroke
$s->setNonStrokingRgbColor(1.0, 0.0, 0.0);   // red fill
$s->setStrokingRgbColor(0.0, 0.0, 0.0);       // black stroke

// CMYK
$s->setNonStrokingCmykColor(0.0, 0.8, 0.8, 0.0);

// Gray
$s->setNonStrokingGray(0.5);

// Color helper (used with tables, gradients, etc.)
use PhpPdf\Color\Color;
Color::fromHex('#3366cc');
Color::rgb(0.2, 0.4, 0.8);
Color::cmyk(0.0, 0.8, 0.8, 0.0);
```

### Gradients

```php
use PhpPdf\Shading\PdfAxialShading;
use PhpPdf\Shading\PdfRadialShading;
use PhpPdf\Shading\ColorStop;
use PhpPdf\Color\Color;

// Two-stop linear gradient
$gradient = PdfAxialShading::between(
    x0: 72,  y0: 600,
    x1: 523, y1: 600,
    colorStart: Color::fromHex('#3b5ce6'),
    colorEnd:   Color::fromHex('#e63b3b'),
);

// Multi-stop linear gradient
$gradient = PdfAxialShading::multiStop(
    x0: 72,  y0: 600,
    x1: 523, y1: 600,
    stops: [
        new ColorStop(0.0, Color::red()),
        new ColorStop(0.5, Color::yellow()),
        new ColorStop(1.0, Color::lime()),
    ],
);

// Radial gradient
$radial = PdfRadialShading::circle(
    cx: 300, cy: 500, radius: 100,
    colorCenter: Color::white(),
    colorEdge:   Color::navy(),
);

$page->useShading('G1', $gradient);

// Gradients must be painted inside a clipping region
$s->saveGraphicsState()
  ->rectangle(72, 550, 451, 60)->clip()->endPath()
  ->paintShading('G1')
  ->restoreGraphicsState();
```

### Images

PNG and JPEG images are supported via the GD extension.

```php
use PhpPdf\Image\PdfImage;

$image = PdfImage::fromFile('/path/to/image.png');
// or from in-memory PNG/JPEG bytes:
$image = PdfImage::fromData($pngBytes);

$page->useImage('IMG', $image);
$s->drawImage('IMG', x: 72, y: 500, width: 200, height: 150);
```

### Tables

```php
use PhpPdf\Color\Color;

$yBelow = TableBuilder::create(x: 72, y: 700)
    ->columns([160, 80, 80, 80])
    ->font('F1', 10, $metrics)
    ->padding(5, 6, 4, 6)
    ->border(Color::fromHex('#aaaaaa'), 0.5)
    ->addRow(
        TableRow::cells([
            TableCell::text('Description')->font('F2', 10, $boldMetrics)->background(Color::fromHex('#1a3a5c'))->textColor(Color::white()),
            TableCell::text('Qty')->background(Color::fromHex('#1a3a5c'))->textColor(Color::white())->align(TextAlign::Right),
            TableCell::text('Price')->background(Color::fromHex('#1a3a5c'))->textColor(Color::white())->align(TextAlign::Right),
            TableCell::text('Total')->background(Color::fromHex('#1a3a5c'))->textColor(Color::white())->align(TextAlign::Right),
        ])
    )
    ->addRow(
        TableRow::cells([
            TableCell::text('Widget A'),
            TableCell::text('2')->align(TextAlign::Right),
            TableCell::text('$49.99')->align(TextAlign::Right),
            TableCell::text('$99.98')->align(TextAlign::Right),
        ])->background(Color::fromHex('#f0f5fa'))
    )
    ->draw($s);   // returns the y coordinate below the table
```

### SVG

A subset of SVG 1.1 is supported: basic shapes, paths, groups, transforms, and fills.

```php


$svg = SvgDocument::fromXml($svgString);
$page->useSvg('SVG1', $svg);
$s->drawSvg('SVG1', x: 72, y: 500, width: 200, height: 150);
```

### QR codes

```php
use PhpPdf\QrCode\QrCode;

$qr = QrCode::encode('https://example.com');
$s->drawQrCode($qr, x: 72, y: 500, moduleSize: 3.0);
```

### Barcodes

```php


$barcode = Code128::encode('ABC-1234');
$s->drawBarcode($barcode, x: 72, y: 400, width: 200, height: 40);

$ean = EAN13::encode('5901234123457');
$s->drawBarcode($ean, x: 72, y: 340, width: 160, height: 40);
```

### Links and annotations

```php
// External URI
$page->addUriLink(x: 72, y: 697, width: 200, height: 15, uri: 'https://example.com');

// Internal link to another page (0-based index)
$page->addPageLink(x: 72, y: 657, width: 200, height: 15, pageIndex: 1);
```

### Outlines (bookmarks)

```php
(new PdfDocumentBuilder())
    ->outline(function (PdfOutlineBuilder $o): void {
        $o->item('Chapter 1', pageIndex: 0);
        $o->item('Chapter 2', pageIndex: 1, configure: function (PdfOutlineBuilder $sub): void {
            $sub->item('Section 2.1', pageIndex: 1);
            $sub->item('Section 2.2', pageIndex: 2);
        });
    })
```

### AcroForms

The form builder attaches interactive fields to the document. Field coordinates are in PDF page-space (origin at
bottom-left, y increases upward).

```php
use PhpPdf\Builder\PdfFormBuilder;

(new PdfDocumentBuilder())
    ->form(function (PdfFormBuilder $form): void {
        $form
            ->textField('firstName', x: 200, y: 700, width: 300, height: 20)
            ->textField('lastName',  x: 200, y: 670, width: 300, height: 20)
            ->textArea ('message',   x: 200, y: 560, width: 300, height: 80)
            ->checkbox ('subscribe', x: 200, y: 540, size: 14)
            ->comboBox ('country',   x: 200, y: 510, width: 300, height: 20,
                        options: ['Netherlands', 'Germany', 'Belgium']);
    })
```

### Headers and footers

```php
(new PdfDocumentBuilder())
    ->globalFont('F1', 'Helvetica')
    ->header(function (PdfContentStreamBuilder $s, int $n, int $total): void {
        $s->beginText()->setFont('F1', 8)
          ->setTextMatrix(Matrix::translate(72, 822))
          ->showText("Page $n of $total")
          ->endText();
    })
    ->footer(function (PdfContentStreamBuilder $s, int $n, int $total): void {
        $s->setLineWidth(0.5)->moveTo(72, 50)->lineTo(523, 50)->stroke();
    })
```

To inject headers/footers into an already-built `PdfDocument`, use `PdfDocumentEditor`:

```php
use PhpPdf\Document\PdfDocumentEditor;

$editor = new PdfDocumentEditor($doc);
$editor->header(function (PdfContentStreamBuilder $s, int $n, int $total): void {
    $s->beginText()->setFont('F1', 8)
      ->setTextMatrix(Matrix::translate(72, 822))
      ->showText("Page $n of $total")
      ->endText();
});
$result = $editor->build();
```

### Encryption

```php
use PhpPdf\Encryption\PdfEncryptionConfig;
use PhpPdf\Encryption\PdfPermissions;

(new PdfDocumentBuilder())
    ->encrypt(
        (new PdfEncryptionConfig())
            ->userPassword('open')
            ->ownerPassword('admin')
            ->permissions(PdfPermissions::none()->allowPrinting())
    )
```

### Digital signatures

```php
use PhpPdf\Builder\PdfSignatureConfig;

$signatureConfig = (new PdfSignatureConfig())
    ->certificate($cert)
    ->privateKey($key);

$document = (new PdfDocumentBuilder())
    ->sign($signatureConfig)
    ->...
    ->build();

$output = new PdfMemoryOutput();
(new PdfDocumentSerializer($output))->writeDocument($document);
$signed = (new PdfDocumentSigner())->sign($output->getContent(), $signatureConfig);
```

### PDF/A conformance

```php


(new PdfDocumentBuilder())
    ->conformTo(PdfAConformance::PdfA2b)
    ->info((new PdfDocumentInfo())->title('Archival Document')->author('...'))
    ->...
```

Supported levels: `PdfA1b`, `PdfA1a`, `PdfA2b`, `PdfA2a`, `PdfA2u`, `PdfA3b`, `PdfA3a`, `PdfA3u`.

### Document metadata

```php
use PhpPdf\Builder\PdfDocumentInfo;

(new PdfDocumentBuilder())
    ->info(
        (new PdfDocumentInfo())
            ->title('My Document')
            ->author('Jane Smith')
            ->subject('Annual Report')
            ->keywords('report, annual, 2025')
            ->creator('My App')
    )
```

### Output targets

```php
use PhpPdf\Output\PdfMemoryOutput;    // write to a string in memory
use PhpPdf\Output\PdfFileOutput;      // write directly to a file
use PhpPdf\Output\PdfStreamOutput;    // write to any PHP stream resource

// File
(new PdfDocumentSerializer(new PdfFileOutput('/path/to/output.pdf')))->writeDocument($doc);

// Stream (e.g. a browser download)
(new PdfDocumentSerializer(new PdfStreamOutput(fopen('php://output', 'wb'))))->writeDocument($doc);

// Memory (inspect or send as HTTP response)
$output = new PdfMemoryOutput();
(new PdfDocumentSerializer($output))->writeDocument($doc);
$bytes = $output->getContent();
```

---

## Reading PDFs

### Open a document

```php
use PhpPdf\Reader\PdfDocumentReader;

$doc = PdfDocumentReader::open('/path/to/file.pdf');

echo $doc->getVersion()->value;  // e.g. "1.7"
echo $doc->getPageCount();

$info = $doc->getInfo();         // PdfDictionary or null
```

### Text extraction

```php
use PhpPdf\Reader\PdfTextExtractor;

$extractor = new PdfTextExtractor($doc);

for ($i = 0; $i < $doc->getPageCount(); $i++) {
    echo $extractor->getTextForPage($i);
}
```

Text extraction handles Type 0 (CID) fonts via ToUnicode CMap and simple fonts via WinAnsiEncoding. Complex custom
encodings and purely glyph-substituted fonts may not extract correctly.

### Image extraction

```php
use PhpPdf\Reader\PdfImageExtractor;

$extractor = new PdfImageExtractor($doc);

foreach ($extractor->getImagesForPage(0) as $image) {
    // $image->getData()   — raw image bytes
    // $image->getFilter() — e.g. "DCTDecode" (JPEG) or "FlateDecode"
}
```

### Annotation reading

```php
use PhpPdf\Reader\PdfAnnotationExtractor;

$extractor = new PdfAnnotationExtractor($doc);

foreach ($extractor->getAnnotationsForPage(0) as $annotation) {
    echo $annotation->getType()->value;   // e.g. "Link"
    echo $annotation->getUri();           // for URI annotations
}
```

### Form reading and filling

```php
use PhpPdf\Reader\PdfAcroFormReader;
use PhpPdf\Reader\PdfAcroFormFiller;

$doc    = PdfDocumentReader::open('/path/to/form.pdf');
$reader = new PdfAcroFormReader($doc);

// List all fields
foreach ($reader->getFields() as $field) {
    echo $field->fullName . ': ' . $field->value;
}

// Fill fields and save as a new PDF (incremental update)
$filler   = new PdfAcroFormFiller($doc, file_get_contents('/path/to/form.pdf'));
$fieldMap = $reader->getFieldsByName();

$filler->setText($fieldMap['firstName'], 'Jane');
$filler->setText($fieldMap['email'],     'jane@example.com');
$filler->setChecked($fieldMap['subscribe'], true);
$filler->save('/path/to/filled.pdf');
```

### Encrypted PDFs

```php
$doc = PdfDocumentReader::openEncrypted('/path/to/file.pdf', 'password');
```

---

## Document operations

### Merging documents

```php
use PhpPdf\Document\PdfDocumentMerger;

$merged = (new PdfDocumentMerger())
    ->add($docA->build())
    ->add($docB->build())
    ->build();
```

Note: encryption, digital signatures, and bookmarks from source documents are not carried over into the merged output.

### Page management

`PdfDocumentEditor` accepts either a compiled `PdfDocument` or a file opened with `PdfDocumentReader`:

```php
use PhpPdf\Document\PdfDocumentEditor;
use PhpPdf\Reader\PdfDocumentReader;

// From a file on disk
$editor = PdfDocumentEditor::fromReadDocument(PdfDocumentReader::open('/path/to/file.pdf'));

// Or from a document built in memory
$editor = new PdfDocumentEditor($doc);

$editor->removePage(2);
$editor->movePage(from: 3, to: 1);  // move page 3 before page 1
$result = $editor->build();
```

### Template import

Import a page from an existing PDF as a reusable background (Form XObject):

```php
$templateDoc  = PdfDocumentReader::open('/path/to/letterhead.pdf');
$templatePage = $templateDoc->getPage(0);

(new PdfDocumentBuilder())
    ->page(function (PdfPageBuilder $page) use ($templatePage): void {
        $page
            ->size(...PdfPageSize::A4)
            ->useImportedPage('TPL', $templatePage)
            ->content(function (PdfContentStreamBuilder $s): void {
                $s->drawImportedPage('TPL');
                // add content on top...
            });
    })
```

### N-up imposition

Arrange multiple source pages on a single output sheet:

```php


$srcDoc = PdfDocumentReader::open('/path/to/source.pdf');

// 2-up: two A4 pages side by side on A4 landscape
$config = NUpConfig::twoUp(842, 595);
$result = (new NUpImposer($srcDoc, $config))->impose();

// 4-up and 9-up presets also available:
// NUpConfig::fourUp(842, 595)
// NUpConfig::nineUp(842, 1190)
```

### PDF/A validation

```php


$result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

if ($result->isCompliant()) {
    echo "Compliant\n";
} else {
    foreach ($result->getIssues() as $issue) {
        echo $issue->level->value . ': ' . $issue->message . "\n";
    }
}
```

---

## CLI

The `phppdf` binary is installed to `vendor/bin/phppdf` by Composer.

```bash
# Extract text from all pages
phppdf text document.pdf

# Show version, page count and metadata
phppdf info document.pdf

# Merge two or more PDF files
phppdf merge -o merged.pdf file1.pdf file2.pdf file3.pdf
```

---

## License

MIT
