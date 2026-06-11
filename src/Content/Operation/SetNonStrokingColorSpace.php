<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation;

use PhpPdf\Object\PdfVersion;
use PhpPdf\Serialization\PdfContentStreamSerializer;

final class SetNonStrokingColorSpace implements PdfContentOperation
{
    public function __construct(private string $name)
    {
    }

    public function minimumVersion(): PdfVersion
    {
        return PdfVersion::PDF_1_2;
    }

    public function serialize(PdfContentStreamSerializer $serializer): void
    {
        $serializer->writeLine(sprintf('/%s cs', $this->name));
    }
}
