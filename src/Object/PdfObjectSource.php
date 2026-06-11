<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * A source from which PDF objects can be loaded by indirect reference.
 *
 * Implemented by sources that can supply resolved PDF objects, a trailer
 * dictionary, and a document version. Used by PdfObjectImporter to remain
 * decoupled from any concrete implementation.
 */
interface PdfObjectSource
{
    /**
     * Resolves an indirect reference and returns the underlying PDF object.
     *
     * Implementations may load the object lazily from a file or return it
     * directly from an in-memory registry.
     */
    public function getObject(PdfIndirectReference $reference): PdfObject;

    /**
     * Returns the document's trailer dictionary.
     *
     * The trailer contains entries such as /Root (catalog reference) and
     * /Info (document information reference) that are needed when importing
     * objects from an external source.
     */
    public function getTrailer(): PdfDictionary;

    /**
     * Returns the PDF version of the source document.
     *
     * Used by PdfDocumentEditor to carry forward the highest version when
     * merging pages from multiple source documents.
     */
    public function getVersion(): PdfVersion;
}
