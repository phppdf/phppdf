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

function generate(): void
{
    [$pageW, $pageH] = PdfPageSize::A4;
    $lm = 72.0;
    $helv = Type1FontMetrics::helvetica();
    $helvB = Type1FontMetrics::helveticaBold();

    $document = (new PdfDocumentBuilder())
        ->info(
            (new PdfDocumentInfo())
                ->title('Marked Content & Accessibility Tags')
                ->author('phppdf'),
        )
        ->page(static function (PdfPageBuilder $page) use ($pageW, $pageH, $lm, $helv): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica')
                ->useType1Font('FB', 'Helvetica-Bold')
                ->content(static function (PdfContentStreamBuilder $s) use ($pageW, $pageH, $lm, $helv): void {

                    $y = $pageH - 60.0;

                    $s->beginText()->setFont('FB', 18)
                      ->setTextMatrix(Matrix::translate($lm, $y))
                      ->showText('Marked Content & Accessibility')
                      ->endText();
                    $y -= 8;
                    $s->saveGraphicsState()
                      ->setLineWidth(0.4)->strokeColor(Color::gray(0.5))
                      ->moveTo($lm, $y)->lineTo($pageW - $lm, $y)->stroke()
                      ->restoreGraphicsState();
                    $y -= 20;

                    $intro = TextBox::create(
                        'Marked content operators embed semantic structure into the PDF content stream. '
                        . 'They are used by PDF/UA (ISO 14289) and tagged-PDF workflows to make '
                        . 'documents accessible to screen readers. Each sequence has a role tag '
                        . '(e.g. P, H1, Figure, Artifact) and optionally a property dictionary '
                        . 'carrying alternate text, language, or MCID for structure-tree linking.',
                        $helv,
                        10,
                        451,
                        13,
                    );
                    $s->drawTextBox($intro, fontName: 'F1', x: $lm, y: $y);
                    $y -= $intro->getHeight() + 18;

                    // ── Operator reference table ──────────────────────────────
                    $s->beginText()->setFont('FB', 11)
                      ->setTextMatrix(Matrix::translate($lm, $y))
                      ->showText('Operators')->endText();
                    $y -= 14;

                    $ops = [
                        ['BMC / tag', 'beginMarkedContent(string $tag)', 'Opens a marked-content sequence with a semantic role tag. Closed by EMC.'],
                        ['BDC / tag props','beginMarkedContentWithProperties(tag, props)', 'Like BMC but references a property dict from the page\'s Properties resource.'],
                        ['EMC', 'endMarkedContent()', 'Closes the most recently opened BMC or BDC sequence.'],
                        ['MP / tag', 'defineMarkedContentPoint(string $tag)', 'Marks a single point (not a range). No closing operator needed.'],
                        ['DP / tag props', 'defineMarkedContentPointWithProperties(t, p)', 'Like MP but with a property dictionary reference.'],
                    ];

                    $headerBg = Color::fromHex('#3355aa');
                    $altBg = Color::fromHex('#f0f4ff');

                    $s->saveGraphicsState()
                      ->fillColor($headerBg)
                      ->rectangle($lm, $y - 18, 451, 20)
                      ->fill()
                      ->restoreGraphicsState();
                    $s->beginText()->setFont('FB', 9)
                      ->fillColor(Color::rgb(1, 1, 1))
                      ->setTextMatrix(Matrix::translate($lm + 4, $y - 13))
                      ->showText('PDF Operator')
                      ->setTextMatrix(Matrix::translate($lm + 90, $y - 13))
                      ->showText('PHP method')
                      ->setTextMatrix(Matrix::translate($lm + 270, $y - 13))
                      ->showText('Purpose')
                      ->endText();
                    $y -= 20;

                    foreach ($ops as $i => [$op, $method, $desc]) {
                        $rh = 26.0;

                        if ($i % 2 === 0) {
                            $s->saveGraphicsState()
                              ->fillColor($altBg)
                              ->rectangle($lm, $y - $rh, 451, $rh)
                              ->fill()
                              ->restoreGraphicsState();
                        }

                        $s->beginText()->setFont('FB', 8)
                          ->fillColor(Color::rgb(0, 0, 0))
                          ->setTextMatrix(Matrix::translate($lm + 4, $y - 9))
                          ->showText($op)->endText();
                        $s->beginText()->setFont('F1', 7.5)
                          ->fillColor(Color::rgb(0.1, 0.3, 0.7))
                          ->setTextMatrix(Matrix::translate($lm + 90, $y - 9))
                          ->showText($method)->endText();
                        $descBox = TextBox::create($desc, $helv, 7.5, 175, 10);
                        $s->drawTextBox($descBox, fontName: 'F1', x: $lm + 270, y: $y - 8);
                        $y -= $rh;
                    }

                    $y -= 20;

                    // ── Live demonstration ────────────────────────────────────
                    $s->beginText()->setFont('FB', 11)
                      ->fillColor(Color::rgb(0, 0, 0))
                      ->setTextMatrix(Matrix::translate($lm, $y))
                      ->showText('Live demonstration — the content below is tagged in the stream')
                      ->endText();
                    $y -= 14;

                    $note = TextBox::create(
                        'Open this PDF in Adobe Acrobat and enable View > Show/Hide > '
                        . 'Navigation Panes > Tags to inspect the content stream tags. '
                        . 'Each marked region below has a distinct tag visible in the panel.',
                        $helv,
                        9,
                        451,
                        12,
                    );
                    $s->drawTextBox($note, fontName: 'F1', x: $lm, y: $y);
                    $y -= $note->getHeight() + 14;

                    // H1 — document heading
                    $s->beginMarkedContent('H1');
                    $s->beginText()->setFont('FB', 16)
                      ->setTextMatrix(Matrix::translate($lm, $y))
                      ->showText('Article Title  (tagged H1)')
                      ->endText();
                    $s->endMarkedContent();
                    $y -= 24;

                    // P — paragraph
                    $s->beginMarkedContent('P');
                    $para = TextBox::create(
                        'This paragraph is enclosed in a P marked-content sequence. '
                        . 'Screen readers and reflow engines use the P tag to recognise '
                        . 'this block as body text distinct from headings or figures.',
                        $helv,
                        11,
                        451,
                        14,
                    );
                    $s->drawTextBox($para, fontName: 'F1', x: $lm, y: $y);
                    $s->endMarkedContent();
                    $y -= $para->getHeight() + 14;

                    // Figure — image-like block with alternate text in the properties
                    $s->beginMarkedContent('Figure');
                    $s->saveGraphicsState()
                      ->fillColor(Color::fromHex('#ddeeff'))
                      ->strokeColor(Color::fromHex('#6688aa'))
                      ->setLineWidth(0.75)
                      ->rectangle($lm, $y - 50, 200, 50)
                      ->fillAndStroke()
                      ->restoreGraphicsState();
                    $s->beginText()->setFont('F1', 9)
                      ->fillColor(Color::rgb(0.2, 0.4, 0.6))
                      ->setTextMatrix(Matrix::translate($lm + 8, $y - 28))
                      ->showText('[Figure placeholder — tagged Figure]')
                      ->endText();
                    $s->endMarkedContent();
                    $y -= 64;

                    // Artifact — decorative element that should be ignored by ATs
                    $s->defineMarkedContentPoint('Artifact');
                    $s->saveGraphicsState()
                      ->setLineWidth(0.3)->strokeColor(Color::gray(0.8))
                      ->moveTo($lm, $y)->lineTo($lm + 451, $y)->stroke()
                      ->restoreGraphicsState();
                    $y -= 10;

                    // Span — inline emphasis
                    $s->beginMarkedContent('P');
                    $s->beginText()->setFont('F1', 11)
                      ->setTextMatrix(Matrix::translate($lm, $y))
                      ->showText('Normal text, then ')
                      ->endText();
                    $s->endMarkedContent();

                    $s->beginMarkedContent('Span');
                    $s->beginText()->setFont('FB', 11)
                      ->setTextMatrix(Matrix::translate($lm + 120, $y))
                      ->showText('emphasised span')
                      ->endText();
                    $s->endMarkedContent();

                    $s->beginMarkedContent('P');
                    $s->beginText()->setFont('F1', 11)
                      ->setTextMatrix(Matrix::translate($lm + 243, $y))
                      ->showText(', then normal again.  (tagged Span inside P)')
                      ->endText();
                    $s->endMarkedContent();
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
