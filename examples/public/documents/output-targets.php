<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Color\Color;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocument;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Font\Type1FontMetrics;
use PhpPdf\Output\PdfFileOutput;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Output\PdfStreamOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PhpPdf\Text\TextBox;

function buildDocument(string $outputTarget): PdfDocument
{
    $helv = Type1FontMetrics::helvetica();
    $helvB = Type1FontMetrics::helveticaBold();

    return (new PdfDocumentBuilder())
        ->info(
            (new PdfDocumentInfo())
                ->title('Output Targets — ' . $outputTarget)
                ->author('phppdf'),
        )
        ->page(static function (PdfPageBuilder $page) use ($outputTarget, $helv): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica')
                ->useType1Font('FB', 'Helvetica-Bold')
                ->content(static function (PdfContentStreamBuilder $s) use ($outputTarget, $helv): void {
                    $lm = 72.0;
                    $y = 790.0;

                    $s->beginText()->setFont('FB', 18)
                      ->setTextMatrix(Matrix::translate($lm, $y))
                      ->showText('PDF Output Targets')->endText();
                    $y -= 30;

                    $intro = TextBox::create(
                        'The library provides three output implementations. '
                        . 'PdfMemoryOutput accumulates bytes in a string buffer — ideal for '
                        . 'sending directly to the browser. PdfFileOutput writes bytes to a '
                        . 'file on disk as they are produced — useful for batch generation '
                        . 'without holding the whole document in memory. PdfStreamOutput '
                        . 'writes to any PHP resource, enabling piping to gzip streams, '
                        . 'network sockets, or temporary files.',
                        $helv,
                        11,
                        451,
                        14,
                    );
                    $s->drawTextBox($intro, fontName: 'F1', x: $lm, y: $y);
                    $y -= $intro->getHeight() + 20;

                    $rows = [
                        ['PdfMemoryOutput', 'new PdfMemoryOutput()', 'Buffers bytes in RAM. Call getContent() to retrieve the PDF string. Best for browser delivery or in-memory processing.'],
                        ['PdfFileOutput', 'new PdfFileOutput($path)', 'Streams bytes directly to a file path. The file handle is opened in binary write mode and closed on destruction.'],
                        ['PdfStreamOutput', 'new PdfStreamOutput($resource)', 'Writes to any PHP resource — fopen(), php://output, gzip streams, network sockets, etc.'],
                    ];

                    foreach ($rows as $i => [$name, $ctor, $desc]) {
                        $bg = $i % 2 === 0
                            ? '#f0f4ff'
                            : '#ffffff';
                        $s->saveGraphicsState()
                          ->fillColor(Color::fromHex($bg))
                          ->rectangle($lm, $y - 48, 451, 54)
                          ->fill()
                          ->restoreGraphicsState();

                        $s->beginText()->setFont('FB', 11)
                          ->setTextMatrix(Matrix::translate($lm + 6, $y - 6))
                          ->showText($name)->endText();

                        $s->beginText()->setFont('F1', 9)
                          ->fillColor(Color::rgb(0.2, 0.4, 0.7))
                          ->setTextMatrix(Matrix::translate($lm + 6, $y - 20))
                          ->showText($ctor)->endText();

                        $descBox = TextBox::create($desc, $helv, 9, 439, 11);
                        $s->drawTextBox($descBox, fontName: 'F1', x: $lm + 6, y: $y - 32);
                        $y -= 62;
                    }

                    $y -= 10;
                    $s->beginText()->setFont('FB', 11)
                      ->fillColor(Color::rgb(0, 0, 0))
                      ->setTextMatrix(Matrix::translate($lm, $y))
                      ->showText('This document was generated using: ' . $outputTarget)
                      ->endText();
                });
        })
        ->build();
}

function generate(): void
{
    $mode = $_GET['output'] ?? 'memory';

    $document = buildDocument(match ($mode) {
        'file' => 'PdfFileOutput',
        'stream' => 'PdfStreamOutput',
        default => 'PdfMemoryOutput',
    });

    switch ($mode) {
        // ── PdfFileOutput ────────────────────────────────────────────────────
        case 'file':
            $path = sys_get_temp_dir() . '/phppdf-output-demo.pdf';
            $output = new PdfFileOutput($path);
            (new PdfDocumentSerializer($output))->writeDocument($document);
            unset($output); // flushes / closes the file handle via __destruct

            $size = filesize($path);
            header('Content-Type: application/pdf');
            header('Content-Length: ' . $size);
            header('Content-Disposition: inline; filename="33-output-file.pdf"');
            readfile($path);
            unlink($path);

            break;

        // ── PdfStreamOutput — pipe directly to php://output ──────────────────
        case 'stream':
            $memory = new PdfMemoryOutput();
            (new PdfDocumentSerializer($memory))->writeDocument($document);
            $size = $memory->position();

            header('Content-Type: application/pdf');
            header('Content-Length: ' . $size);
            header('Content-Disposition: inline; filename="33-output-stream.pdf"');

            $resource = fopen('php://output', 'wb');
            $output = new PdfStreamOutput($resource);
            (new PdfDocumentSerializer($output))->writeDocument(buildDocument('PdfStreamOutput'));
            fclose($resource);

            break;

        // ── PdfMemoryOutput (default) ─────────────────────────────────────────
        default:
            $output = new PdfMemoryOutput();
            (new PdfDocumentSerializer($output))->writeDocument($document);

            header('Content-Type: application/pdf');
            header('Content-Length: ' . $output->position());
            header('Content-Disposition: inline; filename="33-output-memory.pdf"');
            echo $output->getContent();

            break;
    }
}

(static function (): void {
    $autoloader = require __DIR__ . '/../../../vendor/autoload.php';

    setupEnvironment($autoloader);
    generate();
})();
