<?php

declare(strict_types=1);

namespace PhpPdf\Object;

use DateTimeInterface;
use PhpPdf\Serialization\PdfDocumentSerializer;

/**
 * A PDF date object wrapping a PHP DateTimeInterface.
 *
 * PDF dates follow the format D:YYYYMMDDHHmmSSOHH'mm where O is the UTC
 * offset sign. Used in the document information dictionary (CreationDate,
 * ModDate) and in digital signatures.
 */
final class PdfDate implements PdfObject
{
    public function __construct(private readonly DateTimeInterface $date)
    {
    }

    public function getValue(): DateTimeInterface
    {
        return $this->date;
    }

    public function serialize(PdfDocumentSerializer $serializer): void
    {
        $serializer->writeDate($this);
    }
}
