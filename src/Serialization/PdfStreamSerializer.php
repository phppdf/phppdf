<?php

declare(strict_types=1);

namespace PhpPdf\Serialization;

use PhpPdf\Object\PdfContentStreamData;
use PhpPdf\Object\PdfRawStreamData;
use PhpPdf\Output\PdfMemoryOutput;

final class PdfStreamSerializer
{
    public function __construct()
    {
    }

    public function serializeContentStream(PdfContentStreamData $data): string
    {
        $buffer = new PdfMemoryOutput();

        $contentSerializer = new PdfContentStreamSerializer($buffer);

        $operations = $data->getContent()->getOperations();

        foreach ($operations as $op) {
            $op->serialize($contentSerializer);
        }

        return $buffer->getContent();
    }

    public function serializeRawStream(PdfRawStreamData $data): string
    {
        return $data->getData();
    }
}
