<?php

declare(strict_types=1);

namespace PhpPdf\Color\Color;

use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Subject;
use PhpPdf\Color\Color;

/**
 * Benchmarks for {@see Color::orange()}.
 */
#[Iterations(5)]
#[Revs(1000)]
final class OrangeBench
{
    #[Subject]
    public function benchOrange(): void
    {
        Color::orange();
    }
}
