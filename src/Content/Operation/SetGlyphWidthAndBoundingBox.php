<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation;

use PhpPdf\Object\PdfVersion;
use PhpPdf\Serialization\PdfContentStreamSerializer;

// PDF operator 'd1': only valid inside a Type 3 font glyph description stream.
final class SetGlyphWidthAndBoundingBox implements PdfContentOperation
{
    public function __construct(
        private float $wx,
        private float $wy,
        private float $llx,
        private float $lly,
        private float $urx,
        private float $ury,
    ) {
    }

    public function minimumVersion(): PdfVersion
    {
        return PdfVersion::PDF_1_0;
    }

    public function serialize(PdfContentStreamSerializer $serializer): void
    {
        $serializer->writeLine(
            sprintf('%s %s %s %s %s %s d1', $this->wx, $this->wy, $this->llx, $this->lly, $this->urx, $this->ury),
        );
    }
}
