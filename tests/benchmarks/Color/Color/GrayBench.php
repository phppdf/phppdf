<?php

declare(strict_types=1);

namespace PhpPdf\Color\Color;

use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Subject;
use PhpPdf\Color\Color;

/**
 * Benchmarks for {@see Color::gray()}.
 */
#[Iterations(5)]
#[Revs(1000)]
final class GrayBench
{
    #[Subject]
    public function benchGray(): void
    {
        Color::gray(0.5);
    }
}
