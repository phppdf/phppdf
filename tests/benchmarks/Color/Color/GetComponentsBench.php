<?php

declare(strict_types=1);

namespace PhpPdf\Color\Color;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Subject;
use PhpPdf\Color\Color;

/**
 * Benchmarks for {@see Color::getComponents()}.
 */
#[BeforeMethods('setUp')]
#[Iterations(5)]
#[Revs(5000)]
final class GetComponentsBench
{
    private Color $rgb;

    public function setUp(): void
    {
        $this->rgb = Color::rgb(0.2, 0.5, 0.8);
    }

    #[Subject]
    public function benchGetComponents(): void
    {
        $result = $this->rgb->getComponents();
    }
}
