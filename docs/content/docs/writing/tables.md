---
title: "Tables"
weight: 90
---

`TableBuilder` lays out grid-based tables with configurable columns, per-cell styling, and row/column span.

## Basic usage

```php
use PhpPdf\Color\Color;

$metrics     = Type1FontMetrics::helvetica();
$boldMetrics = Type1FontMetrics::helveticaBold();

$yBelow = TableBuilder::create(x: 72, y: 700)
    ->columns([160, 80, 80, 80])             // column widths in points
    ->font('F1', 10, $metrics)               // default font for all cells
    ->padding(5, 6, 4, 6)                    // top, right, bottom, left
    ->border(Color::fromHex('#cccccc'), 0.5) // color, line width
    ->addRow(
        TableRow::cells([
            TableCell::text('Description')
                ->font('F2', 10, $boldMetrics)
                ->background(Color::fromHex('#1a3a5c'))
                ->textColor(Color::white()),
            TableCell::text('Qty')->align(TextAlign::Right)
                ->background(Color::fromHex('#1a3a5c'))
                ->textColor(Color::white()),
            TableCell::text('Price')->align(TextAlign::Right)
                ->background(Color::fromHex('#1a3a5c'))
                ->textColor(Color::white()),
            TableCell::text('Total')->align(TextAlign::Right)
                ->background(Color::fromHex('#1a3a5c'))
                ->textColor(Color::white()),
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
    ->draw($s);  // returns the y coordinate just below the table
```

## Column widths

Column widths are always explicit.  The sum determines the table width.  Any column can be omitted from `->columns()` if you want unequal widths — but every row must supply exactly as many cells as there are columns.

## Row and cell styling

### Row background

```php
TableRow::cells([...])->background(Color::fromHex('#f5f5f5'))
```

### Cell overrides

All cell style methods chain fluently:

```php
TableCell::text('Header')
    ->font('FB', 11, $boldMetrics)    // override font for this cell
    ->background(Color::navy())
    ->textColor(Color::white())
    ->align(TextAlign::Centre)
    ->verticalAlign(TableVerticalAlign::Middle)
    ->padding(8, 10, 8, 10)           // cell-level padding override
```

### Vertical alignment

```php


TableCell::text('...')->verticalAlign(TableVerticalAlign::Top);
TableCell::text('...')->verticalAlign(TableVerticalAlign::Middle);
TableCell::text('...')->verticalAlign(TableVerticalAlign::Bottom);
```

## Column span

```php
TableCell::text('Merged header')->colSpan(2)
```

The spanning cell must be followed by exactly `colSpan - 1` fewer cells in the same row.

## Row span

```php
TableCell::text('Spans two rows')->rowSpan(2)
```

Subsequent rows must leave the covered columns empty (`TableCell::empty()`).

## Multi-line cell content

`TableCell` wraps text automatically within the column width.  Tall cells push the row height to fit.

## Return value

`->draw($s)` returns the `y` coordinate of the bottom edge of the table, making it easy to continue drawing below it:

```php
$yAfterTable = $table->draw($s);
$s->beginText()
  ->setTextMatrix(Matrix::translate(72, $yAfterTable - 10))
  ->showText('Notes:')
  ->endText();
```
