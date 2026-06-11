<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation;

use PhpPdf\Object\PdfVersion;
use PhpPdf\Serialization\PdfContentStreamSerializer;

// PDF operator 'd0': only valid inside a Type 3 font glyph description stream.
final class SetGlyphWidth implements PdfContentOperation
{
    public function __construct(private float $wx, private float $wy)
    {
    }

    public function minimumVersion(): PdfVersion
    {
        return PdfVersion::PDF_1_0;
    }

    public function serialize(PdfContentStreamSerializer $serializer): void
    {
        $serializer->writeLine(sprintf('%s %s d0', $this->wx, $this->wy));
    }
}
