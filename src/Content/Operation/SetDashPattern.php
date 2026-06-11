<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation;

use PhpPdf\Object\PdfVersion;
use PhpPdf\Serialization\PdfContentStreamSerializer;

final class SetDashPattern implements PdfContentOperation
{
    /** @param list<float> $dashArray */
    public function __construct(private array $dashArray, private float $phase)
    {
    }

    public function minimumVersion(): PdfVersion
    {
        return PdfVersion::PDF_1_0;
    }

    public function serialize(PdfContentStreamSerializer $serializer): void
    {
        $serializer->writeLine(
            sprintf('[%s] %s d', implode(' ', $this->dashArray), $this->phase),
        );
    }
}
