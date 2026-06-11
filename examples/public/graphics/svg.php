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
use PhpPdf\Svg\SvgDocument;

function generate(): void
{
    // A simple SVG with shapes, a path and a group with a transform
    $svgXml = <<<'SVG'
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200">
      <!-- Background -->
      <rect x="0" y="0" width="200" height="200" fill="#f0f4ff"/>
      <!-- Coloured circles -->
      <circle cx="100" cy="100" r="80" fill="none" stroke="#3366cc" stroke-width="4"/>
      <circle cx="100" cy="100" r="55" fill="#3366cc"/>
      <!-- White star (polygon) -->
      <polygon points="100,30 112,72 158,72 121,97 134,140 100,115 66,140 79,97 42,72 88,72"
               fill="white"/>
      <!-- Rounded rect label -->
      <rect x="60" y="160" width="80" height="28" rx="6" ry="6"
            fill="#ffcc00" stroke="#cc9900" stroke-width="1.5"/>
      <!-- Diagonal line across the badge -->
      <line x1="10" y1="10" x2="30" y2="30" stroke="#cc0000" stroke-width="3"/>
      <!-- Small ellipse -->
      <ellipse cx="170" cy="30" rx="18" ry="10" fill="#cc0000"/>
    </svg>
    SVG;

    $badge = SvgDocument::fromString($svgXml);

    $document = (new PdfDocumentBuilder())
        ->info(
            (new PdfDocumentInfo())
                ->title('SVG Support Example')
                ->author('phppdf'),
        )
        ->page(static function (PdfPageBuilder $page) use ($badge): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica')
                ->useSvg('Badge', $badge)
                ->content(static function (PdfContentStreamBuilder $stream): void {
                    $stream
                        ->beginText()
                        ->setFont('F1', 16)
                        ->setTextMatrix(Matrix::translate(72, 790))
                        ->showText('SVG Support — vector shapes embedded as Form XObjects')
                        ->endText()

                        // Large badge — 300×300 pt
                        ->drawSvg('Badge', x: 72, y: 460, width: 300, height: 300)

                        // Same SVG at a smaller size — shows vector scaling
                        ->drawSvg('Badge', x: 390, y: 610, width: 150, height: 150)

                        // And very small
                        ->drawSvg('Badge', x: 390, y: 460, width: 80, height: 80)

                        ->beginText()
                        ->setFont('F1', 11)
                        ->setTextMatrix(Matrix::translate(72, 440))
                        ->showText('300 × 300 pt')
                        ->setTextMatrix(Matrix::translate(390, 440))
                        ->showText('150 × 150 pt  /  80 × 80 pt')
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
