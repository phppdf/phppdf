<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation;

use PhpPdf\Object\PdfVersion;
use PhpPdf\Serialization\PdfContentStreamSerializer;

// PDF operator 'TD': moves text position and sets text leading to -ty.
final class MoveTextPositionAndSetLeading implements PdfContentOperation
{
    public function __construct(private float $tx, private float $ty)
    {
    }

    public function minimumVersion(): PdfVersion
    {
        return PdfVersion::PDF_1_0;
    }

    public function serialize(PdfContentStreamSerializer $serializer): void
    {
        $serializer->writeLine(sprintf('%s %s TD', $this->tx, $this->ty));
    }
}
