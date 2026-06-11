<?php

declare(strict_types=1);

namespace PhpPdf\Barcode\EAN13;

use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Subject;
use PhpPdf\Barcode\EAN13;

/**
 * Benchmarks for {@see EAN13::encode()}.
 *
 * Covers 12-digit input (check digit computed) and 13-digit input (check
 * digit verified), exercising each distinct first-digit parity group.
 */
#[Iterations(5)]
#[Revs(1000)]
final class EncodeBench
{
    // -------------------------------------------------------------------------
    // 12 digits — check digit computed
    // -------------------------------------------------------------------------

    #[Subject]
    public function benchEncode12Digits(): void
    {
        EAN13::encode('590123412345');
    }

    #[Subject]
    public function benchEncode12DigitsAllZeros(): void
    {
        EAN13::encode('000000000000');
    }

    #[Subject]
    public function benchEncode12DigitsFirstDigitZero(): void
    {
        // First digit 0 → all-L parity (LLLLLL)
        EAN13::encode('012345678905');
    }

    #[Subject]
    public function benchEncode12DigitsFirstDigitFive(): void
    {
        // First digit 5 → mixed parity (LGGLLG)
        EAN13::encode('512345678908');
    }

    #[Subject]
    public function benchEncode12DigitsFirstDigitNine(): void
    {
        // First digit 9 → mixed parity (LGGLGL)
        EAN13::encode('978020137962');
    }

    // -------------------------------------------------------------------------
    // 13 digits — check digit verified
    // -------------------------------------------------------------------------

    #[Subject]
    public function benchEncode13Digits(): void
    {
        EAN13::encode('5901234123457');
    }

    #[Subject]
    public function benchEncode13DigitsIsbn(): void
    {
        EAN13::encode('9780201379624');
    }
}
