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
use PhpPdf\Table\TableBuilder;
use PhpPdf\Table\TableCell;
use PhpPdf\Table\TableRow;
use PhpPdf\Table\TableVerticalAlign;
use PhpPdf\Text\TextAlign;
use PhpPdf\Text\TextBox;

function heading(PdfContentStreamBuilder $s, float $x, float $y, string $text): void
{
    $s->beginText()->setFont('F2', 10)
      ->setTextMatrix(Matrix::translate($x, $y))
      ->showText($text)->endText();
}

function generate(): void
{
    $helv = Type1FontMetrics::helvetica();
    $helvB = Type1FontMetrics::helveticaBold();

    $document = (new PdfDocumentBuilder())
        ->info((new PdfDocumentInfo())->title('Tables')->author('phppdf'))
        ->page(static function (PdfPageBuilder $page) use ($helv, $helvB): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica')
                ->useType1Font('F2', 'Helvetica-Bold')
                ->content(static function (PdfContentStreamBuilder $s) use ($helv, $helvB): void {
                    $marginL = 72.0;
                    $pageW = 451.0;
                    $y = 800.0;

                    $title = TextBox::create('Table Builder  (page 1 of 2)', $helvB, 18, $pageW);
                    $s->drawTextBox($title, fontName: 'F2', x: $marginL, y: $y);
                    $y -= $title->getHeight() + 20;

                    // =========================================================
                    // 1. Invoice-style table — header row, alternating rows,
                    //    right-aligned numbers, text wrapping in description
                    // =========================================================
                    heading($s, $marginL, $y, '1. Invoice table (header, alternating rows, alignment)');
                    $y -= 14;

                    $headerBg = Color::fromHex('#1a3a5c');
                    $altBg = Color::fromHex('#f0f5fa');
                    $borderCol = Color::fromHex('#99aabb');

                    $y = TableBuilder::create($marginL, $y)
                        ->columns([160, 180, 55, 55])
                        ->font('F1', 9.5, $helv)
                        ->padding(5, 6, 4, 6)
                        ->border($borderCol, 0.5)
                        ->addRow(
                            TableRow::cells([
                                TableCell::text('Description')
                                    ->font('F2', 9.5, $helvB)
                                    ->background($headerBg)
                                    ->textColor(Color::white()),
                                TableCell::text('Notes')
                                    ->font('F2', 9.5, $helvB)
                                    ->background($headerBg)
                                    ->textColor(Color::white()),
                                TableCell::text('Qty')
                                    ->font('F2', 9.5, $helvB)
                                    ->background($headerBg)
                                    ->textColor(Color::white())
                                    ->align(TextAlign::Right),
                                TableCell::text('Price')
                                    ->font('F2', 9.5, $helvB)
                                    ->background($headerBg)
                                    ->textColor(Color::white())
                                    ->align(TextAlign::Right),
                            ]),
                        )
                        ->addRow(
                            TableRow::cells([
                                TableCell::text('Premium Widget A'),
                                TableCell::text('High-strength aluminium alloy. Suitable for industrial use.'),
                                TableCell::text('3')->align(TextAlign::Right),
                                TableCell::text('$19.99')->align(TextAlign::Right),
                            ])->background($altBg),
                        )
                        ->addRow(
                            TableRow::cells([
                                TableCell::text('Standard Bolt Pack'),
                                TableCell::text('M8 stainless steel, 50-piece assorted pack.'),
                                TableCell::text('10')->align(TextAlign::Right),
                                TableCell::text('$4.49')->align(TextAlign::Right),
                            ]),
                        )
                        ->addRow(
                            TableRow::cells([
                                TableCell::text('Precision Bearing Set'),
                                TableCell::text('Low-friction sealed bearings, ABEC-7 grade.'),
                                TableCell::text('2')->align(TextAlign::Right),
                                TableCell::text('$34.00')->align(TextAlign::Right),
                            ])->background($altBg),
                        )
                        ->addRow(
                            TableRow::cells([
                                TableCell::text('Cable Harness'),
                                TableCell::text('Custom wiring harness, 12-way, 1 m, with connector.'),
                                TableCell::text('1')->align(TextAlign::Right),
                                TableCell::text('$89.50')->align(TextAlign::Right),
                            ]),
                        )
                        ->addRow(
                            TableRow::cells([
                                TableCell::text(''),
                                TableCell::text(''),
                                TableCell::text('Total')
                                    ->font('F2', 9.5, $helvB)
                                    ->align(TextAlign::Right),
                                TableCell::text('$217.47')
                                    ->font('F2', 9.5, $helvB)
                                    ->align(TextAlign::Right)
                                    ->background(Color::fromHex('#d0e4f4')),
                            ]),
                        )
                        ->draw($s);

                    $y -= 24;

                    // =========================================================
                    // 2. Colour palette — per-cell backgrounds, no borders
                    // =========================================================
                    heading($s, $marginL, $y, '2. Per-cell background colours, no borders');
                    $y -= 14;

                    $swatches = [
                        ['#e8f4e8', '#c8e6c9', '#a5d6a7', '#81c784'],
                        ['#fff9e6', '#fff3cd', '#ffe082', '#ffd54f'],
                        ['#fce4ec', '#f8bbd0', '#f48fb1', '#f06292'],
                        ['#e3f2fd', '#bbdefb', '#90caf9', '#64b5f6'],
                    ];

                    $y = (static function () use ($s, $marginL, $y, $helv, $swatches): float {
                        $builder = TableBuilder::create($marginL, $y)
                            ->columns([110, 110, 110, 120])
                            ->font('F1', 8.5, $helv)
                            ->paddingAll(4);

                        foreach ($swatches as $rowHexes) {
                            $cells = array_map(
                                static fn (string $hex) => TableCell::text($hex)
                                    ->background(Color::fromHex($hex))
                                    ->align(TextAlign::Center),
                                $rowHexes,
                            );
                            $builder->addRow(TableRow::cells($cells));
                        }

                        return $builder->draw($s);
                    })();

                    $y -= 24;

                    // =========================================================
                    // 3. Text alignment showcase
                    // =========================================================
                    heading($s, $marginL, $y, '3. Text alignment per cell');
                    $y -= 14;

                    $sample = 'Pack my box with five dozen liquor jugs.';

                    $y = TableBuilder::create($marginL, $y)
                        ->columns([110, 110, 110, 120])
                        ->font('F1', 9, $helv)
                        ->padding(5, 6, 4, 6)
                        ->border(Color::fromHex('#cccccc'), 0.5)
                        ->addRow(
                            TableRow::cells([
                                TableCell::text('Left (default)'),
                                TableCell::text('Center')->align(TextAlign::Center),
                                TableCell::text('Right')->align(TextAlign::Right),
                                TableCell::text('Justify')->align(TextAlign::Justify),
                            ])->background(Color::fromHex('#eeeeee')),
                        )
                        ->addRow(
                            TableRow::cells([
                                TableCell::text($sample),
                                TableCell::text($sample)->align(TextAlign::Center),
                                TableCell::text($sample)->align(TextAlign::Right),
                                TableCell::text($sample)->align(TextAlign::Justify),
                            ]),
                        )
                        ->draw($s);

                    $y -= 24;

                    // =========================================================
                    // 4. Outer-only border / inner-only border variants
                    // =========================================================
                    heading($s, $marginL, $y, '4. Outer-only border (left) vs inner-only border (right)');
                    $y -= 14;

                    $cols3 = [75, 75, 70];
                    $rows3 = [
                        ['Alpha', 'Beta', 'Gamma'],
                        ['Delta', 'Epsilon', 'Zeta'],
                        ['Eta', 'Theta', 'Iota'],
                    ];

                    $makeRows = static fn (array $data): array => array_map(
                        static fn ($r) => TableRow::cells(array_map(static fn ($t) => TableCell::text($t), $r)),
                        $data,
                    );

                    $leftBuilder = TableBuilder::create($marginL, $y)
                        ->columns($cols3)
                        ->font('F1', 9, $helv)
                        ->padding(4, 5, 3, 5)
                        ->border(Color::fromHex('#555555'), 0.75, outer: true, inner: false);

                foreach ($makeRows($rows3) as $row) {
                    $leftBuilder->addRow($row);
                }

                    $rightBuilder = TableBuilder::create($marginL + 260, $y)
                        ->columns($cols3)
                        ->font('F1', 9, $helv)
                        ->padding(4, 5, 3, 5)
                        ->border(Color::fromHex('#555555'), 0.75, outer: false, inner: true);

                foreach ($makeRows($rows3) as $row) {
                    $rightBuilder->addRow($row);
                }

                    $y1 = $leftBuilder->draw($s);
                    $y2 = $rightBuilder->draw($s);
                });
        })
        ->page(static function (PdfPageBuilder $page) use ($helv, $helvB): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica')
                ->useType1Font('F2', 'Helvetica-Bold')
                ->content(static function (PdfContentStreamBuilder $s) use ($helv, $helvB): void {
                    $marginL = 72.0;
                    $pageW = 451.0;
                    $y = 800.0;

                    $title = TextBox::create('Table Builder  (page 2 of 2)', $helvB, 18, $pageW);
                    $s->drawTextBox($title, fontName: 'F2', x: $marginL, y: $y);
                    $y -= $title->getHeight() + 20;

                    // =========================================================
                    // 5. Colspan — merged header cells and a footer totals row
                    // =========================================================
                    heading($s, $marginL, $y, '5. Colspan');
                    $y -= 14;

                    $hdrBg = Color::fromHex('#2c5282');
                    $subBg = Color::fromHex('#4a7ab5');
                    $totBg = Color::fromHex('#ebf4ff');
                    $border = Color::fromHex('#7bafd4');

                    $y = TableBuilder::create($marginL, $y)
                        ->columns([90, 90, 90, 90, 50])
                        ->font('F1', 9, $helv)
                        ->padding(5, 5, 4, 5)
                        ->border($border, 0.5)
                        ->addRow(TableRow::cells([
                            TableCell::text('Product')->colspan(2)
                                ->font('F2', 9, $helvB)->background($hdrBg)->textColor(Color::white())
                                ->align(TextAlign::Center),
                            TableCell::text('Q1 / Q2 Sales')->colspan(2)
                                ->font('F2', 9, $helvB)->background($hdrBg)->textColor(Color::white())
                                ->align(TextAlign::Center),
                            TableCell::text('Total')
                                ->font('F2', 9, $helvB)->background($hdrBg)->textColor(Color::white())
                                ->align(TextAlign::Center),
                        ]))
                        ->addRow(TableRow::cells([
                            TableCell::text('SKU')->font('F2', 9, $helvB)->background($subBg)->textColor(
                                Color::white(),
                            ),
                            TableCell::text('Name')->font('F2', 9, $helvB)->background($subBg)->textColor(
                                Color::white(),
                            ),
                            TableCell::text('Q1')->font('F2', 9, $helvB)->background($subBg)->textColor(
                                Color::white(),
                            )->align(
                                TextAlign::Right,
                            ),
                            TableCell::text('Q2')->font('F2', 9, $helvB)->background($subBg)->textColor(
                                Color::white(),
                            )->align(
                                TextAlign::Right,
                            ),
                            TableCell::text('')->background($subBg),
                        ]))
                        ->addRow(TableRow::cells([
                            TableCell::text('A-001'),
                            TableCell::text('Widget Alpha'),
                            TableCell::text('142')->align(TextAlign::Right),
                            TableCell::text('189')->align(TextAlign::Right),
                            TableCell::text('331')->align(TextAlign::Right),
                        ]))
                        ->addRow(TableRow::cells([
                            TableCell::text('A-002'),
                            TableCell::text('Widget Beta'),
                            TableCell::text('98')->align(TextAlign::Right),
                            TableCell::text('110')->align(TextAlign::Right),
                            TableCell::text('208')->align(TextAlign::Right),
                        ])->background(Color::fromHex('#f0f7ff')))
                        ->addRow(TableRow::cells([
                            TableCell::text('Total')->colspan(2)
                                ->font('F2', 9, $helvB)->background($totBg)->align(TextAlign::Right),
                            TableCell::text('240')->font('F2', 9, $helvB)->background($totBg)->align(TextAlign::Right),
                            TableCell::text('299')->font('F2', 9, $helvB)->background($totBg)->align(TextAlign::Right),
                            TableCell::text('539')->font('F2', 9, $helvB)->background($totBg)->align(TextAlign::Right),
                        ]))
                        ->draw($s);

                    $y -= 24;

                    // =========================================================
                    // 6. Rowspan + vertical alignment
                    // =========================================================
                    heading($s, $marginL, $y, '6. Rowspan + vertical alignment (Top / Middle / Bottom)');
                    $y -= 14;

                    $catBg = Color::fromHex('#e8f0fe');
                    $border2 = Color::fromHex('#aab8d4');

                    TableBuilder::create($marginL, $y)
                        ->columns([90, 160, 70, 130])
                        ->font('F1', 9, $helv)
                        ->padding(5, 6, 4, 6)
                        ->border($border2, 0.5)
                        ->addRow(TableRow::cells([
                            TableCell::text('Category')->font('F2', 9, $helvB)->background(
                                Color::fromHex('#1a3a5c'),
                            )->textColor(
                                Color::white(),
                            ),
                            TableCell::text('Description')->font('F2', 9, $helvB)->background(
                                Color::fromHex('#1a3a5c'),
                            )->textColor(
                                Color::white(),
                            ),
                            TableCell::text('Price')->font('F2', 9, $helvB)->background(
                                Color::fromHex('#1a3a5c'),
                            )->textColor(
                                Color::white(),
                            )->align(
                                TextAlign::Right,
                            ),
                            TableCell::text('Vertical align demo')->font('F2', 9, $helvB)->background(
                                Color::fromHex('#1a3a5c'),
                            )->textColor(
                                Color::white(),
                            ),
                        ]))
                        ->addRow(TableRow::cells([
                            TableCell::text('Hardware')->rowspan(2)
                                ->background($catBg)->font('F2', 9, $helvB)
                                ->align(TextAlign::Center)->verticalAlign(TableVerticalAlign::Middle),
                            TableCell::text('M8 bolt pack, 50 pcs stainless steel'),
                            TableCell::text('$4.49')->align(TextAlign::Right),
                            TableCell::text('Top (default)')->verticalAlign(TableVerticalAlign::Top)
                                ->background(Color::fromHex('#fffde7')),
                        ]))
                        ->addRow(TableRow::cells([
                            TableCell::text('Precision bearing set, ABEC-7, sealed'),
                            TableCell::text('$34.00')->align(TextAlign::Right),
                            TableCell::text('Middle — text centred vertically in the taller cell.')
                                ->verticalAlign(TableVerticalAlign::Middle)
                                ->background(Color::fromHex('#e8f5e9')),
                        ]))
                        ->addRow(TableRow::cells([
                            TableCell::text('Electronics')->rowspan(3)
                                ->background($catBg)->font('F2', 9, $helvB)
                                ->align(TextAlign::Center)->verticalAlign(TableVerticalAlign::Bottom),
                            TableCell::text('USB-C cable, 2 m, braided nylon'),
                            TableCell::text('$9.99')->align(TextAlign::Right),
                            TableCell::text('Bottom — text pushed to the lower padding edge.')
                                ->verticalAlign(TableVerticalAlign::Bottom)
                                ->background(Color::fromHex('#fce4ec')),
                        ]))
                        ->addRow(TableRow::cells([
                            TableCell::text('Micro-controller dev board, 32-bit ARM'),
                            TableCell::text('$22.50')->align(TextAlign::Right),
                            TableCell::text('Top')->verticalAlign(TableVerticalAlign::Top)
                                ->background(Color::fromHex('#fffde7')),
                        ]))
                        ->addRow(TableRow::cells([
                            TableCell::text('OLED display module, 128×64, I²C'),
                            TableCell::text('$7.80')->align(TextAlign::Right),
                            TableCell::text('Middle')->verticalAlign(TableVerticalAlign::Middle)
                                ->background(Color::fromHex('#e8f5e9')),
                        ]))
                        ->draw($s);
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
