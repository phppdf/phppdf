---
title: "Colors"
weight: 60
---

## Content stream color operators

Stroke color (outline) and non-stroking color (fill) are set independently.

```php
// RGB  (values 0.0 – 1.0)
$s->setStrokingRgbColor(0.0, 0.0, 0.0);        // black stroke
$s->setNonStrokingRgbColor(0.2, 0.4, 0.8);     // blue fill

// CMYK (values 0.0 – 1.0)
$s->setStrokingCmykColor(0.0, 0.8, 0.8, 0.0);
$s->setNonStrokingCmykColor(0.0, 0.0, 0.0, 0.2);

// Grayscale (0.0 = black, 1.0 = white)
$s->setStrokingGray(0.5);
$s->setNonStrokingGray(0.9);
```

## Color helper

`Color` is a higher-level helper used by tables, gradients, and other utilities.

```php
use PhpPdf\Color\Color;

Color::fromHex('#3366cc');          // hex shorthand
Color::rgb(0.2, 0.4, 0.8);         // RGB
Color::cmyk(0.0, 0.8, 0.8, 0.0);  // CMYK
Color::gray(0.5);                   // grayscale

// Named constructors
Color::black();  Color::white();
Color::red();    Color::green();   Color::blue();
Color::yellow(); Color::cyan();    Color::magenta();

// Manipulation
$lighter = $color->lighter(0.2);   // increase lightness by 0.2
$darker  = $color->darker(0.2);    // decrease lightness by 0.2
$mixed   = $color->mix($other, 0.5); // blend 50/50
```

## Transparency

Transparency is set via a graphics state dictionary registered on the page:

```php
$page->useGraphicsState('GS1', alpha: 0.5);   // 50 % opacity for stroke + fill

$s->saveGraphicsState()
  ->setGraphicsStateParameters('GS1')
  ->setNonStrokingRgbColor(1.0, 0.0, 0.0)
  ->rectangle(100, 400, 200, 100)
  ->fill()
  ->restoreGraphicsState();
```

## Blend modes

Blend modes are also set through a graphics state dictionary:

```php
use PhpPdf\Content\BlendMode;

$page->useGraphicsState('GS_MULT', blendMode: BlendMode::Multiply);

$s->saveGraphicsState()
  ->setGraphicsStateParameters('GS_MULT')
  ->/* draw something */
  ->restoreGraphicsState();
```

Available `BlendMode` values: `Normal`, `Multiply`, `Screen`, `Overlay`, `Darken`, `Lighten`, `ColorDodge`, `ColorBurn`, `HardLight`, `SoftLight`, `Difference`, `Exclusion`.

## Combining opacity and blend mode

A single graphics state dictionary can carry both:

```php
$page->useGraphicsState('GS_OVR', alpha: 0.7, blendMode: BlendMode::Overlay);
```
