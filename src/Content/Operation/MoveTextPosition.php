<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation;

use PhpPdf\Object\PdfVersion;
use PhpPdf\Serialization\PdfContentStreamSerializer;

final class MoveTextPosition implements PdfContentOperation
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
        $serializer->writeLine(
            sprintf('%s %s Td', $this->tx, $this->ty),
        );
    }
}
