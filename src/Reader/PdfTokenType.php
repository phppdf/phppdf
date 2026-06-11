<?php

declare(strict_types=1);

namespace PhpPdf\Reader;

enum PdfTokenType
{
    case Integer;
    case Real;
    case String;
    case Name;
    case ArrayStart;
    case ArrayEnd;
    case DictStart;
    case DictEnd;
    case Keyword;
    case Eof;
}
