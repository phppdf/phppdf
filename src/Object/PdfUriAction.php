<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * A PDF URI action that opens a URL in the user's browser.
 *
 * When activated, the viewer passes the URI to the operating system's default
 * handler (typically a web browser). Use with PdfLinkAnnotation to create
 * external hyperlinks.
 */
final class PdfUriAction extends PdfDictionary
{
    public function __construct(string $uri)
    {
        parent::__construct([
            'S' => new PdfName('URI'),
            'URI' => new PdfString($uri),
        ]);
    }
}
