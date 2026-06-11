<?php

declare(strict_types=1);

namespace PhpPdf\Html;

use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Font\FontMetrics;
use PhpPdf\Font\TrueTypeFont;
use PhpPdf\Font\TrueTypeFontMetrics;
use PhpPdf\Font\Type1FontMetrics;

/**
 * Describes one CSS font family with up to four style variants.
 *
 * A font family has separate fonts for the four combinations of bold × italic:
 *   normal, bold, italic, bold-italic.
 *
 * Missing variants fall back to the normal variant automatically, so you only
 * need to supply the fonts you actually have.
 *
 * Two types of font are supported:
 *
 *  Type 1 (standard)
 *  ─────────────────
 *  Uses one of the 14 standard PDF base fonts — no embedding required.
 *  Viewers are guaranteed to have these fonts available.
 *
 *    $family = HtmlFontFamily::type1(
 *        normal: 'Helvetica',
 *        bold: 'Helvetica-Bold',
 *        italic: 'Helvetica-Oblique',
 *        boldItalic: 'Helvetica-BoldOblique',
 *    );
 *
 *  TrueType / OpenType (embedded)
 *  ───────────────────────────────
 *  Font programs are embedded in the PDF via TrueTypeFont. Only the glyphs
 *  actually used on each page are included (subsetting).
 *
 *    $family = HtmlFontFamily::trueType(
 *        normal: TrueTypeFont::fromFile('/fonts/Roboto-Regular.ttf'),
 *        bold: TrueTypeFont::fromFile('/fonts/Roboto-Bold.ttf'),
 *        italic: TrueTypeFont::fromFile('/fonts/Roboto-Italic.ttf'),
 *        boldItalic: TrueTypeFont::fromFile('/fonts/Roboto-BoldItalic.ttf'),
 *    );
 *
 * Registration
 * ────────────
 *  Register families with HtmlConverterConfig::registerFontFamily() using the
 *  CSS name you want to address in style sheets or inline styles. The three
 *  standard families (helvetica, times-roman, courier) are pre-registered.
 */
final class HtmlFontFamily
{
    // Variant indices (internal, not part of the public API).
    private const int NORMAL = 0;
    private const int BOLD = 1;
    private const int ITALIC = 2;
    private const int BOLD_ITALIC = 3;

    private bool $embedded;

    /**
     * Per-variant Type 1 base-font names (when $embedded === false).
     *
     * @var array<int, string>
     */
    private array $type1Names = [];

    /**
     * Per-variant TrueType font objects (when $embedded === true).
     *
     * @var array<int, \PhpPdf\Font\TrueTypeFont>
     */
    private array $ttFonts = [];

    /**
     * Per-variant font metrics (pre-computed at construction time).
     *
     * @var array<int, \PhpPdf\Font\FontMetrics>
     */
    private array $metrics = [];

    private function __construct()
    {
    }

    // -------------------------------------------------------------------------
    // Named constructors
    // -------------------------------------------------------------------------

    /**
     * Creates a family backed by the 14 standard PDF Type 1 fonts.
     *
     * Pass null for any variant you do not have — that variant will silently
     * fall back to the normal weight/style.
     *
     * Standard base-font names (case-sensitive as required by the PDF spec):
     *   Helvetica, Helvetica-Bold, Helvetica-Oblique, Helvetica-BoldOblique
     *   Times-Roman, Times-Bold, Times-Italic, Times-BoldItalic
     *   Courier, Courier-Bold, Courier-Oblique, Courier-BoldOblique
     *   Symbol, ZapfDingbats
     */
    public static function type1(
        string $normal,
        ?string $bold = null,
        ?string $italic = null,
        ?string $boldItalic = null,
    ): self {
        $self = new self();
        $self->embedded = false;

        $self->type1Names = [
            self::BOLD => $bold ?? $normal,
            self::BOLD_ITALIC => $boldItalic ?? $bold ?? $normal,
            self::ITALIC => $italic ?? $normal,
            self::NORMAL => $normal,
        ];

        $self->metrics = [
            self::BOLD => self::type1Metrics($bold ?? $normal),
            self::BOLD_ITALIC => self::type1Metrics($boldItalic ?? $bold ?? $normal),
            self::ITALIC => self::type1Metrics($italic ?? $normal),
            self::NORMAL => self::type1Metrics($normal),
        ];

        return $self;
    }

    /**
     * Creates a family backed by embedded TrueType / OpenType font files.
     *
     * Pass null for any variant you do not have — that variant will silently
     * fall back to the normal weight/style.
     *
     * Example:
     *   HtmlFontFamily::trueType(
     *       TrueTypeFont::fromFile('/fonts/Roboto-Regular.ttf'),
     *       TrueTypeFont::fromFile('/fonts/Roboto-Bold.ttf'),
     *       TrueTypeFont::fromFile('/fonts/Roboto-Italic.ttf'),
     *       TrueTypeFont::fromFile('/fonts/Roboto-BoldItalic.ttf'),
     *   )
     */
    public static function trueType(
        TrueTypeFont $normal,
        ?TrueTypeFont $bold = null,
        ?TrueTypeFont $italic = null,
        ?TrueTypeFont $boldItalic = null,
    ): self {
        $self = new self();
        $self->embedded = true;

        $self->ttFonts = [
            self::BOLD => $bold ?? $normal,
            self::BOLD_ITALIC => $boldItalic ?? $bold ?? $normal,
            self::ITALIC => $italic ?? $normal,
            self::NORMAL => $normal,
        ];

        $self->metrics = [
            self::BOLD => TrueTypeFontMetrics::fromFont($bold ?? $normal),
            self::BOLD_ITALIC => TrueTypeFontMetrics::fromFont($boldItalic ?? $bold ?? $normal),
            self::ITALIC => TrueTypeFontMetrics::fromFont($italic ?? $normal),
            self::NORMAL => TrueTypeFontMetrics::fromFont($normal),
        ];

        return $self;
    }

    // -------------------------------------------------------------------------
    // Querying
    // -------------------------------------------------------------------------

    /**
     * Returns true when the family uses embedded TrueType / OpenType fonts.
     * Returns false for standard Type 1 fonts.
     */
    public function isEmbedded(): bool
    {
        return $this->embedded;
    }

    /**
     * Returns the font metrics for the given style variant.
     */
    public function getMetrics(bool $bold, bool $italic): FontMetrics
    {
        return $this->metrics[$this->variantIndex($bold, $italic)];
    }

    // -------------------------------------------------------------------------
    // Page integration
    // -------------------------------------------------------------------------

    /**
     * Registers one style variant of this family on $page under $resourceName.
     *
     * For Type 1 fonts this calls PdfPageBuilder::useType1Font().
     * For TrueType fonts this calls PdfPageBuilder::useEmbeddedFont().
     *
     * $resourceName must be unique within the page's resource dictionary;
     * it is produced by HtmlConverterConfig::resourceName() based on the
     * family's registration index.
     */
    public function registerVariantOnPage(PdfPageBuilder $page, string $resourceName, bool $bold, bool $italic,): void
    {
        $idx = $this->variantIndex($bold, $italic);

        if ($this->embedded) {
            $page->useEmbeddedFont($resourceName, $this->ttFonts[$idx]);
        } else {
            $page->useType1Font($resourceName, $this->type1Names[$idx]);
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function variantIndex(bool $bold, bool $italic): int
    {
        return match (true) {
            $bold && $italic => self::BOLD_ITALIC,
            $bold => self::BOLD,
            $italic => self::ITALIC,
            default => self::NORMAL,
        };
    }

    /**
     * Returns a FontMetrics instance for the given Type 1 base-font name.
     *
     * Recognises the 12 standard text fonts (Helvetica, Times, Courier families).
     * Any other name falls back to Helvetica metrics.
     */
    private static function type1Metrics(string $baseFont): FontMetrics
    {
        return match ($baseFont) {
            'Helvetica' => Type1FontMetrics::helvetica(),
            'Helvetica-Bold' => Type1FontMetrics::helveticaBold(),
            'Helvetica-Oblique' => Type1FontMetrics::helveticaOblique(),
            'Helvetica-BoldOblique' => Type1FontMetrics::helveticaBoldOblique(),
            'Times-Roman' => Type1FontMetrics::timesRoman(),
            'Times-Bold' => Type1FontMetrics::timesBold(),
            'Times-Italic' => Type1FontMetrics::timesItalic(),
            'Times-BoldItalic' => Type1FontMetrics::timesBoldItalic(),
            'Courier' => Type1FontMetrics::courier(),
            'Courier-Bold' => Type1FontMetrics::courierBold(),
            'Courier-Oblique' => Type1FontMetrics::courierOblique(),
            'Courier-BoldOblique' => Type1FontMetrics::courierBoldOblique(),
            default => Type1FontMetrics::helvetica(),
        };
    }
}
