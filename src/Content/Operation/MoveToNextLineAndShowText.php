<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation;

use PhpPdf\Object\PdfVersion;
use PhpPdf\Serialization\PdfContentStreamSerializer;

final class MoveToNextLineAndShowText implements PdfContentOperation
{
    /** $pdfString is the pre-encoded PDF string operand (with delimiters). */
    public function __construct(private readonly string $pdfString)
    {
    }

    public function minimumVersion(): PdfVersion
    {
        return PdfVersion::PDF_1_0;
    }

    public function serialize(PdfContentStreamSerializer $serializer): void
    {
        $serializer->writeLine("{$this->pdfString} '");
    }
}
