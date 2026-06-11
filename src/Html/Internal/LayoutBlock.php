<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal;

/**
 * A single, indivisible unit of laid-out HTML content.
 *
 * Produced by HtmlLayoutEngine::collect() during the DOM-walking phase.
 * The $text field carries block content:
 *
 *   - Text / ItemList: the plain text to render (newlines = explicit line breaks;
 *     for ItemList the lines are already prefixed with markers like "• " or "1. ").
 *   - HorizontalRule / LineBreak: $text is empty.
 *   - Table: $text is empty; all data is in $tableData.
 */
final class LayoutBlock
{
    public function __construct(
        private readonly LayoutBlockType $type,
        private readonly ComputedStyle $style,
        private readonly string $text,
        private readonly ?HtmlTableData $tableData = null,
    ) {
    }

    public function getType(): LayoutBlockType
    {
        return $this->type;
    }

    public function getStyle(): ComputedStyle
    {
        return $this->style;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getTableData(): ?HtmlTableData
    {
        return $this->tableData;
    }
}
