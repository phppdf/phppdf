<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation;

use PhpPdf\Object\PdfVersion;
use PhpPdf\Serialization\PdfContentStreamSerializer;

final class BeginMarkedContentWithProperties implements PdfContentOperation
{
    public function __construct(private string $tag, private string $properties)
    {
    }

    public function minimumVersion(): PdfVersion
    {
        return PdfVersion::PDF_1_2;
    }

    public function serialize(PdfContentStreamSerializer $serializer): void
    {
        $serializer->writeLine(sprintf('/%s /%s BDC', $this->tag, $this->properties));
    }
}
