---
title: "Getting started"
weight: 10
---

## Installation

```bash
composer require phppdf/phppdf
```

## Your first PDF

```php
use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;

$document = (new PdfDocumentBuilder())
    ->info((new PdfDocumentInfo())->title('Hello World')->author('My App'))
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

// Send to browser
header('Content-Type: application/pdf');
echo $output->getContent();
```

## Core concepts

**Coordinate system** — PDF uses points (1/72 inch) with the origin at the **bottom-left** of the page.  `y` increases upward.  A4 is 595 × 842 pt; US Letter is 612 × 792 pt.

**Content stream** — All drawing, text, and graphics state changes go through `PdfContentStreamBuilder`.  The builder is passed to the closure given to `->content()`.

**Serializer** — A built `PdfDocument` is an in-memory object graph.  `PdfDocumentSerializer` writes the binary PDF bytes to an `PdfOutput` implementation.

## Output targets

```php
use PhpPdf\Output\PdfMemoryOutput;   // result available as string
use PhpPdf\Output\PdfFileOutput;     // writes directly to disk
use PhpPdf\Output\PdfStreamOutput;   // writes to any PHP stream resource

// File
(new PdfDocumentSerializer(new PdfFileOutput('/path/output.pdf')))->writeDocument($doc);

// PHP stream
$stream = fopen('php://output', 'wb');
(new PdfDocumentSerializer(new PdfStreamOutput($stream)))->writeDocument($doc);

// Memory
$output = new PdfMemoryOutput();
(new PdfDocumentSerializer($output))->writeDocument($doc);
$bytes = $output->getContent();
```

## Next steps

- [Document & pages](writing/document.md) — headers, footers, metadata, multiple pages
- [Fonts](writing/fonts.md) — embedding TrueType fonts
- [Text layout](writing/text.md) — word-wrapped text and automatic pagination
- [Drawing](writing/drawing.md) — paths, shapes, graphics state
