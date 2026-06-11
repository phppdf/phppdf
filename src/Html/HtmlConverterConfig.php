<?php

declare(strict_types=1);

namespace PhpPdf\Html;

use InvalidArgumentException;
use PhpPdf\Builder\PdfPageSize;

/**
 * Configuration for the HTML-to-PDF converter.
 *
 * Controls the output page dimensions, margins, base typography, and the
 * font families available to the converter. All dimension values are in
 * PDF points (1 pt = 1/72 inch).
 *
 * Three families are pre-registered with their standard CSS names:
 *
 *   helvetica / arial / sans-serif → Helvetica Type 1 family
 *   times-roman / times / serif → Times Type 1 family
 *   courier / monospace → Courier Type 1 family
 *
 * Register additional families (including TrueType / OpenType) with
 * registerFontFamily():
 *
 *   $config = new HtmlConverterConfig();
 *   $config->registerFontFamily(
 *       ['roboto', 'sans-serif'], // first name is primary
 *       HtmlFontFamily::trueType(
 *           TrueTypeFont::fromFile('/fonts/Roboto-Regular.ttf'),
 *           TrueTypeFont::fromFile('/fonts/Roboto-Bold.ttf'),
 *           TrueTypeFont::fromFile('/fonts/Roboto-Italic.ttf'),
 *           TrueTypeFont::fromFile('/fonts/Roboto-BoldItalic.ttf'),
 *       ),
 *   );
 *   $config->setDefaultFontFamily('roboto');
 *
 *   // HTML / CSS can now use:
 *   // font-family: roboto;
 *   // font-family: Roboto, sans-serif;
 */
final class HtmlConverterConfig
{
    // ── Page geometry ────────────────────────────────────────────────────────

    private int $pageWidth;
    private int $pageHeight;
    private float $marginTop = 72.0;
    private float $marginRight = 72.0;
    private float $marginBottom = 72.0;
    private float $marginLeft = 72.0;

    // ── Typography ───────────────────────────────────────────────────────────

    private float $baseFontSize = 11.0;
    private float $lineHeightMultiplier = 1.4;

    // ── Font family registry ─────────────────────────────────────────────────

    /**
     * Primary family name → HtmlFontFamily, in registration order.
     *
     * @var array<string, \PhpPdf\Html\HtmlFontFamily>
     */
    private array $fontFamilies = [];

    /**
     * Alias → primary name mapping.
     *
     * @var array<string, string>
     */
    private array $fontAliases = [];

    /** Name of the family used when no font-family CSS is set. */
    private string $defaultFontFamily = 'helvetica';

    // ── Construction ─────────────────────────────────────────────────────────

    public function __construct()
    {
        [$this->pageWidth, $this->pageHeight] = PdfPageSize::A4;

        // Pre-register the three standard Type 1 families.
        $this->registerFontFamily(
            ['helvetica', 'arial', 'sans-serif'],
            HtmlFontFamily::type1('Helvetica', 'Helvetica-Bold', 'Helvetica-Oblique', 'Helvetica-BoldOblique'),
        );
        $this->registerFontFamily(
            ['times-roman', 'times', 'times new roman', 'serif'],
            HtmlFontFamily::type1('Times-Roman', 'Times-Bold', 'Times-Italic', 'Times-BoldItalic'),
        );
        $this->registerFontFamily(
            ['courier', 'courier new', 'monospace'],
            HtmlFontFamily::type1('Courier', 'Courier-Bold', 'Courier-Oblique', 'Courier-BoldOblique'),
        );
    }

    // ── Page geometry getters/setters ────────────────────────────────────────

    public function getPageWidth(): int
    {
        return $this->pageWidth;
    }

    public function setPageWidth(int $width): self
    {
        $this->pageWidth = $width;

        return $this;
    }

    public function getPageHeight(): int
    {
        return $this->pageHeight;
    }

    public function setPageHeight(int $height): self
    {
        $this->pageHeight = $height;

        return $this;
    }

    public function getMarginTop(): float
    {
        return $this->marginTop;
    }

    public function setMarginTop(float $margin): self
    {
        $this->marginTop = $margin;

        return $this;
    }

    public function getMarginRight(): float
    {
        return $this->marginRight;
    }

    public function setMarginRight(float $margin): self
    {
        $this->marginRight = $margin;

        return $this;
    }

    public function getMarginBottom(): float
    {
        return $this->marginBottom;
    }

    public function setMarginBottom(float $margin): self
    {
        $this->marginBottom = $margin;

        return $this;
    }

    public function getMarginLeft(): float
    {
        return $this->marginLeft;
    }

    public function setMarginLeft(float $margin): self
    {
        $this->marginLeft = $margin;

        return $this;
    }

    // ── Typography getters/setters ───────────────────────────────────────────

    public function getBaseFontSize(): float
    {
        return $this->baseFontSize;
    }

    public function setBaseFontSize(float $size): self
    {
        $this->baseFontSize = $size;

        return $this;
    }

    public function getLineHeightMultiplier(): float
    {
        return $this->lineHeightMultiplier;
    }

    public function setLineHeightMultiplier(float $multiplier): self
    {
        $this->lineHeightMultiplier = $multiplier;

        return $this;
    }

    // ── Geometry helpers ─────────────────────────────────────────────────────

    /**
     * Returns the usable content width (page width minus left and right margins).
     */
    public function contentWidth(): float
    {
        return $this->pageWidth - $this->marginLeft - $this->marginRight;
    }

    /**
     * Returns the usable content height (page height minus top and bottom margins).
     */
    public function contentHeight(): float
    {
        return $this->pageHeight - $this->marginTop - $this->marginBottom;
    }

    // ── Font family registration ──────────────────────────────────────────────

    /**
     * Registers a font family under one or more CSS names.
     *
     * The first name in $names is the *primary* name used internally. All
     * remaining names are aliases that resolve to the same family.
     *
     * Registering under an already-registered primary name replaces that
     * family. Registering under an existing alias re-points the alias.
     *
     * @param string|array<string> $names CSS name(s); compared case-insensitively.
     * @throws \InvalidArgumentException when $names is empty.
     */
    public function registerFontFamily(string|array $names, HtmlFontFamily $family): self
    {
        $names = array_values(array_filter(
            array_map(
                static fn (string $n) => strtolower(trim($n)),
                is_array($names) ? $names : [$names],
            ),
            static fn (string $n) => $n !== '',
        ));

        if ($names === []) {
            throw new InvalidArgumentException('At least one font family name must be provided.');
        }

        $primary = $names[0];
        $this->fontFamilies[$primary] = $family;

        foreach (array_slice($names, 1) as $alias) {
            $this->fontAliases[$alias] = $primary;
        }

        return $this;
    }

    /**
     * Sets the family used when no font-family CSS rule is in effect.
     *
     * Must be a primary name previously passed to registerFontFamily().
     *
     * @throws \InvalidArgumentException when $name is not registered.
     */
    public function setDefaultFontFamily(string $name): self
    {
        $name = strtolower(trim($name));

        if (!isset($this->fontFamilies[$name])) {
            throw new InvalidArgumentException(
                "Font family '{$name}' is not registered. Call registerFontFamily() first.",
            );
        }

        $this->defaultFontFamily = $name;

        return $this;
    }

    // ── Internal accessors (used by StyleResolver and HtmlLayoutEngine) ───────

    /**
     * Returns the primary name of the default font family.
     *
     * @internal
     */
    public function getDefaultFontFamily(): string
    {
        return $this->defaultFontFamily;
    }

    /**
     * Parses a CSS font-family declaration value and returns the primary name
     * of the first matching registered family, or null when no match is found.
     *
     * Example: resolveFontFamilyName("'Times New Roman', Times, serif")
     *          → 'times-roman' (if registered under that alias)
     *
     * @internal
     */
    public function resolveFontFamilyName(string $cssValue): ?string
    {
        // Split on commas; each token may be quoted with ' or ".
        foreach (explode(',', $cssValue) as $token) {
            $name = strtolower(trim($token, " \t\n\r\0\x0B'\""));

            if (isset($this->fontFamilies[$name])) {
                return $name;
            }

            if (isset($this->fontAliases[$name])) {
                return $this->fontAliases[$name];
            }
        }

        return null;
    }

    /**
     * Returns all registered families in registration order.
     *
     * @return array<string, \PhpPdf\Html\HtmlFontFamily> primary name → family
     * @internal
     */
    public function getFontFamilies(): array
    {
        return $this->fontFamilies;
    }

    /**
     * Returns the zero-based registration index for a primary family name, or
     * 0 (the first registered family) when the name is not found.
     *
     * @internal
     */
    public function getFamilyIndex(string $primaryName): int
    {
        $index = array_search($primaryName, array_keys($this->fontFamilies), true);

        return $index !== false
            ? $index
            : 0;
    }

    /**
     * Generates the PDF resource name for a specific (family, bold, italic) triple.
     *
     * Format: F{familyIndex}{variant} where variant ∈ {N, B, I, X}
     *   N = normal, B = bold, I = italic, X = bold-italic
     *
     * Examples: F0N, F0B, F1I, F2X
     *
     * @internal
     */
    public function resourceName(string $primaryFamilyName, bool $bold, bool $italic): string
    {
        $idx = $this->getFamilyIndex($primaryFamilyName);

        $suffix = match (true) {
            $bold && $italic => 'X',
            $bold => 'B',
            $italic => 'I',
            default => 'N',
        };

        return "F{$idx}{$suffix}";
    }
}
