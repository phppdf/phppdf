# Forms

## Reading form fields

```php
use PhpPdf\Reader\PdfDocumentReader;
use PhpPdf\Reader\PdfAcroFormReader;

$doc    = PdfDocumentReader::open('/path/to/form.pdf');
$reader = new PdfAcroFormReader($doc);

// All fields as a flat list
foreach ($reader->getFields() as $field) {
    echo $field->fullName . "\n";   // e.g. "address.city"
    echo $field->type->name . "\n"; // Text, Button, Choice
    var_dump($field->value);        // string, bool, or null
    print_r($field->options);       // list<string> for Choice fields
}

// Look up by full name
$fieldMap = $reader->getFieldsByName();
$city = $fieldMap['address.city'] ?? null;
```

### PdfFormField properties

| Property | Type | Description |
|---|---|---|
| `objectNumber` | `int` | PDF object number |
| `generationNumber` | `int` | PDF generation number |
| `name` | `string` | Partial field name (/T) |
| `fullName` | `string` | Dot-separated full name |
| `type` | `PdfFormFieldType` | `Text`, `Button`, `Choice` |
| `value` | `string\|bool\|null` | Current value |
| `options` | `list<string>` | Available choices (Choice fields) |
| `readOnly` | `bool` | Whether the field is read-only |
| `multiLine` | `bool` | Whether a Text field is multi-line |

### Hierarchical forms

Form fields are organised in a tree.  `getFields()` returns only leaf fields (fields with a type).  Parent nodes (intermediate groups) are traversed automatically.

## Filling a form

`PdfAcroFormFiller` writes an **incremental update** — it appends changes to the end of the original bytes, leaving the original byte ranges intact (required for digital-signature compatibility).

```php
use PhpPdf\Reader\PdfAcroFormFiller;

$originalBytes = file_get_contents('/path/to/form.pdf');
$filler = new PdfAcroFormFiller($doc, $originalBytes);

$fields = $reader->getFieldsByName();

// Fill a text field
$filler->setText($fields['firstName'], 'Jane');
$filler->setText($fields['email'],     'jane@example.com');

// Fill a multi-line text area
$filler->setText($fields['message'], "Hello,\nThis is my message.");

// Set a checkbox
$filler->setChecked($fields['subscribe'], true);

// Select a combo-box option (must be one of the field's /Opt values)
$filler->setChoice($fields['country'], 'Netherlands');

// Write to file
$filler->save('/path/to/filled.pdf');

// Or get bytes
$bytes = $filler->getBytes();
```

### What fills and what doesn't

- `setText()` works on Text fields and text areas.
- `setChecked()` works on Button (checkbox) fields.
- `setChoice()` works on Choice (combo box / list box) fields.
- Read-only fields are silently ignored.

### Appearance streams

The filler sets `NeedAppearances = true` in the AcroForm dictionary so that PDF viewers regenerate visual appearances on open.  This is the widely-compatible approach; it does not embed pre-rendered appearance streams itself.
