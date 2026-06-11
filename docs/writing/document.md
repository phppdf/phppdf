# Document & pages

## PdfDocumentBuilder

`PdfDocumentBuilder` is the entry point for creating a PDF.  It is fluent — each method returns `$this` so calls can be chained.

```php
use PhpPdf\Builder\PdfDocumentBuilder;

$doc = (new PdfDocumentBuilder())
    ->info(...)       // metadata
    ->globalFont(...) // font available to every page
    ->header(...)     // header drawn on every page
    ->footer(...)     // footer drawn on every page
    ->outline(...)    // bookmark tree
    ->form(...)       // AcroForm fields
    ->encrypt(...)    // AES-128 encryption
    ->sign(...)       // digital signature
    ->conformTo(...)  // PDF/A conformance
    ->page(...)       // add a page (repeat as needed)
    ->build();        // returns PdfDocument
```

## Pages

Each page is configured in a closure that receives `PdfPageBuilder`:

```php
->page(function (PdfPageBuilder $page): void {
    $page
        ->size(...PdfPageSize::A4)
        ->useType1Font('F1', 'Helvetica')
        ->content(function (PdfContentStreamBuilder $s): void {
            // drawing, text, images …
        });
})
```

## Page sizes

`PdfPageSize` provides named constants in portrait orientation (width × height, in points).

```php
use PhpPdf\Builder\PdfPageSize;

$page->size(...PdfPageSize::A4);       // 595 × 842
$page->size(...PdfPageSize::A3);       // 842 × 1191
$page->size(...PdfPageSize::Letter);   // 612 × 792
$page->size(...PdfPageSize::Legal);    // 612 × 1008
$page->size(...PdfPageSize::Tabloid);  // 792 × 1224
$page->size(200.0, 300.0);             // custom width × height
```

For landscape, swap width and height:

```php
$page->size(842.0, 595.0);  // A4 landscape
```

## Document metadata

```php
use PhpPdf\Document\PdfDocumentInfo;

->info(
    (new PdfDocumentInfo())
        ->title('Annual Report 2025')
        ->author('Jane Smith')
        ->subject('Financial results')
        ->keywords('finance, annual, 2025')
        ->creator('My App 1.0')
)
```

## Headers and footers

Headers and footers are closures that receive a `PdfContentStreamBuilder`, the current page number (1-based), and the total page count.  They are drawn on every page of the document.

```php
->globalFont('F1', 'Helvetica')   // fonts used in header/footer must be global
->header(function (PdfContentStreamBuilder $s, int $pageNum, int $total): void {
    $s->beginText()
      ->setFont('F1', 8)
      ->setTextMatrix(Matrix::translate(72, 822))
      ->showText("Page {$pageNum} of {$total}")
      ->endText();
})
->footer(function (PdfContentStreamBuilder $s, int $pageNum, int $total): void {
    $s->setLineWidth(0.5)
      ->moveTo(72, 50)->lineTo(523, 50)->stroke();
})
```

> **Note** — fonts used in header/footer closures must be registered as global fonts with `->globalFont()`, not per-page fonts, because headers and footers are applied across all pages.

## PDF version

The default output version is 1.7.  You can pin an earlier version:

```php
use PhpPdf\Document\PdfVersion;

->version(PdfVersion::PDF_1_4)
```

## Multiple pages

Add as many `->page()` calls as needed.  They appear in the document in the order they are added.

```php
(new PdfDocumentBuilder())
    ->page(fn(PdfPageBuilder $p) => $p->size(...PdfPageSize::A4)->content(fn($s) => /* page 1 */))
    ->page(fn(PdfPageBuilder $p) => $p->size(...PdfPageSize::A4)->content(fn($s) => /* page 2 */))
    ->build();
```

For variable-length content that spans an unknown number of pages, use [TextFlow](text.md#textflow).
