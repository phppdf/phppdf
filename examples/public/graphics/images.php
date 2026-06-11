<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Color\Color;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Image\PdfImage;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;

/** Creates a 300×200 RGB gradient PNG in memory. */
function makeGradientPng(): string
{
    $gd = imagecreatetruecolor(300, 200);

    for ($x = 0; $x < 300; $x++) {
        $r = (int) ($x / 300 * 220);
        $g = 80;
        $b = 220 - $r;

        $col = imagecolorallocate($gd, $r, $g, $b);
        imageline($gd, $x, 0, $x, 200, $col);
    }

    $white = imagecolorallocate($gd, 255, 255, 255);
    imagestring($gd, 5, 80, 90, 'PNG image (RGB)', $white);

    ob_start();
    imagepng($gd);
    imagedestroy($gd);

    return ob_get_clean();
}

/** Creates a 200×200 PNG with a semi-transparent red circle. */
function makePng(): string
{
    $gd = imagecreatetruecolor(200, 200);
    imagealphablending($gd, false);
    imagesavealpha($gd, true);

    $transparent = imagecolorallocatealpha($gd, 255, 255, 255, 127); // fully transparent
    imagefill($gd, 0, 0, $transparent);

    // Fully opaque red ellipse.
    $red = imagecolorallocatealpha($gd, 220, 50, 50, 0);
    imagefilledellipse($gd, 100, 100, 180, 180, $red);

    // Semi-transparent blue rectangle overlay.
    $blue = imagecolorallocatealpha($gd, 50, 80, 200, 50);
    imagefilledrectangle($gd, 30, 60, 170, 140, $blue);

    ob_start();
    imagepng($gd);
    imagedestroy($gd);

    return ob_get_clean();
}

function generate(): void
{
    $gradient = PdfImage::fromData(makeGradientPng());
    $png = PdfImage::fromData(makePng());

    $document = (new PdfDocumentBuilder())
        ->info(
            (new PdfDocumentInfo())
                ->title('Image Embedding Example')
                ->author('phppdf'),
        )
        ->page(static function (PdfPageBuilder $page) use ($gradient, $png): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica')
                ->useImage('Gradient', $gradient)
                ->useImage('Png1', $png)
                ->content(static function (PdfContentStreamBuilder $stream): void {
                    $stream
                        ->beginText()
                        ->setFont('F1', 14)
                        ->setTextMatrix(Matrix::translate(72, 790))
                        ->showText('Image embedding — PNG opaque and PNG with transparency')
                        ->endText()

                        // Opaque PNG: position (72, 560), 300×200 pt
                        ->drawImage('Gradient', x: 72, y: 560, width: 300, height: 200)

                        // Coloured background swatch so transparency is clearly visible.
                        // Without it everything just blends into the white page.
                        ->fillColor(Color::rgb(0.2, 0.6, 0.9))
                        ->rectangle(72, 340, 200, 200)
                        ->fill()

                        // PNG with alpha composited over the blue swatch.
                        ->drawImage('Png1', x: 72, y: 340, width: 200, height: 200)

                        ->beginText()
                        ->setFont('F1', 11)
                        ->setTextMatrix(Matrix::translate(72, 540))
                        ->showText('Opaque PNG (DeviceRGB, raw pixels)')
                        ->setTextMatrix(Matrix::translate(72, 320))
                        ->showText('Transparent PNG over blue background (/SMask alpha channel)')
                        ->endText();
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
