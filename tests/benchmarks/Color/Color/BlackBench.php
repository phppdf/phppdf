<?php

declare(strict_types=1);

namespace PhpPdf\Color\Color;

use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Subject;
use PhpPdf\Color\Color;

/**
 * Benchmarks for {@see Color::black()}.
 */
#[Iterations(5)]
#[Revs(1000)]
final class BlackBench
{
    #[Subject]
    public function benchBlack(): void
    {
        Color::black();
    }
}
