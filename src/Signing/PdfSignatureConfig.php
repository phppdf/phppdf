<?php

declare(strict_types=1);

namespace PhpPdf\Signing;

/**
 * Configuration for a PDF digital signature placeholder.
 *
 * Pass a configured instance to PdfDocumentBuilder::prepareForSigning().
 * The builder embeds the necessary AcroForm, signature field, and
 * Contents/ByteRange placeholders. After serialization, hand the raw
 * bytes to PdfDocumentSigner::sign() to compute and embed the signature.
 *
 * Example:
 *
 *   $builder->prepareForSigning(
 *       (new PdfSignatureConfig())
 *           ->reason('Approved')
 *           ->location('Amsterdam')
 *           ->contactInfo('signer@example.com')
 *   );
 */
final class PdfSignatureConfig
{
    /**
     * Number of bytes reserved in the Contents hex placeholder.
     * Must match PdfDocumentSigner::SIGNATURE_RESERVED_BYTES.
     */
    public const int RESERVED_BYTES = 8192;

    private string $fieldName = 'Signature1';
    private ?string $name = null;
    private ?string $reason = null;
    private ?string $location = null;
    private ?string $contactInfo = null;

    public function fieldName(string $name): self
    {
        $this->fieldName = $name;

        return $this;
    }

    /** The name of the person or entity signing the document (/Name in the signature dictionary). */
    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function reason(string $reason): self
    {
        $this->reason = $reason;

        return $this;
    }

    public function location(string $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function contactInfo(string $info): self
    {
        $this->contactInfo = $info;

        return $this;
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function getContactInfo(): ?string
    {
        return $this->contactInfo;
    }
}
