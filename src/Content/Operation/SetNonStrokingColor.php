<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation;

use PhpPdf\Object\PdfVersion;
use PhpPdf\Serialization\PdfContentStreamSerializer;

final class SetNonStrokingColor implements PdfContentOperation
{
    /** @var list<float> */
    private array $components;

    public function __construct(float ...$components)
    {
        $this->components = array_values($components);
    }

    public function minimumVersion(): PdfVersion
    {
        return PdfVersion::PDF_1_2;
    }

    public function serialize(PdfContentStreamSerializer $serializer): void
    {
        $serializer->writeLine(implode(' ', $this->components) . ' sc');
    }
}
