<?php

declare(strict_types=1);

namespace PhpPdf\Reader;

enum PdfFormFieldType: string
{
    case Text = 'Tx';
    case Button = 'Btn'; // checkboxes and radio buttons
    case Choice = 'Ch'; // combo boxes and list boxes
    case Signature = 'Sig';
    case Unknown = '';

    public static function fromPdfName(?string $name): self
    {
        if ($name === null) {
            return self::Unknown;
        }

        return self::tryFrom($name) ?? self::Unknown;
    }
}
