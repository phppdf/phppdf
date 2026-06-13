---
title: "N-up imposition"
weight: 170
---

`NUpImposer` arranges multiple source pages on fewer output sheets — useful for booklets, proofing sheets, and print-on-demand.

## Presets

```php
use PhpPdf\Reader\PdfDocumentReader;

$source = PdfDocumentReader::open('/path/to/source.pdf');

// 2-up: two portrait A4 pages side by side on A4 landscape (842 × 595)
$config = NUpConfig::twoUp(sheetWidth: 842, sheetHeight: 595);

// 4-up: four portrait A4 pages on A4 landscape (842 × 595)
$config = NUpConfig::fourUp(sheetWidth: 842, sheetHeight: 595);

// 9-up: nine pages on a larger sheet
$config = NUpConfig::nineUp(sheetWidth: 842, sheetHeight: 1190);

$result = (new NUpImposer($source, $config))->impose();

$output = new PdfMemoryOutput();
(new PdfDocumentSerializer($output))->writeDocument($result);
```

## Custom grid

```php
$config = NUpConfig::custom(
    columns:     3,
    rows:        2,
    sheetWidth:  842,
    sheetHeight: 595,
    margin:      10.0,   // points between cells and at the sheet edge
);
```

## How it works

Each source page is imported as a Form XObject, scaled to fit its cell, and painted at the calculated position on the output sheet.  Source pages are placed left-to-right, top-to-bottom.  If the source page count is not a multiple of the grid size, the last sheet may be partially filled.

Output pages use the sheet dimensions specified in the config; source page dimensions are irrelevant.
