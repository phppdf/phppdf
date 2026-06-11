<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation;

use PhpPdf\Object\PdfVersion;
use PhpPdf\Serialization\PdfContentStreamSerializer;

final class ShowTextWithPositioning implements PdfContentOperation
{
    /** @var list<string|float> */
    private readonly array $elements;

    /**
     * $elements is a mix of:
     *   - string: a pre-encoded PDF string operand (with delimiters), e.g. (Hi) or <0041>
     *   - float: a horizontal kerning adjustment in thousandths of a text unit
     *
     * @param list<string|float> $elements
     */
    public function __construct(array $elements)
    {
        $this->elements = $elements;
    }

    public function minimumVersion(): PdfVersion
    {
        return PdfVersion::PDF_1_0;
    }

    public function serialize(PdfContentStreamSerializer $serializer): void
    {
        $parts = [];

        foreach ($this->elements as $element) {
            $parts[] = is_string($element)
                ? $element
                : (string) $element;
        }

        $serializer->writeLine('[' . implode(' ', $parts) . '] TJ');
    }
}
