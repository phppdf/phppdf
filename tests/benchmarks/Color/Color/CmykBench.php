<?php

declare(strict_types=1);

namespace PhpPdf\Color\Color;

use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Subject;
use PhpPdf\Color\Color;

/**
 * Benchmarks for {@see Color::cmyk()}.
 */
#[Iterations(5)]
#[Revs(1000)]
final class CmykBench
{
    #[Subject]
    public function benchCmyk(): void
    {
        Color::cmyk(0.1, 0.4, 0.7, 0.05);
    }
}
