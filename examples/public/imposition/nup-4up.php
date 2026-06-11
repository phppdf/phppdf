<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Color\Color;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Imposition\NUpConfig;
use PhpPdf\Imposition\NUpImposer;
use PhpPdf\Output\PdfFileOutput;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Reader\PdfDocumentReader;
use PhpPdf\Serialization\PdfDocumentSerializer;

function generate(): void
{
    $tmp = tempnam(sys_get_temp_dir(), 'phppdf_nup_');
    buildSourceDocument($tmp);

    $srcDoc = PdfDocumentReader::open($tmp);

    // 4-up: A4 portrait (595 × 842), 2 columns × 2 rows → 2 output sheets.
    $config = NUpConfig::fourUp(595, 842);
    $result = (new NUpImposer($srcDoc, $config))->impose(
        (new PdfDocumentInfo())->title('4-up Imposition')->author('phppdf'),
    );

    $output = new PdfMemoryOutput();
    (new PdfDocumentSerializer($output))->writeDocument($result);

    @unlink($tmp);

    header('Content-Type: application/pdf');
    header('Content-Length: ' . $output->position());
    header('Content-Disposition: inline; filename="' . basename(__FILE__, '.php') . '.pdf"');
    echo $output->getContent();
}

// -------------------------------------------------------------------------

function buildSourceDocument(string $path): void
{
    $colors = [
        [0.85, 0.92, 1.00], [0.92, 1.00, 0.85],
        [1.00, 0.95, 0.80], [1.00, 0.85, 0.85],
        [0.90, 0.85, 1.00], [0.80, 1.00, 0.98],
        [1.00, 0.90, 0.75], [0.88, 0.88, 0.88],
    ];

    $builder = (new PdfDocumentBuilder())
        ->info((new PdfDocumentInfo())->title('N-up Source')->author('phppdf'));

    foreach ($colors as $i => [$r, $g, $b]) {
        $n = $i + 1;
        $builder->page(static function (PdfPageBuilder $page) use ($r, $g, $b, $n): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica-Bold')
                ->useType1Font('F2', 'Helvetica')
                ->content(static function (PdfContentStreamBuilder $s) use ($r, $g, $b, $n): void {
                    $s->fillColor(Color::rgb($r, $g, $b))
                      ->rectangle(0, 0, 595, 842)->fill();

                    $s->saveGraphicsState()
                      ->strokeColor(Color::rgb(0.55, 0.55, 0.55))
                      ->setLineWidth(1.5)
                      ->rectangle(14, 14, 567, 814)->stroke()
                      ->restoreGraphicsState();

                    $s->fillColor(Color::rgb(0.2, 0.2, 0.2))
                      ->beginText()
                      ->setFont('F1', 140)
                      ->setTextMatrix(Matrix::translate(175, 360))
                      ->showText((string) $n)
                      ->endText();

                    $s->beginText()
                      ->setFont('F2', 13)
                      ->setTextMatrix(Matrix::translate(170, 80))
                      ->showText("Page {$n} of 8 — N-up demo")
                      ->endText();
                });
        });
    }

    (new PdfDocumentSerializer(new PdfFileOutput($path)))->writeDocument($builder->build());
}

(static function (): void {
    $autoloader = require __DIR__ . '/../../../vendor/autoload.php';

    setupEnvironment($autoloader);
    generate();
})();
