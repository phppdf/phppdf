<?php

declare(strict_types=1);

namespace PhpPdf\Barcode\Code128;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Subject;
use PhpPdf\Barcode\Code128;

/**
 * Benchmarks for {@see Code128::getText()}.
 */
#[BeforeMethods('setUp')]
#[Iterations(5)]
#[Revs(5000)]
final class GetTextBench
{
    private Code128 $encoded;

    public function setUp(): void
    {
        $this->encoded = Code128::encode('Hello, World!');
    }

    #[Subject]
    public function benchGetText(): void
    {
        $result = $this->encoded->getText();
    }
}
