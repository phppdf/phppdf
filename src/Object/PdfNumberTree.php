<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * A PDF number tree node that maps integer keys to PDF objects.
 *
 * Number trees associate integer keys with objects and are used for page
 * labels (PageLabels) and the structure parent tree (ParentTree). This
 * implementation creates a single leaf node.
 */
final class PdfNumberTree extends PdfDictionary
{
    /** @param array<int, \PhpPdf\Object\PdfObject> $numbers */
    public function __construct(array $numbers)
    {
        $entries = [];

        foreach ($numbers as $number => $object) {
            $entries[] = new PdfInteger($number);
            $entries[] = $object;
        }

        parent::__construct([
            'Nums' => new PdfArray($entries),
        ]);
    }
}
