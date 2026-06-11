<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation;

use PhpPdf\Content\Matrix;
use PhpPdf\Object\PdfVersion;
use PhpPdf\Serialization\PdfContentStreamSerializer;

final class SetTextMatrix implements PdfContentOperation
{
    public function __construct(private readonly Matrix $matrix)
    {
    }

    public function minimumVersion(): PdfVersion
    {
        return PdfVersion::PDF_1_0;
    }

    public function serialize(PdfContentStreamSerializer $serializer): void
    {
        $serializer->writeLine($this->matrix->toPdfString() . ' Tm');
    }
}
