<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation;

use PhpPdf\Object\PdfVersion;
use PhpPdf\Serialization\PdfContentStreamSerializer;

final class AppendCubicBezier implements PdfContentOperation
{
    public function __construct(
        private float $x1,
        private float $y1,
        private float $x2,
        private float $y2,
        private float $x3,
        private float $y3,
    ) {
    }

    public function minimumVersion(): PdfVersion
    {
        return PdfVersion::PDF_1_0;
    }

    public function serialize(PdfContentStreamSerializer $serializer): void
    {
        $serializer->writeLine(
            sprintf('%s %s %s %s %s %s c', $this->x1, $this->y1, $this->x2, $this->y2, $this->x3, $this->y3),
        );
    }
}
