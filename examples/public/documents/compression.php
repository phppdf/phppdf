<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;

function buildDocument(bool $compress): string
{
    $builder = (new PdfDocumentBuilder())
        ->info(
            (new PdfDocumentInfo())
                ->title('Compression Example')
                ->author('phppdf'),
        );

    if ($compress) {
        $builder->compress();
    }

    $document = $builder->page(static function (PdfPageBuilder $page): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica')
                ->content(static function (PdfContentStreamBuilder $stream): void {
                    $stream->beginText()->setFont('F1', 14)->setTextMatrix(Matrix::translate(72, 720));

                    for ($i = 1; $i <= 30; $i++) {
                        $stream
                            ->showText("Line {$i}: The quick brown fox jumps over the lazy dog.")
                            ->setTextMatrix(Matrix::translate(72, 720 - $i * 20));
                    }

                    $stream->endText();
                });
    })
        ->build();

    $output = new PdfMemoryOutput();
    (new PdfDocumentSerializer($output))->writeDocument($document);

    return $output->getContent();
}

function generate(): void
{
    $uncompressed = buildDocument(compress: false);
    $compressed = buildDocument(compress: true);

    $saving = round((1 - strlen($compressed) / strlen($uncompressed)) * 100);

    header('Content-Type: text/plain');
    echo "Uncompressed: " . strlen($uncompressed) . " bytes\n";
    echo "Compressed:   " . strlen($compressed) . " bytes\n";
    echo "Reduction:    {$saving}%\n";
}

(static function (): void {
    $autoloader = require __DIR__ . '/../../../vendor/autoload.php';

    setupEnvironment($autoloader);
    generate();
})();
