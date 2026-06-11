<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation;

use PhpPdf\Object\PdfVersion;
use PhpPdf\Serialization\PdfContentStreamSerializer;

final class SetStrokingCmykColor implements PdfContentOperation
{
    public function __construct(private float $c, private float $m, private float $y, private float $k)
    {
    }

    public function minimumVersion(): PdfVersion
    {
        return PdfVersion::PDF_1_0;
    }

    public function serialize(PdfContentStreamSerializer $serializer): void
    {
        $serializer->writeLine(sprintf('%s %s %s %s K', $this->c, $this->m, $this->y, $this->k));
    }
}
