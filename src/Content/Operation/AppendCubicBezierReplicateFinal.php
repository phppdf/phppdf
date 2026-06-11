<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation;

use PhpPdf\Object\PdfVersion;
use PhpPdf\Serialization\PdfContentStreamSerializer;

// PDF operator 'y': the second control point is implicitly the endpoint (x3, y3).
final class AppendCubicBezierReplicateFinal implements PdfContentOperation
{
    public function __construct(private float $x1, private float $y1, private float $x3, private float $y3)
    {
    }

    public function minimumVersion(): PdfVersion
    {
        return PdfVersion::PDF_1_0;
    }

    public function serialize(PdfContentStreamSerializer $serializer): void
    {
        $serializer->writeLine(
            sprintf('%s %s %s %s y', $this->x1, $this->y1, $this->x3, $this->y3),
        );
    }
}
