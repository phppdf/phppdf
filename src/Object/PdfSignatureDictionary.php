<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * A PDF digital signature field dictionary.
 *
 * Defines the cryptographic handler (Filter) and encoding format (SubFilter).
 * The defaults target PKCS#7 detached signatures as used by Adobe Acrobat.
 * The actual signature bytes are written to the Contents entry after signing.
 */
final class PdfSignatureDictionary extends PdfDictionary
{
    public function __construct(string $filter = 'Adobe.PPKLite', string $subFilter = 'adbe.pkcs7.detached')
    {
        parent::__construct([
            'Filter' => new PdfName($filter),
            'SubFilter' => new PdfName($subFilter),
            'Type' => new PdfName('Sig'),
        ]);
    }
}
