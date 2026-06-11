<?php

declare(strict_types=1);

namespace PhpPdf\Barcode;

use InvalidArgumentException;

/**
 * EAN-13 barcode encoder (also compatible with ISBN-13 and ISSN-13).
 *
 * Accepts either 12 data digits (check digit computed automatically) or all
 * 13 digits (check digit verified). The bar array represents the 95-module
 * symbol: left guard, 6 left-half digits, centre guard, 6 right-half digits,
 * right guard. Quiet zones are added by drawBarcode().
 *
 * The first digit is encoded as the L/G parity pattern of the left-half digits
 * rather than as an explicit set of bars, so it is not directly visible in the
 * bar pattern itself — it is typically printed in text to the left of the symbol.
 *
 * Usage:
 *   $bc = EAN13::encode('5901234123457'); // 13 digits, check verified
 *   $bc = EAN13::encode('590123412345'); // 12 digits, check computed
 *   $stream->drawBarcode($bc, x: 72, y: 700, height: 40, moduleWidth: 1.0);
 */
final class EAN13 implements LinearBarcode
{
    // L-code: odd parity, used for some left-half digits (7 bits each)
    private const array L = [
        '0001101', '0011001', '0010011', '0111101', '0100011',
        '0110001', '0101111', '0111011', '0110111', '0001011',
    ];

    // G-code: even parity, used for some left-half digits
    private const array G = [
        '0100111', '0110011', '0011011', '0100001', '0011101',
        '0111001', '0000101', '0010001', '0001001', '0010111',
    ];

    // R-code: used for all right-half digits (complement of L)
    private const array R = [
        '1110010', '1100110', '1101100', '1000010', '1011100',
        '1001110', '1010000', '1000100', '1001000', '1110100',
    ];

    // Parity pattern per first digit: which of the 6 left digits use G vs L encoding
    private const array PARITY = [
        'LLLLLL', 'LLGLGG', 'LLGGLG', 'LLGGGL', 'LGLLGG',
        'LGGLLG', 'LGGGLL', 'LGLGLG', 'LGLGGL', 'LGGLGL',
    ];

    private readonly string $digits;

    /** @var list<int> */
    private readonly array $bars;

    /** @param list<int> $bars */
    private function __construct(string $digits, array $bars)
    {
        $this->bars = $bars;
        $this->digits = $digits;
    }

    /**
     * Encodes a 12- or 13-digit string as EAN-13.
     *
     * Pass 12 digits to have the check digit computed automatically.
     * Pass 13 digits to have the check digit verified.
     *
     * @throws \InvalidArgumentException on wrong length or invalid check digit.
     */
    // phpcs:ignore SlevomatCodingStandard.Complexity.Cognitive.ComplexityTooHigh
    public static function encode(string $data): self
    {
        $data = preg_replace('/[^0-9]/', '', $data) ?? '';

        if (strlen($data) === 12) {
            $data .= self::checkDigit($data);
        }

        if (strlen($data) !== 13) {
            throw new InvalidArgumentException('EAN-13 requires exactly 12 or 13 digit characters.');
        }

        $expected = self::checkDigit(substr($data, 0, 12));

        if ($data[12] !== $expected) {
            throw new InvalidArgumentException("EAN-13 check digit mismatch: expected {$expected}, got {$data[12]}.");
        }

        $bits = '101'; // left guard

        $parity = self::PARITY[(int)$data[0]];

        for ($i = 1; $i <= 6; $i++) {
            $d = (int)$data[$i];
            $bits .= $parity[$i - 1] === 'L'
                ? self::L[$d]
                : self::G[$d];
        }

        $bits .= '01010'; // centre guard

        for ($i = 7; $i <= 12; $i++) {
            $bits .= self::R[(int)$data[$i]];
        }

        $bits .= '101'; // right guard

        // Run-length encode the bit string into alternating bar/space widths.
        // The first bit of the left guard is always '1' (a bar).
        $bars = [];
        $current = $bits[0];
        $run = 1;

        for ($i = 1, $len = strlen($bits); $i < $len; $i++) {
            if ($bits[$i] === $current) {
                $run++;
            } else {
                $bars[] = $run;
                $current = $bits[$i];
                $run = 1;
            }
        }

        $bars[] = $run;

        return new self($data, $bars);
    }

    /** @return list<int> */
    public function getBars(): array
    {
        return $this->bars;
    }

    public function getText(): string
    {
        return $this->digits;
    }

    private static function checkDigit(string $data12): string
    {
        $sum = 0;

        for ($i = 0; $i < 12; $i++) {
            $sum += (int)$data12[$i] * ($i % 2 === 0 ? 1 : 3);
        }

        return (string)((10 - ($sum % 10)) % 10);
    }
}
