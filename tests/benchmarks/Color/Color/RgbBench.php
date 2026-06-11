<?php

declare(strict_types=1);

namespace PhpPdf\Color\Color;

use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Subject;
use PhpPdf\Color\Color;

/**
 * Benchmarks for {@see Color::rgb()}.
 */
#[Iterations(5)]
#[Revs(1000)]
final class RgbBench
{
    #[Subject]
    public function benchRgb(): void
    {
        Color::rgb(0.2, 0.5, 0.8);
    }
}
