---
title: "Images, QR codes & barcodes"
weight: 80
---

## Raster images (PNG and JPEG)

The GD extension handles image loading.

```php
use PhpPdf\Image\PdfImage;

// From file
$image = PdfImage::fromFile('/path/to/photo.jpg');
$image = PdfImage::fromFile('/path/to/graphic.png');

// From in-memory bytes
$image = PdfImage::fromData($pngBytes);
```

Register the image on the page, then draw it in the content stream:

```php
$page->useImage('IMG1', $image);

$s->drawImage(
    name:   'IMG1',
    x:      72,
    y:      500,   // bottom-left corner
    width:  200,
    height: 150,
);
```

**PNG alpha** — RGBA PNGs carry their alpha channel as a soft mask.  It is preserved automatically; no extra steps are required.

**Aspect ratio** — supply only `width` or only `height` and the library calculates the other dimension:

```php
$s->drawImage('IMG1', x: 72, y: 500, width: 200);   // height computed
```

## SVG

A subset of SVG 1.1 is supported: basic shapes (`rect`, `circle`, `ellipse`, `line`, `polyline`, `polygon`), paths (`path`), groups (`g`), transforms, and fill/stroke presentation attributes.

```php


$svg = SvgDocument::fromFile('/path/to/logo.svg');
// or:
$svg = SvgDocument::fromXml($svgString);

$page->useSvg('SVG1', $svg);
$s->drawSvg('SVG1', x: 72, y: 500, width: 200, height: 150);
```

## QR codes

All QR code versions are supported.  Error correction levels: L (7 %), M (15 %), Q (25 %), H (30 %).

```php
use PhpPdf\QrCode\QrCode;

$qr = QrCode::encode('https://example.com');                     // default M level
$qr = QrCode::encode('https://example.com', errorLevel: 'H');

// moduleSize: size of each module (dark square) in points
$s->drawQrCode($qr, x: 72, y: 500, moduleSize: 3.0);
```

The drawn size is `moduleSize × (modules per side)`.

## Barcodes

### Code 128

Code 128 encodes arbitrary ASCII text.

```php


$barcode = Code128::encode('ABC-1234-5678');
$s->drawBarcode($barcode, x: 72, y: 400, width: 200, height: 40);
```

### EAN-13

EAN-13 requires exactly 12 or 13 digits.  If 12 are given the check digit is appended automatically.

```php


$barcode = EAN13::encode('590123412345');   // 12 digits → check digit added
$s->drawBarcode($barcode, x: 72, y: 340, width: 160, height: 40);
```
