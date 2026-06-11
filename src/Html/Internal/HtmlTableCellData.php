<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal;

use PhpPdf\Text\TextAlign;

/**
 * Parsed representation of a single <td> or <th> cell inside an HTML table.
 *
 * Produced by HtmlLayoutEngine during the DOM-walking pass.
 * Consumed by the measurement and rendering passes to build TableCell objects.
 */
final class HtmlTableCellData
{
    private string $text = '';
    private int $colspan = 1;
    private int $rowspan = 1;
    private bool $bold = false;
    private bool $italic = false;

    /** @var array{float,float,float}|null */
    private ?array $color = null;

    /** @var array{float,float,float}|null */
    private ?array $backgroundColor = null;
    private TextAlign $textAlign = TextAlign::Left;

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): void
    {
        $this->text = $text;
    }

    public function getColspan(): int
    {
        return $this->colspan;
    }

    public function setColspan(int $colspan): void
    {
        $this->colspan = $colspan;
    }

    public function getRowspan(): int
    {
        return $this->rowspan;
    }

    public function setRowspan(int $rowspan): void
    {
        $this->rowspan = $rowspan;
    }

    public function isBold(): bool
    {
        return $this->bold;
    }

    public function setBold(bool $bold): void
    {
        $this->bold = $bold;
    }

    public function isItalic(): bool
    {
        return $this->italic;
    }

    public function setItalic(bool $italic): void
    {
        $this->italic = $italic;
    }

    /** @return array{float,float,float}|null */
    public function getColor(): ?array
    {
        return $this->color;
    }

    /** @param array{float,float,float}|null $color */
    public function setColor(?array $color): void
    {
        $this->color = $color;
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

    public function getTextAlign(): TextAlign
    {
        return $this->textAlign;
    }

    public function setTextAlign(TextAlign $align): void
    {
        $this->textAlign = $align;
    }
}
