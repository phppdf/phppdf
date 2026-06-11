# Compliance & security

## PDF/A archival conformance

PDF/A restricts certain PDF features (transparency, encryption, external streams) and mandates others (embedded fonts, XMP metadata, color profiles).

```php
use PhpPdf\Document\PdfDocumentInfo;

(new PdfDocumentBuilder())
    ->conformTo(PdfAConformance::PdfA2b)
    ->info(
        (new PdfDocumentInfo())
            ->title('Archival Document')
            ->author('My Organisation')
    )
    // … pages, fonts, etc.
```

Supported conformance levels:

| Level | Meaning |
|---|---|
| `PdfA1b` | PDF/A-1b — basic visual reproduction |
| `PdfA1a` | PDF/A-1a — accessible; requires tagged content |
| `PdfA2b` | PDF/A-2b — adds JPEG2000, transparency |
| `PdfA2a` | PDF/A-2a — accessible PDF/A-2 |
| `PdfA2u` | PDF/A-2u — Unicode mapping required |
| `PdfA3b` | PDF/A-3b — allows embedded non-PDF files |
| `PdfA3a` | PDF/A-3a — accessible PDF/A-3 |
| `PdfA3u` | PDF/A-3u — Unicode PDF/A-3 |

XMP metadata is written automatically.  Fonts must be fully embedded (Type 1 standard fonts do not satisfy PDF/A embedding requirements; use embedded TrueType fonts instead).

To verify compliance after the fact, see [PDF/A validation](../operations/validation.md).

## Encryption

AES-128 encryption (V=4, R=4).

```php
use PhpPdf\Encryption\PdfEncryptionConfig;
use PhpPdf\Encryption\PdfPermissions;

(new PdfDocumentBuilder())
    ->encrypt(
        (new PdfEncryptionConfig())
            ->userPassword('open123')
            ->ownerPassword('admin456')
            ->permissions(
                PdfPermissions::none()
                    ->allowPrinting()
                    ->allowCopying()
            )
    )
```

`PdfPermissions` methods: `allowPrinting()`, `allowHighQualityPrinting()`, `allowCopying()`, `allowModifying()`, `allowAnnotations()`, `allowFormFilling()`, `allowAccessibility()`, `allowDocumentAssembly()`.

`PdfPermissions::all()` grants everything.  `PdfPermissions::none()` denies everything and is the base to chain from.

> Omitting `->userPassword()` creates a document that opens without a password but enforces the owner-password-restricted permissions for modifications.

## Document permissions (without password)

To restrict operations without requiring a password to open the document, set an empty user password:

```php
(new PdfEncryptionConfig())
    ->userPassword('')
    ->ownerPassword('admin456')
    ->permissions(PdfPermissions::none()->allowPrinting())
```

## Digital signatures

Signatures use PKCS#7 / CMS with SHA-256.

```php


$config = (new PdfSignatureConfig())
    ->certificate(file_get_contents('/path/to/cert.pem'))
    ->privateKey(file_get_contents('/path/to/key.pem'))
    ->passphrase('keypassword')        // optional
    ->reason('Document approval')       // appears in signature panel
    ->location('Amsterdam');

$document = (new PdfDocumentBuilder())
    ->sign($config)
    ->page(/* … */)
    ->build();

$output = new PdfMemoryOutput();
(new PdfDocumentSerializer($output))->writeDocument($document);

$signed = (new PdfDocumentSigner())->sign($output->getContent(), $config);

file_put_contents('/path/to/signed.pdf', $signed);
```

The signer reserves a byte-range placeholder in the document and fills it with the PKCS#7 signature in a second pass.
