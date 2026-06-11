<?php

declare(strict_types=1);

namespace PhpPdf\Document;

/**
 * The PDF Trapped entry, indicating whether the document has been modified
 * to include trapping information.
 *
 * Trapping is the process of adding small overlaps between adjacent areas of
 * different colours to compensate for slight misregistration on press. This
 * value is written as a PDF name in the Info dictionary.
 */
enum PdfTrapped: string
{
    /** The document has been fully trapped. */
    case Trapped = 'True';

    /** The document has not been trapped. */
    case NotTrapped = 'False';

    /** Either it is unknown whether the document has been trapped, or it has been partially trapped. */
    case Unknown = 'Unknown';
}
