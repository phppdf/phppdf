<?php

declare(strict_types=1);

namespace PhpPdf\Barcode;

use InvalidArgumentException;
use LogicException;

use const PHP_INT_MAX;

/**
 * Generates a QR Code matrix using byte-mode encoding.
 *
 * Supports versions 1–10 (21×21 to 57×57 modules), which covers strings up
 * to 274 bytes (version 10 / L) or 122 bytes (version 10 / H). Error
 * correction level M is the default and a good all-round choice.
 *
 * Usage:
 *   $qr = QrCode::encode('https://example.com');
 *   $qr = QrCode::encode('Hello', ecLevel: 'H');
 *
 *   // Render in a PDF content stream:
 *   $stream->drawQrCode($qr, x: 72, y: 600, moduleSize: 2.0);
 */
final class QrCode
{
    // EC level bits written into the format information field
    private const array EC_BITS = ['L' => 0b01, 'M' => 0b00, 'Q' => 0b11, 'H' => 0b10];

    /**
     * Block structure per version and EC level.
     * Each entry: [ec_codewords_per_block, g1_count, g1_data, g2_count, g2_data]
     */
    private const array BLOCKS = [
        1 => ['L' => [7, 1, 19, 0, 0], 'M' => [10, 1, 16, 0, 0], 'Q' => [13, 1, 13, 0, 0], 'H' => [17, 1, 9, 0, 0]],
        2 => ['L' => [10, 1, 34, 0, 0], 'M' => [16, 1, 28, 0, 0], 'Q' => [22, 1, 22, 0, 0], 'H' => [28, 1, 16, 0, 0]],
        3 => ['L' => [15, 1, 55, 0, 0], 'M' => [26, 1, 44, 0, 0], 'Q' => [18, 2, 17, 0, 0], 'H' => [22, 2, 13, 0, 0]],
        4 => ['L' => [20, 1, 80, 0, 0], 'M' => [18, 2, 32, 0, 0], 'Q' => [26, 2, 24, 0, 0], 'H' => [16, 4, 9, 0, 0]],
        5 => ['L' => [26, 1, 108, 0, 0], 'M' => [24, 2, 43, 0, 0], 'Q' => [18, 2, 15, 2, 16], 'H' => [22, 2, 11, 2, 12]], // phpcs:ignore
        6 => ['L' => [18, 2, 68, 0, 0], 'M' => [16, 4, 27, 0, 0], 'Q' => [24, 4, 19, 0, 0], 'H' => [28, 4, 15, 0, 0]],
        7 => ['L' => [20, 2, 78, 0, 0], 'M' => [18, 4, 31, 0, 0], 'Q' => [18, 2, 14, 4, 15], 'H' => [26, 4, 13, 1, 14]],
        8 => ['L' => [24, 2, 97, 0, 0], 'M' => [22, 2, 38, 2, 39], 'Q' => [22, 4, 18, 2, 19], 'H' => [26, 4, 14, 2, 15]], // phpcs:ignore
        9 => ['L' => [30, 2, 116, 0, 0], 'M' => [22, 3, 36, 2, 37], 'Q' => [20, 4, 16, 4, 17], 'H' => [24, 4, 12, 4, 13]], // phpcs:ignore
        10 => ['L' => [18, 2, 68, 2, 69], 'M' => [26, 4, 43, 1, 44], 'Q' => [24, 6, 19, 2, 20], 'H' => [28, 6, 15, 2, 16]], // phpcs:ignore
    ];

    /** Alignment pattern centre positions indexed by version. */
    private const array ALIGN = [
        1 => [], 2 => [6, 18], 3 => [6, 22], 4 => [6, 26], 5 => [6, 30],
        6 => [6, 34], 7 => [6, 22, 38], 8 => [6, 24, 42], 9 => [6, 26, 46], 10 => [6, 28, 50],
    ];

    /** @var array<int, array<int, bool>> */
    private readonly array $matrix;


    /**
     * GF(2^8) tables, lazy-initialised once
     *
     * @var array<int, int>
     */
    private static array $gfExp = [];

    /** @var array<int, int> */
    private static array $gfLog = [];// -------------------------------------------------------------------------


    /** @param array<int, array<int, bool>> $matrix */
    private function __construct(array $matrix)
    {
        $this->matrix = $matrix;
    }

    /**
     * Encodes $data as a byte-mode QR Code.
     *
     * @param string $ecLevel 'L' (~7 % recovery), 'M' (~15 %), 'Q' (~25 %), 'H' (~30 %)
     * @throws \InvalidArgumentException if data exceeds version-10 capacity.
     */
    public static function encode(string $data, string $ecLevel = 'M'): self
    {
        if (!array_key_exists($ecLevel, self::EC_BITS)) {
            throw new InvalidArgumentException("Invalid EC level '" . $ecLevel . "'. Use L, M, Q, or H.");
        }

        $version = self::selectVersion($data, $ecLevel);
        $codewords = self::buildCodewords($data, $version, $ecLevel);
        $matrix = self::buildMatrix($version, $ecLevel, $codewords);

        return new self($matrix);
    }

    /** Returns the number of modules on one side of the QR code. */
    public function getSize(): int
    {
        return count($this->matrix);
    }

    /** Returns true if the module at ($row, $col) is dark (black). */
    public function isDark(int $row, int $col): bool
    {
        return $this->matrix[$row][$col];
    }

    // =========================================================================
    // Version selection
    // =========================================================================

    private static function selectVersion(string $data, string $ec): int
    {
        $len = strlen($data);

        foreach (self::BLOCKS as $v => $ecMap) {
            [, $g1n, $g1k, $g2n, $g2k] = $ecMap[$ec];
            $capacity = $g1n * $g1k + $g2n * $g2k;

            // Byte mode bit-stream: 4-bit mode + 8-bit char count + 8 bits/char
            if ((int)ceil((4 + 8 + 8 * $len) / 8) <= $capacity) {
                return $v;
            }
        }

        throw new InvalidArgumentException(
            "Data too long for QR Code versions 1–10 at EC level {$ec} "
            . "(max ~" . (self::BLOCKS[10][$ec][1] * self::BLOCKS[10][$ec][2]
                + self::BLOCKS[10][$ec][3] * self::BLOCKS[10][$ec][4]) . " bytes).",
        );
    }

    // =========================================================================
    // Data encoding + Reed-Solomon
    // =========================================================================

    /** @return list<int> Interleaved data + EC codewords */
    private static function buildCodewords(string $data, int $version, string $ec): array // phpcs:ignore
    {
        [$ecPer, $g1n, $g1k, $g2n, $g2k] = self::BLOCKS[$version][$ec];
        $totalData = $g1n * $g1k + $g2n * $g2k;

        // ── Bit stream ────────────────────────────────────────────────────────
        $bits = [];
        self::pushBits($bits, 0b0100, 4); // byte mode indicator
        self::pushBits($bits, strlen($data), 8); // character count (8 bits for v1–9)

        for ($i = 0, $n = strlen($data); $i < $n; $i++) {
            self::pushBits($bits, ord($data[$i]), 8);
        }

        // Terminator + byte-boundary padding
        for ($i = 0, $lim = min(4, $totalData * 8 - count($bits)); $i < $lim; $i++) {
            $bits[] = 0;
        }

        while (count($bits) % 8) {
            // @codeCoverageIgnoreStart
            $bits[] = 0;
            // @codeCoverageIgnoreEnd
        }

        // Pad codewords 0xEC / 0x11
        $pad = 0;

        while (count($bits) / 8 < $totalData) {
            self::pushBits($bits, $pad ? 0x11 : 0xEC, 8);
            $pad ^= 1;
        }

        $dataBytes = self::bitsToBytes($bits);

        // ── Split into blocks, add EC ─────────────────────────────────────────
        $blocks = [];
        $offset = 0;

        for ($b = 0; $b < $g1n + $g2n; $b++) {
            $k = $b < $g1n
                ? $g1k
                : $g2k;
            $blockData = array_slice($dataBytes, $offset, $k);
            $offset += $k;
            $blocks[] = ['d' => $blockData, 'e' => self::rsEC($blockData, $ecPer)];
        }

        // ── Interleave data codewords ─────────────────────────────────────────
        $out = [];

        for ($i = 0, $max = max($g1k, $g2k); $i < $max; $i++) {
            foreach ($blocks as $blk) {
                if ($i >= count($blk['d'])) {
                    continue;
                }

                $out[] = $blk['d'][$i];
            }
        }

        // ── Interleave EC codewords ───────────────────────────────────────────
        for ($i = 0; $i < $ecPer; $i++) {
            foreach ($blocks as $blk) {
                $out[] = $blk['e'][$i];
            }
        }

        return $out;
    }

    // =========================================================================
    // GF(2^8) and Reed-Solomon
    // =========================================================================

    private static function gfInit(): void
    {
        if (self::$gfExp) {
            return;
        }

        self::$gfExp = array_fill(0, 512, 0);
        self::$gfLog = array_fill(0, 256, 0);
        $x = 1;

        for ($i = 0; $i < 255; $i++) {
            self::$gfExp[$i] = $x;
            self::$gfLog[$x] = $i;
            $x <<= 1;

            if (!($x & 0x100)) {
                continue;
            }

            $x ^= 0x11D; // primitive polynomial x^8+x^4+x^3+x^2+1
        }

        for ($i = 255; $i < 512; $i++) {
            self::$gfExp[$i] = self::$gfExp[$i - 255];
        }
    }

    private static function gfMul(int $a, int $b): int
    {
        if ($a === 0 || $b === 0) {
            return 0;
        }

        return self::$gfExp[abs((self::$gfLog[$a] + self::$gfLog[$b]) % 255)];
    }

    /**
     * Polynomial multiply in GF(2^8).
     *
     * @param array<int, int> $p
     * @param array<int, int> $q
     * @return array<int, int>
     */
    private static function gfPolyMul(array $p, array $q): array
    {
        $r = array_fill(0, count($p) + count($q) - 1, 0);

        foreach ($p as $i => $pi) {
            foreach ($q as $j => $qj) {
                $r[$i + $j] ^= self::gfMul($pi, $qj);
            }
        }

        return $r;
    }

    /**
     * @param list<int> $data
     * @return list<int> EC codewords for $data
     */
    private static function rsEC(array $data, int $count): array // phpcs:ignore
    {
        self::gfInit();

        // Generator polynomial: product of (x + α^0)(x + α^1)…(x + α^(n-1))
        $gen = [1];

        for ($i = 0; $i < $count; $i++) {
            $gen = self::gfPolyMul($gen, [1, self::$gfExp[$i]]);
        }

        $msg = array_merge($data, array_fill(0, $count, 0));

        for ($i = 0, $n = count($data); $i < $n; $i++) {
            if ($msg[$i] === 0) {
                continue;
            }

            $c = $msg[$i];

            for ($j = 0, $gn = count($gen); $j < $gn; $j++) {
                $msg[$i + $j] ^= self::gfMul($gen[$j], $c);
            }
        }

        return array_slice($msg, count($data));
    }

    // =========================================================================
    // Matrix construction
    // =========================================================================

    /**
     * @param list<int> $codewords
     * @return array<int, array<int, bool>>
     */
    private static function buildMatrix(int $version, string $ec, array $codewords): array // phpcs:ignore
    {
        $n = 4 * $version + 17;

        /** @var array<int, array<int, bool|null>> $mat */
        $mat = array_fill(0, $n, array_fill(0, $n, null)); // null = unplaced

        /** @var array<int, array<int, bool>> $fixed */
        $fixed = array_fill(0, $n, array_fill(0, $n, false)); // true = function module

        self::placeFinders($mat, $fixed, $n);
        self::placeSeparators($mat, $fixed, $n);
        self::placeTiming($mat, $fixed, $n);
        self::placeDarkModule($mat, $fixed, $version);

        if ($version >= 2) {
            self::placeAlignment($mat, $fixed, $version);
        }

        self::reserveFormat($mat, $fixed, $n); // mark format info areas as fixed

        self::placeData($mat, $n, $codewords);

        // Finalise: null → 0 (light)
        $filled = [];

        foreach ($mat as $row) {
            $filled[] = array_map(static fn (bool|null $v): int => (int)$v, $row);
        }

        // Try all 8 mask patterns; keep the one with the lowest penalty
        $bestMat = null;
        $bestScore = PHP_INT_MAX;

        for ($mask = 0; $mask < 8; $mask++) {
            $m = self::applyMask($filled, $fixed, $n, $mask);
            self::writeFormatInfo($m, $n, $ec, $mask);
            $score = self::penalty($m, $n);

            if ($score >= $bestScore) {
                continue;
            }

            $bestScore = $score;
            $bestMat = $m;
        }

        if ($bestMat === null) {
            throw new LogicException('No mask pattern was evaluated.');
        }

        $result = [];

        foreach ($bestMat as $row) {
            $result[] = array_map(static fn (int $v): bool => $v !== 0, $row);
        }

        return $result;
    }

    // ── Function patterns ─────────────────────────────────────────────────────

    /**
     * @param array<int, array<int, bool|null>> $mat
     * @param array<int, array<int, bool>> $fixed
     */
    private static function placeFinders(array &$mat, array &$fixed, int $n): void // phpcs:ignore
    {
        // Top-left, top-right, bottom-left
        foreach ([[0, 0], [0, $n - 7], [$n - 7, 0]] as [$r, $c]) {
            /** @var list<list<int>> $fp */
            static $fp = [
                [1, 1, 1, 1, 1, 1, 1],
                [1, 0, 0, 0, 0, 0, 1],
                [1, 0, 1, 1, 1, 0, 1],
                [1, 0, 1, 1, 1, 0, 1],
                [1, 0, 1, 1, 1, 0, 1],
                [1, 0, 0, 0, 0, 0, 1],
                [1, 1, 1, 1, 1, 1, 1],
            ];

            for ($dr = 0; $dr < 7; $dr++) {
                for ($dc = 0; $dc < 7; $dc++) {
                    $mat[$r + $dr][$c + $dc] = (bool)$fp[$dr][$dc];
                    $fixed[$r + $dr][$c + $dc] = true;
                }
            }
        }
    }

    /**
     * @param array<int, array<int, bool|null>> $mat
     * @param array<int, array<int, bool>> $fixed
     */
    private static function placeSeparators(array &$mat, array &$fixed, int $n): void
    {
        // Horizontal
        for ($c = 0; $c < 8; $c++) {
            $mat[7][$c] = false;
            $fixed[7][$c] = true;
            $mat[7][$n - 8 + $c] = false;
            $fixed[7][$n - 8 + $c] = true;
            $mat[$n - 8][$c] = false;
            $fixed[$n - 8][$c] = true;
        }

        // Vertical
        for ($r = 0; $r < 8; $r++) {
            $mat[$r][7] = false;
            $fixed[$r][7] = true;
            $mat[$r][$n - 8] = false;
            $fixed[$r][$n - 8] = true;
            $mat[$n - 8 + $r][7] = false;
            $fixed[$n - 8 + $r][7] = true;
        }
    }

    /**
     * @param array<int, array<int, bool|null>> $mat
     * @param array<int, array<int, bool>> $fixed
     */
    private static function placeTiming(array &$mat, array &$fixed, int $n): void
    {
        for ($i = 8; $i < $n - 8; $i++) {
            $v = ($i % 2 === 0);

            if ($mat[6][$i] === null) {
                $mat[6][$i] = $v;
                $fixed[6][$i] = true;
            }

            if ($mat[$i][6] !== null) {
                continue;
            }

            $mat[$i][6] = $v;
            $fixed[$i][6] = true;
        }
    }

    /**
     * @param array<int, array<int, bool|null>> $mat
     * @param array<int, array<int, bool>> $fixed
     */
    private static function placeDarkModule(array &$mat, array &$fixed, int $version): void
    {
        $r = 4 * $version + 9;
        $mat[$r][8] = true;
        $fixed[$r][8] = true;
    }

    /**
     * @param array<int, array<int, bool|null>> $mat
     * @param array<int, array<int, bool>> $fixed
     */
    private static function placeAlignment(array &$mat, array &$fixed, int $version): void // phpcs:ignore
    {
        $pos = self::ALIGN[$version];
        /** @var list<list<int>> $ap */
        static $ap = [[1, 1, 1, 1, 1], [1, 0, 0, 0, 1], [1, 0, 1, 0, 1], [1, 0, 0, 0, 1], [1, 1, 1, 1, 1]];

        foreach ($pos as $cr) {
            foreach ($pos as $cc) {
                if ($fixed[$cr][$cc]) {
                    continue; // overlaps finder area
                }

                for ($dr = -2; $dr <= 2; $dr++) {
                    for ($dc = -2; $dc <= 2; $dc++) {
                        $mat[$cr + $dr][$cc + $dc] = (bool)$ap[$dr + 2][$dc + 2];
                        $fixed[$cr + $dr][$cc + $dc] = true;
                    }
                }
            }
        }
    }

    /**
     * @param array<int, array<int, bool|null>> $mat
     * @param array<int, array<int, bool>> $fixed
     */
    private static function reserveFormat(array &$mat, array &$fixed, int $n): void // phpcs:ignore
    {
        for ($i = 0; $i < 9; $i++) {
            if ($mat[8][$i] === null) {
                $mat[8][$i] = false;
            }

            $fixed[8][$i] = true;

            if ($mat[$i][8] === null) {
                $mat[$i][8] = false;
            }

            $fixed[$i][8] = true;
        }

        for ($i = 0; $i < 8; $i++) {
            if ($mat[8][$n - 1 - $i] === null) {
                $mat[8][$n - 1 - $i] = false;
            }

            $fixed[8][$n - 1 - $i] = true;
        }

        for ($i = 0; $i < 7; $i++) {
            if ($mat[$n - 7 + $i][8] === null) {
                $mat[$n - 7 + $i][8] = false;
            }

            $fixed[$n - 7 + $i][8] = true;
        }
    }

    // ── Data placement ────────────────────────────────────────────────────────

    /**
     * @param array<int, array<int, bool|null>> $mat
     * @param list<int> $codewords
     */
    private static function placeData(array &$mat, int $n, array $codewords): void // phpcs:ignore
    {
        $bits = [];

        foreach ($codewords as $cw) {
            for ($b = 7; $b >= 0; $b--) {
                $bits[] = ($cw >> $b) & 1;
            }
        }

        $bi = 0;
        $up = true;

        for ($right = $n - 1; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right = 5; // skip timing column
            }

            for ($j = 0; $j < $n; $j++) {
                $row = $up
                    ? ($n - 1 - $j)
                    : $j;

                for ($dc = 0; $dc <= 1; $dc++) {
                    $col = $right - $dc;

                    if ($mat[$row][$col] !== null) {
                        continue;
                    }

                    $mat[$row][$col] = isset($bits[$bi]) && $bits[$bi++];
                }
            }

            $up = !$up;
        }
    }

    // ── Masking ───────────────────────────────────────────────────────────────

    /**
     * @param array<int, array<int, int>> $mat
     * @param array<int, array<int, bool>> $fixed
     * @return array<int, array<int, int>>
     */
    private static function applyMask(array $mat, array $fixed, int $n, int $mask): array // phpcs:ignore
    {
        $m = $mat;

        for ($r = 0; $r < $n; $r++) {
            for ($c = 0; $c < $n; $c++) {
                if ($fixed[$r][$c] || !self::maskCondition($mask, $r, $c)) {
                    continue;
                }

                $m[$r][$c] = $m[$r][$c] === 0 ? 1 : 0;
            }
        }

        return $m;
    }

    private static function maskCondition(int $id, int $r, int $c): bool
    {
        return match ($id) {
            0 => ($r + $c) % 2 === 0,
            1 => $r % 2 === 0,
            2 => $c % 3 === 0,
            3 => ($r + $c) % 3 === 0,
            4 => ((int)($r / 2) + (int)($c / 3)) % 2 === 0,
            5 => ($r * $c) % 2 + ($r * $c) % 3 === 0,
            6 => (($r * $c) % 2 + ($r * $c) % 3) % 2 === 0,
            7 => (($r + $c) % 2 + ($r * $c) % 3) % 2 === 0,
            default => throw new InvalidArgumentException("Invalid mask id '{$id}'."),
        };
    }

    // ── Format information ────────────────────────────────────────────────────

    /**
     * Format info positions for copy 1 (top-left area), indexed bit-0 … bit-14.
     *
     * @return list<array{int,int}>
     */
    private static function fmtPos1(): array
    {
        return [
            [8, 0], [8, 1], [8, 2], [8, 3], [8, 4], [8, 5], [8, 7], [8, 8],
            [7, 8], [5, 8], [4, 8], [3, 8], [2, 8], [1, 8], [0, 8],
        ];
    }

    /**
     * Format info positions for copy 2 (top-right / bottom-left), bit-0 … bit-14.
     *
     * @return list<array{int,int}>
     */
    private static function fmtPos2(int $n): array
    {
        return [
            [$n - 1, 8], [$n - 2, 8], [$n - 3, 8], [$n - 4, 8], [$n - 5, 8], [$n - 6, 8], [$n - 7, 8],
            [8, $n - 8], [8, $n - 7], [8, $n - 6], [8, $n - 5], [8, $n - 4], [8, $n - 3], [8, $n - 2], [8, $n - 1],
        ];
    }

    /** @param array<int, array<int, int>> $mat */
    private static function writeFormatInfo(array &$mat, int $n, string $ec, int $mask): void
    {
        $fmt = self::formatBits($ec, $mask);
        $pos1 = self::fmtPos1();
        $pos2 = self::fmtPos2($n);

        for ($i = 0; $i < 15; $i++) {
            $bit = ($fmt >> $i) & 1;
            [$r1, $c1] = $pos1[$i];
            [$r2, $c2] = $pos2[$i];
            $mat[$r1][$c1] = $bit;
            $mat[$r2][$c2] = $bit;
        }
    }

    /** Computes the 15-bit format info word (EC level + mask + BCH + XOR mask). */
    private static function formatBits(string $ec, int $mask): int
    {
        $data = (self::EC_BITS[$ec] << 3) | $mask;

        // BCH(15,5): generator polynomial 0x537 = x^10+x^8+x^5+x^4+x^2+x+1
        $rem = $data << 10;

        for ($i = 14; $i >= 10; $i--) {
            if (!(($rem >> $i) & 1)) {
                continue;
            }

            $rem ^= 0x537 << $i - 10;
        }

        return (($data << 10) | ($rem & 0x3FF)) ^ 0x5412;
    }

    // ── Penalty scoring ───────────────────────────────────────────────────────

    /** @param array<int, array<int, int>> $mat */
    private static function penalty(array $mat, int $n): int // phpcs:ignore
    {
        $score = 0;

        // Rule 1: runs of 5+ same-color modules
        for ($r = 0; $r < $n; $r++) {
            $score += self::runPenalty($mat[$r]);
        }

        for ($c = 0; $c < $n; $c++) {
            $score += self::runPenalty(array_column($mat, $c));
        }

        // Rule 2: 2×2 same-color blocks
        for ($r = 0; $r < $n - 1; $r++) {
            for ($c = 0; $c < $n - 1; $c++) {
                $v = $mat[$r][$c];

                if ($v !== $mat[$r][$c + 1] || $v !== $mat[$r + 1][$c] || $v !== $mat[$r + 1][$c + 1]) {
                    continue;
                }

                $score += 3;
            }
        }

        // Rule 3: finder-like patterns
        static $p1 = [true, false, true, true, true, false, true, false, false, false, false];
        static $p2 = [false, false, false, false, true, false, true, true, true, false, true];

        for ($r = 0; $r < $n; $r++) {
            for ($c = 0; $c + 11 <= $n; $c++) {
                $row = array_slice($mat[$r], $c, 11);

                if ($row !== $p1 && $row !== $p2) {
                    continue;
                }

                $score += 40;
            }
        }

        for ($c = 0; $c < $n; $c++) {
            $col = array_column($mat, $c);

            for ($r = 0; $r + 11 <= $n; $r++) {
                $seg = array_slice($col, $r, 11);

                if ($seg !== $p1 && $seg !== $p2) {
                    continue;
                }

                $score += 40;
            }
        }

        // Rule 4: dark module ratio
        $dark = 0;

        foreach ($mat as $row) {
            $dark += array_sum($row);
        }

        $pct = (int)($dark * 100 / ($n * $n));
        $prev5 = (int)($pct / 5) * 5;
        $next5 = $prev5 + 5;

        return $score + min((int)(abs($prev5 - 50) / 5), (int)(abs($next5 - 50) / 5)) * 10;
    }

    /** @param array<int, int> $modules */
    private static function runPenalty(array $modules): int // phpcs:ignore
    {
        $score = 0;
        $run = 1;
        $prev = $modules[0];

        for ($i = 1, $n = count($modules); $i < $n; $i++) {
            if ($modules[$i] === $prev) {
                $run++;
            } else {
                if ($run >= 5) {
                    $score += $run - 2; // 3 + (run - 5) = run - 2
                }

                $run = 1;
                $prev = $modules[$i];
            }
        }

        if ($run >= 5) {
            $score += $run - 2;
        }

        return $score;
    }

    // =========================================================================
    // Bit helpers
    // =========================================================================

    /** @param list<int> $bits */
    private static function pushBits(array &$bits, int $value, int $count): void
    {
        for ($i = $count - 1; $i >= 0; $i--) {
            $bits[] = ($value >> $i) & 1;
        }
    }

    /**
     * @param list<int> $bits
     * @return list<int>
     */
    private static function bitsToBytes(array $bits): array
    {
        $bytes = [];

        for ($i = 0, $n = count($bits); $i < $n; $i += 8) {
            $b = 0;

            for ($j = 0; $j < 8; $j++) {
                $b |= (($bits[$i + $j] ?? 0) << 7 - $j);
            }

            $bytes[] = $b;
        }

        return $bytes;
    }
}
