<?php

declare(strict_types=1);

namespace PhpPdf\Svg;

/**
 * Converts an SVG path `d` attribute into PDF content-stream path operators.
 *
 * Handles: M, m, L, l, H, h, V, v, C, c, S, s, Q, q, T, t, A, a, Z/z.
 * Quadratic Bézier curves are converted to cubic. Arcs are approximated
 * with up to four cubic Bézier segments.
 */
final class SvgPathParser
{
    private float $cx   = 0.0;
    private float $cy   = 0.0;
    private float $spx  = 0.0; // subpath start x
    private float $spy  = 0.0; // subpath start y
    private float $pcpx = 0.0; // previous control point x (for S/T reflection)
    private float $pcpy = 0.0;
    private string $prev = '';

    public function parse(string $d): string
    {
        $this->cx = $this->cy = $this->spx = $this->spy = $this->pcpx = $this->pcpy = 0.0;
        $this->prev = '';

        $tokens = $this->tokenize($d);
        $out    = '';
        $i      = 0;
        $n      = count($tokens);

        while ($i < $n) {
            $cmd = $tokens[$i];
            if (!ctype_alpha($cmd)) {
                break;
            }
            $i++;
            $rel   = ctype_lower($cmd);
            $upper = strtoupper($cmd);

            do {
                switch ($upper) {
                    case 'M':
                        [$x, $y, $i] = $this->abs2($tokens, $i, $rel, $this->cx, $this->cy);
                        $this->cx = $this->spx = $x;
                        $this->cy = $this->spy = $y;
                        $out .= $this->f($x) . ' ' . $this->f($y) . " m\n";
                        // Subsequent pairs in an M sequence are implicit L
                        $upper = 'L';
                        break;

                    case 'L':
                        [$x, $y, $i] = $this->abs2($tokens, $i, $rel, $this->cx, $this->cy);
                        $this->cx = $x;
                        $this->cy = $y;
                        $out .= $this->f($x) . ' ' . $this->f($y) . " l\n";
                        break;

                    case 'H':
                        $v = (float) $tokens[$i++];
                        $x = $rel ? $this->cx + $v : $v;
                        $this->cx = $x;
                        $out .= $this->f($x) . ' ' . $this->f($this->cy) . " l\n";
                        break;

                    case 'V':
                        $v = (float) $tokens[$i++];
                        $y = $rel ? $this->cy + $v : $v;
                        $this->cy = $y;
                        $out .= $this->f($this->cx) . ' ' . $this->f($y) . " l\n";
                        break;

                    case 'C':
                        [$x1, $y1, $i] = $this->abs2($tokens, $i, $rel, $this->cx, $this->cy);
                        [$x2, $y2, $i] = $this->abs2($tokens, $i, $rel, $this->cx, $this->cy);
                        [$x,  $y,  $i] = $this->abs2($tokens, $i, $rel, $this->cx, $this->cy);
                        $this->pcpx = $x2;
                        $this->pcpy = $y2;
                        $this->cx = $x;
                        $this->cy = $y;
                        $out .= $this->f($x1) . ' ' . $this->f($y1) . ' '
                              . $this->f($x2) . ' ' . $this->f($y2) . ' '
                              . $this->f($x)  . ' ' . $this->f($y)  . " c\n";
                        break;

                    case 'S':
                        [$x2, $y2, $i] = $this->abs2($tokens, $i, $rel, $this->cx, $this->cy);
                        [$x,  $y,  $i] = $this->abs2($tokens, $i, $rel, $this->cx, $this->cy);
                        $prevIsC = in_array($this->prev, ['C','c','S','s'], true);
                        $x1 = $prevIsC ? 2 * $this->cx - $this->pcpx : $this->cx;
                        $y1 = $prevIsC ? 2 * $this->cy - $this->pcpy : $this->cy;
                        $this->pcpx = $x2;
                        $this->pcpy = $y2;
                        $this->cx = $x;
                        $this->cy = $y;
                        $out .= $this->f($x1) . ' ' . $this->f($y1) . ' '
                              . $this->f($x2) . ' ' . $this->f($y2) . ' '
                              . $this->f($x)  . ' ' . $this->f($y)  . " c\n";
                        break;

                    case 'Q':
                        [$qx1, $qy1, $i] = $this->abs2($tokens, $i, $rel, $this->cx, $this->cy);
                        [$x,   $y,   $i] = $this->abs2($tokens, $i, $rel, $this->cx, $this->cy);
                        [$cx1, $cy1, $cx2, $cy2] = $this->quadToCubic($this->cx, $this->cy, $qx1, $qy1, $x, $y);
                        $this->pcpx = $qx1;
                        $this->pcpy = $qy1;
                        $this->cx = $x;
                        $this->cy = $y;
                        $out .= $this->f($cx1) . ' ' . $this->f($cy1) . ' '
                              . $this->f($cx2) . ' ' . $this->f($cy2) . ' '
                              . $this->f($x)   . ' ' . $this->f($y)   . " c\n";
                        break;

                    case 'T':
                        [$x, $y, $i] = $this->abs2($tokens, $i, $rel, $this->cx, $this->cy);
                        $prevIsQ = in_array($this->prev, ['Q','q','T','t'], true);
                        $qx1 = $prevIsQ ? 2 * $this->cx - $this->pcpx : $this->cx;
                        $qy1 = $prevIsQ ? 2 * $this->cy - $this->pcpy : $this->cy;
                        [$cx1, $cy1, $cx2, $cy2] = $this->quadToCubic($this->cx, $this->cy, $qx1, $qy1, $x, $y);
                        $this->pcpx = $qx1;
                        $this->pcpy = $qy1;
                        $this->cx = $x;
                        $this->cy = $y;
                        $out .= $this->f($cx1) . ' ' . $this->f($cy1) . ' '
                              . $this->f($cx2) . ' ' . $this->f($cy2) . ' '
                              . $this->f($x)   . ' ' . $this->f($y)   . " c\n";
                        break;

                    case 'A':
                        $rx       = abs((float) $tokens[$i++]);
                        $ry       = abs((float) $tokens[$i++]);
                        $xRot     = (float) $tokens[$i++];
                        $largeArc = (int)   $tokens[$i++];
                        $sweep    = (int)   $tokens[$i++];
                        [$x, $y, $i] = $this->abs2($tokens, $i, $rel, $this->cx, $this->cy);
                        $out .= $this->arcToCubic($this->cx, $this->cy, $rx, $ry, $xRot, $largeArc, $sweep, $x, $y);
                        $this->cx = $x;
                        $this->cy = $y;
                        break;

                    case 'Z':
                        $this->cx = $this->spx;
                        $this->cy = $this->spy;
                        $out .= "h\n";
                        // Z never has implicit repeats
                        break 2;
                }

                $this->prev = $cmd;
            } while ($i < $n && !ctype_alpha($tokens[$i]) && $upper !== 'Z');

            $this->prev = $cmd;
        }

        return $out;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Reads two tokens and returns absolute coordinates + updated index.
     *
     * @param list<string> $tokens
     * @return array{float, float, int}
     */
    private function abs2(array $tokens, int $i, bool $rel, float $ox, float $oy): array
    {
        $x = (float) $tokens[$i];
        $y = (float) $tokens[$i + 1];
        if ($rel) {
            $x += $ox;
            $y += $oy;
        }
        return [$x, $y, $i + 2];
    }

    /** @return array{float, float, float, float} [cx1, cy1, cx2, cy2] */
    private function quadToCubic(float $sx, float $sy, float $qx, float $qy, float $ex, float $ey): array
    {
        return [
            $sx + 2 / 3 * ($qx - $sx),
            $sy + 2 / 3 * ($qy - $sy),
            $ex + 2 / 3 * ($qx - $ex),
            $ey + 2 / 3 * ($qy - $ey),
        ];
    }

    private function arcToCubic(
        float $x1,
        float $y1,
        float $rx,
        float $ry,
        float $xRotDeg,
        int $largeArc,
        int $sweep,
        float $x2,
        float $y2
    ): string {
        if ($rx === 0.0 || $ry === 0.0 || ($x1 === $x2 && $y1 === $y2)) {
            return $this->f($x2) . ' ' . $this->f($y2) . " l\n";
        }

        $phi    = deg2rad($xRotDeg);
        $cosPhi = cos($phi);
        $sinPhi = sin($phi);

        $dx  = ($x1 - $x2) / 2;
        $dy  = ($y1 - $y2) / 2;
        $x1p =  $cosPhi * $dx + $sinPhi * $dy;
        $y1p = -$sinPhi * $dx + $cosPhi * $dy;

        $x1pSq = $x1p * $x1p;
        $y1pSq = $y1p * $y1p;
        $rxSq  = $rx * $rx;
        $rySq  = $ry * $ry;

        $lam = $x1pSq / $rxSq + $y1pSq / $rySq;
        if ($lam > 1.0) {
            $sqrtL = sqrt($lam);
            $rx   *= $sqrtL;
            $ry  *= $sqrtL;
            $rxSq  = $rx * $rx;
            $rySq = $ry * $ry;
        }

        $num = max(0.0, $rxSq * $rySq - $rxSq * $y1pSq - $rySq * $x1pSq);
        $den = $rxSq * $y1pSq + $rySq * $x1pSq;
        $sq  = ($den > 0.0) ? sqrt($num / $den) : 0.0;
        if ($largeArc === $sweep) {
            $sq = -$sq;
        }

        $cxp =  $sq * $rx * $y1p / $ry;
        $cyp = -$sq * $ry * $x1p / $rx;
        $cx  = $cosPhi * $cxp - $sinPhi * $cyp + ($x1 + $x2) / 2;
        $cy  = $sinPhi * $cxp + $cosPhi * $cyp + ($y1 + $y2) / 2;

        $theta1 = $this->vecAngle(1, 0, ($x1p - $cxp) / $rx, ($y1p - $cyp) / $ry);
        $dTheta = $this->vecAngle(
            ($x1p - $cxp) / $rx,
            ($y1p - $cyp) / $ry,
            (-$x1p - $cxp) / $rx,
            (-$y1p - $cyp) / $ry,
        );

        if ($sweep === 0 && $dTheta > 0) {
            $dTheta -= 2 * M_PI;
        }
        if ($sweep === 1 && $dTheta < 0) {
            $dTheta += 2 * M_PI;
        }

        $segs   = max(1, (int) ceil(abs($dTheta) / (M_PI / 2)));
        $dTheta /= $segs;
        $alpha   = sin($dTheta) * (sqrt(4 + 3 * pow(tan($dTheta / 2), 2)) - 1) / 3;

        $out = '';
        $t   = $theta1;
        $epx = $cx + $cosPhi * $rx * cos($t) - $sinPhi * $ry * sin($t);
        $epy = $cy + $sinPhi * $rx * cos($t) + $cosPhi * $ry * sin($t);
        $edx = -$cosPhi * $rx * sin($t) - $sinPhi * $ry * cos($t);
        $edy = -$sinPhi * $rx * sin($t) + $cosPhi * $ry * cos($t);

        for ($s = 0; $s < $segs; $s++) {
            $t2  = $t + $dTheta;
            $npx = $cx + $cosPhi * $rx * cos($t2) - $sinPhi * $ry * sin($t2);
            $npy = $cy + $sinPhi * $rx * cos($t2) + $cosPhi * $ry * sin($t2);
            $ndx = -$cosPhi * $rx * sin($t2) - $sinPhi * $ry * cos($t2);
            $ndy = -$sinPhi * $rx * sin($t2) + $cosPhi * $ry * cos($t2);

            $out .= $this->f($epx + $alpha * $edx) . ' ' . $this->f($epy + $alpha * $edy) . ' '
                  . $this->f($npx - $alpha * $ndx) . ' ' . $this->f($npy - $alpha * $ndy) . ' '
                  . $this->f($npx) . ' ' . $this->f($npy) . " c\n";

            $t   = $t2;
            $epx = $npx;
            $epy = $npy;
            $edx = $ndx;
            $edy = $ndy;
        }

        return $out;
    }

    private function vecAngle(float $ux, float $uy, float $vx, float $vy): float
    {
        $dot   = $ux * $vx + $uy * $vy;
        $len   = sqrt(($ux * $ux + $uy * $uy) * ($vx * $vx + $vy * $vy));
        $angle = $len > 0.0 ? acos(max(-1.0, min(1.0, $dot / $len))) : 0.0;
        return ($ux * $vy - $uy * $vx < 0.0) ? -$angle : $angle;
    }

    private function f(float $v): string
    {
        return rtrim(rtrim(sprintf('%.6F', $v), '0'), '.');
    }

    /**
     * Tokenizes path data into command letters and numeric strings.
     *
     * @return list<string>
     */
    private function tokenize(string $d): array
    {
        // Separate command letters
        $d = preg_replace('/([MmLlHhVvCcSsQqTtAaZz])/', ' $1 ', $d) ?? $d;
        // Separate minus signs not preceded by 'e'/'E' (handle exponent notation)
        $d = preg_replace('/([^eE])([+\-])/', '$1 $2', $d) ?? $d;
        $parts = preg_split('/[\s,]+/', trim($d)) ?: [];
        return array_values(array_filter($parts, fn($p) => $p !== ''));
    }
}
