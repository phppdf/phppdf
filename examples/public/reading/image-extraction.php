<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Image\PdfImage;
use PhpPdf\Output\PdfFileOutput;
use PhpPdf\Reader\PdfDocumentReader;
use PhpPdf\Reader\PdfImageExtractor;
use PhpPdf\Serialization\PdfDocumentSerializer;

function generate(): void
{
    if (!function_exists('imagecreatetruecolor')) {
        echo 'This example requires the GD extension (ext-gd).' . PHP_EOL;

        return;
    }

    $pdfPath = '/tmp/phppdf-images-demo.pdf';

    // -------------------------------------------------------------------------
    // Step 1: Build a PDF containing two images on one page.
    // -------------------------------------------------------------------------

    $gradientPng = makeGradientPng(); // 200 × 150 RGB PNG
    $solidJpeg = makeSolidJpeg(); // 180 × 120 RGB JPEG
    $alphaPng = makeAlphaPng(); // 100 × 100 RGBA PNG

    $document = (new PdfDocumentBuilder())
        ->info(
            (new PdfDocumentInfo())
                ->title('Image Extraction Demo')
                ->author('phppdf'),
        )
        ->page(static function (PdfPageBuilder $page) use ($gradientPng, $solidJpeg, $alphaPng): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica-Bold')
                ->useImage('Gradient', PdfImage::fromData($gradientPng))
                ->useImage('Solid', PdfImage::fromData($solidJpeg))
                ->useImage('Alpha', PdfImage::fromData($alphaPng))
                ->content(static function (PdfContentStreamBuilder $s): void {
                    $s->beginText()->setFont('F1', 14)
                      ->setTextMatrix(Matrix::translate(72, 780))
                      ->showText('Image Extraction Demo')->endText();

                    $s->drawImage('Gradient', x: 72, y: 580, width: 200, height: 150);
                    $s->drawImage('Solid', x: 310, y: 600, width: 180, height: 120);
                    $s->drawImage('Alpha', x: 72, y: 450, width: 100, height: 100);
                });
        })
        ->build();

    $output = new PdfFileOutput($pdfPath);
    (new PdfDocumentSerializer($output))->writeDocument($document);
    echo "Written: {$pdfPath}" . PHP_EOL;

    // -------------------------------------------------------------------------
    // Step 2: Open the PDF and list all image XObjects.
    // -------------------------------------------------------------------------

    $doc = PdfDocumentReader::open($pdfPath);
    $extractor = new PdfImageExtractor($doc);
    $images = $extractor->getAllImages();

    echo PHP_EOL . count($images) . ' image(s) found:' . PHP_EOL;

    foreach ($images as $i => $img) {
        echo sprintf(
            '  [%d] name=%-10s  objNum=%-3d  %dx%d  cs=%-12s  bpc=%d  jpeg=%s  smask=%s' . PHP_EOL,
            $i,
            $img->name,
            $img->objectNumber,
            $img->width,
            $img->height,
            $img->colorSpace,
            $img->bitsPerComponent,
            $img->isJpeg() ? 'yes' : 'no',
            $img->smaskData !== null ? 'yes' : 'no',
        );
    }

    // -------------------------------------------------------------------------
    // Step 3: Save each extracted image to /tmp as a JPEG or PNG file.
    // -------------------------------------------------------------------------

    echo PHP_EOL . 'Saving extracted images:' . PHP_EOL;

    foreach ($images as $i => $img) {
        $ext = $img->getFileExtension();
        $dest = "/tmp/phppdf-extracted-{$i}.{$ext}";
        file_put_contents($dest, $img->toFileBytes());
        echo "  → {$dest}  (" . strlen($img->toFileBytes()) . " bytes)" . PHP_EOL;
    }

    // -------------------------------------------------------------------------
    // Step 4: Verify re-read images parse correctly with PHP GD.
    // -------------------------------------------------------------------------

    echo PHP_EOL . 'GD verification:' . PHP_EOL;

    foreach ($images as $i => $img) {
        $ext = $img->getFileExtension();
        $dest = "/tmp/phppdf-extracted-{$i}.{$ext}";
        $gd = @imagecreatefromstring(file_get_contents($dest));

        if ($gd !== false) {
            echo "  [✓] {$dest}: " . imagesx($gd) . 'x' . imagesy($gd) . PHP_EOL;
            // imagedestroy() is a no-op since PHP 8.0 (deprecated in 8.5).
        } else {
            echo "  [✗] {$dest}: GD could not parse this image" . PHP_EOL;
        }
    }
}

// -------------------------------------------------------------------------
// Synthetic image factories
// -------------------------------------------------------------------------

function makeGradientPng(): string
{
    $w = 200;
    $h = 150;
    $gd = imagecreatetruecolor($w, $h);

    for ($x = 0; $x < $w; $x++) {
        $r = (int) ($x / $w * 255);

        for ($y = 0; $y < $h; $y++) {
            $b = (int) ($y / $h * 255);
            $col = imagecolorallocate($gd, $r, 80, $b);
            imagesetpixel($gd, $x, $y, $col);
        }
    }

    ob_start();
    imagepng($gd);
    imagedestroy($gd);

    return ob_get_clean();
}

function makeSolidJpeg(): string
{
    $gd = imagecreatetruecolor(180, 120);
    $orange = imagecolorallocate($gd, 220, 110, 30);
    imagefill($gd, 0, 0, $orange);
    imagestring($gd, 5, 30, 50, 'JPEG image', imagecolorallocate($gd, 255, 255, 255));

    ob_start();
    imagejpeg($gd, null, 90);
    imagedestroy($gd);

    return ob_get_clean();
}

function makeAlphaPng(): string
{
    $gd = imagecreatetruecolor(100, 100);
    imagealphablending($gd, false);
    imagesavealpha($gd, true);

    $transparent = imagecolorallocatealpha($gd, 0, 0, 0, 127);
    imagefill($gd, 0, 0, $transparent);

    $blue = imagecolorallocatealpha($gd, 50, 100, 200, 0);
    imagefilledellipse($gd, 50, 50, 80, 80, $blue);

    ob_start();
    imagepng($gd);
    imagedestroy($gd);

    return ob_get_clean();
}

(static function (): void {
    $autoloader = require __DIR__ . '/../../../vendor/autoload.php';

    setupEnvironment($autoloader);
    generate();
})();
