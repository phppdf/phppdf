# phppdf documentation

phppdf is a pure PHP library for creating and processing PDF files.  It maps closely to the PDF specification while providing higher-level helpers for common tasks.  No Composer dependencies are required beyond three standard PHP extensions.

## Requirements

| Requirement | Version |
|---|---|
| PHP | 8.4+ |
| ext-gd | any |
| ext-mbstring | any |
| ext-openssl | any |

## Installation

```bash
composer require phppdf/phppdf
```

---

## Contents

### Getting started
- [Getting started](getting-started.md) — installation, first PDF, output targets

### Writing PDFs
- [Document & pages](writing/document.md) — builder, pages, metadata, page sizes, output
- [Fonts](writing/fonts.md) — Type 1, TrueType/OpenType, subsetting, global fonts
- [Text layout](writing/text.md) — TextBox, TextFlow, lists, hyphenation, alignment
- [Drawing](writing/drawing.md) — paths, shapes, graphics state, transformations
- [Colors](writing/colors.md) — RGB, CMYK, grayscale, transparency, blend modes
- [Gradients](writing/gradients.md) — axial, radial, multi-stop
- [Images, QR codes & barcodes](writing/images.md) — PNG, JPEG, SVG, QR, Code 128, EAN-13
- [Tables](writing/tables.md) — columns, rows, cells, borders, spans
- [Interactive features](writing/interactive.md) — links, annotations, outlines, AcroForms
- [Compliance & security](writing/compliance.md) — PDF/A, encryption, permissions, signing

### Reading PDFs
- [Opening documents](reading/opening.md) — PdfDocumentReader, page access, metadata
- [Content extraction](reading/extraction.md) — text, images, annotations
- [Forms](reading/forms.md) — reading fields, filling and saving

### Document operations
- [Merging](operations/merging.md) — combine multiple documents
- [Editing & page management](operations/editing.md) — add, remove, reorder, rotate pages
- [Imposition](operations/imposition.md) — N-up layout (2-up, 4-up, 9-up)
- [PDF/A validation](operations/validation.md) — compliance checking

### Reference
- [CLI](cli.md) — command-line tools
