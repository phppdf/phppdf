<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation;

use PhpPdf\Object\PdfVersion;
use PhpPdf\Serialization\PdfContentStreamSerializer;

final class SetRenderingIntent implements PdfContentOperation
{
    public function __construct(private string $intent)
    {
    }

    public function minimumVersion(): PdfVersion
    {
        return PdfVersion::PDF_1_1;
    }

    public function serialize(PdfContentStreamSerializer $serializer): void
    {
        $serializer->writeLine(sprintf('/%s ri', $this->intent));
    }
}
