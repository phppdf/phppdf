<?php

declare(strict_types=1);

namespace PhpPdf\Content;

/**
 * An immutable 2D affine transformation matrix for PDF content streams.
 *
 * PDF represents a 2D affine transform as six numbers [a b c d e f], which
 * correspond to the following 3×3 matrix (using row-vector convention):
 *
 *   [ a b 0 ]
 *   [ c d 0 ]
 *   [ e f 1 ]
 *
 * Use the named constructors for the most common cases:
 *
 *   Matrix::translate(72, 720) // position at (72, 720)
 *   Matrix::scale(2) // uniform scale ×2
 *   Matrix::scale(1.5, 0.5) // non-uniform scale
 *   Matrix::rotate(45) // 45° counter-clockwise
 *
 * Combine transforms with then(), which applies $next after $this:
 *
 *   Matrix::scale(2)->then(Matrix::translate(72, 720))
 *   // scale first, then translate
 *
 * Pass the result directly to PdfContentStreamBuilder::setTextMatrix() or
 * concatenateMatrix().
 */
final class Matrix
{
    private readonly float $a;
    private readonly float $b;
    private readonly float $c;
    private readonly float $d;
    private readonly float $e;
    private readonly float $f;

    public function __construct(float $a, float $b, float $c, float $d, float $e, float $f)
    {
        $this->a = $a;
        $this->b = $b;
        $this->c = $c;
        $this->d = $d;
        $this->e = $e;
        $this->f = $f;
    }

    // -------------------------------------------------------------------------
    // Named constructors
    // -------------------------------------------------------------------------

    /** Identity matrix — no transformation. */
    public static function identity(): self
    {
        return new self(1, 0, 0, 1, 0, 0);
    }

    /** Translates the origin to (x, y). Equivalent to [1 0 0 1 x y]. */
    public static function translate(float $x, float $y): self
    {
        return new self(1, 0, 0, 1, $x, $y);
    }

    /**
     * Scales along both axes. Pass a single value for uniform scaling.
     * Equivalent to [sx 0 0 sy 0 0].
     */
    public static function scale(float $sx, ?float $sy = null): self
    {
        $sy ??= $sx;

        return new self($sx, 0, 0, $sy, 0, 0);
    }

    /**
     * Rotates counter-clockwise by $degrees around the origin.
     * Equivalent to [cos θ sin θ −sin θ cos θ 0 0].
     */
    public static function rotate(float $degrees): self
    {
        $rad = deg2rad($degrees);
        $cos = cos($rad);
        $sin = sin($rad);

        return new self($cos, $sin, -$sin, $cos, 0, 0);
    }

    // -------------------------------------------------------------------------
    // Getters
    // -------------------------------------------------------------------------

    public function getA(): float
    {
        return $this->a;
    }

    public function getB(): float
    {
        return $this->b;
    }

    public function getC(): float
    {
        return $this->c;
    }

    public function getD(): float
    {
        return $this->d;
    }

    public function getE(): float
    {
        return $this->e;
    }

    public function getF(): float
    {
        return $this->f;
    }

    // -------------------------------------------------------------------------
    // Composition
    // -------------------------------------------------------------------------

    /**
     * Returns a new matrix that applies $this first, then $next.
     *
     * Mathematically this is the matrix product $this × $next under the PDF
     * row-vector convention. Use it to chain transforms in reading order:
     *
     *   Matrix::rotate(30)->then(Matrix::translate(72, 720))
     *   // rotate 30° around origin, then shift to (72, 720)
     */
    public function then(self $next): self
    {
        return new self(
            $this->a * $next->a + $this->b * $next->c,
            $this->a * $next->b + $this->b * $next->d,
            $this->c * $next->a + $this->d * $next->c,
            $this->c * $next->b + $this->d * $next->d,
            $this->e * $next->a + $this->f * $next->c + $next->e,
            $this->e * $next->b + $this->f * $next->d + $next->f,
        );
    }

    // -------------------------------------------------------------------------
    // Serialization
    // -------------------------------------------------------------------------

    /**
     * Returns the six values as a space-separated string suitable for embedding
     * in a PDF content stream operator, e.g. "1 0 0 1 72 720".
     */
    public function toPdfString(): string
    {
        return implode(' ', array_map(
            self::fmt(...),
            [$this->a, $this->b, $this->c, $this->d, $this->e, $this->f],
        ));
    }

    // -------------------------------------------------------------------------

    private static function fmt(float $v): string
    {
        return rtrim(rtrim(sprintf('%.6F', $v), '0'), '.');
    }
}
