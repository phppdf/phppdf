<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal;

/**
 * Parsed representation of a single <tr> inside an HTML table.
 *
 * Produced by HtmlLayoutEngine during the DOM-walking pass.
 * Consumed by the measurement and rendering passes to build TableRow objects.
 */
final class HtmlTableRowData
{
    /** @var array<\PhpPdf\Html\Internal\HtmlTableCellData> */
    private array $cells = [];

    /** @var array{float,float,float}|null */
    private ?array $backgroundColor = null;

    private bool $isHeader = false;

    /** @return array<\PhpPdf\Html\Internal\HtmlTableCellData> */
    public function getCells(): array
    {
        return $this->cells;
    }

    /** @param array<\PhpPdf\Html\Internal\HtmlTableCellData> $cells */
    public function setCells(array $cells): void
    {
        $this->cells = $cells;
    }

    public function addCell(HtmlTableCellData $cell): void
    {
        $this->cells[] = $cell;
    }

    /** @return array{float,float,float}|null */
    public function getBackgroundColor(): ?array
    {
        return $this->backgroundColor;
    }

    /** @param array{float,float,float}|null $color */
    public function setBackgroundColor(?array $color): void
    {
        $this->backgroundColor = $color;
    }

    public function isHeader(): bool
    {
        return $this->isHeader;
    }

    public function setIsHeader(bool $isHeader): void
    {
        $this->isHeader = $isHeader;
    }
}
