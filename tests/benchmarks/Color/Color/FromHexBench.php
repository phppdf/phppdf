<?php

declare(strict_types=1);

namespace PhpPdf\Color\Color;

use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Subject;
use PhpPdf\Color\Color;

/**
 * Benchmarks for {@see Color::fromHex()}.
 *
 * Covers the three accepted input formats: full hex with and without a leading
 * hash, and the three-character shorthand.
 */
#[Iterations(5)]
#[Revs(1000)]
final class FromHexBench
{
    #[Subject]
    public function benchFullWithHash(): void
    {
        Color::fromHex('#e63b3b');
    }

    #[Subject]
    public function benchFullWithoutHash(): void
    {
        Color::fromHex('e63b3b');
    }

    #[Subject]
    public function benchShorthand(): void
    {
        Color::fromHex('#f0a');
    }
}
