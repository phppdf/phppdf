<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Image\PdfImage;
use PhpPdf\Object\PdfGraphicsStateDictionary;
use PhpPdf\Object\PdfVersion;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;

// ---------------------------------------------------------------------------
// Watermark image factory
// ---------------------------------------------------------------------------

/**
 * Creates a "DRAFT" rubber-stamp PNG on a transparent background.
 *
 * Uses imagettftext() with NotoSansMono when available; falls back to the
 * largest built-in GD font otherwise.  The image is intentionally large so
 * it scales down cleanly to PDF point dimensions.
 */
function makeDraftStamp(): string
{
    $w = 420;
    $h = 180;

    $img = imagecreatetruecolor($w, $h);
    imagealphablending($img, false);
    imagesavealpha($img, true);

    // Fully transparent background
    $clear = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $clear);
    imagealphablending($img, true);

    $red = imagecolorallocate($img, 190, 20, 20);

    // Double-line rectangular border
    imagerectangle($img, 6, 6, $w -  7, $h -  7, $red);
    imagerectangle($img, 10, 10, $w - 11, $h - 11, $red);

    // Horizontal rules that bracket the text (stamp look)
    for ($dy = 0; $dy <= 1; $dy++) {
        imageline($img, 6, 38 + $dy, $w -  7, 38 + $dy, $red);
        imageline($img, 6, $h - 39 + $dy, $w -  7, $h - 39 + $dy, $red);
    }

    // "DRAFT" label
    $ttfFont = '/usr/share/fonts/truetype/noto/NotoSansMono-Regular.ttf';

    if (function_exists('imagettftext') && is_readable($ttfFont)) {
        $fontSize = 72;
        $bbox     = imagettfbbox($fontSize, 0, $ttfFont, 'DRAFT');
        $tw       = abs($bbox[2] - $bbox[0]);
        $th       = $bbox[1] - $bbox[7]; // baseline-to-top span
        $tx       = (int) round(($w - $tw) / 2);
        $ty       = (int) round(($h + $th) / 2);
        imagettftext($img, $fontSize, 0, $tx, $ty, $red, $ttfFont, 'DRAFT');
    } else {
        // Built-in GD font 5: characters are 9 × 15 px
        $text  = 'DRAFT';
        $charW = imagefontwidth(5);
        $charH = imagefontheight(5);
        $tx    = (int) round(($w - strlen($text) * $charW) / 2);
        $ty    = (int) round(($h - $charH) / 2);
        imagestring($img, 5, $tx, $ty, $text, $red);
    }

    ob_start();
    imagepng($img);
    imagedestroy($img);

    return ob_get_clean();
}

// ---------------------------------------------------------------------------
// Content helpers
// ---------------------------------------------------------------------------

function pageHeading(PdfContentStreamBuilder $s, float $x, float $y, string $text): void
{
    $s->beginText()
      ->setFont('FBold', 18)
      ->setTextMatrix(Matrix::translate($x, $y))
      ->showText($text)
      ->endText();
}

function pageBody(PdfContentStreamBuilder $s, float $x, float $y, string $text): void
{
    $s->beginText()
      ->setFont('FReg', 11)
      ->setTextMatrix(Matrix::translate($x, $y))
      ->showText($text)
      ->endText();
}

// ---------------------------------------------------------------------------
// Watermark drawing helpers
// ---------------------------------------------------------------------------

/**
 * Paints the watermark image centred on the page with no rotation.
 *
 * The image is drawn using drawImage() after setGraphicsStateParameters() has
 * reduced the effective fill alpha to the watermark opacity.  drawImage()
 * internally wraps the call in saveGraphicsState() / restoreGraphicsState(),
 * so the inherited ca value is in force when the XObject is painted.
 */
function watermarkCentered(
    PdfContentStreamBuilder $s,
    string $name,
    float $displayW,
    float $displayH,
    float $pageW,
    float $pageH,
): void {
    $x = ($pageW - $displayW) / 2.0;
    $y = ($pageH - $displayH) / 2.0;

    $s->saveGraphicsState()
      ->setGraphicsStateParameters('GS_wm')
      ->drawImage($name, x: $x, y: $y, width: $displayW, height: $displayH)
      ->restoreGraphicsState();
}

/**
 * Paints the watermark image centred on the page, rotated by $degrees
 * counter-clockwise.
 *
 * Because the rotation has to be composed with scaling and centering,
 * concatenateMatrix() + invokeXObject() are used directly instead of
 * the drawImage() convenience method.
 *
 * The transformation chain (applied in reading order via ->then()):
 *   1. scale($w, $h)          — stretch the unit square to display size
 *   2. translate(-$w/2, -$h/2)— move the image centre to the origin
 *   3. rotate($degrees)        — rotate around the origin
 *   4. translate($cx, $cy)     — shift the centre to the middle of the page
 */
function watermarkRotated(
    PdfContentStreamBuilder $s,
    string $name,
    float $displayW,
    float $displayH,
    float $pageW,
    float $pageH,
    float $degrees,
): void {
    $cx = $pageW  / 2.0;
    $cy = $pageH  / 2.0;

    $transform = Matrix::scale($displayW, $displayH)
        ->then(Matrix::translate(-$displayW / 2.0, -$displayH / 2.0))
        ->then(Matrix::rotate($degrees))
        ->then(Matrix::translate($cx, $cy));

    $s->saveGraphicsState()
      ->setGraphicsStateParameters('GS_wm')
      ->concatenateMatrix($transform)
      ->invokeXObject($name)
      ->restoreGraphicsState();
}

// ---------------------------------------------------------------------------
// Generate
// ---------------------------------------------------------------------------

function generate(): void
{
    [$pageW, $pageH] = PdfPageSize::A4;

    $stamp = PdfImage::fromData(makeDraftStamp());

    // Display the stamp at 350 pt wide, preserving the pixel aspect ratio.
    $displayW = 350.0;
    $displayH = $displayW / $stamp->getWidth() * $stamp->getHeight();

    $document = (new PdfDocumentBuilder())
        ->version(PdfVersion::PDF_1_4)          // transparency requires PDF 1.4+
        ->info((new PdfDocumentInfo())->title('Image Watermark Example')->author('phppdf'))

        // ── Page 1: centred stamp, no rotation ───────────────────────────
        ->page(function (PdfPageBuilder $page) use ($pageW, $pageH, $stamp, $displayW, $displayH): void {
            $page->size($pageW, $pageH)
                 ->useType1Font('FReg', 'Helvetica')
                 ->useType1Font('FBold', 'Helvetica-Bold')
                 ->useImage('Stamp', $stamp)
                 ->useGraphicsState('GS_wm', new PdfGraphicsStateDictionary(fillAlpha: 0.20))
                 ->content(function (PdfContentStreamBuilder $s) use ($pageW, $pageH, $displayW, $displayH): void {
                     $ml = 72.0;
                     $y  = $pageH - 80.0;

                     pageHeading($s, $ml, $y, 'Project Report - Q2 2026');
                     $y -= 30;

                    foreach (
                        [
                         'This document contains sensitive information and is intended',
                         'solely for the named recipient. Distribution, reproduction,',
                         'or disclosure to any third party is strictly prohibited.',
                         '',
                         'All figures contained herein are preliminary estimates and',
                         'subject to revision pending the final audit sign-off.',
                         '',
                         'Please handle with appropriate care and store securely.',
                         ] as $line
                    ) {
                        pageBody($s, $ml, $y, $line);
                        $y -= 18;
                    }

                     // Watermark drawn last so it sits on top of the body text.
                     watermarkCentered($s, 'Stamp', $displayW, $displayH, $pageW, $pageH);
                 });
        })

        // ── Page 2: stamp rotated 45° counter-clockwise ───────────────────
        ->page(function (PdfPageBuilder $page) use ($pageW, $pageH, $stamp, $displayW, $displayH): void {
            $page->size($pageW, $pageH)
                 ->useType1Font('FReg', 'Helvetica')
                 ->useType1Font('FBold', 'Helvetica-Bold')
                 ->useImage('Stamp', $stamp)
                 ->useGraphicsState('GS_wm', new PdfGraphicsStateDictionary(fillAlpha: 0.18))
                 ->content(function (PdfContentStreamBuilder $s) use ($pageW, $pageH, $displayW, $displayH): void {
                     $ml = 72.0;
                     $y  = $pageH - 80.0;

                     pageHeading($s, $ml, $y, 'Appendix A - Financial Summary');
                     $y -= 30;

                    foreach (
                        [
                         'Revenue:         $4,820,000',
                         'Operating cost:  $2,310,000',
                         'Net income:      $2,510,000',
                         'Margin:               52.1 %',
                         ] as $row
                    ) {
                        pageBody($s, $ml, $y, $row);
                        $y -= 18;
                    }

                     watermarkRotated($s, 'Stamp', $displayW, $displayH, $pageW, $pageH, degrees: 45.0);
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

(function (): void {
    $autoloader = require __DIR__ . '/../../../vendor/autoload.php';

    setupEnvironment($autoloader);
    generate();
})();
