<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation;

use PhpPdf\Object\PdfVersion;
use PhpPdf\Serialization\PdfContentStreamSerializer;

interface PdfContentOperation
{
    public function minimumVersion(): PdfVersion;

    public function serialize(PdfContentStreamSerializer $serializer): void;
}
