<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfFormBuilder;
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
    [$pageW, $pageH] = PdfPageSize::A4;  // 595 × 842

    // ── Layout constants ─────────────────────────────────────────────────────
    $lm     = 72.0;                         // left margin
    $labelW = 130.0;                        // label column width
    $fieldX = $lm + $labelW;               // field left edge  (= 202)
    $fieldW = $pageW - $fieldX - $lm;      // field width      (= 321)
    $fh     = 20.0;                         // single-line field height
    $cbSize = 14.0;                         // checkbox size
    $rowGap = 6.0;                          // vertical gap between adjacent fields

    // ── Header positions (all Y = PDF baseline / line position) ──────────────
    $titleY    = $pageH - 65.0;   // "Contact Form" title baseline         = 777
    $subtitleY = $pageH - 90.0;   // subtitle baseline                     = 752
    $sepY      = $pageH - 110.0;  // separator line                        = 732

    // ── Personal details section ──────────────────────────────────────────────
    $pdHeadY = $pageH - 128.0;    // section heading baseline              = 714

    // Field bottoms (field occupies [bottom, bottom + $fh]).
    // First field sits 8 pt below the heading baseline (heading text ≈ 9 pt cap).
    $fnBot = $pdHeadY - 9.0 - $fh;           // firstName bottom          = 685
    $lnBot = $fnBot - ($fh + $rowGap);        // lastName bottom           = 659
    $emBot = $lnBot - ($fh + $rowGap);        // email bottom              = 633
    $phBot = $emBot - ($fh + $rowGap);        // phone bottom              = 607
    $coBot = $phBot - ($fh + $rowGap);        // country bottom            = 581

    // ── Message section ────────────────────────────────────────────────────────
    $msgHeadY = $coBot - 22.0;               // heading baseline           = 559
    $msgH     = 60.0;
    $msgBot   = $msgHeadY - 10.0 - $msgH;   // textarea bottom            = 489

    // ── Preferences section ────────────────────────────────────────────────────
    $prefHeadY = $msgBot - 22.0;             // heading baseline           = 467
    $nlBot     = $prefHeadY - 10.0 - $cbSize; // newsletter checkbox bottom = 443
    $trBot     = $nlBot - ($cbSize + $rowGap); // terms checkbox bottom     = 423

    // ── Helper: vertical centre baseline for a label beside a field ───────────
    $labelBaseline = fn(float $bot, float $h = 20.0): float => $bot + $h / 2.0 - 3.5;

    $helv  = Type1FontMetrics::helvetica();
    $helvB = Type1FontMetrics::helveticaBold();

    $document = (new PdfDocumentBuilder())
        ->info(
            (new PdfDocumentInfo())
                ->title('Contact Form')
                ->author('phppdf'),
        )
        ->page(function (PdfPageBuilder $page) use (
            $pageW,
            $pageH,
            $lm,
            $fieldX,
            $fieldW,
            $fh,
            $cbSize,
            $titleY,
            $subtitleY,
            $sepY,
            $pdHeadY,
            $fnBot,
            $lnBot,
            $emBot,
            $phBot,
            $coBot,
            $msgHeadY,
            $msgH,
            $msgBot,
            $prefHeadY,
            $nlBot,
            $trBot,
            $labelBaseline,
            $helv,
            $helvB,
        ): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica')
                ->useType1Font('FB', 'Helvetica-Bold')
                ->content(function (PdfContentStreamBuilder $s) use (
                    $pageW,
                    $lm,
                    $fieldX,
                    $fieldW,
                    $fh,
                    $cbSize,
                    $titleY,
                    $subtitleY,
                    $sepY,
                    $pdHeadY,
                    $fnBot,
                    $lnBot,
                    $emBot,
                    $phBot,
                    $coBot,
                    $msgHeadY,
                    $msgH,
                    $msgBot,
                    $prefHeadY,
                    $nlBot,
                    $trBot,
                    $labelBaseline,
                    $helv,
                ): void {

                    // ── Title & subtitle ──────────────────────────────────────
                    $s->beginText()->setFont('FB', 18)
                      ->setTextMatrix(Matrix::translate($lm, $titleY))
                      ->showText('Contact Form')
                      ->endText();

                    $sub = TextBox::create(
                        'Fill in the fields below. Fields marked with * are required.',
                        $helv,
                        10,
                        $pageW - $lm * 2,
                        13,
                    );
                    $s->drawTextBox($sub, fontName: 'F1', x: $lm, y: $subtitleY);

                    $s->saveGraphicsState()
                      ->strokeColor(Color::fromHex('#aaaacc'))
                      ->setLineWidth(0.5)
                      ->moveTo($lm, $sepY)->lineTo($lm + $pageW - $lm * 2, $sepY)->stroke()
                      ->restoreGraphicsState();

                    // ── Shorthand closures ────────────────────────────────────
                    $heading = function (string $text, float $y) use ($s, $lm): void {
                        $s->beginText()->setFont('FB', 11)
                          ->setTextMatrix(Matrix::translate($lm, $y))
                          ->showText($text)->endText();
                    };

                    $rowLabel = function (
                        string $text,
                        float $bot,
                        float $h = 20.0
                    ) use (
                        $s,
                        $lm,
                        $labelBaseline,
                    ): void {
                        $s->beginText()->setFont('F1', 10)
                          ->setTextMatrix(Matrix::translate($lm, $labelBaseline($bot, $h)))
                          ->showText($text)->endText();
                    };

                    $fieldBox = function (
                        float $bot,
                        float $h = 20.0
                    ) use (
                        $s,
                        $fieldX,
                        $fieldW,
                    ): void {
                        $s->saveGraphicsState()
                          ->fillColor(Color::fromHex('#f8f8ff'))
                          ->strokeColor(Color::fromHex('#9999bb'))
                          ->setLineWidth(0.4)
                          ->rectangle($fieldX, $bot, $fieldW, $h)
                          ->fillAndStroke()
                          ->restoreGraphicsState();
                    };

                    // ── Personal details ──────────────────────────────────────
                    $heading('Personal details', $pdHeadY);

                    $rowLabel('First name *', $fnBot);
                $fieldBox($fnBot);
                    $rowLabel('Last name *', $lnBot);
                $fieldBox($lnBot);
                    $rowLabel('Email address *', $emBot);
                $fieldBox($emBot);
                    $rowLabel('Phone number', $phBot);
                $fieldBox($phBot);
                    $rowLabel('Country', $coBot);
                $fieldBox($coBot);

                    // ── Message ───────────────────────────────────────────────
                    $heading('Message', $msgHeadY);

                    $rowLabel('Your message', $msgBot, $msgH);
                    $fieldBox($msgBot, $msgH);

                    // ── Preferences ───────────────────────────────────────────
                    $heading('Preferences', $prefHeadY);

                    // Checkbox labels sit to the right of the box, not in the label column
                    $s->beginText()->setFont('F1', 10)
                      ->setTextMatrix(Matrix::translate($fieldX + $cbSize + 6, $labelBaseline($nlBot, $cbSize)))
                      ->showText('Subscribe to the newsletter')->endText();

                    $s->beginText()->setFont('F1', 10)
                      ->setTextMatrix(Matrix::translate($fieldX + $cbSize + 6, $labelBaseline($trBot, $cbSize)))
                      ->showText('I agree to the terms and conditions *')->endText();
                });
        })
        ->form(function (PdfFormBuilder $form) use (
            $fieldX,
            $fieldW,
            $fh,
            $cbSize,
            $fnBot,
            $lnBot,
            $emBot,
            $phBot,
            $coBot,
            $msgH,
            $msgBot,
            $nlBot,
            $trBot,
        ): void {
            $form
                // ── Personal details ─────────────────────────────────────────
                ->textField(
                    'firstName',
                    $fieldX,
                    $fnBot,
                    $fieldW,
                    $fh,
                    tooltip: 'Enter your first name',
                )
                ->textField(
                    'lastName',
                    $fieldX,
                    $lnBot,
                    $fieldW,
                    $fh,
                    tooltip: 'Enter your last name',
                )
                ->textField(
                    'email',
                    $fieldX,
                    $emBot,
                    $fieldW,
                    $fh,
                    tooltip: 'Enter your email address',
                )
                ->textField(
                    'phone',
                    $fieldX,
                    $phBot,
                    $fieldW,
                    $fh,
                    tooltip: 'Enter your phone number (optional)',
                )
                ->comboBox(
                    'country',
                    $fieldX,
                    $coBot,
                    $fieldW,
                    $fh,
                    options: ['', 'Australia', 'Canada', 'France', 'Germany',
                              'Netherlands', 'United Kingdom', 'United States', 'Other'],
                    tooltip: 'Select your country',
                )

                // ── Message ──────────────────────────────────────────────────
                ->textArea(
                    'message',
                    $fieldX,
                    $msgBot,
                    $fieldW,
                    $msgH,
                    tooltip: 'Type your message here',
                )

                // ── Preferences ───────────────────────────────────────────────
                ->checkbox(
                    'newsletter',
                    $fieldX,
                    $nlBot,
                    $cbSize,
                    tooltip: 'Check to subscribe to our newsletter',
                )
                ->checkbox(
                    'terms',
                    $fieldX,
                    $trBot,
                    $cbSize,
                    tooltip: 'You must accept the terms to submit',
                );
        })
        ->build();

    $output = new PdfMemoryOutput();
    (new PdfDocumentSerializer($output))->writeDocument($document);

    header('Content-Type: application/pdf');
    header('Content-Length: ' . $output->position());
    header('Content-Disposition: inline; filename="' . basename(__FILE__, '.php') . '.pdf"');
    echo $output->getContent();
}

(function (): void {
    $autoloader = require __DIR__ . '/../../../vendor/autoload.php';

    setupEnvironment($autoloader);
    generate();
})();
