<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Color\Color;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Object\PdfGraphicsStateDictionary;
use PhpPdf\Object\PdfVersion;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function bodyText(PdfContentStreamBuilder $s, float $x, float $y, string $text): void
{
    $s->beginText()
      ->setFont('FBody', 11)
      ->setTextMatrix(Matrix::translate($x, $y))
      ->showText($text)
      ->endText();
}

function heading(PdfContentStreamBuilder $s, float $x, float $y, string $text): void
{
    $s->beginText()
      ->setFont('FBold', 18)
      ->setTextMatrix(Matrix::translate($x, $y))
      ->showText($text)
      ->endText();
}

/**
 * Draws a diagonal "CONFIDENTIAL" watermark centred on the page.
 *
 * Transparency requires PDF 1.4+. The watermark is rendered last so it sits
 * above all other content; swap the drawing order to place it behind body text.
 */
function watermark(PdfContentStreamBuilder $s, float $pageW, float $pageH): void
{
    $cx = $pageW / 2;
    $cy = $pageH / 2;

    // Rotate 45° counter-clockwise around the page centre.
    $transform = Matrix::rotate(45)->then(Matrix::translate($cx, $cy));

    $s->saveGraphicsState()
      ->setGraphicsStateParameters('GS_watermark')
      ->fillColor(Color::rgb(0.80, 0.10, 0.10))
      ->beginText()
      ->setFont('FWatermark', 72)
      ->setTextMatrix($transform)
      // Shift the text left by roughly half its width so it is centred.
      ->moveTextPosition(-185, 0)
      ->showText('CONFIDENTIAL')
      ->endText()
      ->restoreGraphicsState();
}

// ---------------------------------------------------------------------------
// Generate
// ---------------------------------------------------------------------------

function generate(): void
{
    [$pageW, $pageH] = PdfPageSize::A4;

    $document = (new PdfDocumentBuilder())
        ->version(PdfVersion::PDF_1_4)
        ->info((new PdfDocumentInfo())->title('Watermark example')->author('phppdf'))
        ->page(static function (PdfPageBuilder $page) use ($pageW, $pageH): void {
            $page->size($pageW, $pageH)
                 ->useType1Font('FBody', 'Helvetica')
                 ->useType1Font('FBold', 'Helvetica-Bold')
                 ->useType1Font('FWatermark', 'Helvetica-Bold')
                 ->useGraphicsState(
                     'GS_watermark',
                     new PdfGraphicsStateDictionary(fillAlpha: 0.15),
                 )
                 ->content(static function (PdfContentStreamBuilder $s) use ($pageW, $pageH): void {
                     $ml = 72.0;
                     $y = $pageH - 80;

                     heading($s, $ml, $y, 'Project Report — Q2 2026');
                     $y -= 30;

                     $lines = [
                         'This document contains sensitive information and is intended',
                         'solely for the named recipient. Distribution, reproduction,',
                         'or disclosure to any third party is strictly prohibited.',
                         '',
                         'All figures contained herein are preliminary estimates and',
                         'subject to revision pending the final audit sign-off.',
                         '',
                         'Please handle with appropriate care and store securely.',
                     ];

                     foreach ($lines as $line) {
                         bodyText($s, $ml, $y, $line);
                         $y -= 18;
                     }

                     watermark($s, $pageW, $pageH);
                 });
        })
        ->page(static function (PdfPageBuilder $page) use ($pageW, $pageH): void {
            $page->size($pageW, $pageH)
                 ->useType1Font('FBody', 'Helvetica')
                 ->useType1Font('FBold', 'Helvetica-Bold')
                 ->useType1Font('FWatermark', 'Helvetica-Bold')
                 ->useGraphicsState(
                     'GS_watermark',
                     new PdfGraphicsStateDictionary(fillAlpha: 0.15),
                 )
                 ->content(static function (PdfContentStreamBuilder $s) use ($pageW, $pageH): void {
                     $ml = 72.0;
                     $y = $pageH - 80;

                     heading($s, $ml, $y, 'Appendix A — Financial Summary');
                     $y -= 30;

                     $rows = [
                         'Revenue:       $4,820,000',
                         'Operating cost:  $2,310,000',
                         'Net income:    $2,510,000',
                         'Margin:              52.1 %',
                     ];

                     foreach ($rows as $row) {
                         bodyText($s, $ml, $y, $row);
                         $y -= 18;
                     }

                     watermark($s, $pageW, $pageH);
                 });
        })
        ->build();

    $output = new PdfMemoryOutput();
    (new PdfDocumentSerializer($output))->writeDocument($document);

    header('Content-Type: application/pdf');
    header('Content-Length: ' . $output->position());
    header('Content-Disposition: inline; filename="' . basename(__FILE__, '.php') . '.pdf"');
    echo $output->getContent();
}

(static function (): void {
    $autoloader = require __DIR__ . '/../../../vendor/autoload.php';

    setupEnvironment($autoloader);
    generate();
})();
