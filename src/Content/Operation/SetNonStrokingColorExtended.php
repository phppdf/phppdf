<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation;

use PhpPdf\Object\PdfVersion;
use PhpPdf\Serialization\PdfContentStreamSerializer;

// PDF operator 'scn': used for Pattern, Separation, DeviceN, and ICCBased color spaces.
final class SetNonStrokingColorExtended implements PdfContentOperation
{
    /** @param list<float> $components */
    public function __construct(private array $components, private ?string $patternName = null)
    {
    }

    public function minimumVersion(): PdfVersion
    {
        return PdfVersion::PDF_1_2;
    }

    public function serialize(PdfContentStreamSerializer $serializer): void
    {
        $parts = array_map(static fn (float $c) => (string) $c, $this->components);

        if ($this->patternName !== null) {
            $parts[] = '/' . $this->patternName;
        }

        $serializer->writeLine(implode(' ', $parts) . ' scn');
    }
}
