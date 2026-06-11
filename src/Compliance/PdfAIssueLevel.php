<?php

declare(strict_types=1);

namespace PhpPdf\Compliance;

enum PdfAIssueLevel: string
{
    case Error = 'error';
    case Warning = 'warning';
}
