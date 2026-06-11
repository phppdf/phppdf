<?php

declare(strict_types=1);

namespace PhpPdf\Html;

use DOMDocument;
use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Html\Internal\CssParser;
use PhpPdf\Html\Internal\HtmlLayoutEngine;
use PhpPdf\Html\Internal\StyleResolver;

/**
 * Converts an HTML string into a PdfDocumentBuilder ready for further
 * configuration and serialisation.
 *
 * The converter performs two passes:
 *   1. Parse HTML with PHP's DOMDocument (libxml2). Extract embedded
 *      `<style>` rules and walk the DOM to collect layout blocks.
 *   2. Measure each block with Helvetica font metrics and flow the blocks
 *      into A4 pages (or whatever size is configured), appending one
 *      PdfPageBuilder per page to the returned PdfDocumentBuilder.
 *
 * Because the result is a plain PdfDocumentBuilder you can:
 *   - Prepend / append additional pages
 *   - Add metadata, encryption, outlines, or digital signatures
 *   - Apply compression
 *   - Use PdfDocumentBuilder::page() / insertPage() to mix hand-crafted pages
 *     with the HTML-generated ones
 *
 * Basic usage
 * ───────────
 *   $html = '<h1>Hello World</h1><p>Welcome to the PDF.</p>';
 *
 *   $document = HtmlConverter::fromHtml($html)->build();
 *
 *   $output = new PdfMemoryOutput();
 *   (new PdfDocumentSerializer($output))->writeDocument($document);
 *
 * Custom layout
 * ─────────────
 *   $config = new HtmlConverterConfig();
 *   $config->setMarginTop(54)->setMarginBottom(54);
 *   $config->setBaseFontSize(10);
 *
 *   $builder = HtmlConverter::fromHtml($html, $config);
 *   $builder->info((new PdfDocumentInfo())->title('My Report'));
 *   $document = $builder->build();
 *
 * Supported HTML
 * ──────────────
 * Block: p div h1–h6 section article header footer main
 *          nav aside blockquote address
 * Lists: ul ol li
 * Inline: strong b em i span a code abbr br
 * Special: hr
 * Table: table thead tbody tfoot tr th td
 *          colspan rowspan per-cell background-color / color / text-align /
 *          font-weight / font-style; header rows bold + grey by default
 * CSS: color background-color font-size font-weight font-style
 *          text-align margin (all shorthands) margin-top/bottom/left
 *          padding / padding-left line-height (element + class selectors)
 *
 * Known limitations (v1)
 * ──────────────────────
 * - Only the Helvetica family (the 14 standard PDF Type 1 fonts).
 *   Embedded TrueType/OpenType support is not yet wired up.
 * - Mixed inline styles within a single block are not supported; inline
 *   bold/italic elements propagate to the whole block.
 * - Images are not rendered.
 * - Table column widths are always equally distributed; <col> widths and
 *   percentage widths are not yet respected.
 * - Text blocks are not split across page boundaries; a block that exceeds
 *   the remaining page space is moved to the next page intact.
 */
final class HtmlConverter
{
    /**
     * Converts $html into a configured PdfDocumentBuilder.
     *
     * The builder has no pages if $html contains no renderable content.
     * In that case a single blank page is appended automatically so that
     * PdfDocumentBuilder::build() never fails.
     *
     * @param string $html   Full or partial HTML document.
     *                                            A bare fragment like `<h1>Hi</h1><p>…</p>`
     *                                            is accepted; a full `<!DOCTYPE html>…` is
     *                                            also fine.
     * @param \PhpPdf\Html\HtmlConverterConfig|null $config Page layout configuration; A4 defaults used
     *                                          when null.
     */
    public static function fromHtml(string $html, ?HtmlConverterConfig $config = null): PdfDocumentBuilder
    {
        $config ??= new HtmlConverterConfig();

        // ── Parse HTML ───────────────────────────────────────────────────────
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);

        // Prepend an encoding declaration so libxml respects UTF-8 text.
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();

        // ── Extract embedded <style> rules ───────────────────────────────────
        $cssRules = [];

        foreach ($dom->getElementsByTagName('style') as $styleNode) {
            $sheetRules = CssParser::parseStylesheet($styleNode->textContent);

            // Merge: later rules override earlier ones for the same selector + property.
            foreach ($sheetRules as $selector => $declarations) {
                $cssRules[$selector] = array_merge($cssRules[$selector] ?? [], $declarations);
            }
        }

        // ── Build and run the layout engine ──────────────────────────────────
        $builder = new PdfDocumentBuilder();

        $resolver = new StyleResolver($cssRules, $config);

        $engine = new HtmlLayoutEngine($config, $resolver);
        $engine->collect($dom);
        $engine->applyToBuilder($builder);

        // Guarantee at least one page so build() can always succeed.
        if ($builder->getPageCount() === 0) {
            $cfg = $config;
            $builder->page(static function (PdfPageBuilder $p) use ($cfg): void {
                $p->size($cfg->getPageWidth(), $cfg->getPageHeight());
            });
        }

        return $builder;
    }
}
