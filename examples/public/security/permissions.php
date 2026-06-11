<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Color\Color;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Encryption\PdfEncryptionConfig;
use PhpPdf\Encryption\PdfPermissions;
use PhpPdf\Font\Type1FontMetrics;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PhpPdf\Table\TableBuilder;
use PhpPdf\Table\TableCell;
use PhpPdf\Table\TableRow;
use PhpPdf\Text\TextAlign;
use PhpPdf\Text\TextBox;

/**
 * Each scenario entry:
 *   label - human-readable name shown in the document title
 *   description - one-sentence summary
 *   userPass - password required to open the document (empty = no password)
 *   ownerPass - password required to change security settings
 *   permissions - PdfPermissions instance
 *   rows - per-permission status lines for the table on the page
 */
function scenarios(): array
{
    return [

        // ── 1. No restrictions ────────────────────────────────────────────────
        'all' => [
            'description' => 'No restrictions. Users can print, copy, edit, annotate and fill forms.',
            'label' => 'All permissions',
            'ownerPass' => 'owner',
            'permissions' => PdfPermissions::all(),
            'rows' => [
                ['Printing (high quality)', true],
                ['Copying text & images', true],
                ['Modifying content', true],
                ['Adding annotations', true],
                ['Filling form fields', true],
                ['Assembling pages', true],
            ],
            'userPass' => '',
        ],

        // ── 7. Draft printing (low quality only) ─────────────────────────────
        'draft-print' => [
            'description' => 'Only low-quality (draft) printing is allowed; high-quality print is blocked.',
            'label' => 'Draft printing only',
            'ownerPass' => 'owner',
            'permissions' => PdfPermissions::none()->allowPrinting(highQuality: false),
            'rows' => [
                ['Printing (draft / low quality)', true],
                ['Printing (high quality)', false],
                ['Copying text & images', false],
                ['Modifying content', false],
                ['Adding annotations', false],
                ['Filling form fields', false],
            ],
            'userPass' => '',
        ],

        // ── 3. Password-protected, view only ─────────────────────────────────
        'locked' => [
            'description' => 'A user password is required to open. No other operations are permitted.',
            'label' => 'Password-protected, view only',
            'ownerPass' => 'owner',
            'permissions' => PdfPermissions::none(),
            'rows' => [
                ['Printing (high quality)', false],
                ['Copying text & images', false],
                ['Modifying content', false],
                ['Adding annotations', false],
                ['Filling form fields', false],
                ['Assembling pages', false],
            ],
            'userPass' => 'open',
        ],

        // ── 5. Print + copy ───────────────────────────────────────────────────
        'print-copy' => [
            'description' => 'Users may print and copy text/images but not edit or annotate.',
            'label' => 'Print and copy',
            'ownerPass' => 'owner',
            'permissions' => PdfPermissions::none()->allowPrinting()->allowCopying(),
            'rows' => [
                ['Printing (high quality)', true],
                ['Copying text & images', true],
                ['Modifying content', false],
                ['Adding annotations', false],
                ['Filling form fields', false],
                ['Assembling pages', false],
            ],
            'userPass' => '',
        ],

        // ── 4. Print only ─────────────────────────────────────────────────────
        'print-only' => [
            'description' => 'Users may print in high quality but cannot copy, edit or annotate.',
            'label' => 'Print only',
            'ownerPass' => 'owner',
            'permissions' => PdfPermissions::none()->allowPrinting(),
            'rows' => [
                ['Printing (high quality)', true],
                ['Copying text & images', false],
                ['Modifying content', false],
                ['Adding annotations', false],
                ['Filling form fields', false],
                ['Assembling pages', false],
            ],
            'userPass' => '',
        ],

        // ── 6. Annotate + fill forms (read-only content) ─────────────────────
        'review' => [
            'description' => 'Content cannot be edited or copied, but reviewers may add comments and fill forms.',
            'label' => 'Review (annotate & fill forms)',
            'ownerPass' => 'owner',
            'permissions' => PdfPermissions::none()->allowAnnotations()->allowFormFilling(),
            'rows' => [
                ['Printing (high quality)', false],
                ['Copying text & images', false],
                ['Modifying content', false],
                ['Adding annotations', true],
                ['Filling form fields', true],
                ['Assembling pages', false],
            ],
            'userPass' => '',
        ],

        // ── 2. View only (no password to open) ───────────────────────────────
        'view-only' => [
            'description' => 'Users can open and read the document but cannot print, copy or edit anything.',
            'label' => 'View only',
            'ownerPass' => 'owner',
            'permissions' => PdfPermissions::none(),
            'rows' => [
                ['Printing (high quality)', false],
                ['Copying text & images', false],
                ['Modifying content', false],
                ['Adding annotations', false],
                ['Filling form fields', false],
                ['Assembling pages', false],
            ],
            'userPass' => '',
        ],
    ];
}

function generate(): void
{
    $all = scenarios();
    $key = $_GET['scenario'] ?? 'print-only';
    $scenario = $all[$key] ?? $all['print-only'];

    $helv = Type1FontMetrics::helvetica();
    $helvB = Type1FontMetrics::helveticaBold();

    $document = (new PdfDocumentBuilder())
        ->info(
            (new PdfDocumentInfo())
                ->title('Permissions: ' . $scenario['label'])
                ->author('phppdf'),
        )
        ->encrypt(
            (new PdfEncryptionConfig())
                ->userPassword($scenario['userPass'])
                ->ownerPassword($scenario['ownerPass'])
                ->permissions($scenario['permissions']),
        )
        ->page(static function (PdfPageBuilder $page) use ($scenario, $helv, $helvB, $all): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica')
                ->useType1Font('FB', 'Helvetica-Bold')
                ->content(static function (PdfContentStreamBuilder $s) use ($scenario, $helv, $helvB, $all): void {

                    $lm = 72.0;
                    $y = 790.0;

                    // ── Title ─────────────────────────────────────────────────
                    $heading = TextBox::create(
                        'PDF Permissions — ' . $scenario['label'],
                        $helvB,
                        16,
                        451,
                    );
                    $s->drawTextBox($heading, fontName: 'FB', x: $lm, y: $y);
                    $y -= $heading->getHeight() + 10;

                    // ── Description ───────────────────────────────────────────
                    $desc = TextBox::create($scenario['description'], $helv, 11, 451, 14);
                    $s->drawTextBox($desc, fontName: 'F1', x: $lm, y: $y);
                    $y -= $desc->getHeight() + 18;

                    // ── Credentials table ─────────────────────────────────────
                    $s->beginText()->setFont('FB', 11)
                      ->setTextMatrix(Matrix::translate($lm, $y))
                      ->showText('Encryption credentials')->endText();
                    $y -= 14;

                    $borderColor = Color::fromHex('#aaaacc');
                    $altBg = Color::fromHex('#f5f7ff');

                    $credentials = [
                        ['User password (to open)', $scenario['userPass'] === '' ? '(none)' : '"' . $scenario['userPass'] . '"'],
                        ['Owner password (to change settings)', '"' . $scenario['ownerPass'] . '"'],
                        ['Encryption', 'AES-128 (Standard Security Handler R=4)'],
                    ];

                    $credBuilder = TableBuilder::create($lm, $y)
                        ->columns([180, 260])
                        ->font('F1', 10, $helv)
                        ->padding(8, 4, 8, 4)
                        ->border($borderColor, 0.5);

                    foreach ($credentials as $i => [$label, $value]) {
                        $row = TableRow::cells([
                            TableCell::text($label)->font('FB', 10, $helvB),
                            TableCell::text($value),
                        ]);

                        if ($i % 2 === 0) {
                            $row = $row->background($altBg);
                        }

                        $credBuilder->addRow($row);
                    }

                    $y = $credBuilder->draw($s) - 20;

                    // ── Permissions table ─────────────────────────────────────
                    $s->beginText()->setFont('FB', 11)
                      ->setTextMatrix(Matrix::translate($lm, $y))
                      ->showText('Permission flags')->endText();
                    $y -= 14;

                    $headerBg = Color::fromHex('#3355aa');

                    $permBuilder = TableBuilder::create($lm, $y)
                        ->columns([310, 130])
                        ->font('F1', 10, $helv)
                        ->padding(8, 4, 8, 4)
                        ->border($borderColor, 0.5)
                        ->addRow(
                            TableRow::cells([
                                TableCell::text('Permission')->font('FB', 10, $helvB)->background($headerBg)->textColor(
                                    Color::white(),
                                ),
                                TableCell::text('Status')->font('FB', 10, $helvB)->background($headerBg)->textColor(
                                    Color::white(),
                                )->align(
                                    TextAlign::Center,
                                ),
                            ]),
                        );

                    foreach ($scenario['rows'] as $i => [$label, $allowed]) {
                        $statusText = $allowed
                            ? 'Allowed'
                            : 'Denied';
                        $statusColor = $allowed
                            ? Color::fromHex('#1a7a1a')
                            : Color::fromHex('#cc0000');
                        $rowBg = $i % 2 === 0
                            ? Color::fromHex('#f9f9f9')
                            : Color::white();

                        $permBuilder->addRow(
                            TableRow::cells([
                                TableCell::text($label)->background($rowBg),
                                TableCell::text($statusText)->font('FB', 10, $helvB)->textColor(
                                    $statusColor,
                                )->background(
                                    $rowBg,
                                )->align(
                                    TextAlign::Center,
                                ),
                            ]),
                        );
                    }

                    $y = $permBuilder->draw($s) - 20;

                    // ── How to open note ──────────────────────────────────────
                    if ($scenario['userPass'] !== '') {
                        $note = TextBox::create(
                            'Note: this document requires the user password "'
                            . $scenario['userPass'] . '" to open. '
                            . 'The owner password "' . $scenario['ownerPass'] . '" unlocks all restrictions.',
                            $helv,
                            10,
                            451,
                            13,
                        );
                        $s->saveGraphicsState()
                          ->fillColor(Color::fromHex('#fff8e1'))
                          ->strokeColor(Color::fromHex('#f0c030'))
                          ->setLineWidth(0.5)
                          ->rectangle($lm, $y - $note->getHeight() - 10, 440.0, $note->getHeight() + 16)
                          ->fillAndStroke()
                          ->restoreGraphicsState();
                        $s->drawTextBox($note, fontName: 'F1', x: $lm + 6, y: $y - 6);
                        $y -= $note->getHeight() + 26;
                    }

                    // ── Scenario index at the bottom ──────────────────────────
                    $y = 120.0;
                    $s->saveGraphicsState()
                      ->strokeColor(Color::fromHex('#cccccc'))
                      ->setLineWidth(0.5)
                      ->moveTo($lm, $y + 14)->lineTo($lm + 451, $y + 14)->stroke()
                      ->restoreGraphicsState();

                    $s->beginText()->setFont('FB', 9)
                      ->fillColor(Color::rgb(0.3, 0.3, 0.3))
                      ->setTextMatrix(Matrix::translate($lm, $y))
                      ->showText('Other scenarios in this example (append ?scenario=... to the URL):')
                      ->endText();
                    $y -= 13;

                    $keys = array_keys($all);
                    $perRow = 3;

                    foreach (array_chunk($keys, $perRow) as $chunk) {
                        $cx = $lm;

                        foreach ($chunk as $k) {
                            $s->beginText()->setFont('F1', 9)
                              ->fillColor(Color::rgb(0, 0, 0.7))
                              ->setTextMatrix(Matrix::translate($cx, $y))
                              ->showText($k)
                              ->endText();
                            $cx += 150;
                        }

                        $y -= 12;
                    }
                });
        })
        ->build();

    $output = new PdfMemoryOutput();
    (new PdfDocumentSerializer($output))->writeDocument($document);

    header('Content-Type: application/pdf');
    header('Content-Length: ' . $output->position());
    header('Content-Disposition: inline; filename="30-permissions-' . $key . '.pdf"');
    echo $output->getContent();
}

(static function (): void {
    $autoloader = require __DIR__ . '/../../../vendor/autoload.php';

    setupEnvironment($autoloader);
    generate();
})();
