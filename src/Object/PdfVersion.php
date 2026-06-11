<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * PDF specification version as declared in the file header (e.g. "%PDF-1.7").
 *
 * The backing string value is the version number used verbatim in the header
 * and in the /Version entry of the document catalog. When combining objects
 * from multiple sources the highest version must be used in the output.
 */
enum PdfVersion: string
{
    /** PDF 1.0 - first public release (Acrobat 1.0, 1993). */
    case PDF_1_0 = '1.0';

    /** PDF 1.1 - added external links, device-independent colour (Acrobat 2.0, 1994). */
    case PDF_1_1 = '1.1';

    /** PDF 1.2 - introduced interactive forms (AcroForms) and halftones (Acrobat 3.0, 1996). */
    case PDF_1_2 = '1.2';

    /** PDF 1.3 - added digital signatures and JavaScript actions (Acrobat 4.0, 1999). */
    case PDF_1_3 = '1.3';

    /** PDF 1.4 - introduced transparency model and 40/128-bit RC4 encryption (Acrobat 5.0, 2001). */
    case PDF_1_4 = '1.4';

    /** PDF 1.5 - added object streams, cross-reference streams, and JPEG2000 (Acrobat 6.0, 2003). */
    case PDF_1_5 = '1.5';

    /** PDF 1.6 - introduced AES encryption and 3D content via U3D (Acrobat 7.0, 2005). */
    case PDF_1_6 = '1.6';

    /** PDF 1.7 - published as ISO 32000-1:2008; added portfolio collections and XFA forms. */
    case PDF_1_7 = '1.7';

    /** PDF 2.0 - published as ISO 32000-2:2017; deprecated XFA, improved encryption and digital signatures. */
    case PDF_2_0 = '2.0';
}
