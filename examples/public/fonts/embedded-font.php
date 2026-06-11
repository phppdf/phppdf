<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Font\TrueTypeFont;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;

function findFont(): TrueTypeFont
{
    $candidates = [
        // Noto Sans CJK (Simplified Chinese) — covers Latin + Chinese
        ['/usr/share/fonts/opentype/noto/NotoSansCJK-Regular.ttc', 0],
        // Noto Sans Mono — Latin only, good for basic embedding test
        ['/usr/share/fonts/truetype/noto/NotoSansMono-Regular.ttf', 0],
    ];

    foreach ($candidates as [$path, $index]) {
        if (is_readable($path)) {
            return TrueTypeFont::fromFile($path, $index);
        }
    }

    throw new RuntimeException(
        'No suitable font found. Install fonts-noto-core or fonts-noto-cjk, ' .
        'or adjust the path in findFont().',
    );
}

function generate(): void
{
    $font = findFont();

    $document = (new PdfDocumentBuilder())
        ->info(
            (new PdfDocumentInfo())
                ->title('Embedded Font Example')
                ->author('phppdf'),
        )
        ->page(static function (PdfPageBuilder $page) use ($font): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useEmbeddedFont('F1', $font)
                ->content(static function (PdfContentStreamBuilder $stream): void {
                    $stream
                        ->beginText()
                        ->setFont('F1', 24)
                        ->setTextMatrix(Matrix::translate(72, 750))
                        ->showText('Embedded TrueType font')
                        ->setFont('F1', 16)
                        ->setTextMatrix(Matrix::translate(72, 700))
                        ->showText('Latin: Hello World — em dash works!')
                        ->setTextMatrix(Matrix::translate(72, 660))
                        ->showText('Chinese: 你好，世界！')
                        ->setTextMatrix(Matrix::translate(72, 620))
                        ->showText('Mixed: Hello 世界 — αβγ')
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
