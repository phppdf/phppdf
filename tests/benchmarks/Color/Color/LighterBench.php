<?php

declare(strict_types=1);

namespace PhpPdf\Color\Color;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Subject;
use PhpPdf\Color\Color;

/**
 * Benchmarks for {@see Color::lighter()}.
 *
 * Covers RGB and CMYK color models, since each follows a different code path.
 */
#[BeforeMethods('setUp')]
#[Iterations(5)]
#[Revs(1000)]
final class LighterBench
{
    private Color $rgb;
    private Color $cmyk;

    public function setUp(): void
    {
        $this->rgb = Color::rgb(0.2, 0.5, 0.8);
        $this->cmyk = Color::cmyk(0.1, 0.4, 0.7, 0.05);
    }

    #[Subject]
    public function benchLighterRgb(): void
    {
        $this->rgb->lighter(0.3);
    }

    #[Subject]
    public function benchLighterCmyk(): void
    {
        $this->cmyk->lighter(0.3);
    }
}
