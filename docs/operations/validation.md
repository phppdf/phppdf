# PDF/A validation

`PdfAValidator` checks whether a document conforms to a given PDF/A level and reports any issues found.

## Usage

```php
use PhpPdf\Reader\PdfDocumentReader;

$doc    = PdfDocumentReader::open('/path/to/file.pdf');
$result = (new PdfAValidator())->validate($doc, PdfAConformance::PdfA2b);

if ($result->isCompliant()) {
    echo "The document is PDF/A-2b compliant.\n";
} else {
    foreach ($result->getIssues() as $issue) {
        echo '[' . $issue->level->value . '] ' . $issue->message . "\n";
    }
}
```

## Issue severity levels

`PdfAIssueLevel` values:

| Level | Meaning |
|---|---|
| `Error` | Violates a PDF/A requirement; document is not conformant |
| `Warning` | Possibly non-conformant; review recommended |
| `Info` | Informational note, not a compliance failure |

## Filtering by level

```php


$errors = array_filter(
    $result->getIssues(),
    fn($issue) => $issue->level === PdfAIssueLevel::Error,
);
```

## PdfAValidationResult

| Method | Returns |
|---|---|
| `isCompliant()` | `true` if no errors were found |
| `getIssues()` | `list<PdfAValidationIssue>` |

## Common issues detected

- Missing or invalid XMP metadata
- Fonts not fully embedded
- Transparency used in a level that forbids it (PDF/A-1)
- Missing color profile information
- Encryption present (forbidden by all PDF/A levels)
- Invalid PDF version marker
