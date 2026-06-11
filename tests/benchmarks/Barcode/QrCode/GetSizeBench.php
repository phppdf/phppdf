<?php

declare(strict_types=1);

namespace PhpPdf\Barcode\QrCode;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Subject;
use PhpPdf\Barcode\QrCode;

/**
 * Benchmarks for {@see QrCode::getSize()}.
 */
#[BeforeMethods('setUp')]
#[Iterations(5)]
#[Revs(5000)]
final class GetSizeBench
{
    private QrCode $encoded;

    public function setUp(): void
    {
        $this->encoded = QrCode::encode('https://example.com');
    }

    #[Subject]
    public function benchGetSize(): void
    {
        $result = $this->encoded->getSize();
    }
}
