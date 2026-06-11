# Interactive features

## Links

### URI links (external)

```php
$page->addUriLink(
    x: 72, y: 697, width: 200, height: 15,
    uri: 'https://example.com',
);
```

The rectangle defines the clickable area in page coordinates (bottom-left origin).

### Internal page links

```php
$page->addPageLink(
    x: 72, y: 657, width: 200, height: 15,
    pageIndex: 2,   // 0-based
);
```

## Annotations

Annotations attach a visible or invisible mark to a location on the page.

```php
// Text note (sticky note icon)
$page->addAnnotation(PdfAnnotationType::Text,
    x: 72, y: 700, width: 20, height: 20,
    contents: 'This needs review.',
);

// Highlight
$page->addAnnotation(PdfAnnotationType::Highlight,
    x: 72, y: 695, width: 200, height: 12,
    color: [1.0, 1.0, 0.0],   // RGB yellow
);

// Underline
$page->addAnnotation(PdfAnnotationType::Underline,
    x: 72, y: 695, width: 200, height: 12,
);

// Square / Circle shapes
$page->addAnnotation(PdfAnnotationType::Square,
    x: 72, y: 640, width: 100, height: 40,
    color: [1.0, 0.0, 0.0],
    borderWidth: 1.5,
);
```

## Document outlines (bookmarks)

Outlines create the bookmark panel shown in PDF viewers.

```php
use PhpPdf\Builder\PdfOutlineBuilder;

(new PdfDocumentBuilder())
    ->outline(function (PdfOutlineBuilder $o): void {
        $o->item('Introduction', pageIndex: 0);
        $o->item('Chapter 1',    pageIndex: 1);
        $o->item('Chapter 2',    pageIndex: 3, configure: function (PdfOutlineBuilder $sub): void {
            $sub->item('Section 2.1', pageIndex: 3);
            $sub->item('Section 2.2', pageIndex: 5);
        });
        $o->item('Conclusion',   pageIndex: 7);
    })
```

Nesting is unlimited.  `pageIndex` is 0-based.

## AcroForms

### Building a form

```php
use PhpPdf\Builder\PdfFormBuilder;

(new PdfDocumentBuilder())
    ->form(function (PdfFormBuilder $form): void {
        // Text fields
        $form->textField('firstName', x: 200, y: 700, width: 300, height: 20);
        $form->textField('lastName',  x: 200, y: 670, width: 300, height: 20);

        // Multi-line text area
        $form->textArea('message', x: 200, y: 560, width: 300, height: 80);

        // Checkbox
        $form->checkbox('subscribe', x: 200, y: 540, size: 14);

        // Combo box (drop-down)
        $form->comboBox('country', x: 200, y: 510, width: 300, height: 20,
            options: ['', 'Germany', 'Netherlands', 'United Kingdom'],
        );
    })
```

Field coordinates are in PDF page space (origin at bottom-left, y increases upward).

### Read-only fields

```php
$form->textField('id', x: 200, y: 740, width: 100, height: 20, readOnly: true);
```

### Reading and filling forms

See the [Forms](../reading/forms.md) guide for how to read field values from an existing PDF and fill them via an incremental update.
