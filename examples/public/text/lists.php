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
use PhpPdf\Text\ListBox;
use PhpPdf\Text\TextBox;

function generate(): void
{
    $helv = Type1FontMetrics::helvetica();
    $helvB = Type1FontMetrics::helveticaBold();

    $document = (new PdfDocumentBuilder())
        ->info(
            (new PdfDocumentInfo())
                ->title('Bullet & Numbered Lists')
                ->author('phppdf'),
        )
        ->page(static function (PdfPageBuilder $page) use ($helv, $helvB): void {
            // Page 2 — indent / gap control
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica')
                ->useType1Font('FB', 'Helvetica-Bold')
                ->content(static function (PdfContentStreamBuilder $s) use ($helv, $helvB): void {

                    $lm = 72.0;
                    $y = 790.0;
                    $colW = 130.0; // three equal columns
                    $gap = 18.0;

                    // ── Title ────────────────────────────────────────────────
                    $title = TextBox::create('Controlling the gap between marker and text', $helvB, 14, 451);
                    $s->drawTextBox($title, fontName: 'FB', x: $lm, y: $y);
                    $y -= $title->getHeight() + 6;

                    $intro = TextBox::create(
                        'The indent parameter sets the distance from the list\'s x position to the text '
                        . 'column. The visible gap between marker and text is indent - markerWidth. '
                        . 'Smaller indent -> tighter gap; larger indent -> more breathing room.',
                        $helv,
                        10,
                        451,
                        13,
                    );
                    $s->drawTextBox($intro, fontName: 'F1', x: $lm, y: $y);
                    $y -= $intro->getHeight() + 20;

                    // ── Bullet — three indent values side by side ─────────────
                    $s->beginText()->setFont('FB', 11)
                      ->setTextMatrix(Matrix::translate($lm, $y))
                      ->showText('Bullet list — varying indent')->endText();
                    $y -= 14;

                    $items = ['First item', 'Second item', 'A longer item that wraps here'];

                    $configs = [
                        ['label' => 'indent: 10 pt  (tight)', 'indent' => 10.0],
                        ['label' => 'indent: 22 pt  (default)', 'indent' => 22.0],
                        ['label' => 'indent: 40 pt  (wide)', 'indent' => 40.0],
                    ];

                    // cap: distance from baseline to top of capital letters (~80 % of font size)
                    $cap = 11 * 0.8;
                    $pad = 4.0; // padding below last descender

                    $maxH = 0.0;

                    foreach ($configs as $k => $cfg) {
                        $bx = $lm + $k * ($colW + $gap);
                        $list = ListBox::bullet(
                            items: $items,
                            metrics: $helv,
                            fontSize: 11,
                            maxWidth: $colW,
                            indent: $cfg['indent'],
                        );

                        // Column label (drawn above the box)
                        $s->beginText()->setFont('FB', 9)
                          ->setTextMatrix(Matrix::translate($bx, $y))
                          ->showText($cfg['label'])->endText();

                        // Box: top is baseline + cap height; bottom is below last descender
                        $listY = $y - 14;
                        $boxTop = $listY + $cap;
                        $boxBottom = $listY - $list->getHeight() - $pad;
                        $s->saveGraphicsState()
                          ->fillColor(Color::fromHex('#f5f5f5'))
                          ->rectangle($bx, $boxBottom, $colW, $boxTop - $boxBottom)
                          ->fill()
                          ->restoreGraphicsState();

                        // Dashed marker-column indicator
                        $s->saveGraphicsState()
                          ->strokeColor(Color::fromHex('#cccccc'))
                          ->setLineWidth(0.4)
                          ->setDashPattern([2, 2], 0)
                          ->moveTo($bx + $cfg['indent'], $boxTop)
                          ->lineTo($bx + $cfg['indent'], $boxBottom)
                          ->stroke()
                          ->restoreGraphicsState();

                        $s->drawListBox($list, fontName: 'F1', x: $bx, y: $listY);
                        $maxH = max($maxH, $list->getHeight());
                    }

                    $y -= 14 + $cap + $maxH + $pad + 28;

                    // ── Numbered — three indent values side by side ───────────
                    $s->beginText()->setFont('FB', 11)
                      ->setTextMatrix(Matrix::translate($lm, $y))
                      ->showText('Numbered list — varying indent')->endText();
                    $y -= 14;

                    $numItems = ['First step', 'Second step', 'A longer step description that wraps'];

                    $numConfigs = [
                        ['label' => 'indent: 14 pt  (tight)', 'indent' => 14.0],
                        ['label' => 'auto indent', 'indent' => 0.0], // auto from marker width
                        ['label' => 'indent: 40 pt  (wide)', 'indent' => 40.0],
                    ];

                    $maxH = 0.0;

                    foreach ($numConfigs as $k => $cfg) {
                        $bx = $lm + $k * ($colW + $gap);
                        $list = ListBox::numbered(
                            items: $numItems,
                            metrics: $helv,
                            fontSize: 11,
                            maxWidth: $colW,
                            indent: $cfg['indent'],
                        );

                        $s->beginText()->setFont('FB', 9)
                          ->setTextMatrix(Matrix::translate($bx, $y))
                          ->showText($cfg['label'])->endText();

                        $listY = $y - 14;
                        $boxTop = $listY + $cap;
                        $boxBottom = $listY - $list->getHeight() - $pad;
                        $s->saveGraphicsState()
                          ->fillColor(Color::fromHex('#f5f5f5'))
                          ->rectangle($bx, $boxBottom, $colW, $boxTop - $boxBottom)
                          ->fill()
                          ->restoreGraphicsState();

                        $s->saveGraphicsState()
                          ->strokeColor(Color::fromHex('#cccccc'))
                          ->setLineWidth(0.4)
                          ->setDashPattern([2, 2], 0)
                          ->moveTo($bx + $list->getIndent(), $boxTop)
                          ->lineTo($bx + $list->getIndent(), $boxBottom)
                          ->stroke()
                          ->restoreGraphicsState();

                        $s->drawListBox($list, fontName: 'F1', x: $bx, y: $listY);
                        $maxH = max($maxH, $list->getHeight());
                    }

                    $y -= 14 + $cap + $maxH + $pad + 28;

                    // ── Caption explaining the dashed line ────────────────────
                    $s->saveGraphicsState()
                      ->strokeColor(Color::fromHex('#cccccc'))
                      ->setLineWidth(0.4)
                      ->setDashPattern([2, 2], 0)
                      ->moveTo($lm, $y + 10)->lineTo($lm + 20, $y + 10)->stroke()
                      ->restoreGraphicsState();
                    $s->beginText()->setFont('F1', 9)
                      ->fillColor(Color::rgb(0.4, 0.4, 0.4))
                      ->setTextMatrix(Matrix::translate($lm + 24, $y + 7))
                      ->showText('dashed line marks the text-column boundary (x + indent)')
                      ->endText();
                });
        })
        ->page(static function (PdfPageBuilder $page) use ($helv, $helvB): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica')
                ->useType1Font('FB', 'Helvetica-Bold')
                ->content(static function (PdfContentStreamBuilder $s) use ($helv, $helvB): void {

                    $lm = 72.0; // left margin
                    $colW = 210.0; // column width
                    $y = 790.0;

                    // ── Title ────────────────────────────────────────────────
                    $title = TextBox::create('Bullet & Numbered Lists', $helvB, 16, 451);
                    $s->drawTextBox($title, fontName: 'FB', x: $lm, y: $y);
                    $y -= $title->getHeight() + 20;

                    // ── Section 1: simple bullet list ─────────────────────────
                    $s->beginText()->setFont('FB', 11)
                      ->setTextMatrix(Matrix::translate($lm, $y))
                      ->showText('Simple bullet list')->endText();
                    $y -= 16;

                    $bullet = ListBox::bullet(
                        items: [
                            'First item in the list',
                            'Second item',
                            'A third item with a body that is long enough to wrap onto a second line within the column',
                            'Fourth item',
                        ],
                        metrics: $helv,
                        fontSize: 11,
                        maxWidth: $colW,
                    );

                    $s->drawListBox($bullet, fontName: 'F1', x: $lm, y: $y);
                    $y -= $bullet->getHeight() + 22;

                    // ── Section 2: custom bullet character ───────────────────
                    $s->beginText()->setFont('FB', 11)
                      ->setTextMatrix(Matrix::translate($lm, $y))
                      ->showText('Custom bullet character')->endText();
                    $y -= 16;

                    $arrowList = ListBox::bullet(
                        items: [
                            'Arrow bullets use any UTF-8 character',
                            'Indent is set explicitly to 18 pt',
                            'Item spacing adds a small gap between items',
                        ],
                        metrics: $helv,
                        fontSize: 11,
                        maxWidth: $colW,
                        bullet: '->',
                        indent: 22.0,
                        itemSpacing: 3.0,
                    );

                    $s->drawListBox($arrowList, fontName: 'F1', x: $lm, y: $y);
                    $y -= $arrowList->getHeight() + 22;

                    // ── Section 3: numbered list ──────────────────────────────
                    $s->beginText()->setFont('FB', 11)
                      ->setTextMatrix(Matrix::translate($lm, $y))
                      ->showText('Numbered list')->endText();
                    $y -= 16;

                    $numbered = ListBox::numbered(
                        items: [
                            'Install dependencies with composer install',
                            'Configure your web server to point at the project root',
                            'Copy .env.example to .env and fill in your settings',
                            'Run the database migrations',
                            'Open the application in your browser',
                        ],
                        metrics: $helv,
                        fontSize: 11,
                        maxWidth: $colW,
                        itemSpacing: 2.0,
                    );

                    $s->drawListBox($numbered, fontName: 'F1', x: $lm, y: $y);
                    $y -= $numbered->getHeight() + 22;

                    // ── Section 4: numbered with custom start / format ────────
                    $s->beginText()->setFont('FB', 11)
                      ->setTextMatrix(Matrix::translate($lm, $y))
                      ->showText('Custom format & start number')->endText();
                    $y -= 16;

                    $custom = ListBox::numbered(
                        items: ['Continued from previous section', 'Another step', 'Final step'],
                        metrics: $helv,
                        fontSize: 11,
                        maxWidth: $colW,
                        startAt: 6,
                        numberFormat: '(%d)',
                        itemSpacing: 2.0,
                    );

                    $s->drawListBox($custom, fontName: 'F1', x: $lm, y: $y);

                    // ── Right column: two lists side by side ──────────────────
                    $rx = $lm + $colW + 28;
                    $colR = 451.0 - $colW - 28; // remaining width
                    $ry = 790.0 - $title->getHeight() - 20;

                    $s->beginText()->setFont('FB', 11)
                      ->setTextMatrix(Matrix::translate($rx, $ry))
                      ->showText('Nested / indented lists')->endText();
                    $ry -= 16;

                    // Simulate nesting: draw a parent list then an indented child
                    $parent = ListBox::bullet(
                        items: ['Fruits', 'Vegetables'],
                        metrics: $helv,
                        fontSize: 11,
                        maxWidth: $colR,
                    );
                    $fruits = ListBox::bullet(
                        items: ['Apple', 'Banana', 'Mango'],
                        metrics: $helv,
                        fontSize: 10,
                        maxWidth: $colR - 16,
                        bullet: '-',
                        indent: 14.0,
                    );
                    $vegs = ListBox::bullet(
                        items: ['Carrot', 'Broccoli'],
                        metrics: $helv,
                        fontSize: 10,
                        maxWidth: $colR - 16,
                        bullet: '-',
                        indent: 14.0,
                    );

                    // Draw: Fruits bullet, then child list, then Vegetables, child list
                    $fruitsParent = ListBox::bullet(items: ['Fruits'], metrics: $helv, fontSize: 11, maxWidth: $colR);
                    $s->drawListBox($fruitsParent, fontName: 'F1', x: $rx, y: $ry);
                    $ry -= $fruitsParent->getLineHeight();
                    $s->drawListBox($fruits, fontName: 'F1', x: $rx + 16, y: $ry);
                    $ry -= $fruits->getHeight() + 2;

                    $vegsParent = ListBox::bullet(items: ['Vegetables'], metrics: $helv, fontSize: 11, maxWidth: $colR);
                    $s->drawListBox($vegsParent, fontName: 'F1', x: $rx, y: $ry);
                    $ry -= $vegsParent->getLineHeight();
                    $s->drawListBox($vegs, fontName: 'F1', x: $rx + 16, y: $ry);
                    $ry -= $vegs->getHeight() + 22;

                    // ── Right column: mixed line heights ──────────────────────
                    $s->beginText()->setFont('FB', 11)
                      ->setTextMatrix(Matrix::translate($rx, $ry))
                      ->showText('Larger line height (relaxed)')->endText();
                    $ry -= 16;

                    $relaxed = ListBox::numbered(
                        items: [
                            'Step one: prepare your workspace',
                            'Step two: write the code',
                            'Step three: run the tests',
                        ],
                        metrics: $helv,
                        fontSize: 11,
                        maxWidth: $colR,
                        lineHeight: 18.0,
                    );

                    $s->drawListBox($relaxed, fontName: 'F1', x: $rx, y: $ry);
                    $ry -= $relaxed->getHeight() + 22;

                    // ── Shaded background demo ────────────────────────────────
                    $s->beginText()->setFont('FB', 11)
                      ->setTextMatrix(Matrix::translate($rx, $ry))
                      ->showText('List inside a shaded box')->endText();
                    $ry -= 16;

                    $shaded = ListBox::bullet(
                        items: ['Design', 'Implement', 'Review', 'Ship'],
                        metrics: $helv,
                        fontSize: 11,
                        maxWidth: $colR - 20,
                        itemSpacing: 3.0,
                    );

                    $pad = 10.0;
                    $boxH = $shaded->getHeight() + $pad * 2;
                    $s->saveGraphicsState()
                      ->fillColor(Color::fromHex('#f0f4ff'))
                      ->strokeColor(Color::fromHex('#aabbdd'))
                      ->setLineWidth(0.5)
                      ->rectangle($rx, $ry - $boxH, $colR, $boxH)
                      ->fillAndStroke()
                      ->restoreGraphicsState();

                    $s->drawListBox($shaded, fontName: 'F1', x: $rx + $pad, y: $ry - $pad);
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
