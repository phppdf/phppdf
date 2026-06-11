# Gradients

Gradients are painted as shading patterns.  The workflow is:

1. Create the shading object.
2. Register it on the page with `->useShading()`.
3. Set a clipping region in the content stream.
4. Call `->paintShading()`.

## Axial (linear) gradients

### Two-color

```php
use PhpPdf\Shading\PdfAxialShading;
use PhpPdf\Color\Color;

$gradient = PdfAxialShading::between(
    x0: 72,   y0: 600,   // start point
    x1: 523,  y1: 600,   // end point
    colorStart: Color::fromHex('#3b5ce6'),
    colorEnd:   Color::fromHex('#e63b3b'),
);

$page->useShading('G1', $gradient);
```

### Multi-stop

```php
use PhpPdf\Shading\PdfAxialShading;
use PhpPdf\Shading\ColorStop;

$gradient = PdfAxialShading::multiStop(
    x0: 72,  y0: 600,
    x1: 523, y1: 600,
    stops: [
        new ColorStop(0.0, Color::fromHex('#e63b3b')),
        new ColorStop(0.5, Color::fromHex('#f5a623')),
        new ColorStop(1.0, Color::fromHex('#3b5ce6')),
    ],
);
```

## Radial gradients

```php
use PhpPdf\Shading\PdfRadialShading;

// Circle expanding outward
$radial = PdfRadialShading::circle(
    cx: 297, cy: 420, radius: 150,
    colorCenter: Color::white(),
    colorEdge:   Color::fromHex('#1a3a5c'),
);

// Explicit control: inner circle (cx0,cy0,r0) → outer circle (cx1,cy1,r1)
$radial = PdfRadialShading::between(
    cx0: 297, cy0: 420, r0: 0,
    cx1: 297, cy1: 420, r1: 150,
    colorStart: Color::white(),
    colorEnd:   Color::navy(),
);
```

## Painting a gradient

A gradient must be painted inside a clipping path — otherwise it floods the entire page.

```php
$s->saveGraphicsState()
  ->rectangle(72, 560, 451, 60)
  ->clip()
  ->endPath()
  ->paintShading('G1')
  ->restoreGraphicsState();
```

Any path operator can serve as the clip: rectangles, ellipses, Bézier shapes, or even text (with rendering mode 7).

## Diagonal and vertical gradients

The direction is controlled entirely by the start and end coordinates:

```php
// Vertical (top to bottom)
PdfAxialShading::between(x0: 72, y0: 620, x1: 72, y1: 560, ...);

// Diagonal
PdfAxialShading::between(x0: 72, y0: 560, x1: 523, y1: 620, ...);
```
