<?php

declare(strict_types=1);

use PhpPdf\Barcode\QrCode;
use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Color\Color;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;

function label(PdfContentStreamBuilder $s, float $x, float $y, string $text): void
{
    $s->beginText()
      ->setFont('F1', 8)
      ->setTextMatrix(Matrix::translate($x, $y))
      ->showText($text)
      ->endText();
}

function sectionTitle(PdfContentStreamBuilder $s, float $x, float $y, string $text): void
{
    $s->beginText()
      ->setFont('F2', 10)
      ->setTextMatrix(Matrix::translate($x, $y))
      ->showText($text)
      ->endText();
}

function generate(): void
{
    [$pageW, $pageH] = PdfPageSize::A4;

    $document = (new PdfDocumentBuilder())
        ->info((new PdfDocumentInfo())->title('QR Code Example')->author('phppdf'))
        ->page(static function (PdfPageBuilder $page) use ($pageW, $pageH): void {
            $page
                ->size($pageW, $pageH)
                ->useType1Font('F1', 'Helvetica')
                ->useType1Font('F2', 'Helvetica-Bold')
                ->content(static function (PdfContentStreamBuilder $s) use ($pageW, $pageH): void {

                    $ml = 52.0;
                    $y = (float) $pageH - 60.0;

                    // ── Page title ────────────────────────────────────────
                    $s->beginText()
                      ->setFont('F2', 16)
                      ->setTextMatrix(Matrix::translate($ml, $y))
                      ->showText('QR Codes — byte-mode, versions 1-10')
                      ->endText();
                    $y -= 8;

                    $s->saveGraphicsState()
                      ->setLineWidth(0.4)->strokeColor(Color::gray(0.4))
                      ->moveTo($ml, $y)->lineTo((float) $pageW - $ml, $y)->stroke()
                      ->restoreGraphicsState();
                    $y -= 20;

                    // ── Section 1: EC levels ──────────────────────────────
                    sectionTitle($s, $ml, $y, '1. Error correction levels — same data, different redundancy');
                    $y -= 14;

                    $data = 'https://example.com';
                    $levels = ['L', 'M', 'Q', 'H'];
                    $msize = 2.2;
                    $spacing = 110.0;

                    foreach ($levels as $i => $ec) {
                        $qr = QrCode::encode($data, $ec);
                        $qsz = ($qr->getSize() + 8) * $msize; // size inc. quiet zone
                        $cx = $ml + $i * $spacing;
                        $s->drawQrCode($qr, x: $cx, y: $y - $qsz, moduleSize: $msize);
                        label(
                            $s,
                            $cx + 2,
                            $y - $qsz - 10,
                            "Level {$ec}  v{$qr->getSize()}×{$qr->getSize()}",
                        );
                    }

                    $y -= 110;

                    // ── Section 2: Short vs long ──────────────────────────
                    $y -= 20;
                    sectionTitle($s, $ml, $y, '2. Short vs long data — automatic version selection');
                    $y -= 14;

                    $samples = [
                        ['Hi', 'Short: "Hi"'],
                        ['Hello, World!', 'Medium: 13 chars'],
                        ['The quick brown fox', 'Longer: 19 chars'],
                        ['https://phppdf.example.com/documents/report-2026', 'URL: 49 chars'],
                    ];

                    foreach ($samples as $i => [$text, $lbl]) {
                        $qr = QrCode::encode($text, 'M');
                        $qsz = ($qr->getSize() + 8) * $msize;
                        $cx = $ml + $i * $spacing;
                        $s->drawQrCode($qr, x: $cx, y: $y - $qsz, moduleSize: $msize);
                        label($s, $cx + 2, $y - $qsz - 10, $lbl);
                        label($s, $cx + 2, $y - $qsz - 19, "v{$qr->getSize()}×{$qr->getSize()} (M)");
                    }

                    $y -= 110;

                    // ── Section 3: Module sizes ───────────────────────────
                    $y -= 20;
                    sectionTitle($s, $ml, $y, '3. Module sizes — same QR code, different point sizes');
                    $y -= 14;

                    $qr = QrCode::encode('https://phppdf.example.com', 'M');
                    $msizes = [1.5, 2.0, 3.0, 4.0];
                    $cx = $ml;

                    foreach ($msizes as $ms) {
                        $qsz = ($qr->getSize() + 8) * $ms;
                        $s->drawQrCode($qr, x: $cx, y: $y - $qsz, moduleSize: $ms);
                        label($s, $cx + 2, $y - $qsz - 10, "{$ms} pt/module");
                        $cx += $qsz + 12;
                    }
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
