<?php

declare(strict_types=1);

namespace PhpPdf\Object;

use PhpPdf\Serialization\PdfDocumentSerializer;
use TypeError;

/**
 * An ordered sequence of PDF content stream operations.
 *
 * Represents the content of a page or form XObject as a list of
 * PdfContentOperation instances. When serialized, the operations are
 * rendered in order by PdfContentStreamSerializer to produce the PDF
 * operator syntax. Build instances using PdfContentStreamBuilder rather
 * than constructing this class directly.
 */
final class PdfContentStream implements PdfObject
{
    /** @var list<\PhpPdf\Content\Operation\PdfContentOperation> */
    private array $operations;

    /** @param list<\PhpPdf\Content\Operation\PdfContentOperation> $operations */
    public function __construct(array $operations)
    {
        $this->operations = $operations;
    }

    /** @return list<\PhpPdf\Content\Operation\PdfContentOperation> */
    public function getOperations(): array
    {
        return $this->operations;
    }

    public function serialize(PdfDocumentSerializer $serializer): void
    {
        throw new TypeError(
            'PdfContentStream cannot be serialized directly; wrap it in PdfContentStreamData and a PdfStream.',
        );
    }
}
