<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Color\Color;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Object\PdfGraphicsStateDictionary;
use PhpPdf\Object\PdfVersion;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PhpPdf\Shading\ColorStop;
use PhpPdf\Shading\PdfAxialShading;
use PhpPdf\Shading\PdfRadialShading;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Paints a named shading clipped to a rectangle, then draws a hairline border.
 */
function shadingCell(
    PdfContentStreamBuilder $s,
    float $x,
    float $y,
    float $w,
    float $h,
    string $shadingName,
): void {
    $s->saveGraphicsState()
      ->rectangle($x, $y, $w, $h)
      ->clip()
      ->endPath()
      ->paintShading($shadingName)
      ->restoreGraphicsState();

    $s->saveGraphicsState()
      ->setLineWidth(0.4)
      ->strokeColor(Color::gray(0.55))
      ->rectangle($x, $y, $w, $h)
      ->stroke()
      ->restoreGraphicsState();
}

function sectionLabel(PdfContentStreamBuilder $s, float $x, float $y, string $text): void
{
    $s->beginText()
      ->setFont('FBold', 10)
      ->setTextMatrix(Matrix::translate($x, $y))
      ->showText($text)
      ->endText();
}

function cellLabel(PdfContentStreamBuilder $s, float $x, float $y, string $text): void
{
    $s->beginText()
      ->setFont('FReg', 8)
      ->setTextMatrix(Matrix::translate($x, $y))
      ->showText($text)
      ->endText();
}

// ---------------------------------------------------------------------------
// Generate
// ---------------------------------------------------------------------------

function generate(): void
{
    [$pageW, $pageH] = PdfPageSize::A4;

    // ── Layout constants ──────────────────────────────────────────────────
    $ml        = 52.0;          // left margin
    $rightEdge = (float) $pageW - $ml;
    $cellW     = 155.0;         // cell width
    $cellH     = 110.0;         // cell height
    $colGap    = 12.0;
    $labelH    = 14.0;          // label row height below each cell
    $rowGap    = 18.0;          // gap between label and next row's section heading
    $sectionH  = 16.0;          // section heading height
    $col       = [              // left edge of each column
        $ml,
        $ml + $cellW + $colGap,
        $ml + 2 * ($cellW + $colGap),
    ];

    // Row 0 (axial demos)
    $secY0   = (float) $pageH - 80.0;   // section heading baseline
    $row0Top = $secY0 - $sectionH - 4.0;
    $row0Bot = $row0Top - $cellH;        // cell bottom y
    $lab0Y   = $row0Bot - $labelH;

    // Row 1 (radial demos)
    $secY1   = $lab0Y - $rowGap;
    $row1Top = $secY1 - $sectionH - 4.0;
    $row1Bot = $row1Top - $cellH;
    $lab1Y   = $row1Bot - $labelH;

    // Row 2 (advanced demos: multi-stop axial + transparency)
    $secY2   = $lab1Y - $rowGap;
    $row2Top = $secY2 - $sectionH - 4.0;
    $row2Bot = $row2Top - $cellH;
    $lab2Y   = $row2Bot - $labelH;

    // Vertical centre of each row (used for horizontal gradient y-coords)
    $cY0 = $row0Bot + $cellH / 2.0;
    $cY1 = $row1Bot + $cellH / 2.0;
    $cY2 = $row2Bot + $cellH / 2.0;

    // Column centres
    $cX = array_map(fn($x) => $x + $cellW / 2.0, $col);

    // ── Shading definitions ───────────────────────────────────────────────
    // Row 0 — Axial 2-stop
    $sh_A_horiz = PdfAxialShading::between(
        x0: $col[0],
        y0: $cY0,
        x1: $col[0] + $cellW,
        y1: $cY0,
        colorStart: Color::fromHex('#e63b3b'),
        colorEnd:   Color::fromHex('#3b5ce6'),
    );

    $sh_A_vert = PdfAxialShading::between(
        x0: $cX[1],
        y0: $row0Bot,
        x1: $cX[1],
        y1: $row0Top,
        colorStart: Color::fromHex('#1a1a2e'),
        colorEnd:   Color::fromHex('#e0e0f8'),
    );

    $sh_A_diag = PdfAxialShading::between(
        x0: $col[2],
        y0: $row0Bot,
        x1: $col[2] + $cellW,
        y1: $row0Top,
        colorStart: Color::fromHex('#ff6600'),
        colorEnd:   Color::fromHex('#008080'),
    );

    // Row 1 — Radial
    $sh_R_circle = PdfRadialShading::circle(
        cx: $cX[0],
        cy: $cY1,
        radius: min($cellW, $cellH) / 2.0,
        colorCenter: Color::rgb(1, 1, 1),       // white as RGB to match navy
        colorEdge:   Color::navy(),
    );

    $sh_R_offset = PdfRadialShading::between(
        cx0: $cX[1] - 18.0,
        cy0: $cY1 + 22.0,
        r0: 0.0,
        cx1: $cX[1],
        cy1: $cY1,
        r1: min($cellW, $cellH) / 2.0,
        colorStart: Color::fromHex('#ffe066'),
        colorEnd:   Color::fromHex('#cc00cc'),
    );

    $sh_R_gray = PdfRadialShading::circle(
        cx: $cX[2],
        cy: $cY1,
        radius: min($cellW, $cellH) / 2.0,
        colorCenter: Color::gray(1),             // pure gray gradient; both stops are Gray
        colorEdge:   Color::gray(0),
    );

    // Row 2 — Multi-stop axial + overlay
    $sh_MS_ryg = PdfAxialShading::multiStop(
        x0: $col[0],
        y0: $cY2,
        x1: $col[0] + $cellW,
        y1: $cY2,
        stops: [
            new ColorStop(0.0, Color::fromHex('#e63b3b')),
            new ColorStop(0.33, Color::fromHex('#f0a800')),
            new ColorStop(0.66, Color::fromHex('#00c060')),
            new ColorStop(1.0, Color::fromHex('#3b5ce6')),
        ],
    );

    $sh_MS_rad = PdfRadialShading::multiStop(
        cx0: $cX[1],
        cy0: $cY2,
        r0: 0.0,
        cx1: $cX[1],
        cy1: $cY2,
        r1: min($cellW, $cellH) / 2.0,
        stops: [
            new ColorStop(0.0, Color::rgb(1, 1, 1)),       // white as RGB to match hex stops
            new ColorStop(0.4, Color::fromHex('#4488ff')),
            new ColorStop(0.75, Color::fromHex('#0022aa')),
            new ColorStop(1.0, Color::rgb(0, 0, 0)),       // black as RGB
        ],
    );

    // Semi-transparent gradient overlaid on text (cell 2, row 2)
    $sh_MS_overlay = PdfAxialShading::between(
        x0: $col[2],
        y0: $cY2,
        x1: $col[2] + $cellW,
        y1: $cY2,
        colorStart: Color::fromHex('#ff8800'),
        colorEnd:   Color::fromHex('#8800ff'),
    );

    // ── Build the document ────────────────────────────────────────────────
    $document = (new PdfDocumentBuilder())
        ->version(PdfVersion::PDF_1_3)
        ->info((new PdfDocumentInfo())->title('Gradients - Axial and Radial Shadings')->author('phppdf'))
        ->page(function (PdfPageBuilder $page) use (
            $pageW,
            $pageH,
            $ml,
            $rightEdge,
            $col,
            $cX,
            $cY0,
            $cY1,
            $cY2,
            $cellW,
            $cellH,
            $colGap,
            $row0Bot,
            $row0Top,
            $lab0Y,
            $secY0,
            $row1Bot,
            $row1Top,
            $lab1Y,
            $secY1,
            $row2Bot,
            $row2Top,
            $lab2Y,
            $secY2,
            $sh_A_horiz,
            $sh_A_vert,
            $sh_A_diag,
            $sh_R_circle,
            $sh_R_offset,
            $sh_R_gray,
            $sh_MS_ryg,
            $sh_MS_rad,
            $sh_MS_overlay,
        ): void {
            $page->size($pageW, $pageH)
                 ->useType1Font('FReg', 'Helvetica')
                 ->useType1Font('FBold', 'Helvetica-Bold')
                 // Axial 2-stop
                 ->useShading('ShAHoriz', $sh_A_horiz)
                 ->useShading('ShAVert', $sh_A_vert)
                 ->useShading('ShADiag', $sh_A_diag)
                 // Radial
                 ->useShading('ShRCircle', $sh_R_circle)
                 ->useShading('ShROffset', $sh_R_offset)
                 ->useShading('ShRGray', $sh_R_gray)
                 // Multi-stop + overlay
                 ->useShading('ShMsRyg', $sh_MS_ryg)
                 ->useShading('ShMsRad', $sh_MS_rad)
                 ->useShading('ShOverlay', $sh_MS_overlay)
                 // Semi-transparent state for the overlay demo
                 ->useGraphicsState('GS60', new PdfGraphicsStateDictionary(fillAlpha: 0.6))
                 ->content(function (PdfContentStreamBuilder $s) use (
                     $pageW,
                     $pageH,
                     $ml,
                     $rightEdge,
                     $col,
                     $cX,
                     $cY0,
                     $cY1,
                     $cY2,
                     $cellW,
                     $cellH,
                     $row0Bot,
                     $row0Top,
                     $lab0Y,
                     $secY0,
                     $row1Bot,
                     $row1Top,
                     $lab1Y,
                     $secY1,
                     $row2Bot,
                     $row2Top,
                     $lab2Y,
                     $secY2,
                 ): void {

                     // ── Page title ────────────────────────────────────────
                     $s->beginText()
                       ->setFont('FBold', 18)
                       ->setTextMatrix(Matrix::translate($ml, (float) $pageH - 60.0))
                       ->showText('Gradients - Axial and Radial Shading Patterns')
                       ->endText();

                     $s->saveGraphicsState()
                       ->setLineWidth(0.4)->strokeColor(Color::gray(0.4))
                       ->moveTo($ml, (float) $pageH - 68.0)
                       ->lineTo($rightEdge, (float) $pageH - 68.0)
                       ->stroke()
                       ->restoreGraphicsState();

                     // ── Row 0: Axial 2-stop ───────────────────────────────
                     sectionLabel($s, $ml, $secY0, '1. Axial (Type 2) — linear fills, two colour stops');

                     shadingCell($s, $col[0], $row0Bot, $cellW, $cellH, 'ShAHoriz');
                     shadingCell($s, $col[1], $row0Bot, $cellW, $cellH, 'ShAVert');
                     shadingCell($s, $col[2], $row0Bot, $cellW, $cellH, 'ShADiag');

                     cellLabel($s, $col[0], $lab0Y, 'Horizontal: red -> blue');
                     cellLabel($s, $col[1], $lab0Y, 'Vertical: dark -> light');
                     cellLabel($s, $col[2], $lab0Y, 'Diagonal: orange -> teal');

                     // ── Row 1: Radial ──────────────────────────────────────
                     sectionLabel($s, $ml, $secY1, '2. Radial (Type 3) — circular fills');

                     shadingCell($s, $col[0], $row1Bot, $cellW, $cellH, 'ShRCircle');
                     shadingCell($s, $col[1], $row1Bot, $cellW, $cellH, 'ShROffset');
                     shadingCell($s, $col[2], $row1Bot, $cellW, $cellH, 'ShRGray');

                     cellLabel($s, $col[0], $lab1Y, 'Concentric: white -> navy');
                     cellLabel($s, $col[1], $lab1Y, 'Offset focal: yellow -> purple');
                     cellLabel($s, $col[2], $lab1Y, 'Grayscale: white -> black');

                     // ── Row 2: Multi-stop + overlay ────────────────────────
                     sectionLabel($s, $ml, $secY2, '3. Multi-stop (Type 3 stitching function) and transparency overlay');

                     shadingCell($s, $col[0], $row2Bot, $cellW, $cellH, 'ShMsRyg');
                     shadingCell($s, $col[1], $row2Bot, $cellW, $cellH, 'ShMsRad');

                     // Cell 2: body text, then semi-transparent gradient on top
                     $tx = $col[2];
                     $ty = $row2Bot;

                     // White background so text is readable
                     $s->saveGraphicsState()
                       ->fillColor(Color::gray(1.0))
                       ->rectangle($tx, $ty, $cellW, $cellH)
                       ->fill()
                       ->restoreGraphicsState();

                     // Body text drawn before the overlay
                     $s->beginText()
                       ->setFont('FBold', 9)
                       ->setTextMatrix(Matrix::translate($tx + 6.0, $ty + $cellH - 16.0))
                       ->showText('Text under gradient')
                       ->setFont('FReg', 8)
                       ->setTextMatrix(Matrix::translate($tx + 6.0, $ty + $cellH - 30.0))
                       ->showText('The gradient is painted at')
                       ->setTextMatrix(Matrix::translate($tx + 6.0, $ty + $cellH - 41.0))
                       ->showText('60 % opacity so the text')
                       ->setTextMatrix(Matrix::translate($tx + 6.0, $ty + $cellH - 52.0))
                       ->showText('beneath remains visible.')
                       ->endText();

                     // Semi-transparent gradient overlay
                     $s->saveGraphicsState()
                       ->setGraphicsStateParameters('GS60')
                       ->rectangle($tx, $ty, $cellW, $cellH)
                       ->clip()
                       ->endPath()
                       ->paintShading('ShOverlay')
                       ->restoreGraphicsState();

                     // Border
                     $s->saveGraphicsState()
                       ->setLineWidth(0.4)->strokeColor(Color::gray(0.55))
                       ->rectangle($tx, $ty, $cellW, $cellH)->stroke()
                       ->restoreGraphicsState();

                     cellLabel($s, $col[0], $lab2Y, 'Axial 4-stop: red-amber-green-blue');
                     cellLabel($s, $col[1], $lab2Y, 'Radial 4-stop: white->blue->black');
                     cellLabel($s, $col[2], $lab2Y, 'Axial at 60% opacity over text');
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

(function (): void {
    $autoloader = require __DIR__ . '/../../../vendor/autoload.php';

    setupEnvironment($autoloader);
    generate();
})();
