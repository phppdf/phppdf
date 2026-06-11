<?php

declare(strict_types=1);

use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Html\HtmlConverter;
use PhpPdf\Html\HtmlConverterConfig;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;

function generate(): void
{
    // Build a 50-row inventory table that spans two pages.
    $inventoryRows = '';
    $items = [
        ['Widget Alpha', 'WGT-001', 'Electronics', 142, '€ 9.99'],
        ['Gadget Beta', 'GDG-002', 'Electronics', 87, '€14.50'],
        ['Sprocket Gamma', 'SPR-003', 'Mechanical', 310, '€ 3.25'],
        ['Doohickey Delta', 'DOH-004', 'Miscellaneous', 55, '€22.00'],
        ['Thingamajig Eps.', 'THG-005', 'Mechanical', 200, '€ 7.75'],
        ['Gizmo Zeta', 'GIZ-006', 'Electronics', 30, '€199.00'],
        ['Knob Eta', 'KNB-007', 'Mechanical', 500, '€ 1.10'],
        ['Flange Theta', 'FLN-008', 'Mechanical', 275, '€ 4.80'],
        ['Lever Iota', 'LVR-009', 'Mechanical', 120, '€ 6.50'],
        ['Dial Kappa', 'DIL-010', 'Electronics', 65, '€34.99'],
    ];

    // Repeat items five times to produce a table taller than one A4 page.
    $allItems = array_merge($items, $items, $items, $items, $items);

    foreach ($allItems as $i => [$name, $sku, $category, $stock, $price]) {
        $rowNum = $i + 1;
        $bgStyle = $i % 2 === 1
            ? ' style="background-color:#f7f9fc;"'
            : '';
        $stockColor = $stock < 60
            ? ' style="color:#c0392b; text-align:right;"'
            : ' style="text-align:right;"';
        $inventoryRows .= <<<ROW
          <tr{$bgStyle}>
            <td>{$rowNum}</td>
            <td>{$name}</td>
            <td>{$sku}</td>
            <td>{$category}</td>
            <td{$stockColor}>{$stock}</td>
            <td style="text-align:right;">{$price}</td>
          </tr>
        ROW;
    }

    $html = <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
      <style>
        body  { font-size: 10pt; color: #1a1a1a; }
        h1    { color: #1a3a6b; font-size: 20pt; }
        h2    { color: #2c5282; font-size: 14pt; margin-top: 18pt; }
        p     { margin-bottom: 6pt; }
        .note { font-style: italic; color: #666666; }
        .warn { color: #c0392b; font-weight: bold; }
      </style>
    </head>
    <body>

      <h1>HTML Table Examples</h1>

      <!-- ───────────────────────────────────────────────────────────────── -->
      <h2>1 — Simple bordered table</h2>

      <p>
        A plain <code>&lt;table border="1"&gt;</code> with a <code>&lt;thead&gt;</code>
        section.  Header cells (<code>&lt;th&gt;</code>) are rendered bold with a
        light-grey background automatically.
      </p>

      <table border="1">
        <thead>
          <tr>
            <th>Country</th>
            <th>Capital</th>
            <th>Population</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Netherlands</td>
            <td>Amsterdam</td>
            <td style="text-align:right;">17 900 000</td>
          </tr>
          <tr style="background-color:#f0f4ff;">
            <td>Germany</td>
            <td>Berlin</td>
            <td style="text-align:right;">84 400 000</td>
          </tr>
          <tr>
            <td>France</td>
            <td>Paris</td>
            <td style="text-align:right;">68 000 000</td>
          </tr>
        </tbody>
      </table>

      <!-- ───────────────────────────────────────────────────────────────── -->
      <h2>2 — colspan and rowspan</h2>

      <p>
        Cells can span multiple columns with <code>colspan</code> and multiple rows
        with <code>rowspan</code>.  Borders are drawn only where a real cell boundary
        exists, so spanning cells appear seamless.
      </p>

      <table border="1">
        <thead>
          <tr>
            <th colspan="2">Product</th>
            <th>Q1</th>
            <th>Q2</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td rowspan="2" style="font-weight:bold;">Hardware</td>
            <td>Servers</td>
            <td style="text-align:right;">12</td>
            <td style="text-align:right;">15</td>
            <td style="text-align:right; font-weight:bold;">27</td>
          </tr>
          <tr>
            <td>Workstations</td>
            <td style="text-align:right;">34</td>
            <td style="text-align:right;">28</td>
            <td style="text-align:right; font-weight:bold;">62</td>
          </tr>
          <tr>
            <td rowspan="2" style="font-weight:bold;">Software</td>
            <td>Licences</td>
            <td style="text-align:right;">210</td>
            <td style="text-align:right;">195</td>
            <td style="text-align:right; font-weight:bold;">405</td>
          </tr>
          <tr>
            <td>Support contracts</td>
            <td style="text-align:right;">88</td>
            <td style="text-align:right;">91</td>
            <td style="text-align:right; font-weight:bold;">179</td>
          </tr>
          <tr>
            <td colspan="4" style="text-align:right; font-style:italic;">Grand total</td>
            <td style="text-align:right; font-weight:bold; color:#1a3a6b;">673</td>
          </tr>
        </tbody>
      </table>

      <!-- ───────────────────────────────────────────────────────────────── -->
      <h2>3 — Per-cell background and text colour</h2>

      <p>
        Individual cells can carry a <code>background-color</code> and a
        <code>color</code> via their inline <code>style</code> attribute.
        This is useful for heat maps, status tables, and highlighted totals.
      </p>

      <table border="1">
        <thead>
          <tr>
            <th>Service</th>
            <th>Jan</th>
            <th>Feb</th>
            <th>Mar</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>API Gateway</td>
            <td style="background-color:#d4edda; text-align:center;">99.9 %</td>
            <td style="background-color:#d4edda; text-align:center;">99.8 %</td>
            <td style="background-color:#d4edda; text-align:center;">99.9 %</td>
            <td style="background-color:#28a745; color:#ffffff; text-align:center; font-weight:bold;">OK</td>
          </tr>
          <tr>
            <td>Auth Service</td>
            <td style="background-color:#d4edda; text-align:center;">99.7 %</td>
            <td style="background-color:#fff3cd; text-align:center;">97.1 %</td>
            <td style="background-color:#d4edda; text-align:center;">99.5 %</td>
            <td style="background-color:#ffc107; color:#1a1a1a; text-align:center; font-weight:bold;">WARN</td>
          </tr>
          <tr>
            <td>Storage</td>
            <td style="background-color:#d4edda; text-align:center;">99.9 %</td>
            <td style="background-color:#f8d7da; text-align:center;">88.3 %</td>
            <td style="background-color:#d4edda; text-align:center;">99.8 %</td>
            <td style="background-color:#dc3545; color:#ffffff; text-align:center; font-weight:bold;">FAIL</td>
          </tr>
        </tbody>
      </table>

      <!-- ───────────────────────────────────────────────────────────────── -->
      <h2>4 — Table without borders</h2>

      <p>
        Omitting the <code>border</code> attribute produces a borderless table.
        Alternating row backgrounds (via inline <code>style</code> on each
        <code>&lt;tr&gt;</code>) are still rendered, making it easy to build
        clean, modern layouts.
      </p>

      <table>
        <thead>
          <tr>
            <th style="background-color:#1a3a6b; color:#ffffff;">Rank</th>
            <th style="background-color:#1a3a6b; color:#ffffff;">Language</th>
            <th style="background-color:#1a3a6b; color:#ffffff;">Paradigm</th>
            <th style="background-color:#1a3a6b; color:#ffffff;">First appeared</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td style="text-align:center;">1</td>
            <td>Python</td>
            <td>Multi-paradigm</td>
            <td style="text-align:center;">1991</td>
          </tr>
          <tr style="background-color:#eef2f7;">
            <td style="text-align:center;">2</td>
            <td>JavaScript</td>
            <td>Multi-paradigm</td>
            <td style="text-align:center;">1995</td>
          </tr>
          <tr>
            <td style="text-align:center;">3</td>
            <td>PHP</td>
            <td>Imperative / OOP</td>
            <td style="text-align:center;">1994</td>
          </tr>
          <tr style="background-color:#eef2f7;">
            <td style="text-align:center;">4</td>
            <td>TypeScript</td>
            <td>Multi-paradigm</td>
            <td style="text-align:center;">2012</td>
          </tr>
          <tr>
            <td style="text-align:center;">5</td>
            <td>Rust</td>
            <td>Systems / OOP</td>
            <td style="text-align:center;">2010</td>
          </tr>
        </tbody>
      </table>

      <!-- ───────────────────────────────────────────────────────────────── -->
      <h2>5 — Long table spanning two pages</h2>

      <p>
        The 50-row inventory table below is taller than a single A4 page.
        The layout engine splits it at row boundaries and flows the rows
        across as many pages as needed — rows are never cut in the middle,
        and rowspan cells are always kept intact within a single page segment.
      </p>

      <p class="note">
        Rows with stock below 60 units are shown in red to highlight low
        inventory.
      </p>

      <table border="1">
        <thead>
          <tr>
            <th>#</th>
            <th>Product</th>
            <th>SKU</th>
            <th>Category</th>
            <th>Stock</th>
            <th>Unit price</th>
          </tr>
        </thead>
        <tbody>
          {$inventoryRows}
        </tbody>
      </table>

      <p class="note">
        Note: the header row is not repeated on continuation pages.
        That feature will be added in a future version.
      </p>

    </body>
    </html>
    HTML;

    $config = new HtmlConverterConfig();
    $config->setMarginTop(54.0);
    $config->setMarginBottom(54.0);
    $config->setMarginLeft(60.0);
    $config->setMarginRight(60.0);
    $config->setBaseFontSize(10.0);
    $config->setLineHeightMultiplier(1.4);

    $builder = HtmlConverter::fromHtml($html, $config);

    $builder->info(
        (new PdfDocumentInfo())
            ->title('HTML Table Examples')
            ->author('PhpPdf')
            ->subject('Demonstrates <table> rendering in HtmlConverter'),
    );

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
