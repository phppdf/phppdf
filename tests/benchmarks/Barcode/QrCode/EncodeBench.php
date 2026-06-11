<?php

declare(strict_types=1);

namespace PhpPdf\Barcode\QrCode;

use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Subject;
use PhpPdf\Barcode\QrCode;

/**
 * Benchmarks for {@see QrCode::encode()}.
 *
 * Covers all four error-correction levels and a range of input sizes, since
 * encoding involves Reed-Solomon computation, matrix construction, and
 * penalty scoring across all eight mask patterns.
 */
#[Iterations(5)]
#[Revs(100)]
final class EncodeBench
{
    // -------------------------------------------------------------------------
    // Error-correction levels (same short input)
    // -------------------------------------------------------------------------

    #[Subject]
    public function benchLevelL(): void
    {
        QrCode::encode('https://example.com', 'L');
    }

    #[Subject]
    public function benchLevelM(): void
    {
        QrCode::encode('https://example.com', 'M');
    }

    #[Subject]
    public function benchLevelQ(): void
    {
        QrCode::encode('https://example.com', 'Q');
    }

    #[Subject]
    public function benchLevelH(): void
    {
        QrCode::encode('https://example.com', 'H');
    }

    // -------------------------------------------------------------------------
    // Input length (level M)
    // -------------------------------------------------------------------------

    #[Subject]
    public function benchTinyInput(): void
    {
        // ~3 bytes → version 1 (21×21 matrix)
        QrCode::encode('Hi!');
    }

    #[Subject]
    public function benchMediumInput(): void
    {
        // ~43 bytes → version 3–4
        QrCode::encode('https://example.com/products/item?id=12345');
    }

    #[Subject]
    public function benchLongInput(): void
    {
        // ~93 bytes → version 8–9
        QrCode::encode('https://example.com/very/long/path/to/some/resource?param1=value1&param2=value2&param3=value3');
    }
}
