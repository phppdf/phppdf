<?php

declare(strict_types=1);

namespace PhpPdf\Reader;

use PhpPdf\Encryption\PdfDecryptionHandler;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfString;
use PhpPdf\Object\PdfVersion;
use PhpPdf\Reader\Exception\PdfReadException;

/**
 * Entry point for reading an existing PDF file.
 *
 * Reads the file header, cross-reference table, and trailer to build a
 * PdfReadDocument that gives lazy access to the document's objects and pages.
 *
 * Supports traditional xref tables (PDF 1.0–1.4) and PDF 1.5+ xref streams,
 * including cross-references stored in compressed object streams (ObjStm).
 */
final class PdfDocumentReader
{
    /**
     * Opens a PDF file and returns a parsed document ready for reading.
     *
     * @throws \PhpPdf\Reader\Exception\PdfReadException on file I/O errors or unsupported PDF features.
     */
    public static function open(string $filePath): PdfReadDocument
    {
        $lexer = PdfLexer::openFile($filePath);

        $version = self::readVersion($lexer);
        $startXRef = $lexer->findStartXRef();

        $xrefParser = new PdfXRefTable($lexer);
        [$xref, $trailer] = $xrefParser->parse($startXRef);

        return new PdfReadDocument($lexer, $xref, $trailer, $version, null, $startXRef);
    }

    /**
     * Opens a password-protected PDF. Tries $password as the user password
     * first, then as the owner password.
     *
     * @throws \PhpPdf\Reader\Exception\PdfReadException on I/O errors, missing /Encrypt dict, or wrong password.
     */
    public static function openEncrypted(string $filePath, string $password = ''): PdfReadDocument
    {
        $lexer = PdfLexer::openFile($filePath);
        $version = self::readVersion($lexer);
        $startXRef = $lexer->findStartXRef();

        $xrefParser = new PdfXRefTable($lexer);
        [$xref, $trailer] = $xrefParser->parse($startXRef);

        // Locate and load the /Encrypt dictionary (always a direct-offset object).
        $encryptRef = $trailer->get('Encrypt');

        if (!$encryptRef instanceof PdfIndirectReference) {
            throw PdfReadException::encryptDictNotFound();
        }

        $encryptObjNum = $encryptRef->getObjectNumber();
        $encryptEntry = $xref[$encryptObjNum] ?? null;

        if ($encryptEntry === null || $encryptEntry['type'] !== 'n') {
            throw PdfReadException::encryptDictNotFound();
        }

        $lexer->seekTo($encryptEntry['offset'] ?? 0);
        $encryptParser = new PdfObjectParser($lexer);
        $encryptResult = $encryptParser->parseIndirectObject();

        if ($encryptResult === null) {
            throw PdfReadException::encryptDictNotFound();
        }

        [, , $encryptDictObj] = $encryptResult;

        if (!$encryptDictObj instanceof PdfDictionary) {
            throw PdfReadException::encryptDictNotFound();
        }

        // Validate that this is a supported encryption scheme.
        $filter = $encryptDictObj->get('Filter');
        $v = $encryptDictObj->get('V');
        $r = $encryptDictObj->get('R');

        if (!($filter instanceof PdfName && $filter->getValue() === 'Standard')) {
            throw PdfReadException::unsupportedEncryption('only /Filter /Standard is supported');
        }

        $vVal = $v instanceof PdfInteger
            ? $v->getValue()
            : 0;
        $rVal = $r instanceof PdfInteger
            ? $r->getValue()
            : 0;

        if ($vVal !== 4 || $rVal !== 4) {
            throw PdfReadException::unsupportedEncryption(
                "V={$vVal}/R={$rVal} — only V=4/R=4 (AES-128) is supported",
            );
        }

        // Extract the file ID (first element of trailer /ID array).
        $fileId = self::extractFileId($trailer);

        // Authenticate the password and derive the decryption key.
        $context = PdfDecryptionHandler::authenticate($encryptDictObj, $fileId, $password);

        if ($context === null) {
            throw PdfReadException::wrongPassword();
        }

        $context->setEncryptDictObjectNumber($encryptObjNum);

        return new PdfReadDocument($lexer, $xref, $trailer, $version, $context, $startXRef);
    }

    // -------------------------------------------------------------------------

    private static function extractFileId(PdfDictionary $trailer): string
    {
        $idEntry = $trailer->get('ID');

        if (!$idEntry instanceof PdfArray) {
            return '';
        }

        $first = $idEntry->getItems()[0] ?? null;

        return $first instanceof PdfString
            ? $first->getValue()
            : '';
    }

    private static function readVersion(PdfLexer $lexer): PdfVersion
    {
        $lexer->seekTo(0);

        // Read the first line: %PDF-x.y
        $limit = min(20, $lexer->size());
        $raw = $lexer->readBytesAt(0, $limit);

        if (!str_starts_with($raw, '%PDF-')) {
            throw PdfReadException::invalidHeader();
        }

        // Extract version string after '%PDF-'
        $after = substr($raw, 5);
        $versionStr = '';

        foreach (str_split($after) as $char) {
            if ($char === "\r" || $char === "\n" || $char === ' ') {
                break;
            }

            $versionStr .= $char;
        }

        return PdfVersion::tryFrom($versionStr) ?? PdfVersion::PDF_1_7;
    }
}
