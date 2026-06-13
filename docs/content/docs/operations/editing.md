---
title: "Editing & page management"
weight: 160
---

`PdfDocumentEditor` manipulates an existing compiled `PdfDocument`, enabling you to add, remove, reorder, rotate, and crop pages, and to inject headers or footers after the fact.

## Creating an editor

From a `PdfDocument` built in memory:

```php
use PhpPdf\Document\PdfDocumentEditor;

$editor = new PdfDocumentEditor($doc);
```

From a file on disk (combines reading and editing in one step):

```php
use PhpPdf\Reader\PdfDocumentReader;

$editor = PdfDocumentEditor::fromReadDocument(
    PdfDocumentReader::open('/path/to/file.pdf')
);
```

## Page operations

All page indices are **0-based**.

```php
// Remove a page
$editor->removePage(2);

// Move page 4 before page 1
$editor->movePage(from: 4, to: 1);

// Rotate a page (degrees: 0, 90, 180, 270)
$editor->rotatePage(0, 90);

// Crop a page (sets CropBox)
$editor->cropPage(0, x: 50, y: 50, width: 495, height: 742);
```

## Adding pages from another document

```php
use PhpPdf\Reader\PdfDocumentReader;

$source = PdfDocumentReader::open('/path/to/source.pdf');
$editor->insertPage($source->getPage(0), at: 2);
```

## Injecting headers and footers

`PdfDocumentEditor` can add headers and footers to a document that was built without them, or replace existing ones.

```php
$editor->header(function (PdfContentStreamBuilder $s, int $n, int $total): void {
    $s->beginText()
      ->setFont('F1', 8)
      ->setTextMatrix(Matrix::translate(72, 822))
      ->showText("Page $n of $total")
      ->endText();
});

$editor->footer(function (PdfContentStreamBuilder $s, int $n, int $total): void {
    $s->setLineWidth(0.25)
      ->moveTo(72, 45)->lineTo(523, 45)->stroke();
});

$result = $editor->build();
```

> Fonts referenced in the closures must already exist in the source document.

## Template import

Import a page from an existing PDF as a reusable background (Form XObject):

```php
$letterhead = PdfDocumentReader::open('/path/to/letterhead.pdf');

(new PdfDocumentBuilder())
    ->page(function (PdfPageBuilder $page) use ($letterhead): void {
        $page
            ->size(...PdfPageSize::A4)
            ->useType1Font('F1', 'Helvetica')
            ->useImportedPage('TPL', $letterhead->getPage(0))
            ->content(function (PdfContentStreamBuilder $s): void {
                // Draw the letterhead first, then add content on top
                $s->drawImportedPage('TPL');
                $s->beginText()
                  ->setFont('F1', 12)
                  ->setTextMatrix(Matrix::translate(72, 680))
                  ->showText('Dear Customer,')
                  ->endText();
            });
    })
    ->build();
```

The imported page is embedded as a Form XObject and can be reused across multiple pages without duplicating its content stream.

## Producing the result

```php
$result = $editor->build();   // PdfDocument

$output = new PdfMemoryOutput();
(new PdfDocumentSerializer($output))->writeDocument($result);
```
