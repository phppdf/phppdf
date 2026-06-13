---
title: "Text layout"
weight: 40
---

## Raw text operators

At the lowest level, text is placed using PDF text operators exposed on `PdfContentStreamBuilder`.

```php
$s->beginText()
  ->setFont('F1', 12)
  ->setTextMatrix(Matrix::translate(72, 720))   // position (x, y)
  ->showText('Hello World')
  ->endText();
```

`Matrix::translate(x, y)` sets the text position.  `y` is measured from the bottom of the page.

## TextBox — word-wrapped text block

`TextBox` measures and wraps a string into a fixed-width block.  It does not draw anything itself; drawing is done by `PdfContentStreamBuilder::drawTextBox()`.

```php


$metrics = Type1FontMetrics::helvetica();

$box = TextBox::create(
    text:       $longText,
    metrics:    $metrics,
    fontSize:   12,
    width:      451.0,        // available width in points
    lineHeight: 15.0,         // leading (defaults to fontSize * 1.2)
    align:      TextAlign::Justify,
);

// Draw at position (x=72, y=770) — y is the top of the first line
$s->drawTextBox($box, fontName: 'F1', x: 72, y: 770);

// How much vertical space did it take?
$yAfter = 770 - $box->getHeight();
```

### Alignment

`TextAlign` values: `Left`, `Centre`, `Right`, `Justify`.

### Overflow detection

```php
if ($box->isOverflowing(maxHeight: 698.0)) {
    // text did not fit in the available height
}
```

## TextFlow — automatic pagination

`TextFlow::pour()` paginates a `TextBox` across as many pages as needed.

```php


TextFlow::pour(
    box:       $box,
    document:  $docBuilder,      // PdfDocumentBuilder (before ->build())
    configure: fn(PdfPageBuilder $p) => $p
        ->size(...PdfPageSize::A4)
        ->useType1Font('F1', 'Helvetica'),
    fontName:  'F1',
    x:         72.0,
    y:         770.0,            // starting y on each new page
    maxHeight: 698.0,            // height budget per page
);

$document = $docBuilder->build();
```

`TextFlow::pour()` adds pages to the builder until the full text fits.  Call `->build()` after it returns.

## Hyphenation

Wrap a `TeXHyphenator` in the `TextBox::create()` call to enable TeX-based soft hyphenation:

```php


$box = TextBox::create(
    $text, $metrics, 12, 200.0,
    hyphenator: new TeXHyphenator('en_US'),
);
```

Language codes follow the IETF BCP 47 convention.  Available patterns depend on which pattern files ship with the library.

## Lists

```php


$metrics = Type1FontMetrics::helvetica();

$list = new ListBox([
    new ListItem('First item',  $metrics, 11, 400.0),
    new ListItem('Second item', $metrics, 11, 400.0),
    new ListItem('Third item',  $metrics, 11, 400.0),
], $metrics, 11);

$s->drawListBox($list, 'F1', x: 72, y: 700);
```

Each `ListItem` wraps its text at the given `width` and contributes its `getHeight()` to the total list height.

## Text spacing operators

These operators operate within a `beginText()` / `endText()` block:

```php
$s->setCharacterSpacing(1.5)      // extra space between characters (pt)
  ->setWordSpacing(2.0)            // extra space between words (pt)
  ->setHorizontalTextScaling(90)   // percentage of normal width
  ->setTextLeading(16.0)           // line spacing for T* / moveToNextLine
  ->setTextRise(3.0);              // vertical offset for superscript/subscript
```

## Text rendering modes

```php
use PhpPdf\Content\Operation\SetTextRenderingMode;

$s->setTextRenderingMode(0);  // fill (default)
$s->setTextRenderingMode(1);  // stroke
$s->setTextRenderingMode(2);  // fill then stroke
$s->setTextRenderingMode(3);  // invisible
$s->setTextRenderingMode(7);  // clip (use for knockout effects)
```
