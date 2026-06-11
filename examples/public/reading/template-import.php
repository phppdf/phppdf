<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Color\Color;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Output\PdfFileOutput;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Reader\PdfDocumentReader;
use PhpPdf\Serialization\PdfDocumentSerializer;

/**
 * Step 1: generate a "letterhead" template PDF with a background and header line.
 */
function buildTemplate(string $path): void
{
    $document = (new PdfDocumentBuilder())
        ->page(static function (PdfPageBuilder $page): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica-Bold')
                ->useType1Font('F2', 'Helvetica')
                ->content(static function (PdfContentStreamBuilder $s): void {
                    // Light grey background band across the top
                    $s->fillColor(Color::gray(0.88))
                      ->rectangle(0, 782, 595, 60)
                      ->fill();

                    // Company name in the header band
                    $s->fillColor(Color::gray(0.2))
                      ->beginText()
                      ->setFont('F1', 18)
                      ->setTextMatrix(Matrix::translate(36, 800))
                      ->showText('ACME Corporation')
                      ->endText();

                    // Thin rule below the band
                    $s->strokeColor(Color::gray(0.5))
                      ->setLineWidth(0.5)
                      ->moveTo(0, 782)
                      ->lineTo(595, 782)
                      ->stroke();

                    // Footer line
                    $s->strokeColor(Color::gray(0.7))
                      ->moveTo(36, 36)
                      ->lineTo(559, 36)
                      ->stroke()
                      ->fillColor(Color::gray(0.5))
                      ->beginText()
                      ->setFont('F2', 8)
                      ->setTextMatrix(Matrix::translate(36, 24))
                      ->showText('ACME Corporation  |  123 Business Rd  |  contact@acme.example')
                      ->endText();
                });
        })
        ->build();

    $output = new PdfFileOutput($path);
    (new PdfDocumentSerializer($output))->writeDocument($document);
}

/**
 * Step 2: import the template page and add custom content on top.
 */
function buildFromTemplate(string $templatePath): void
{
    // Open the template and grab its first page.
    $templateDoc = PdfDocumentReader::open($templatePath);
    $templatePage = $templateDoc->getPage(0);

    $document = (new PdfDocumentBuilder())
        // --- Page 1: invoice with template background ---
        ->page(static function (PdfPageBuilder $page) use ($templatePage): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica-Bold')
                ->useType1Font('F2', 'Helvetica')
                ->useImportedPage('TPL', $templatePage)
                ->content(static function (PdfContentStreamBuilder $s): void {
                    // Place the letterhead at full size, behind everything else.
                    $s->drawImportedPage('TPL');

                    // Invoice title
                    $s->beginText()
                      ->setFont('F1', 16)
                      ->setTextMatrix(Matrix::translate(36, 740))
                      ->showText('INVOICE #2025-042')
                      ->endText();

                    // Date and client
                    $s->beginText()
                      ->setFont('F2', 11)
                      ->setTextMatrix(Matrix::translate(36, 715))
                      ->showText('Date: 21 May 2026')
                      ->setTextMatrix(Matrix::translate(36, 698))
                      ->showText('Bill To: Jane Smith, 456 Client Ave')
                      ->endText();

                    // Simple line items
                    $items = [
                        ['Widget A', 2, 49.99],
                        ['Widget B', 5, 12.50],
                        ['Consulting', 3, 150.00],
                    ];

                    $y = 660.0;
                    $s->beginText()
                      ->setFont('F1', 10)
                      ->setTextMatrix(Matrix::translate(36, $y))
                      ->showText('Item')
                      ->setTextMatrix(Matrix::translate(300, $y))
                      ->showText('Qty')
                      ->setTextMatrix(Matrix::translate(380, $y))
                      ->showText('Unit Price')
                      ->setTextMatrix(Matrix::translate(480, $y))
                      ->showText('Total')
                      ->endText();

                    $total = 0.0;

                    foreach ($items as [$desc, $qty, $price]) {
                        $y -= 20;
                        $lineTotal = $qty * $price;
                        $total += $lineTotal;

                        $s->beginText()
                          ->setFont('F2', 10)
                          ->setTextMatrix(Matrix::translate(36, $y))
                          ->showText($desc)
                          ->setTextMatrix(Matrix::translate(300, $y))
                          ->showText((string) $qty)
                          ->setTextMatrix(Matrix::translate(380, $y))
                          ->showText(sprintf('$%.2f', $price))
                          ->setTextMatrix(Matrix::translate(480, $y))
                          ->showText(sprintf('$%.2f', $lineTotal))
                          ->endText();
                    }

                    // Total line
                    $y -= 25;
                    $s->beginText()
                      ->setFont('F1', 11)
                      ->setTextMatrix(Matrix::translate(380, $y))
                      ->showText(sprintf('TOTAL: $%.2f', $total))
                      ->endText();
                });
        })
        // --- Page 2: same template, different content (thumbnail version) ---
        ->page(static function (PdfPageBuilder $page) use ($templatePage): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica-Bold')
                ->useType1Font('F2', 'Helvetica')
                ->useImportedPage('TPL', $templatePage)
                ->content(static function (PdfContentStreamBuilder $s): void {
                    // Place the template at 70% scale in the upper-right quadrant.
                    $s->drawImportedPage('TPL', x: 200, y: 400, scale: 0.65);

                    $s->beginText()
                      ->setFont('F1', 14)
                      ->setTextMatrix(Matrix::translate(36, 740))
                      ->showText('Page 2 — Template at 65% scale (upper right)')
                      ->endText();

                    $s->beginText()
                      ->setFont('F2', 11)
                      ->setTextMatrix(Matrix::translate(36, 710))
                      ->showText('The letterhead above is the imported template, scaled down.')
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

    $templatePath = sys_get_temp_dir() . '/phppdf-template.pdf';

    buildTemplate($templatePath);
    buildFromTemplate($templatePath);
})();
