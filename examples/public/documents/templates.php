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
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PhpPdf\Text\TextBox;

const DOC_TITLE = 'Annual Report 2024';

const BODY_TEXT =
    'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod '
    . 'tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, '
    . 'quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo '
    . 'consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse '
    . 'cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat '
    . 'non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';

function addPage(PdfDocumentBuilder $b, string $chapter, string $body): void
{
    $helv = Type1FontMetrics::helvetica();
    $helvB = Type1FontMetrics::helveticaBold();

    $b->page(static function (PdfPageBuilder $page) use ($chapter, $body, $helv): void {
        $page
            ->size(...PdfPageSize::A4)
            ->content(static function (PdfContentStreamBuilder $s) use ($chapter, $body, $helv): void {
                // Chapter heading
                $s->beginText()
                  ->setFont('F2', 18)
                  ->setTextMatrix(Matrix::translate(72, 730))
                  ->showText($chapter)
                  ->endText();

                // Body text — two paragraphs
                $fullText = $body . "\n\n" . $body;
                $box = TextBox::create($fullText, $helv, 11, 451, 16);
                $s->drawTextBox($box, fontName: 'F1', x: 72, y: 700);
            });
    });
}

function generate(): void
{
    $builder = (new PdfDocumentBuilder())
        ->info(
            (new PdfDocumentInfo())
                ->title(DOC_TITLE)
                ->author('phppdf'),
        )
        // Fonts declared here are available on every page AND in the templates.
        ->globalFont('F1', 'Helvetica')
        ->globalFont('F2', 'Helvetica-Bold')

        // =====================================================================
        // Header — drawn before the page body on every page.
        // $pageNumber 1 is the cover page; skip the header there.
        // =====================================================================
        ->header(static function (PdfContentStreamBuilder $s, int $pageNumber, int $totalPages): void {
            if ($pageNumber === 1) {
                return; // no header on the cover page
            }

            // Rule line
            $s->saveGraphicsState()
              ->strokeColor(Color::fromHex('#aaaaaa'))
              ->setLineWidth(0.5)
              ->moveTo(72, 792)->lineTo(523, 792)->stroke()
              ->restoreGraphicsState();

            // Document title on the left
            $s->beginText()
              ->setFont('F2', 9)
              ->setTextMatrix(Matrix::translate(72, 797))
              ->showText(DOC_TITLE)
              ->endText();

            // Page number on the right
            $label = "Page {$pageNumber} of {$totalPages}";
            $s->beginText()
              ->setFont('F1', 9)
              ->setTextMatrix(Matrix::translate(523 - strlen($label) * 4.8, 797))
              ->showText($label)
              ->endText();
        })

        // =====================================================================
        // Footer — drawn after the page body on every page.
        // =====================================================================
        ->footer(static function (PdfContentStreamBuilder $s, int $pageNumber, int $totalPages): void {
            // Rule line
            $s->saveGraphicsState()
              ->strokeColor(Color::fromHex('#aaaaaa'))
              ->setLineWidth(0.5)
              ->moveTo(72, 48)->lineTo(523, 48)->stroke()
              ->restoreGraphicsState();

            if ($pageNumber === 1) {
                // Cover page footer: just the company name
                $s->beginText()
                  ->setFont('F1', 8)
                  ->setTextMatrix(Matrix::translate(72, 36))
                  ->showText('Confidential — phppdf Inc.')
                  ->endText();

                return;
            }

            // Inner pages: centred page indicator
            $label = "— {$pageNumber} —";
            $s->beginText()
              ->setFont('F1', 9)
              ->setTextMatrix(Matrix::translate(285, 36))
              ->showText($label)
              ->endText();
        });

    // =========================================================================
    // Page 1: cover page — header is suppressed, footer shows company name
    // =========================================================================
    $builder->page(static function (PdfPageBuilder $page): void {
        $page
            ->size(...PdfPageSize::A4)
            ->content(static function (PdfContentStreamBuilder $s): void {
                $s->saveGraphicsState()
                  ->fillColor(Color::fromHex('#1a3a5c'))
                  ->rectangle(0, 620, 595, 222)->fill()
                  ->restoreGraphicsState();

                $s->beginText()
                  ->setFont('F2', 32)
                  ->fillColor(Color::rgb(1, 1, 1))
                  ->setTextMatrix(Matrix::translate(72, 720))
                  ->showText(DOC_TITLE)
                  ->endText();

                $s->beginText()
                  ->setFont('F1', 16)
                  ->fillColor(Color::rgb(0.8, 0.9, 1.0))
                  ->setTextMatrix(Matrix::translate(72, 680))
                  ->showText('Fiscal Year Overview')
                  ->endText();

                $s->beginText()
                  ->setFont('F1', 12)
                  ->fillColor(Color::gray(0.3))
                  ->setTextMatrix(Matrix::translate(72, 590))
                  ->showText('Prepared by: Finance Department')
                  ->endText();
            });
    });

    // Pages 2–4: chapter pages
    addPage($builder, 'Chapter 1 — Revenue', BODY_TEXT);
    addPage($builder, 'Chapter 2 — Expenses', BODY_TEXT);
    addPage($builder, 'Chapter 3 — Outlook', BODY_TEXT);

    $document = $builder->build();

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
