<?php

declare(strict_types=1);

namespace PhpPdf\Object;

use PhpPdf\Serialization\PdfDocumentSerializer;

/**
 * A PDF hexadecimal string, serialized between angle brackets (e.g. <48656C6C6F>).
 *
 * The binary data supplied to the constructor is converted to uppercase hex.
 * Use for binary data that must be expressed as a PDF string — such as
 * encrypted content, font character codes, and CMap entries — where a
 * literal string would be unsafe or unreadable.
 */
final class PdfHexString implements PdfObject
{
    public function __construct(private readonly string $binary,)
    {
    }

    public function getBinary(): string
    {
        return $this->binary;
    }

    /**
     * Returns the PDF hex string, e.g. '<48656C6C6F>'.
     */
    public function toPdfString(): string
    {
        return '<' . strtoupper(bin2hex($this->binary)) . '>';
    }

    public function serialize(PdfDocumentSerializer $serializer): void
    {
        $serializer->writeHexString($this);
    }
}
