<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Color\Color;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Font\FontMetrics;
use PhpPdf\Html\HtmlConverterConfig;
use PhpPdf\Table\TableBuilder;
use PhpPdf\Table\TableCell;
use PhpPdf\Table\TableRow;
use PhpPdf\Text\TextAlign;
use PhpPdf\Text\TextBox;

/**
 * Two-pass HTML layout engine.
 *
 * Pass 1 — collect(): walks the DOM tree and produces a flat list of
 * LayoutBlocks. Each block represents a single indivisible unit of content
 * (paragraph, heading, list, rule, …) with a resolved ComputedStyle.
 *
 * Pass 2 — applyToBuilder(): measures every block's height using the
 * appropriate font metrics, flows the blocks into pages respecting the
 * configured content area, and appends each page to the supplied
 * PdfDocumentBuilder. Only the font variants actually used on each page
 * are registered, so unused families do not add weight to the PDF.
 *
 * Supported HTML elements
 * ───────────────────────
 * Block: p div h1–h6 section article header footer main
 *           nav blockquote address aside
 * Lists: ul ol li
 * Inline: strong b em i span a code abbr
 * Special: hr br
 * Table: table thead tbody tfoot tr th td (colspan / rowspan)
 * Skipped: head script style meta link title img
 *
 * Known limitations (v1)
 * ──────────────────────
 * - Mixed inline styles within a single block are not supported; bold/italic
 *   applied to an inline element propagates to the whole block.
 * - Text blocks are not split mid-paragraph across page boundaries; a block
 *   that is shorter than a full page is moved to the next page intact.
 *   Blocks taller than a full page are placed on one page and may overflow.
 * - Images and float layout are not supported.
 * - Table header rows are not repeated when a table is split across pages.
 */
final class HtmlLayoutEngine
{
    /**
     * HTML tag names that introduce a block-level formatting context.
     */
    private const array BLOCK_TAGS = [
        'div', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'section', 'article', 'header', 'footer', 'main',
        'nav', 'aside', 'address', 'blockquote', 'pre',
        'figure', 'figcaption', 'hr',
    ];

    private const array BOLD_TAGS = ['strong', 'b'];
    private const array ITALIC_TAGS = ['em', 'i'];

    /** @var array<LayoutBlock> Flat list built by collect(). */
    private array $blocks = [];
    private readonly HtmlConverterConfig $config;
    private readonly StyleResolver $resolver;

    public function __construct(HtmlConverterConfig $config, StyleResolver $resolver)
    {
        $this->resolver = $resolver;
        $this->config = $config;
    }

    // =========================================================================
    // Pass 1 — DOM walking
    // =========================================================================

    /**
     * Walks $dom and populates the internal block list.
     *
     * Call this once before applyToBuilder(). Calling it again resets and
     * re-populates the block list.
     */
    public function collect(DOMDocument $dom): void
    {
        $this->blocks = [];

        $root = $dom->getElementsByTagName('body')->item(0)
            ?? $dom->documentElement
            ?? $dom;

        $rootStyle = new ComputedStyle(
            $this->config->getDefaultFontFamily(),
            $this->config->getBaseFontSize(),
        );
        $rootStyle->setBold(false);
        $rootStyle->setItalic(false);
        $rootStyle->setColor([0.0, 0.0, 0.0]);

        $this->walkChildren($root, $rootStyle);
    }

    // ── DOM walkers ──────────────────────────────────────────────────────────

    /**
     * Measures every collected block and flows them into pages on $builder.
     */
    public function applyToBuilder(PdfDocumentBuilder $builder): PdfDocumentBuilder
    {
        if ($this->blocks === []) {
            return $builder;
        }

        $contentHeight = $this->config->contentHeight();

        // ── Measure all blocks ────────────────────────────────────────────
        $measured = array_map(fn (LayoutBlock $b) => $this->measure($b), $this->blocks);

        // ── Flow into pages with dynamic table splitting ──────────────────
        //
        // Tables are split at the actual remaining space on each page rather
        // than at fixed page-height boundaries, so a table can start on a
        // partially-used page and flow naturally onto the next one(s).
        //
        // Non-table blocks keep the original behaviour: moved to the next page
        // intact when they don't fit in the remaining space.
        /** @var array<array<\PhpPdf\Html\Internal\MeasuredBlock>> $pages */
        $pages = [];
        $currentPage = [];
        $usedHeight = 0.0;

        // Using a queue lets us re-enqueue table remainder chunks produced
        // by the dynamic split without complicating the outer loop.
        $queue = $measured;

        while ($queue !== []) {
            $mb = array_shift($queue);
            $block = $mb->getBlock();

            // ── Dynamic table splitting ───────────────────────────────────
            if ($block->getType() === LayoutBlockType::Table && $block->getTableData() !== null) {
                $available = $contentHeight - $usedHeight - $mb->getMarginTop();
                $tableH = $this->measureTableHeight($block->getStyle(), $block->getTableData());

                if ($tableH > $available && $available > 0) {
                    [$firstData, $restData] = $this->splitTableAtHeight(
                        $block->getStyle(),
                        $block->getTableData(),
                        $available,
                    );

                    if ($firstData !== null && $restData !== null) {
                        // First chunk fills the current page.
                        $firstBlock = new LayoutBlock(LayoutBlockType::Table, $block->getStyle(), '', $firstData);
                        $firstH = $this->measureTableHeight($block->getStyle(), $firstData);
                        $currentPage[] = new MeasuredBlock($firstBlock, $firstH, $mb->getMarginTop(), 0.0);
                        $pages[] = $currentPage;
                        $currentPage = [];
                        $usedHeight = 0.0;

                        // Remainder goes to the front of the queue for the next page.
                        $restBlock = new LayoutBlock(LayoutBlockType::Table, $block->getStyle(), '', $restData);
                        $restH = $this->measureTableHeight($block->getStyle(), $restData);
                        array_unshift($queue, new MeasuredBlock($restBlock, $restH, 0.0, $mb->getMarginBottom()));

                        continue;
                    }
                }
            }

            // ── Standard flow for all other blocks ────────────────────────
            $blockTotal = $mb->totalHeight();

            if ($currentPage !== [] && $usedHeight + $blockTotal > $contentHeight) {
                if ($mb->getHeight() <= $contentHeight) {
                    // Fits on a full page — push to the next page.
                    $pages[] = $currentPage;
                    $currentPage = [];
                    $usedHeight = 0.0;
                    // Drop the top margin so the block starts flush at the top.
                    $mb = new MeasuredBlock($mb->getBlock(), $mb->getHeight(), 0.0, $mb->getMarginBottom());
                    $blockTotal = $mb->totalHeight();
                }
                // else: taller than a full page — place it anyway (may overflow).
            }

            $currentPage[] = $mb;
            $usedHeight += $blockTotal;
        }

        if ($currentPage !== []) {
            $pages[] = $currentPage;
        }

        // ── Add one page per group to the builder ─────────────────────────
        $cfg = $this->config;

        foreach ($pages as $pageBlocks) {
            $usedVariants = $this->collectUsedVariants($pageBlocks);

            $builder->page(
                function (PdfPageBuilder $page) use ($pageBlocks, $cfg, $usedVariants): void {
                    $page->size($cfg->getPageWidth(), $cfg->getPageHeight());

                    // Register only the font variants actually used on this page.
                    $families = $cfg->getFontFamilies();

                    foreach ($usedVariants as $familyName => $boldMap) {
                        if (!isset($families[$familyName])) {
                            continue;
                        }

                        foreach ($boldMap as $boldInt => $italicMap) {
                            $isBold = (bool) $boldInt;

							// phpcs:ignore SlevomatCodingStandard.Variables.UnusedVariable.UnusedVariable
                            foreach ($italicMap as $italicInt => $ignored) {
                                $isItalic = (bool) $italicInt;
                                $resourceName = $cfg->resourceName($familyName, $isBold, $isItalic);
                                $families[$familyName]->registerVariantOnPage($page, $resourceName, $isBold, $isItalic);
                            }
                        }
                    }

                    $page->content(
                        function (PdfContentStreamBuilder $stream) use ($pageBlocks, $cfg): void {
                            $this->renderPage($stream, $pageBlocks, $cfg);
                        },
                    );
                },
            );
        }

        return $builder;
    }





    private function walkChildren(DOMNode $parent, ComputedStyle $style): void
    {
        foreach ($parent->childNodes as $child) {
            $this->walkNode($child, $style);
        }
    }

    private function walkNode(DOMNode $node, ComputedStyle $parentStyle): void
    {
        // Bare text at block level
        if ($node instanceof DOMText) {
            $text = $this->normaliseWhitespace($node->nodeValue ?? '');

            if ($text !== '') {
                $this->blocks[] = new LayoutBlock(LayoutBlockType::Text, $parentStyle, $text);
            }

            return;
        }

        if (!$node instanceof DOMElement) {
            return;
        }

        $tag = strtolower($node->tagName);

        if (in_array($tag, ['head', 'script', 'style', 'meta', 'link', 'title', 'img'], true)) {
            return;
        }

        $style = $this->resolver->resolve($node, $parentStyle);

        if ($tag === 'table') {
            $this->collectTable($node, $style);

            return;
        }

        if ($tag === 'hr') {
            $this->blocks[] = new LayoutBlock(LayoutBlockType::HorizontalRule, $style, '');

            return;
        }

        if ($tag === 'br') {
            $this->blocks[] = new LayoutBlock(LayoutBlockType::LineBreak, $parentStyle, '');

            return;
        }

        if ($tag === 'ul' || $tag === 'ol') {
            $this->collectList($node, $style, $tag === 'ol');

            return;
        }

        if (in_array($tag, self::BLOCK_TAGS, true)) {
            if ($this->hasBlockLevelChild($node)) {
                $this->walkChildren($node, $style);
            } else {
                $leafStyle = clone $style;
                $text = $this->extractInlineText($node, $leafStyle);

                if ($text !== '') {
                    $this->blocks[] = new LayoutBlock(LayoutBlockType::Text, $leafStyle, $text);
                }
            }

            return;
        }

        // Inline or unknown elements — recurse using parent style.
        $this->walkChildren($node, $parentStyle);
    }

    private function collectList(DOMElement $listNode, ComputedStyle $listStyle, bool $ordered): void
    {
        $lines = [];
        $counter = 1;

        foreach ($listNode->childNodes as $child) {
            if (!$child instanceof DOMElement || strtolower($child->tagName) !== 'li') {
                continue;
            }

            $itemStyle = $this->resolver->resolve($child, $listStyle);
            $text = $this->extractInlineText($child, $itemStyle);
            $marker = $ordered
                ? "{$counter}."
                : '•';
            $lines[] = "{$marker} {$text}";
            $counter++;
        }

        if ($lines === []) {
            return;
        }

        $this->blocks[] = new LayoutBlock(
            LayoutBlockType::ItemList,
            $listStyle,
            implode("\n", $lines),
        );
    }

    // ── Table collection ─────────────────────────────────────────────────────

    private function collectTable(DOMElement $tableNode, ComputedStyle $style): void
    {
        $tableData = new HtmlTableData();

        // Border detection: <table border="1"> or a non-empty border attribute.
        $borderAttr = $tableNode->getAttribute('border');

        if ($borderAttr !== '' && $borderAttr !== '0') {
            $tableData->setHasBorders(true);
        }

        // Walk table children: thead, tbody, tfoot (each containing tr), or bare tr.
        foreach ($tableNode->childNodes as $section) {
            if (!$section instanceof DOMElement) {
                continue;
            }

            $sectionTag = strtolower($section->tagName);

            if (in_array($sectionTag, ['thead', 'tbody', 'tfoot'], true)) {
                $isHeader = $sectionTag === 'thead';

                foreach ($section->childNodes as $trNode) {
                    if (!$trNode instanceof DOMElement || strtolower($trNode->tagName) !== 'tr') {
                        continue;
                    }

                    $this->collectTableRow($trNode, $tableData, $isHeader, $style);
                }
            } elseif ($sectionTag === 'tr') {
                $this->collectTableRow($section, $tableData, false, $style);
            }
        }

        if ($tableData->getRows() === []) {
            return;
        }

        // Determine column count: maximum sum of colspans across any single row.
        $colCount = 0;

        foreach ($tableData->getRows() as $row) {
            $rowCols = 0;

            foreach ($row->getCells() as $cell) {
                $rowCols += $cell->getColspan();
            }

            $colCount = max($colCount, $rowCols);
        }

        // Distribute the available content width equally across columns.
        $contentWidth = $this->config->contentWidth()
            - $style->getMarginLeft()
            - $style->getPaddingLeft();
        $colWidth = $contentWidth / $colCount;
        $tableData->setColumnWidths(array_fill(0, $colCount, $colWidth));

        $this->blocks[] = new LayoutBlock(LayoutBlockType::Table, $style, '', $tableData);
    }

    private function collectTableRow(
        DOMElement $trNode,
        HtmlTableData $tableData,
        bool $isHeader,
        ComputedStyle $tableStyle,
    ): void {
        $rowData = new HtmlTableRowData();
        $rowData->setIsHeader($isHeader);

        // Row-level background from inline style.
        $rowInlineStyle = $trNode->getAttribute('style');

        if ($rowInlineStyle !== '') {
            $decls = CssParser::parseInline($rowInlineStyle);

            if (isset($decls['background-color'])) {
                $rowData->setBackgroundColor(CssParser::parseColor($decls['background-color']));
            }
        }

        // Default header row background (light grey) when not overridden.
        if ($isHeader && $rowData->getBackgroundColor() === null) {
            $rowData->setBackgroundColor([0.90, 0.90, 0.90]);
        }

        foreach ($trNode->childNodes as $cellNode) {
            if (!$cellNode instanceof DOMElement) {
                continue;
            }

            $cellTag = strtolower($cellNode->tagName);

            if ($cellTag !== 'td' && $cellTag !== 'th') {
                continue;
            }

            $cellData = new HtmlTableCellData();
            $cellData->setBold($cellTag === 'th' || $isHeader);

            // colspan / rowspan attributes.
            $colspanAttr = $cellNode->getAttribute('colspan');

            if ($colspanAttr !== '') {
                $cellData->setColspan(max(1, (int) $colspanAttr));
            }

            $rowspanAttr = $cellNode->getAttribute('rowspan');

            if ($rowspanAttr !== '') {
                $cellData->setRowspan(max(1, (int) $rowspanAttr));
            }

            // Extract plain text content (respects inline bold/italic hints).
            $cellComputedStyle = clone $tableStyle;
            $cellComputedStyle->setBold($cellData->isBold());
            $cellComputedStyle->setItalic($cellData->isItalic());
            $cellData->setText($this->extractInlineText($cellNode, $cellComputedStyle));

            // Propagate style hints resolved from the cell's children.
            $cellData->setBold($cellComputedStyle->isBold());
            $cellData->setItalic($cellComputedStyle->isItalic());

            // Apply inline cell styles.
            $cellInlineStyle = $cellNode->getAttribute('style');

            if ($cellInlineStyle !== '') {
                $decls = CssParser::parseInline($cellInlineStyle);

                if (isset($decls['background-color'])) {
                    $cellData->setBackgroundColor(CssParser::parseColor($decls['background-color']));
                }

                if (isset($decls['color'])) {
                    $cellData->setColor(CssParser::parseColor($decls['color']));
                }

                if (isset($decls['text-align'])) {
                    $cellData->setTextAlign(match (strtolower($decls['text-align'])) {
                        'center' => TextAlign::Center,
                        'right' => TextAlign::Right,
                        'justify' => TextAlign::Justify,
                        default => TextAlign::Left,
                    });
                }

                if (isset($decls['font-weight'])) {
                    $v = strtolower($decls['font-weight']);
                    $cellData->setBold($v === 'bold' || $v === 'bolder'
                        || (is_numeric($v) && (int) $v >= 700));
                }

                if (isset($decls['font-style'])) {
                    $v = strtolower($decls['font-style']);
                    $cellData->setItalic($v === 'italic' || $v === 'oblique');
                }
            }

            $rowData->addCell($cellData);
        }

        if ($rowData->getCells() === []) {
            return;
        }

        $tableData->addRow($rowData);
    }

    // ── Inline text helpers ──────────────────────────────────────────────────

    private function extractInlineText(DOMNode $node, ComputedStyle &$style): string
    {
        $parts = [];
        $this->gatherText($node, $style, $parts);
        $text = implode('', $parts);

        // Collapse runs of spaces, then trim the whole paragraph.
        // Individual parts are NOT trimmed so that word-boundary spaces between
        // a text node and an adjacent inline element (e.g. "returns a <strong>…")
        // are preserved.
        return trim(preg_replace('/ {2,}/', ' ', $text) ?? $text);
    }

    /**
     * @param list<string> $parts
     */
    private function gatherText(DOMNode $node, ComputedStyle &$style, array &$parts): void
    {
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMText) {
                // Collapse whitespace runs to a single space but do NOT trim so
                // that a trailing space like "returns a " is not eaten when the
                // next sibling is an inline element such as <strong>.
                $t = (string) preg_replace('/\s+/', ' ', $child->nodeValue ?? '');

                if ($t !== '') {
                    $parts[] = $t;
                }

                continue;
            }

            if (!$child instanceof DOMElement) {
                continue;
            }

            $childTag = strtolower($child->tagName);

            if (in_array($childTag, ['script', 'style'], true)) {
                continue;
            }

            if ($childTag === 'br') {
                $parts[] = "\n";

                continue;
            }

            // Inline style hints — propagate to the whole block.
            if (in_array($childTag, self::BOLD_TAGS, true)) {
                $style->setBold(true);
            }

            if (in_array($childTag, self::ITALIC_TAGS, true)) {
                $style->setItalic(true);
            }

            // font-family on an inline element — resolve and propagate.
            $inlineStyleAttr = $child->getAttribute('style');

            if ($inlineStyleAttr !== '') {
                $decls = CssParser::parseInline($inlineStyleAttr);

                if (isset($decls['font-family'])) {
                    $resolved = $this->config->resolveFontFamilyName($decls['font-family']);

                    if ($resolved !== null) {
                        $style->setFontFamily($resolved);
                    }
                }
            }

            $this->gatherText($child, $style, $parts);
        }
    }

    private function hasBlockLevelChild(DOMNode $node): bool
    {
        foreach ($node->childNodes as $child) {
            if (!$child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::BLOCK_TAGS, true) || $tag === 'ul' || $tag === 'ol') {
                return true;
            }
        }

        return false;
    }

    private function normaliseWhitespace(string $text): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $text));
    }

    // =========================================================================
    // Pass 2 — measurement and page flow
    // =========================================================================

    // ── Font variant tracking ────────────────────────────────────────────────

    /**
     * Scans $pageBlocks and returns the set of (family, bold, italic) triples
     * that are needed on this page.
     *
     * Return shape: ['familyName' => [bold(0|1) => [italic(0|1) => true]]]
     *
     * @param array<\PhpPdf\Html\Internal\MeasuredBlock> $pageBlocks
     * @return array<string, array<int, array<int, bool>>>
     */
    private function collectUsedVariants(array $pageBlocks): array
    {
        $used = [];

        foreach ($pageBlocks as $mb) {
            $block = $mb->getBlock();
            $style = $block->getStyle();
            $used[$style->getFontFamily()][(int) $style->isBold()][(int) $style->isItalic()] = true;

            // For table blocks, also register the font variants used in each cell.
            if ($block->getType() !== LayoutBlockType::Table || $block->getTableData() === null) {
                continue;
            }

            foreach ($block->getTableData()->getRows() as $row) {
                foreach ($row->getCells() as $cell) {
                    $used[$style->getFontFamily()][(int) $cell->isBold()][(int) $cell->isItalic()] = true;
                }
            }
        }

        return $used;
    }

    // ── Measurement ──────────────────────────────────────────────────────────

    private function measure(LayoutBlock $block): MeasuredBlock
    {
        $style = $block->getStyle();
        $blockWidth = $this->blockWidth($style);
        $lineHeight = $style->effectiveLineHeight($this->config->getLineHeightMultiplier());

        switch ($block->getType()) {
            case LayoutBlockType::Text:
            case LayoutBlockType::ItemList:
                $metrics = $this->metricsFor($style);
                $box = TextBox::create(
                    $block->getText(),
                    $metrics,
                    $style->getFontSize(),
                    $blockWidth,
                    $lineHeight,
                    $style->getTextAlign(),
                );

                return new MeasuredBlock($block, $box->getHeight(), $style->getMarginTop(), $style->getMarginBottom());
            case LayoutBlockType::HorizontalRule:
                return new MeasuredBlock(
                    $block,
                    1.0,
                    $style->getMarginTop() + 6.0,
                    $style->getMarginBottom() + 6.0,
                );
            case LayoutBlockType::LineBreak:
                return new MeasuredBlock($block, $lineHeight, 0.0, 0.0);
            case LayoutBlockType::Table:
                $height = $block->getTableData() !== null
                    ? $this->measureTableHeight($style, $block->getTableData())
                    : 0.0;

                // marginTop:    6 pt guards against text descenders from the preceding block.
                // marginBottom: needs to exceed the cap-height of the following text so that
                //               the text baseline (which drawTextBox uses as its y origin)
                //               sits far enough below the table's bottom edge for the glyphs
                //               not to overlap it.  fontSize × 0.72 ≈ cap-height; +4 pt adds
                //               a comfortable visual gap.
                return new MeasuredBlock(
                    $block,
                    $height,
                    $style->getMarginTop() + 6.0,
                    $style->getMarginBottom() + $style->getFontSize() * 0.72 + 4.0,
                );
        }
    }

    /**
     * Approximates the rendered height of a table in points without drawing.
     */
    private function measureTableHeight(ComputedStyle $style, HtmlTableData $tableData): float
    {
        return (float) array_sum($this->computeRowHeights($style, $tableData));
    }

    /**
     * Computes the height of every row in $tableData and returns them as an
     * indexed array (index = row position in $tableData->getRows()).
     *
     * Mirrors the row-height algorithm in TableBuilder::computeLayout() phases 2–3.
     *
     * @return array<float>
     */
    private function computeRowHeights(ComputedStyle $style, HtmlTableData $tableData): array
    {
        if ($tableData->getRows() === [] || $tableData->getColumnWidths() === []) {
            return [];
        }

        $fontSize = $style->getFontSize();
        $colCount = count($tableData->getColumnWidths());
        $rowCount = count($tableData->getRows());

        // Padding converted to baseline offsets (same as TableBuilder).
        $pt = $tableData->getPaddingTop() + $fontSize * 0.72;
        $pr = $tableData->getPaddingRight();
        $pb = $tableData->getPaddingBottom() + $fontSize * 0.20;
        $pl = $tableData->getPaddingLeft();

        /** @var array<int,array<int,array{rowspan:int,textBox:\PhpPdf\Text\TextBox}>> $placementData */
        $placementData = [];
        $occupiedBy = [];

        foreach ($tableData->getRows() as $ri => $row) {
            $ci = 0;
            $cellIdx = 0;
            $cells = $row->getCells();

            while ($ci < $colCount) {
                while ($ci < $colCount && isset($occupiedBy[$ri][$ci])) {
                    $ci++;
                }

                if ($ci >= $colCount || !isset($cells[$cellIdx])) {
                    break;
                }

                $cell = $cells[$cellIdx];
                $columnWidths = $tableData->getColumnWidths();
                $colspan = max(1, min($cell->getColspan(), $colCount - $ci));
                $rowspan = max(1, min($cell->getRowspan(), $rowCount - $ri));

                for ($r2 = $ri; $r2 < $ri + $rowspan; $r2++) {
                    for ($c2 = $ci; $c2 < $ci + $colspan; $c2++) {
                        $occupiedBy[$r2][$c2] = true;
                    }
                }

                $cellStyle = clone $style;
                $cellStyle->setBold($cell->isBold());
                $cellStyle->setItalic($cell->isItalic());
                $cellMetrics = $this->metricsFor($cellStyle);

                $spanWidth = 0.0;

                for ($c2 = $ci; $c2 < $ci + $colspan; $c2++) {
                    $spanWidth += $columnWidths[$c2];
                }

                $textBox = TextBox::create(
                    text: $cell->getText(),
                    metrics: $cellMetrics,
                    fontSize: $fontSize,
                    maxWidth: max(1.0, $spanWidth - $pl - $pr),
                    align: $cell->getTextAlign(),
                );

                $placementData[$ri][$ci] = ['rowspan' => $rowspan, 'textBox' => $textBox];

                $ci += $colspan;
                $cellIdx += 1;
            }
        }

        // Phase A: rowspan=1 cells set the minimum height for their single row.
        $rowHeights = array_fill(0, $rowCount, 0.0);

        foreach ($placementData as $ri => $rowCells) {
            foreach ($rowCells as $data) {
                if ($data['rowspan'] !== 1) {
                    continue;
                }

                $tb = $data['textBox'];
                $minH = $pt + max(0.0, $tb->getHeight() - $tb->getLineHeight()) + $pb;
                $rowHeights[$ri] = max($rowHeights[$ri], $minH);
            }
        }

        // Phase B: rowspan>1 cells grow the last spanned row as needed.
        foreach ($placementData as $ri => $rowCells) {
            foreach ($rowCells as $data) {
                if ($data['rowspan'] <= 1) {
                    continue;
                }

                $tb = $data['textBox'];
                $minH = $pt + max(0.0, $tb->getHeight() - $tb->getLineHeight()) + $pb;
                $spanTotal = 0.0;

                for ($r2 = $ri; $r2 < $ri + $data['rowspan']; $r2++) {
                    $spanTotal += $rowHeights[$r2];
                }

                if ($minH <= $spanTotal) {
                    continue;
                }

                $rowHeights[$ri + $data['rowspan'] - 1] += $minH - $spanTotal;
            }
        }

        return $rowHeights;
    }

    /**
     * Splits $src at the first safe row boundary where the cumulative row
     * height exceeds $availableHeight.
     *
     * Returns [firstChunk, restChunk]. Both are non-null on a successful
     * split. Returns [null, null] when no split is possible — either because
     * no rows fit within $availableHeight, or because all rows fit (no split
     * is needed). Row boundaries inside an active rowspan are skipped so
     * spanning cells always remain within a single chunk.
     *
     * @return array{\PhpPdf\Html\Internal\HtmlTableData|null, \PhpPdf\Html\Internal\HtmlTableData|null}
     */
    private function splitTableAtHeight(ComputedStyle $style, HtmlTableData $src, float $availableHeight,): array
    {
        if ($src->getRows() === []) {
            return [null, null];
        }

        $rowHeights = $this->computeRowHeights($style, $src);
        $rowCount = count($src->getRows());

        // Pre-compute the furthest rowspan end seen up to each row, so we can
        // quickly determine whether a potential split boundary is safe.
        $rowspanEnd = [];
        $maxEnd = -1;

        foreach ($src->getRows() as $ri => $row) {
            foreach ($row->getCells() as $cell) {
                if ($cell->getRowspan() <= 1) {
                    continue;
                }

                $maxEnd = max($maxEnd, $ri + $cell->getRowspan() - 1);
            }

            $rowspanEnd[$ri] = $maxEnd;
        }

        $usedH = 0.0;
        $splitAt = 0; // number of rows assigned to the first chunk

        foreach ($rowHeights as $ri => $rowH) {
            if ($usedH + $rowH > $availableHeight) {
                // Adding this row would overflow.  Split here if it is safe
                // (no active rowspan bridges the boundary) and we have at
                // least one row already queued.
                if ($splitAt > 0 && ($rowspanEnd[$ri - 1] ?? -1) < $ri) {
                    break;
                }

                // Unsafe or no rows placed yet — include the row anyway and
                // let the overflow happen (avoids an infinite-split loop).
            }

            $usedH += $rowH;
            $splitAt = $ri + 1;
        }

        if ($splitAt === 0 || $splitAt >= $rowCount) {
            // No safe split point found.
            return [null, null];
        }

        $first = clone $src;
        $first->setRows(array_slice($src->getRows(), 0, $splitAt));

        $rest = clone $src;
        $rest->setRows(array_slice($src->getRows(), $splitAt));

        return [$first, $rest];
    }

    // ── Rendering ────────────────────────────────────────────────────────────

    /** @param array<\PhpPdf\Html\Internal\MeasuredBlock> $pageBlocks */
    private function renderPage(PdfContentStreamBuilder $stream, array $pageBlocks, HtmlConverterConfig $cfg,): void
    {
        $cursorFromTop = 0.0;

        foreach ($pageBlocks as $mb) {
            $cursorFromTop += $mb->getMarginTop();

            $block = $mb->getBlock();
            $style = $block->getStyle();
            $x = $cfg->getMarginLeft() + $style->getMarginLeft() + $style->getPaddingLeft();
            $pdfY = $cfg->getPageHeight() - $cfg->getMarginTop() - $cursorFromTop;
            $width = $this->blockWidth($style);

            switch ($block->getType()) {
                case LayoutBlockType::Text:
                case LayoutBlockType::ItemList:
                    $this->renderTextBlock($stream, $block->getText(), $style, $x, $pdfY, $width, $cfg);

                    break;
                case LayoutBlockType::HorizontalRule:
                    $stream->saveGraphicsState()
                           ->strokeColor(Color::gray(0.6))
                           ->setLineWidth(0.5)
                           ->moveTo($x, $pdfY)
                           ->lineTo($x + $width, $pdfY)
                           ->stroke()
                           ->restoreGraphicsState();

                    break;
                case LayoutBlockType::LineBreak:
                    break;
                case LayoutBlockType::Table:
                    if ($block->getTableData() !== null) {
                        $this->renderTable($stream, $block, $x, $pdfY, $cfg);
                    }

                    break;
            }

            $cursorFromTop += $mb->getHeight() + $mb->getMarginBottom();
        }
    }

    private function renderTable(
        PdfContentStreamBuilder $stream,
        LayoutBlock $block,
        float $x,
        float $pdfY,
        HtmlConverterConfig $cfg,
    ): void {
        $tableData = $block->getTableData();

        if ($tableData === null || $tableData->getRows() === [] || $tableData->getColumnWidths() === []) {
            return;
        }

        $style = $block->getStyle();
        $fontSize = $style->getFontSize();
        $metrics = $this->metricsFor($style);
        $fontName = $cfg->resourceName($style->getFontFamily(), false, false);

        $builder = TableBuilder::create($x, $pdfY)
            ->columns($tableData->getColumnWidths())
            ->font($fontName, $fontSize, $metrics)
            ->padding(
                $tableData->getPaddingTop(),
                $tableData->getPaddingRight(),
                $tableData->getPaddingBottom(),
                $tableData->getPaddingLeft(),
            );

        if ($tableData->hasBorders()) {
            $bc = $tableData->getBorderColor();
            $borderColor = $bc !== null
                ? Color::rgb($bc[0], $bc[1], $bc[2])
                : Color::gray(0.6);
            $builder->border($borderColor, $tableData->getBorderWidth());
        }

        foreach ($tableData->getRows() as $row) {
            $tableCells = [];

            foreach ($row->getCells() as $cell) {
                $tableCell = TableCell::text($cell->getText());

                if ($cell->getColspan() > 1) {
                    $tableCell->colspan($cell->getColspan());
                }

                if ($cell->getRowspan() > 1) {
                    $tableCell->rowspan($cell->getRowspan());
                }

                if ($cell->getTextAlign() !== TextAlign::Left) {
                    $tableCell->align($cell->getTextAlign());
                }

                if ($cell->getColor() !== null) {
                    [$r, $g, $b] = $cell->getColor();
                    $tableCell->textColor(Color::rgb($r, $g, $b));
                }

                if ($cell->getBackgroundColor() !== null) {
                    [$r, $g, $b] = $cell->getBackgroundColor();
                    $tableCell->background(Color::rgb($r, $g, $b));
                }

                // Apply per-cell font variant when bold or italic differs from
                // the table default (normal weight, normal style).
                if ($cell->isBold() || $cell->isItalic()) {
                    $cellFontName = $cfg->resourceName($style->getFontFamily(), $cell->isBold(), $cell->isItalic());
                    $cellStyle = clone $style;
                    $cellStyle->setBold($cell->isBold());
                    $cellStyle->setItalic($cell->isItalic());
                    $cellMetrics = $this->metricsFor($cellStyle);
                    $tableCell->font($cellFontName, $fontSize, $cellMetrics);
                }

                $tableCells[] = $tableCell;
            }

            $tableRow = TableRow::cells($tableCells);

            if ($row->getBackgroundColor() !== null) {
                [$r, $g, $b] = $row->getBackgroundColor();
                $tableRow->background(Color::rgb($r, $g, $b));
            }

            $builder->addRow($tableRow);
        }

        $builder->draw($stream);
    }

    private function renderTextBlock(
        PdfContentStreamBuilder $stream,
        string $text,
        ComputedStyle $style,
        float $x,
        float $pdfY,
        float $maxWidth,
        HtmlConverterConfig $cfg,
    ): void {
        if ($text === '') {
            return;
        }

        $metrics = $this->metricsFor($style);
        $lineHeight = $style->effectiveLineHeight($cfg->getLineHeightMultiplier());
        $box = TextBox::create(
            $text,
            $metrics,
            $style->getFontSize(),
            $maxWidth,
            $lineHeight,
            $style->getTextAlign(),
        );
        $resourceName = $cfg->resourceName($style->getFontFamily(), $style->isBold(), $style->isItalic());

        [$r, $g, $b] = $style->getColor();

        // pdfY is the top of the layout slot; drawTextBox expects the baseline.
        // Offset down by the ascent approximation so glyph tops align with the slot top.
        $baseline = $pdfY - $style->getFontSize() * 0.72;

        $stream->saveGraphicsState()
               ->fillColor(Color::rgb($r, $g, $b))
               ->drawTextBox($box, $resourceName, $x, $baseline)
               ->restoreGraphicsState();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function blockWidth(ComputedStyle $style): float
    {
        return $this->config->contentWidth() - $style->getMarginLeft() - $style->getPaddingLeft();
    }

    private function metricsFor(ComputedStyle $style): FontMetrics
    {
        $families = $this->config->getFontFamilies();
        $family = $families[$style->getFontFamily()] ?? $families[$this->config->getDefaultFontFamily()];

        return $family->getMetrics($style->isBold(), $style->isItalic());
    }
}
