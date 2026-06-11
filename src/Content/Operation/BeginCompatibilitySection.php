<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation;

use PhpPdf\Object\PdfVersion;
use PhpPdf\Serialization\PdfContentStreamSerializer;

final class BeginCompatibilitySection implements PdfContentOperation
{
    public function minimumVersion(): PdfVersion
    {
        return PdfVersion::PDF_1_1;
    }

    public function serialize(PdfContentStreamSerializer $serializer): void
    {
        $serializer->writeLine('BX');
    }
}
