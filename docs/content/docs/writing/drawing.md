---
title: "Drawing"
weight: 50
---

All drawing goes through `PdfContentStreamBuilder`.  The PDF graphics model is a state machine: you construct a path, then paint it.

## Paths

```php
// Straight lines
$s->moveTo(72, 400)
  ->lineTo(523, 400)
  ->stroke();

// Rectangle (shorthand)
$s->rectangle(x: 72, y: 500, width: 200, height: 100)
  ->fill();

// Cubic Bézier curve: moveTo then curveTo(cp1x, cp1y, cp2x, cp2y, x, y)
$s->moveTo(100, 300)
  ->curveTo(150, 400, 200, 200, 250, 300)
  ->stroke();
```

## Painting operators

| Method | Effect |
|---|---|
| `->stroke()` | Stroke the path with the current stroke color |
| `->fill()` | Fill using non-zero winding rule |
| `->fillEvenOdd()` | Fill using even-odd rule |
| `->fillAndStroke()` | Fill then stroke (non-zero) |
| `->fillAndStrokeEvenOdd()` | Fill then stroke (even-odd) |
| `->closeAndStroke()` | Close open subpath, then stroke |
| `->endPath()` | Discard path without painting |
| `->clip()` | Set clipping region (non-zero); follow with `endPath()` |
| `->clipEvenOdd()` | Set clipping region (even-odd) |

## Graphics state

Save and restore the full graphics state to isolate style changes:

```php
$s->saveGraphicsState()
  ->setLineWidth(2.0)
  ->setStrokingRgbColor(1.0, 0.0, 0.0)
  ->rectangle(72, 500, 200, 100)
  ->stroke()
  ->restoreGraphicsState();  // width and color reset here
```

### Line style

```php
$s->setLineWidth(0.5)
  ->setLineCap(1)       // 0 = butt, 1 = round, 2 = square
  ->setLineJoin(1)      // 0 = miter, 1 = round, 2 = bevel
  ->setMiterLimit(4.0)
  ->setDashPattern([3, 2], 0);  // [dash, gap, ...], phase
```

## Transformations

Transformations affect all subsequent drawing until the graphics state is restored.

```php
use PhpPdf\Content\Matrix;

// Translate (move origin)
$s->concatenateMatrix(Matrix::translate(100, 200));

// Rotate (radians, counter-clockwise around current origin)
$s->concatenateMatrix(Matrix::rotate(deg2rad(45)));

// Scale
$s->concatenateMatrix(Matrix::scale(1.5, 1.5));

// Arbitrary matrix: [a, b, c, d, e, f]
$s->concatenateMatrix(new Matrix(1, 0, 0, 1, 50, 100));
```

Always wrap transformations in `saveGraphicsState()` / `restoreGraphicsState()`:

```php
$s->saveGraphicsState()
  ->concatenateMatrix(Matrix::rotate(deg2rad(30)))
  ->rectangle(0, 0, 100, 40)
  ->fill()
  ->restoreGraphicsState();
```

## Clipping

Set a clipping region before painting gradients, images, or any content that needs a shape mask:

```php
// Circular clip
$s->saveGraphicsState()
  ->circle(cx: 300, cy: 500, radius: 80)   // helper if available, or use curveTo
  ->clip()
  ->endPath()
  ->drawImage('IMG', 220, 420, 160, 160)
  ->restoreGraphicsState();

// Rectangular clip (common for gradients)
$s->saveGraphicsState()
  ->rectangle(72, 550, 451, 60)
  ->clip()
  ->endPath()
  ->paintShading('G1')
  ->restoreGraphicsState();
```

## Transparency

Graphics state dictionaries control opacity and blend mode.  See [Colors](colors.md#transparency) for usage.
