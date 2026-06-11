<?php

declare(strict_types=1);

namespace PhpPdf\Color\Color;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Subject;
use PhpPdf\Color\Color;

/**
 * Benchmarks for {@see Color::mix()}.
 *
 * Covers midpoint blending and the boundary values t=0 and t=1.
 */
#[BeforeMethods('setUp')]
#[Iterations(5)]
#[Revs(1000)]
final class MixBench
{
    private Color $rgb;
    private Color $rgbOther;

    public function setUp(): void
    {
        $this->rgb = Color::rgb(0.2, 0.5, 0.8);
        $this->rgbOther = Color::rgb(0.8, 0.5, 0.2);
    }

    #[Subject]
    public function benchMixMidpoint(): void
    {
        $this->rgb->mix($this->rgbOther, 0.5);
    }

    #[Subject]
    public function benchMixAtZero(): void
    {
        $this->rgb->mix($this->rgbOther, 0.0);
    }

    #[Subject]
    public function benchMixAtOne(): void
    {
        $this->rgb->mix($this->rgbOther, 1.0);
    }
}
