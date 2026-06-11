<?php

declare(strict_types=1);

namespace PhpPdf\Barcode\EAN13;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Subject;
use PhpPdf\Barcode\EAN13;

/**
 * Benchmarks for {@see EAN13::getText()}.
 */
#[BeforeMethods('setUp')]
#[Iterations(5)]
#[Revs(5000)]
final class GetTextBench
{
    private EAN13 $encoded;

    public function setUp(): void
    {
        $this->encoded = EAN13::encode('5901234123457');
    }

    #[Subject]
    public function benchGetText(): void
    {
        $result = $this->encoded->getText();
    }
}
