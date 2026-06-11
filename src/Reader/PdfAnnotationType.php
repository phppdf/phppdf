<?php

declare(strict_types=1);

namespace PhpPdf\Reader;

enum PdfAnnotationType: string
{
    case Text = 'Text'; // sticky note
    case Link = 'Link'; // clickable link (URI or GoTo)
    case Highlight = 'Highlight'; // yellow band over text
    case Underline = 'Underline'; // line under text
    case StrikeOut = 'StrikeOut'; // line through text
    case Squiggly = 'Squiggly'; // wavy underline
    case Square = 'Square'; // rectangular border
    case Circle = 'Circle'; // oval border
    case Unknown = '';

    public static function fromPdfName(?string $name): self
    {
        if ($name === null) {
            return self::Unknown;
        }

        return self::tryFrom($name) ?? self::Unknown;
    }
}
