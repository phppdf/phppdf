---
title: "Fonts"
weight: 30
---

## Standard Type 1 fonts

Type 1 fonts are built into every PDF viewer.  They require no embedding and add no file size.

```php
$page->useType1Font('F1', 'Helvetica');
$page->useType1Font('F2', 'Helvetica-Bold');
$page->useType1Font('F3', 'Helvetica-Oblique');
$page->useType1Font('F4', 'Helvetica-BoldOblique');

$page->useType1Font('F5', 'Times-Roman');
$page->useType1Font('F6', 'Times-Bold');
$page->useType1Font('F7', 'Times-Italic');
$page->useType1Font('F8', 'Times-BoldItalic');

$page->useType1Font('F9',  'Courier');
$page->useType1Font('F10', 'Courier-Bold');
$page->useType1Font('F11', 'Courier-Oblique');
$page->useType1Font('F12', 'Courier-BoldOblique');

$page->useType1Font('F13', 'Symbol');
$page->useType1Font('F14', 'ZapfDingbats');
```

The first argument is the **resource name** used in content stream operators (`setFont`, `drawTextBox`, etc.).  It can be any non-empty string without spaces.

## Embedded TrueType / OpenType fonts

Embedded fonts support the full Unicode range and are the right choice for non-Latin scripts.  Only the glyphs actually used are written to the file (automatic subsetting).

```php
use PhpPdf\Font\TrueTypeFont;

$font = TrueTypeFont::fromFile('/path/to/font.ttf');
$page->useEmbeddedFont('F1', $font);
```

**TTC (TrueType Collection) files** contain multiple fonts at numbered indices:

```php
$regular = TrueTypeFont::fromFile('/path/to/NotoSansCJK-Regular.ttc', 0);
$bold    = TrueTypeFont::fromFile('/path/to/NotoSansCJK-Regular.ttc', 1);
```

## Font metrics

`TextBox` and `TableCell` need a `FontMetrics` object to measure text.  The two implementations are:

```php


// Type 1 built-ins
$metrics = Type1FontMetrics::helvetica();
$metrics = Type1FontMetrics::helveticaBold();
$metrics = Type1FontMetrics::timesRoman();
$metrics = Type1FontMetrics::courier();

// TrueType
$font    = TrueTypeFont::fromFile('/path/to/font.ttf');
$metrics = new TrueTypeFontMetrics($font);
```

## Global fonts

A global font is registered once on the builder and is available on every page, including in header and footer closures.

```php
(new PdfDocumentBuilder())
    ->globalFont('F1', 'Helvetica')          // Type 1
    ->globalFont('F2', $trueTypeFont)         // TrueType
    ->header(function (PdfContentStreamBuilder $s, int $n, int $total): void {
        $s->beginText()->setFont('F1', 9)
          ->setTextMatrix(Matrix::translate(72, 822))
          ->showText("Page $n of $total")
          ->endText();
    })
```

> Per-page fonts registered with `->useType1Font()` or `->useEmbeddedFont()` are **not** available in the header/footer closures.

## Using fonts in content

After registration the resource name is used in all text operators:

```php
->content(function (PdfContentStreamBuilder $s): void {
    $s->beginText()
      ->setFont('F1', 12)          // resource name, size in points
      ->setTextMatrix(Matrix::translate(72, 720))
      ->showText('Hello')
      ->endText();
})
```

See [Text layout](text.md) for higher-level helpers built on top of these operators.
