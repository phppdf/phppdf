<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal;

/**
 * Parsed representation of an HTML <table> element.
 *
 * Produced by HtmlLayoutEngine during the DOM-walking pass and consumed
 * by the measurement and rendering passes to build a TableBuilder.
 *
 * Font defaults (family, size, bold, italic) are stored on the owning
 * LayoutBlock's ComputedStyle and are not duplicated here.
 */
final class HtmlTableData
{
    /** @var array<float> */
    private array $columnWidths = [];
    private bool $hasBorders = false;

    /** @var array{float,float,float}|null */
    private ?array $borderColor = null;
    private float $borderWidth = 0.5;
    private float $paddingTop = 4.0;
    private float $paddingRight = 6.0;
    private float $paddingBottom = 3.0;
    private float $paddingLeft = 6.0;

    /** @var array<\PhpPdf\Html\Internal\HtmlTableRowData> */
    private array $rows = [];

    /** @return array<float> */
    public function getColumnWidths(): array
    {
        return $this->columnWidths;
    }

    /** @param array<float> $widths */
    public function setColumnWidths(array $widths): void
    {
        $this->columnWidths = $widths;
    }

    public function hasBorders(): bool
    {
        return $this->hasBorders;
    }

    public function setHasBorders(bool $hasBorders): void
    {
        $this->hasBorders = $hasBorders;
    }

    /** @return array{float,float,float}|null */
    public function getBorderColor(): ?array
    {
        return $this->borderColor;
    }

    /** @param array{float,float,float}|null $color */
    public function setBorderColor(?array $color): void
    {
        $this->borderColor = $color;
    }

    public function getBorderWidth(): float
    {
        return $this->borderWidth;
    }

    public function setBorderWidth(float $width): void
    {
        $this->borderWidth = $width;
    }

    public function getPaddingTop(): float
    {
        return $this->paddingTop;
    }

    public function setPaddingTop(float $padding): void
    {
        $this->paddingTop = $padding;
    }

    public function getPaddingRight(): float
    {
        return $this->paddingRight;
    }

    public function setPaddingRight(float $padding): void
    {
        $this->paddingRight = $padding;
    }

    public function getPaddingBottom(): float
    {
        return $this->paddingBottom;
    }

    public function setPaddingBottom(float $padding): void
    {
        $this->paddingBottom = $padding;
    }

    public function getPaddingLeft(): float
    {
        return $this->paddingLeft;
    }

    public function setPaddingLeft(float $padding): void
    {
        $this->paddingLeft = $padding;
    }

    /** @return array<\PhpPdf\Html\Internal\HtmlTableRowData> */
    public function getRows(): array
    {
        return $this->rows;
    }

    /** @param array<\PhpPdf\Html\Internal\HtmlTableRowData> $rows */
    public function setRows(array $rows): void
    {
        $this->rows = $rows;
    }

    public function addRow(HtmlTableRowData $row): void
    {
        $this->rows[] = $row;
    }
}
