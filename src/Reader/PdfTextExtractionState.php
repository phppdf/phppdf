<?php

declare(strict_types=1);

namespace PhpPdf\Reader;

/**
 * Mutable state bag threaded through content stream processing.
 * Not part of the public API.
 */
final class PdfTextExtractionState
{
    private string $text = '';
    private bool $inText = false;
    private ?string $currentFont = null;

    /** @var array{float, float, float, float, float, float} */
    private array $textMatrix = [1.0, 0.0, 0.0, 1.0, 0.0, 0.0];

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): void
    {
        $this->text = $text;
    }

    public function appendText(string $text): void
    {
        $this->text .= $text;
    }

    public function isInText(): bool
    {
        return $this->inText;
    }

    public function setInText(bool $inText): void
    {
        $this->inText = $inText;
    }

    public function getCurrentFont(): ?string
    {
        return $this->currentFont;
    }

    public function setCurrentFont(?string $font): void
    {
        $this->currentFont = $font;
    }

    /** @return array{float, float, float, float, float, float} */
    public function getTextMatrix(): array
    {
        return $this->textMatrix;
    }

    /** @param array{float, float, float, float, float, float} $matrix */
    public function setTextMatrix(array $matrix): void
    {
        $this->textMatrix = $matrix;
    }
}
