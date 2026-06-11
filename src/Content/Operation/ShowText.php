<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation;

use PhpPdf\Object\PdfVersion;
use PhpPdf\Serialization\PdfContentStreamSerializer;

/**
 * The Tj (show text) operator.
 *
 * $pdfString is the complete PDF string operand — including delimiters — as
 * produced by PdfContentStreamBuilder::encodedString(). For Type1 fonts this
 * is a Windows-1252 literal string like (Hello); for embedded fonts it is a
 * hex string like <00410042>. No further encoding is applied here.
 */
final class ShowText implements PdfContentOperation
{
    public function __construct(private readonly string $pdfString)
    {
    }

    public function minimumVersion(): PdfVersion
    {
        return PdfVersion::PDF_1_0;
    }

    public function serialize(PdfContentStreamSerializer $serializer): void
    {
        $serializer->writeLine("{$this->pdfString} Tj");
    }
}
