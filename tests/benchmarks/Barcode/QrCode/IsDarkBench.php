<?php

declare(strict_types=1);

namespace PhpPdf\Barcode\QrCode;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Subject;
use PhpPdf\Barcode\QrCode;

/**
 * Benchmarks for {@see QrCode::isDark()}.
 *
 * Exercises the finder-pattern corner (always dark), the quiet-zone border
 * (always light), and a data-area module in the centre of the matrix.
 */
#[BeforeMethods('setUp')]
#[Iterations(5)]
#[Revs(5000)]
final class IsDarkBench
{
    private QrCode $encoded;
    private int $mid;

    public function setUp(): void
    {
        $this->encoded = QrCode::encode('https://example.com');
        $this->mid = (int) ($this->encoded->getSize() / 2);
    }

    #[Subject]
    public function benchIsDarkFinderCorner(): void
    {
        // Top-left finder pattern (0,0) is always a dark module
        $result = $this->encoded->isDark(0, 0);
    }

    #[Subject]
    public function benchIsDarkCenter(): void
    {
        $result = $this->encoded->isDark($this->mid, $this->mid);
    }

    #[Subject]
    public function benchIsDarkBottomRight(): void
    {
        $size = $this->encoded->getSize();
        $result = $this->encoded->isDark($size - 1, $size - 1);
    }
}
