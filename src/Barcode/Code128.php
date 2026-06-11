<?php

declare(strict_types=1);

namespace PhpPdf\Barcode;

use InvalidArgumentException;

/**
 * Code 128 B barcode encoder.
 *
 * Encodes any string of ASCII characters in the range 32–127 (all printable
 * characters including lowercase) using the Code 128 B symbology.
 *
 * The generated bar array includes the Start B symbol, all data characters,
 * the computed checksum, and the Stop symbol. Quiet zones are NOT included;
 * PdfContentStreamBuilder::drawBarcode() adds them automatically.
 *
 * Usage:
 *   $bc = Code128::encode('Hello, World!');
 *   $stream->drawBarcode($bc, x: 72, y: 700, height: 40, moduleWidth: 1.2);
 */
final class Code128 implements LinearBarcode
{
    /**
     * Code 128 pattern table, indexed by symbol value (0–106).
     * Each entry is an array of 6 integers (bar, space, bar, space, bar, space)
     * that sum to 11 modules. Entry 106 (Stop) has 7 integers summing to 13.
     */
    private const array PATTERNS = [
        // 0–63
        [2, 1, 2, 2, 2, 2], [2, 2, 2, 1, 2, 2], [2, 2, 2, 2, 2, 1], [1, 2, 1, 2, 2, 3],
        [1, 2, 1, 3, 2, 2], [1, 3, 1, 2, 2, 2], [1, 2, 2, 2, 1, 3], [1, 2, 2, 3, 1, 2],
        [1, 3, 2, 2, 1, 2], [2, 2, 1, 2, 1, 3], [2, 2, 1, 3, 1, 2], [2, 3, 1, 2, 1, 2],
        [1, 1, 2, 2, 3, 2], [1, 2, 2, 1, 3, 2], [1, 2, 2, 2, 3, 1], [1, 1, 3, 2, 2, 2],
        [1, 2, 3, 1, 2, 2], [1, 2, 3, 2, 2, 1], [2, 2, 3, 2, 1, 1], [2, 2, 1, 1, 3, 2],
        [2, 2, 1, 2, 3, 1], [2, 1, 3, 2, 1, 2], [2, 2, 3, 1, 1, 2], [3, 1, 2, 1, 3, 1],
        [3, 1, 1, 2, 2, 2], [3, 2, 1, 1, 2, 2], [3, 2, 1, 2, 2, 1], [3, 1, 2, 2, 1, 2],
        [3, 2, 2, 1, 1, 2], [3, 2, 2, 2, 1, 1], [2, 1, 2, 1, 2, 3], [2, 1, 2, 3, 2, 1],
        [2, 3, 2, 1, 2, 1], [1, 1, 1, 3, 2, 3], [1, 3, 1, 1, 2, 3], [1, 3, 1, 3, 2, 1],
        [1, 1, 2, 3, 1, 3], [1, 3, 2, 1, 1, 3], [1, 3, 2, 3, 1, 1], [2, 1, 1, 3, 1, 3],
        [2, 3, 1, 1, 1, 3], [2, 3, 1, 3, 1, 1], [1, 1, 2, 1, 3, 3], [1, 1, 2, 3, 3, 1],
        [1, 3, 2, 1, 3, 1], [1, 1, 3, 1, 2, 3], [1, 1, 3, 3, 2, 1], [1, 3, 3, 1, 2, 1],
        [3, 1, 3, 1, 2, 1], [2, 1, 1, 3, 3, 1], [2, 3, 1, 1, 3, 1], [2, 1, 3, 1, 1, 3],
        [2, 1, 3, 3, 1, 1], [2, 1, 3, 1, 3, 1], [3, 1, 1, 1, 2, 3], [3, 1, 1, 3, 2, 1],
        [3, 3, 1, 1, 2, 1], [3, 1, 2, 1, 1, 3], [3, 1, 2, 3, 1, 1], [3, 3, 2, 1, 1, 1],
        [3, 1, 4, 1, 1, 1], [2, 2, 1, 4, 1, 1], [4, 3, 1, 1, 1, 1], [1, 1, 1, 2, 2, 4],
        // 64–106
        [1, 1, 1, 4, 2, 2], [1, 2, 1, 1, 2, 4], [1, 2, 1, 4, 2, 1], [1, 4, 1, 1, 2, 2],
        [1, 4, 1, 2, 2, 1], [1, 1, 2, 2, 1, 4], [1, 1, 2, 4, 1, 2], [1, 2, 2, 1, 1, 4],
        [1, 2, 2, 4, 1, 1], [1, 4, 2, 1, 1, 2], [1, 4, 2, 2, 1, 1], [2, 4, 1, 2, 1, 1],
        [2, 2, 1, 1, 1, 4], [4, 1, 3, 1, 1, 1], [2, 4, 1, 1, 1, 2], [1, 3, 4, 1, 1, 1],
        [1, 1, 1, 2, 4, 2], [1, 2, 1, 1, 4, 2], [1, 2, 1, 2, 4, 1], [1, 1, 4, 2, 1, 2],
        [1, 2, 4, 1, 1, 2], [1, 2, 4, 2, 1, 1], [4, 1, 1, 2, 1, 2], [4, 2, 1, 1, 1, 2],
        [4, 2, 1, 2, 1, 1], [2, 1, 2, 1, 4, 1], [2, 1, 4, 1, 2, 1], [4, 1, 2, 1, 2, 1],
        [1, 1, 1, 1, 4, 3], [1, 1, 1, 3, 4, 1], [1, 3, 1, 1, 4, 1], [1, 1, 4, 1, 1, 3],
        [1, 1, 4, 3, 1, 1], [4, 1, 1, 1, 1, 3], [4, 1, 1, 3, 1, 1], [1, 1, 3, 1, 4, 1],
        [1, 1, 4, 1, 3, 1], [3, 1, 1, 1, 4, 1], [4, 1, 1, 1, 3, 1], [2, 1, 1, 4, 1, 2],
        // 103 = Start A, 104 = Start B, 105 = Start C, 106 = Stop
        [2, 1, 1, 2, 1, 4], [2, 1, 1, 2, 3, 2], [2, 3, 3, 1, 1, 1, 2],
    ];

    private readonly string $text;

    /** @var list<int> */
    private readonly array $bars;

    /** @param list<int> $bars */
    private function __construct(string $text, array $bars)
    {
        $this->bars = $bars;
        $this->text = $text;
    }

    /**
     * Encodes a string using Code 128 B (ASCII 32–127).
     *
     * @throws \InvalidArgumentException if the string contains characters outside 32–127.
     */
    public static function encode(string $text): self
    {
        self::validateCode128BSupportedCharacters($text);

        // Symbol values: Start B (104), then one value per character, then checksum, Stop (106)
        $values = [104];

        for ($i = 0, $len = strlen($text); $i < $len; $i++) {
            $values[] = ord($text[$i]) - 32;
        }

        // Checksum = (Start B value + Σ(i × data_value_i)) mod 103
        $check = 104;

        for ($i = 1; $i < count($values); $i++) {
            $check += $i * $values[$i];
        }

        $values[] = $check % 103;
        $values[] = 106; // Stop

        $bars = [];

        foreach ($values as $v) {
            foreach (self::PATTERNS[$v] as $w) {
                $bars[] = $w;
            }
        }

        return new self($text, $bars);
    }

    /** @return list<int> */
    public function getBars(): array
    {
        return $this->bars;
    }

    public function getText(): string
    {
        return $this->text;
    }

    private static function validateCode128BSupportedCharacters(string $text): void
    {
        for ($i = 0, $len = strlen($text); $i < $len; $i++) {
            $ord = ord($text[$i]);

            if ($ord < 32 || $ord > 127) {
                throw new InvalidArgumentException(
                    "Code 128 B only supports ASCII 32–127. "
                    . "Character at index {$i} has value {$ord}.",
                );
            }
        }
    }
}
