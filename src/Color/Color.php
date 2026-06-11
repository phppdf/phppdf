<?php

declare(strict_types=1);

namespace PhpPdf\Color;

use BadMethodCallException;
use InvalidArgumentException;

/**
 * An immutable colour value for PDF stroking and fill operations.
 *
 * Supports the three PDF device colour models — grayscale, RGB, and CMYK.
 * Use one of the named constructors to create a colour, then pass it to
 * PdfContentStreamBuilder::strokeColor() or fillColor():
 *
 *   $stream->strokeColor(Color::fromHex('#e63b3b'))
 *          ->fillColor(Color::cmyk(0, 0.76, 0.76, 0.1))
 *          ->fillColor(Color::red()->lighter(0.3))
 *          ->fillColor(Color::blue()->mix(Color::white(), 0.5))
 *
 * All component values are in the range 0.0–1.0.
 */
final class Color
{
    private readonly ColorType $type;

    /** @var list<float> */
    private readonly array $components;

    /** @param list<float> $components */
    private function __construct(ColorType $type, array $components)
    {
        $this->components = $components;
        $this->type = $type;
    }

    // -------------------------------------------------------------------------
    // Constructors — colour models
    // -------------------------------------------------------------------------

    /** Grayscale: 0.0 = black, 1.0 = white. */
    public static function gray(float $lightness): self
    {
        return new self(ColorType::Gray, [self::clamp($lightness)]);
    }

    /** RGB: each component 0.0–1.0. */
    public static function rgb(float $r, float $g, float $b): self
    {
        return new self(ColorType::Rgb, [
            self::clamp($r),
            self::clamp($g),
            self::clamp($b),
        ]);
    }

    /** CMYK: each component 0.0–1.0. */
    public static function cmyk(float $c, float $m, float $y, float $k): self
    {
        return new self(ColorType::Cmyk, [
            self::clamp($c),
            self::clamp($m),
            self::clamp($y),
            self::clamp($k),
        ]);
    }

    /**
     * Parses a CSS-style hex string into an RGB colour.
     *
     * Accepts '#rrggbb', 'rrggbb', '#rgb', and 'rgb' (shorthand expanded).
     */
    public static function fromHex(string $hex): self
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            throw new InvalidArgumentException("Invalid hex colour: #{$hex}");
        }

        return self::rgb(
            hexdec(substr($hex, 0, 2)) / 255,
            hexdec(substr($hex, 2, 2)) / 255,
            hexdec(substr($hex, 4, 2)) / 255,
        );
    }

    // -------------------------------------------------------------------------
    // Named colours (CSS keyword set)
    // -------------------------------------------------------------------------

    public static function black(): self
    {
        return self::gray(0);
    }

    public static function white(): self
    {
        return self::gray(1);
    }

    public static function red(): self
    {
        return self::fromHex('#ff0000');
    }

    public static function green(): self
    {
        return self::fromHex('#008000');
    }

    public static function lime(): self
    {
        return self::fromHex('#00ff00');
    }

    public static function blue(): self
    {
        return self::fromHex('#0000ff');
    }

    public static function yellow(): self
    {
        return self::fromHex('#ffff00');
    }

    public static function cyan(): self
    {
        return self::fromHex('#00ffff');
    }

    public static function magenta(): self
    {
        return self::fromHex('#ff00ff');
    }

    public static function orange(): self
    {
        return self::fromHex('#ff6600');
    }

    public static function purple(): self
    {
        return self::fromHex('#800080');
    }

    public static function pink(): self
    {
        return self::fromHex('#ff69b4');
    }

    public static function brown(): self
    {
        return self::fromHex('#a52a2a');
    }

    public static function navy(): self
    {
        return self::fromHex('#000080');
    }

    public static function teal(): self
    {
        return self::fromHex('#008080');
    }

    // -------------------------------------------------------------------------
    // Colour manipulation
    // -------------------------------------------------------------------------

    /**
     * Returns a lighter version by mixing this colour toward white.
     *
     * $factor 0.0 = unchanged, 1.0 = white. Values are clamped to 0–1.
     */
    public function lighter(float $factor): self
    {
        $factor = self::clamp($factor);

        return new self($this->type, array_map(
            fn (float $c) => $this->type === ColorType::Cmyk
                ? $c * (1 - $factor) // reduce ink towards 0
                : $c + (1 - $c) * $factor, // shift towards 1
            $this->components,
        ));
    }

    /**
     * Returns a darker version by mixing this colour toward black.
     *
     * $factor 0.0 = unchanged, 1.0 = black. Values are clamped to 0–1.
     */
    public function darker(float $factor): self
    {
        $factor = self::clamp($factor);

        return new self($this->type, array_map(
            fn (float $c) => $this->type === ColorType::Cmyk
                ? min(1.0, $c + (1 - $c) * $factor) // increase ink towards 1
                : $c * (1 - $factor), // shift towards 0
            $this->components,
        ));
    }

    /**
     * Linearly interpolates between this colour and $other.
     *
     * $t = 0.0 returns $this, $t = 1.0 returns $other. Both colours must use
     * the same colour model.
     *
     * @throws \InvalidArgumentException if the colour models differ.
     */
    public function mix(self $other, float $t = 0.5): self
    {
        if ($this->type !== $other->type) {
            throw new InvalidArgumentException(
                'Cannot mix colours of different types. Convert both to the same model first.',
            );
        }

        $t = self::clamp($t);

        $blended = array_map(
            static fn (float $a, float $b) => $a + ($b - $a) * $t,
            $this->components,
            $other->components,
        );

        return new self($this->type, $blended);
    }

    // -------------------------------------------------------------------------
    // Getters — consumed by PdfContentStreamBuilder
    // -------------------------------------------------------------------------

    public function getType(): ColorType
    {
        return $this->type;
    }

    /** @return array<float> */
    /** @return list<float> */
    public function getComponents(): array
    {
        return $this->components;
    }

    /**
     * Returns the '#rrggbb' hex representation. Only valid for RGB colours.
     *
     * @throws \BadMethodCallException for non-RGB colours.
     */
    public function toHex(): string
    {
        if ($this->type !== ColorType::Rgb) {
            throw new BadMethodCallException('toHex() is only available for RGB colours.');
        }

        return sprintf(
            '#%02x%02x%02x',
            (int) round($this->components[0] * 255),
            (int) round($this->components[1] * 255),
            (int) round($this->components[2] * 255),
        );
    }

    // -------------------------------------------------------------------------

    private static function clamp(float $v): float
    {
        return max(0.0, min(1.0, $v));
    }
}
