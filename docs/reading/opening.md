# Opening documents

## PdfDocumentReader

```php
use PhpPdf\Reader\PdfDocumentReader;

$doc = PdfDocumentReader::open('/path/to/file.pdf');
```

### Password-protected files

```php
$doc = PdfDocumentReader::openEncrypted('/path/to/file.pdf', 'password');
```

The password is tried first as the user password, then as the owner password.  Throws `PdfReadException` on wrong password.

## Document-level information

```php
echo $doc->getVersion()->value;   // e.g. "1.7"
echo $doc->getPageCount();        // integer

$info = $doc->getInfo();          // PdfDictionary or null
if ($info !== null) {
    $title = $info->get('Title'); // PdfString or null
}
```

## Page access

```php
$page = $doc->getPage(0);         // PdfReadPage, 0-based index
```

`PdfReadPage` provides:

```php
$page->getMediaBox();             // [x, y, width, height]
$page->getDictionary();           // raw PdfDictionary
$page->getResources();            // resource PdfDictionary
$page->getContentStreams();       // list<string> — decoded stream bytes
```

## Supported cross-reference formats

| Format | PDF versions |
|---|---|
| Traditional xref table | 1.0 – 1.4 |
| Xref streams | 1.5+ |
| Compressed object streams (ObjStm) | 1.5+ |

Objects are loaded lazily and cached in memory on first access.
