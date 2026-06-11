<?php

declare(strict_types=1);

namespace PhpPdf\Document;

use PhpPdf\Encryption\PdfEncryptionContext;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Object\PdfVersion;

final class PdfDocument
{
    public function __construct(
        private readonly PdfObjectRegistry $objects,
        private readonly PdfVersion $version,
        private readonly PdfIndirectReference $catalog,
        private readonly ?PdfIndirectReference $info,
        private readonly ?string $documentId = null,
        private readonly ?PdfIndirectReference $encryptDict = null,
        private readonly ?PdfEncryptionContext $encryptionContext = null,
        private readonly bool $compressionEnabled = false,
    ) {
    }

    public function getObjects(): PdfObjectRegistry
    {
        return $this->objects;
    }

    public function getCatalog(): PdfIndirectReference
    {
        return $this->catalog;
    }

    public function getInfo(): ?PdfIndirectReference
    {
        return $this->info;
    }

    public function getVersion(): PdfVersion
    {
        return $this->version;
    }

    /** 16 random bytes used as the /ID array value and encryption salt. */
    public function getDocumentId(): ?string
    {
        return $this->documentId;
    }

    /** Reference to the encryption dictionary indirect object. */
    public function getEncryptDict(): ?PdfIndirectReference
    {
        return $this->encryptDict;
    }

    /** Active encryption context, or null when the document is not encrypted. */
    public function getEncryptionContext(): ?PdfEncryptionContext
    {
        return $this->encryptionContext;
    }

    /** Whether stream bodies should be compressed with FlateDecode during serialization. */
    public function isCompressionEnabled(): bool
    {
        return $this->compressionEnabled;
    }
}
