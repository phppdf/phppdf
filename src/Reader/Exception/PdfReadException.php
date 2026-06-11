<?php

declare(strict_types=1);

namespace PhpPdf\Reader\Exception;

use RuntimeException;

final class PdfReadException extends RuntimeException
{
    public static function fileNotFound(string $path): self
    {
        return new self("PDF file not found: {$path}");
    }

    public static function cannotOpenFile(string $path): self
    {
        return new self("Cannot open PDF file: {$path}");
    }

    public static function invalidHeader(): self
    {
        return new self('Not a valid PDF file: missing %PDF header');
    }

    public static function invalidXRef(string $reason): self
    {
        return new self("Invalid cross-reference table: {$reason}");
    }

    public static function unexpectedToken(string $expected, string $got): self
    {
        return new self("Unexpected token: expected '{$expected}', got '{$got}'");
    }

    public static function unexpectedEndOfFile(): self
    {
        return new self('Unexpected end of file while parsing PDF');
    }

    public static function objectNotFound(int $objectNumber): self
    {
        return new self("PDF object {$objectNumber} not found in cross-reference table");
    }

    public static function streamDecodeFailed(string $filter): self
    {
        return new self("Failed to decode PDF stream with filter: {$filter}");
    }

    public static function xrefStreamNotSupported(): self
    {
        return new self(
            'Cross-reference streams (PDF 1.5+ xref) are not yet supported. '
            . 'The file uses a compressed xref stream instead of a traditional xref table.',
        );
    }

    public static function pageIndexOutOfBounds(int $index, int $count): self
    {
        return new self("Page index {$index} is out of bounds (document has {$count} page(s))");
    }

    public static function wrongPassword(): self
    {
        return new self('The supplied password is incorrect for this PDF document');
    }

    public static function encryptDictNotFound(): self
    {
        return new self('PDF encryption dictionary not found or could not be loaded');
    }

    public static function unsupportedEncryption(string $reason): self
    {
        return new self("Unsupported PDF encryption: {$reason}");
    }
}
