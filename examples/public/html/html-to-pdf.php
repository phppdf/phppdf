<?php

declare(strict_types=1);

use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Html\HtmlConverter;
use PhpPdf\Html\HtmlConverterConfig;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;

function generate(): void
{
    $html = <<<'HTML'
    <!DOCTYPE html>
    <html>
    <head>
      <style>
        body  { font-size: 11pt; color: #222222; }
        h1    { color: #1a3a6b; }
        h2    { color: #2c5282; }
        p     { margin-bottom: 8pt; }
        .note { font-style: italic; color: #666666; }
        .lead { font-size: 13pt; }
        .code { font-family: monospace; background-color: #f5f5f5; }
      </style>
    </head>
    <body>

      <h1>HTML to PDF — Feature Overview</h1>

      <p class="lead">
        This document was generated directly from HTML using the PhpPdf library.
        The converter parses HTML and CSS, lays out content into pages, and
        returns a <strong>PdfDocumentBuilder</strong> instance that you can further
        customise before serialising.
      </p>

      <h2>Typography</h2>

      <p>
        The converter supports the four Helvetica Type 1 variants:
        normal text, <strong>bold text</strong>, <em>italic text</em>, and
        <strong><em>bold italic text</em></strong> — all fully embedded in the PDF.
      </p>

      <p>
        Font sizes are respected: headings scale from h1 (≈ 2×) down to h6 (≈ 0.9×),
        all relative to the base font size configured in <em>HtmlConverterConfig</em>.
      </p>

      <h2>Lists</h2>

      <p>Unordered and ordered lists are fully supported:</p>

      <ul>
        <li>Bullet item one — wraps correctly at the right margin</li>
        <li>Bullet item two</li>
        <li>Bullet item three with a <strong>bold phrase</strong> inside</li>
      </ul>

      <ol>
        <li>First ordered step</li>
        <li>Second ordered step</li>
        <li>Third ordered step</li>
      </ol>

      <h2>CSS Styling</h2>

      <p>
        Both <code>&lt;style&gt;</code> blocks and inline <code>style=""</code>
        attributes are parsed.  Element selectors and class selectors work:
      </p>

      <p class="note">
        This paragraph uses the <em>.note</em> class (italic, grey colour).
      </p>

      <p style="color: #c0392b; font-weight: bold;">
        This paragraph uses an inline style (red, bold).
      </p>

      <p style="text-align: center;">
        This paragraph is centred using an inline style.
      </p>

      <h2>Horizontal Rules</h2>

      <p>A horizontal rule separates sections:</p>

      <hr>

      <p>Content continues after the rule.</p>

      <h2>Multi-page Flow</h2>

      <p>
        When content exceeds the available page height a new page is started
        automatically.  The block that does not fit is moved to the top of the
        next page intact.  The page size and all four margins are fully
        configurable via <em>HtmlConverterConfig</em>.
      </p>

      <h2>Further Customisation</h2>

      <p>
        Because <em>HtmlConverter::fromHtml()</em> returns a plain
        <strong>PdfDocumentBuilder</strong> you can:
      </p>

      <ul>
        <li>Add document metadata (title, author, subject)</li>
        <li>Apply AES encryption or PDF/A conformance</li>
        <li>Prepend or append hand-crafted pages</li>
        <li>Add bookmarks, hyperlinks, or form fields</li>
        <li>Enable FlateDecode stream compression</li>
        <li>Sign the document with a PKCS#7 certificate</li>
      </ul>

      <h2>Limitations (v1)</h2>

      <p>
        The following features are not yet supported in this version:
      </p>

      <ul>
        <li>Images (<code>&lt;img&gt;</code>)</li>
        <li>Embedded TrueType / OpenType fonts (Helvetica family only)</li>
        <li>Mixed inline styles within a single paragraph</li>
        <li>Splitting a long paragraph across page boundaries</li>
      </ul>

      <p class="note">
        These limitations will be addressed in future versions.
      </p>

    </body>
    </html>
    HTML;

    // ── Build a custom config (optional) ────────────────────────────────────
    $config = new HtmlConverterConfig();
    $config->setMarginTop(60.0);
    $config->setMarginBottom(60.0);
    $config->setMarginLeft(72.0);
    $config->setMarginRight(72.0);
    $config->setBaseFontSize(11.0);
    $config->setLineHeightMultiplier(1.45);

    // ── Convert HTML → PdfDocumentBuilder ───────────────────────────────────
    $builder = HtmlConverter::fromHtml($html, $config);

    // ── (Optional) Attach metadata ──────────────────────────────────────────
    $builder->info(
        (new PdfDocumentInfo())
            ->title('HTML to PDF Example')
            ->author('PhpPdf')
            ->subject('Demonstrates HtmlConverter'),
    );

    // ── Build and serialise ──────────────────────────────────────────────────
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
