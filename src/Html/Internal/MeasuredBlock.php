<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal;

/**
 * A LayoutBlock paired with its pre-computed vertical dimensions.
 *
 * The layout engine measures every block before flowing content into pages,
 * so it can decide where page breaks fall without rendering anything twice.
 *
 * All values are in PDF points.
 */
final class MeasuredBlock
{
    /**
     * @param \PhpPdf\Html\Internal\LayoutBlock $block        The block to render.
     * @param float $height       Content height (excluding margins).
     * @param float $marginTop    Space to reserve above the content.
     * @param float $marginBottom Space to reserve below the content.
     */
    public function __construct(
        private readonly LayoutBlock $block,
        private readonly float $height,
        private readonly float $marginTop,
        private readonly float $marginBottom,
    ) {
    }

    public function getBlock(): LayoutBlock
    {
        return $this->block;
    }

    public function getHeight(): float
    {
        return $this->height;
    }

    public function getMarginTop(): float
    {
        return $this->marginTop;
    }

    public function getMarginBottom(): float
    {
        return $this->marginBottom;
    }

    /**
     * Total vertical space occupied by this block including both margins.
     */
    public function totalHeight(): float
    {
        return $this->marginTop + $this->height + $this->marginBottom;
    }
}
