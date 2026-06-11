<?php

declare(strict_types=1);

namespace PhpPdf\Object;

use PhpPdf\Serialization\PdfDocumentSerializer;

/**
 * Root interface for all PDF objects.
 *
 * Every value that can appear inside a PDF file — numbers, strings, names,
 * arrays, dictionaries, streams, and indirect references — implements this
 * interface. The single serialize() method dispatches to the
 * PdfDocumentSerializer, which handles the concrete PDF syntax for each type.
 */
interface PdfObject
{
    /**
     * Writes this object's PDF representation to the serializer.
     */
    public function serialize(PdfDocumentSerializer $serializer): void;
}
