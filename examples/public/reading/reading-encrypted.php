<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Encryption\PdfEncryptionConfig;
use PhpPdf\Encryption\PdfPermissions;
use PhpPdf\Output\PdfFileOutput;
use PhpPdf\Reader\Exception\PdfReadException;
use PhpPdf\Reader\PdfDocumentReader;
use PhpPdf\Reader\PdfTextExtractor;
use PhpPdf\Serialization\PdfDocumentSerializer;

function generate(): void
{
    $path = '/tmp/phppdf-encrypted-demo.pdf';
    $userPassword = 'open-sesame';
    $ownerPassword = 'admin-secret';

    // -------------------------------------------------------------------------
    // Step 1: Build and encrypt a two-page PDF
    // -------------------------------------------------------------------------

    $document = (new PdfDocumentBuilder())
        ->info(
            (new PdfDocumentInfo())
                ->title('Encrypted Reading Demo')
                ->author('phppdf'),
        )
        ->encrypt(
            (new PdfEncryptionConfig())
                ->userPassword($userPassword)
                ->ownerPassword($ownerPassword)
                ->permissions(
                    PdfPermissions::none()
                        ->allowPrinting()
                        ->allowCopying(),
                ),
        )
        ->page(static function (PdfPageBuilder $page): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica')
                ->useType1Font('F2', 'Helvetica-Bold')
                ->content(static function (PdfContentStreamBuilder $s): void {
                    $s->beginText()
                        ->setFont('F2', 20)
                        ->setTextMatrix(Matrix::translate(72, 750))
                        ->showText('Confidential Report')
                        ->setFont('F1', 12)
                        ->setTextMatrix(Matrix::translate(72, 710))
                        ->showText('This document is protected with AES-128 encryption.')
                        ->setTextMatrix(Matrix::translate(72, 690))
                        ->showText('User password: open-sesame')
                        ->setTextMatrix(Matrix::translate(72, 670))
                        ->showText('Owner password: admin-secret')
                        ->endText();
                });
        })
        ->page(static function (PdfPageBuilder $page): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica')
                ->content(static function (PdfContentStreamBuilder $s): void {
                    $s->beginText()
                        ->setFont('F1', 14)
                        ->setTextMatrix(Matrix::translate(72, 750))
                        ->showText('Page 2 — also encrypted')
                        ->setFont('F1', 12)
                        ->setTextMatrix(Matrix::translate(72, 720))
                        ->showText('All strings and streams are AES-128-CBC encrypted at the object level.')
                        ->endText();
                });
        })
        ->build();

    $output = new PdfFileOutput($path);
    (new PdfDocumentSerializer($output))->writeDocument($document);
    echo "Written : {$path}" . PHP_EOL;

    // -------------------------------------------------------------------------
    // Step 2: Open with the correct user password and extract text
    // -------------------------------------------------------------------------

    $doc = PdfDocumentReader::openEncrypted($path, $userPassword);
    echo "Version : {$doc->getVersion()->value}" . PHP_EOL;
    echo "Pages   : {$doc->getPageCount()}" . PHP_EOL;

    $extractor = new PdfTextExtractor($doc);

    for ($i = 0; $i < $doc->getPageCount(); $i++) {
        echo PHP_EOL . "--- Page " . ($i + 1) . " ---" . PHP_EOL;
        echo $extractor->getTextForPage($i) . PHP_EOL;
    }

    // -------------------------------------------------------------------------
    // Step 3: Open with the owner password (should also work)
    // -------------------------------------------------------------------------

    echo PHP_EOL . "--- Opening with owner password ---" . PHP_EOL;
    $docOwner = PdfDocumentReader::openEncrypted($path, $ownerPassword);
    echo "Pages via owner password: {$docOwner->getPageCount()}" . PHP_EOL;

    // -------------------------------------------------------------------------
    // Step 4: Demonstrate that a wrong password throws a clear exception
    // -------------------------------------------------------------------------

    echo PHP_EOL . "--- Opening with wrong password ---" . PHP_EOL;

    try {
        PdfDocumentReader::openEncrypted($path, 'wrong-password');
        echo 'ERROR: should have thrown' . PHP_EOL;
    } catch (PdfReadException $e) {
        echo 'Caught expected exception: ' . $e->getMessage() . PHP_EOL;
    }
}

(static function (): void {
    $autoloader = require __DIR__ . '/../../../vendor/autoload.php';

    setupEnvironment($autoloader);
    generate();
})();
