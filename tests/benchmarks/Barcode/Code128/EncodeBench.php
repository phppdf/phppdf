<?php

declare(strict_types=1);

namespace PhpPdf\Barcode\Code128;

use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Subject;
use PhpPdf\Barcode\Code128;

/**
 * Benchmarks for {@see Code128::encode()}.
 *
 * Covers varying input lengths and character distributions.
 */
#[Iterations(5)]
#[Revs(1000)]
final class EncodeBench
{
    #[Subject]
    public function benchSingleCharacter(): void
    {
        Code128::encode('A');
    }

    #[Subject]
    public function benchShortString(): void
    {
        Code128::encode('Hello');
    }

    #[Subject]
    public function benchMediumString(): void
    {
        Code128::encode('Hello, World!');
    }

    #[Subject]
    public function benchLongString(): void
    {
        Code128::encode('The quick brown fox jumps over the lazy dog.');
    }

    #[Subject]
    public function benchNumericString(): void
    {
        Code128::encode('1234567890');
    }

    #[Subject]
    public function benchAllPrintableAscii(): void
    {
        $text = '';

        for ($i = 32; $i <= 95; $i++) {
            $text .= chr($i);
        }

        Code128::encode($text);
    }
}
