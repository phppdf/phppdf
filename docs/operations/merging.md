# Merging documents

`PdfDocumentMerger` combines multiple `PdfDocument` objects into one.

## Basic usage

```php
use PhpPdf\Document\PdfDocumentMerger;

$docA = (new PdfDocumentBuilder())/* … */->build();
$docB = (new PdfDocumentBuilder())/* … */->build();

$merged = (new PdfDocumentMerger())
    ->add($docA)
    ->add($docB)
    ->build();

$output = new PdfMemoryOutput();
(new PdfDocumentSerializer($output))->writeDocument($merged);
```

Pages are added in the order the documents are passed to `->add()`.

## How merging works

The merger copies each source document's object graph (fonts, images, content streams, resource dictionaries) into a shared `PdfObjectRegistry`.  Object numbers are reassigned to avoid collisions.  The page tree is rebuilt from the merged page list.

Each page retains its own fonts and resources unchanged — a page using Times-Roman stays in Times-Roman regardless of what other pages use.

## Limitations

| Feature | Behaviour in merged output |
|---|---|
| Encryption | Dropped — output is unencrypted |
| Digital signatures | Invalidated — byte ranges change |
| Document outlines (bookmarks) | Dropped |
| AcroForm fields | Not merged |
| Metadata (/Info) | From the first document only |

For non-destructive combination of already-signed or encrypted PDFs, use page-by-page import via `PdfDocumentEditor` or template import instead.
