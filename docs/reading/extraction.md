# Content extraction

## Text extraction

```php
use PhpPdf\Reader\PdfTextExtractor;

$doc       = PdfDocumentReader::open('/path/to/file.pdf');
$extractor = new PdfTextExtractor($doc);

for ($i = 0; $i < $doc->getPageCount(); $i++) {
    $text = $extractor->getTextForPage($i);   // string
    echo "--- Page " . ($i + 1) . " ---\n";
    echo $text . "\n";
}
```

### Font support

| Font type | Decoding method |
|---|---|
| Type 0 / CID | ToUnicode CMap |
| Type 1, TrueType (WinAnsi) | WinAnsiEncoding / Latin-1 fallback |

Glyphs with purely custom or glyph-substituted encodings may not extract correctly.

### Text operators recognised

`Tj`, `TJ`, `'`, `"` — show text.
`Td`, `TD`, `Tm`, `T*` — position and line breaks.

Large negative kerning values in `TJ` arrays (below −200) are treated as word breaks.

## Image extraction

```php
use PhpPdf\Reader\PdfImageExtractor;

$extractor = new PdfImageExtractor($doc);

// Images on a specific page (0-based)
$images = $extractor->getImagesForPage(0);

// All unique images across the whole document
$images = $extractor->getAllImages();

foreach ($images as $image) {
    echo $image->name . "\n";          // resource name, e.g. "Im1"
    echo $image->width . "×" . $image->height . "\n";
    echo $image->colorSpace . "\n";    // e.g. "DeviceRGB"
    echo $image->bitsPerComponent . "\n";

    // Raw decoded pixel bytes (or JPEG bytes for DCTDecode images)
    $bytes = $image->data;

    // Write to file
    file_put_contents('/tmp/' . $image->name . '.' . $image->getFileExtension(), $image->toFileBytes());
}
```

`getAllImages()` deduplicates shared images — an image referenced from multiple pages appears only once.

`PdfExtractedImage` methods:

| Method | Returns |
|---|---|
| `isJpeg()` | `true` if the data is a JPEG byte stream |
| `getFileExtension()` | `'jpg'` or `'png'` |
| `toFileBytes()` | Raw JPEG bytes or a valid PNG file |
| `toPng()` | Always returns a PNG (wraps raw pixels; converts RGBA with SMask) |

## Annotation extraction

```php
use PhpPdf\Reader\PdfAnnotationExtractor;

$extractor = new PdfAnnotationExtractor($doc);

// Annotations on a single page
$annotations = $extractor->getAnnotationsForPage(0);

// All annotations in the document
$annotations = $extractor->getAllAnnotations();

foreach ($annotations as $ann) {
    echo $ann->type->value;   // e.g. "Link", "Text", "Highlight"
    echo $ann->x . ', ' . $ann->y . '  ' . $ann->width . '×' . $ann->height . "\n";

    if ($ann->isUriLink()) {
        echo 'URL: ' . $ann->uri . "\n";
    }
}
```

### PdfAnnotation properties

| Property | Type | Description |
|---|---|---|
| `type` | `PdfAnnotationType` | Annotation subtype |
| `x`, `y` | `float` | Bottom-left corner |
| `width`, `height` | `float` | Bounding box size |
| `contents` | `?string` | Annotation text/tooltip |
| `title` | `?string` | Popup title |
| `color` | `?array{float,float,float}` | RGB color |
| `interiorColor` | `?array{float,float,float}` | Interior fill RGB |
| `borderWidth` | `float` | Border line width |
| `quadPoints` | `?list<float>` | Quad points for text markup |
| `uri` | `?string` | URI for Link annotations |
| `open` | `?bool` | Whether the popup is open |
