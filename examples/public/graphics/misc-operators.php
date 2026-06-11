<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Color\Color;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Font\Type1FontMetrics;
use PhpPdf\Object\PdfVersion;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;

function generate(): void
{
    [$pageW, $pageH] = PdfPageSize::A4;
    $lm = 72.0;
    $helv = Type1FontMetrics::helvetica();

    $document = (new PdfDocumentBuilder())
        ->version(PdfVersion::PDF_1_4) // ← explicit version (default is 1.7)
        ->info(
            (new PdfDocumentInfo())
                ->title('Miscellaneous Operators')
                ->author('phppdf'),
        )
        ->page(static function (PdfPageBuilder $page) use ($pageW, $pageH, $lm): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica')
                ->useType1Font('FB', 'Helvetica-Bold')
                ->content(static function (PdfContentStreamBuilder $s) use ($pageW, $pageH, $lm): void {

                    $y = $pageH - 60.0;

                    $s->beginText()->setFont('FB', 18)
                      ->setTextMatrix(Matrix::translate($lm, $y))
                      ->showText('Miscellaneous Operators')
                      ->endText();
                    $y -= 8;
                    $s->saveGraphicsState()
                      ->setLineWidth(0.4)->strokeColor(Color::gray(0.5))
                      ->moveTo($lm, $y)->lineTo($pageW - $lm, $y)->stroke()
                      ->restoreGraphicsState();
                    $y -= 20;

                    $section = static function (string $title) use ($s, $lm, &$y): void {
                        $s->beginText()->setFont('FB', 12)
                          ->fillColor(Color::rgb(0, 0, 0))
                          ->setTextMatrix(Matrix::translate($lm, $y))
                          ->showText($title)->endText();
                        $y -= 16;
                    };

                    // ── 1. PdfVersion ─────────────────────────────────────────
                    $section('1.  PdfVersion — targeting a specific PDF specification');

                    $versions = [
                        ['PDF_1_3', 'PDF 1.3', 'Acrobat 4. Adds smooth shading and DeviceN colour spaces.'],
                        ['PDF_1_4', 'PDF 1.4', 'Acrobat 5. Adds transparency model (used by this document).'],
                        ['PDF_1_5', 'PDF 1.5', 'Acrobat 6. Adds object streams and cross-reference streams.'],
                        ['PDF_1_6', 'PDF 1.6', 'Acrobat 7. Adds AES encryption and 3D annotations.'],
                        ['PDF_1_7', 'PDF 1.7', 'Acrobat 8 / ISO 32000-1. Current default for this library.'],
                        ['PDF_2_0', 'PDF 2.0', 'ISO 32000-2. Removes deprecated features; adds new annotation types.'],
                    ];

                    $s->beginText()->setFont('F1', 9)
                      ->setTextMatrix(Matrix::translate($lm, $y))
                      ->showText(
                          'Set with PdfDocumentBuilder::version(PdfVersion::PDF_x_y). This document targets PDF 1.4.',
                      )
                      ->endText();
                    $y -= 12;

                    foreach ($versions as $i => [$const, $label, $desc]) {
                        $bg = $i % 2 === 0
                            ? '#f0f4ff'
                            : '#ffffff';
                        $active = $const === 'PDF_1_4';
                        $s->saveGraphicsState()
                          ->fillColor(Color::fromHex($active ? '#d0e8ff' : $bg))
                          ->rectangle($lm, $y - 14, 451, 16)->fill()->restoreGraphicsState();
                        $s->beginText()->setFont('FB', 8)->fillColor(Color::rgb(0.1, 0.3, 0.7))
                          ->setTextMatrix(Matrix::translate($lm + 4, $y - 10))
                          ->showText('PdfVersion::' . $const)->endText();
                        $s->beginText()->setFont($active ? 'FB' : 'F1', 8)
                          ->fillColor(Color::rgb(0, 0, 0))
                          ->setTextMatrix(Matrix::translate($lm + 130, $y - 10))
                          ->showText(($active ? '* ' : '  ') . $label . ' — ' . $desc)->endText();
                        $y -= 16;
                    }

                    $y -= 16;

                    // ── 2. curveToReplicateInitial (V operator) ───────────────
                    $section('2.  curveToReplicateInitial (V) & curveToReplicateFinal (Y)');

                    $s->beginText()->setFont('F1', 9)
                      ->setTextMatrix(Matrix::translate($lm, $y))
                      ->showText(
                          'V: first control point = current point.   Y: second control point = endpoint.   C: all four points explicit.',
                      )
                      ->endText();
                    $y -= 16;

                    $bx = $lm;
                    $by = $y - 90.0;

                    // Background grid
                    $s->saveGraphicsState()
                      ->setLineWidth(0.2)->strokeColor(Color::gray(0.85));

                    for ($gx = 0; $gx <= 120; $gx += 20) {
                        $s->moveTo($bx + $gx, $by)->lineTo($bx + $gx, $by + 80);
                    }

                    for ($gy = 0; $gy <= 80; $gy += 20) {
                        $s->moveTo($bx, $by + $gy)->lineTo($bx + 120, $by + $gy);
                    }

                    $s->stroke()->restoreGraphicsState();

                    // C — full cubic: start(0,20), cp1(30,80), cp2(90,80), end(120,20)
                    $s->saveGraphicsState()->strokeColor(Color::rgb(0.0, 0.4, 0.9))->setLineWidth(1.5)
                      ->moveTo($bx, $by + 20)
                      ->curveTo($bx + 30, $by + 80, $bx + 90, $by + 80, $bx + 120, $by + 20)
                      ->stroke()->restoreGraphicsState();
                    $s->beginText()->setFont('F1', 8)->fillColor(Color::rgb(0.0, 0.4, 0.9))
                      ->setTextMatrix(Matrix::translate($bx, $by - 10))->showText('C (curveTo)')->endText();

                    // V — replicate initial: current point becomes cp1
                    // start(160,20), cp2(230,80), end(280,20)  [cp1 = (160,20)]
                    $bx2 = $bx + 150;
                    $s->saveGraphicsState()->strokeColor(Color::rgb(0.8, 0.2, 0.0))->setLineWidth(1.5)
                      ->moveTo($bx2, $by + 20)
                      ->curveToReplicateInitial($bx2 + 70, $by + 80, $bx2 + 120, $by + 20)
                      ->stroke()->restoreGraphicsState();
                    $s->beginText()->setFont('F1', 8)->fillColor(Color::rgb(0.8, 0.2, 0.0))
                      ->setTextMatrix(Matrix::translate($bx2, $by - 10))->showText(
                          'V (curveToReplicateInitial)',
                      )->endText();

                    // Y — replicate final: cp2 = end point
                    // start(310,20), cp1(340,80), end(430,20)  [cp2 = (430,20)]
                    $bx3 = $bx + 310;
                    $s->saveGraphicsState()->strokeColor(Color::rgb(0.1, 0.6, 0.1))->setLineWidth(1.5)
                      ->moveTo($bx3, $by + 20)
                      ->curveToReplicateFinal($bx3 + 30, $by + 60, $bx3 + 120, $by + 20)
                      ->stroke()->restoreGraphicsState();
                    $s->beginText()->setFont('F1', 8)->fillColor(Color::rgb(0.1, 0.6, 0.1))
                      ->setTextMatrix(Matrix::translate($bx3, $by - 10))->showText(
                          'Y (curveToReplicateFinal)',
                      )->endText();

                    $y -= 110;

                    // ── 3. Compatibility sections ─────────────────────────────
                    $section('3.  Compatibility sections  (BX / EX)');

                    $s->beginText()->setFont('F1', 9)
                      ->setTextMatrix(Matrix::translate($lm, $y))
                      ->showText('BX opens a section where unknown operators are silently ignored by older viewers.')
                      ->endText();
                    $y -= 12;
                    $s->beginText()->setFont('F1', 9)
                      ->setTextMatrix(Matrix::translate($lm, $y))
                      ->showText(
                          'EX closes it. Use when embedding operators from a newer PDF version in a document targeting an older one.',
                      )
                      ->endText();
                    $y -= 20;

                    // Draw a shape using a compatibility section
                    $s->saveGraphicsState();
                    $s->beginCompatibilitySection();
                    // Any operators here that an older viewer does not understand are skipped
                    $s->fillColor(Color::rgb(0.2, 0.6, 0.9))
                      ->rectangle($lm, $y - 30, 180, 30)->fill();
                    $s->endCompatibilitySection();
                    $s->restoreGraphicsState();

                    $s->beginText()->setFont('F1', 9)
                      ->fillColor(Color::rgb(1, 1, 1))
                      ->setTextMatrix(Matrix::translate($lm + 6, $y - 18))
                      ->showText('Drawn inside BX / EX compatibility section')
                      ->endText();
                    $y -= 44;

                    // Code snippet
                    $snippets = [
                        '$s->beginCompatibilitySection();',
                        '// operators here are ignored by viewers that do not understand them',
                        '$s->fillColor(Color::rgb(...))->rectangle(...)->fill();',
                        '$s->endCompatibilitySection();',
                    ];

                    foreach ($snippets as $line) {
                        $s->beginText()->setFont('F1', 8.5)
                          ->fillColor(Color::rgb(0.1, 0.35, 0.7))
                          ->setTextMatrix(Matrix::translate($lm + 4, $y))
                          ->showText($line)->endText();
                        $y -= 12;
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
