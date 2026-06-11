<?php

declare(strict_types=1);

namespace PhpPdf\Content;

use PhpPdf\Barcode\LinearBarcode;
use PhpPdf\Barcode\QrCode;
use PhpPdf\Color\Color;
use PhpPdf\Color\ColorType;
use PhpPdf\Font\FontMetrics;
use PhpPdf\Font\TrueTypeFont;
use PhpPdf\Object\PdfContentStream;
use PhpPdf\Text\ListBox;
use PhpPdf\Text\RichTextBox;
use PhpPdf\Text\TextAlign;
use PhpPdf\Text\TextBox;

use const INF;

/**
 * Fluent builder for PDF content streams.
 *
 * Accumulates PDF content stream operators in order and produces a
 * PdfContentStream once build() is called. Methods are grouped by the
 * PDF specification's operator categories: graphics state, path construction,
 * path painting, clipping, text, color, XObjects, shading, marked content,
 * compatibility, and Type 3 font glyph programs.
 *
 * The general drawing sequence is:
 *   1. Optionally save/restore graphics state around changes.
 *   2. Construct a path with moveTo(), lineTo(), curveTo(), etc.
 *   3. Paint it with stroke(), fill(), or a combined operator.
 *
 * The general text sequence is:
 *   1. Open a text object with beginText().
 *   2. Set font and size with setFont().
 *   3. Position the text with setTextMatrix() or moveTextPosition().
 *   4. Show the text with showText() or a related operator.
 *   5. Close the text object with endText().
 */
final class PdfContentStreamBuilder
{
    /** @var list<\PhpPdf\Content\Operation\PdfContentOperation> */
    private array $operations = [];

    /** Local font name set by the most recent setFont() call. */
    private string $currentFont = '';

    /**
     * Maps local font name → TrueTypeFont for embedded fonts.
     * Injected by PdfPageBuilder so showText() can look up glyph IDs.
     *
     * @var array<string, \PhpPdf\Font\TrueTypeFont>
     */
    private array $embeddedFonts = [];

    /**
     * Accumulated glyph usage per embedded font: [fontName => [glyphId => codePoint]].
     * Used by PdfPageBuilder to build /W and ToUnicode entries with only the
     * glyphs that were actually referenced in the content stream.
     *
     * @var array<string, array<int, int>>
     */
    private array $usedGlyphs = [];

    /**
     * @param array<string, \PhpPdf\Font\TrueTypeFont> $embeddedFonts
     *   Map of local resource name → TrueTypeFont for every font registered
     *   with PdfPageBuilder::useEmbeddedFont(). Type1 fonts need not appear here.
     */
    public function __construct(array $embeddedFonts = [])
    {
        $this->embeddedFonts = $embeddedFonts;
    }

    /**
     * Returns accumulated glyph usage collected while building the content stream.
     * Called by PdfPageBuilder after the content callbacks have run.
     *
     * @return array<string, array<int, int>> [fontName => [glyphId => codePoint]]
     */
    public function getUsedGlyphs(): array
    {
        return $this->usedGlyphs;
    }

    // -------------------------------------------------------------------------
    // Graphics State
    // -------------------------------------------------------------------------

    /**
     * Saves the entire current graphics state onto a stack.
     *
     * Use this before making temporary changes - such as applying a
     * transformation matrix, changing line style, or altering color - that
     * should not affect drawing operations that follow. Always pair with a
     * matching restoreGraphicsState().
     */
    public function saveGraphicsState(): self
    {
        $this->operations[] = new Operation\SaveGraphicsState();

        return $this;
    }

    /**
     * Restores the most recently saved graphics state from the stack.
     *
     * Reverts all graphics state parameters (CTM, color, line style, clipping
     * path, etc.) to the values they had when saveGraphicsState() was called.
     * Must be balanced with a preceding saveGraphicsState().
     */
    public function restoreGraphicsState(): self
    {
        $this->operations[] = new Operation\RestoreGraphicsState();

        return $this;
    }

    /**
     * Concatenates a transformation matrix to the current transformation matrix (CTM).
     *
     * Use this to translate, scale, rotate, or skew all subsequent path and
     * text drawing operations. Wrap in saveGraphicsState() /
     * restoreGraphicsState() to limit the scope of the transformation.
     *
     * Example — double the size of everything drawn in a block:
     *   $stream->saveGraphicsState()
     *          ->concatenateMatrix(Matrix::scale(2))
     *          ->...
     *          ->restoreGraphicsState();
     */
    public function concatenateMatrix(Matrix $matrix): self
    {
        $this->operations[] = new Operation\ConcatenateMatrix($matrix);

        return $this;
    }

    /**
     * Sets the stroke width for subsequent path-stroking operations.
     *
     * The width is expressed in user-space units. The default is 1. Set this
     * before calling stroke(), closeAndStroke(), or any combined fill-and-stroke
     * operator.
     */
    public function setLineWidth(float $width): self
    {
        $this->operations[] = new Operation\SetLineWidth($width);

        return $this;
    }

    /**
     * Sets the shape used at the ends of open stroked paths.
     *
     * Valid values: 0 = butt (square, flush with endpoint), 1 = round (semicircle),
     * 2 = projecting square (extends half the line width beyond the endpoint).
     * Only affects open subpaths; closed subpaths use the line join style at
     * the closing point.
     */
    public function setLineCap(int $style): self
    {
        $this->operations[] = new Operation\SetLineCap($style);

        return $this;
    }

    /**
     * Sets the shape used at corners where two stroked path segments meet.
     *
     * Valid values: 0 = miter (sharp point, subject to miter limit),
     * 1 = round (arc centered on the join point), 2 = bevel (flattened corner).
     * The miter style can produce very long spikes at sharp angles; use
     * setMiterLimit() to cap the spike length.
     */
    public function setLineJoin(int $style): self
    {
        $this->operations[] = new Operation\SetLineJoin($style);

        return $this;
    }

    /**
     * Sets the maximum length of miter joins as a ratio to the line width.
     *
     * Only applies when the line join style is miter (0). When a miter join
     * would exceed this limit the join is cut to a bevel instead. The default
     * is 10. Use a lower value (e.g. 2) to avoid excessively pointed corners
     * on acute angles.
     */
    public function setMiterLimit(float $limit): self
    {
        $this->operations[] = new Operation\SetMiterLimit($limit);

        return $this;
    }

    /**
     * Sets the dash pattern used when stroking paths.
     *
     * $dashArray alternates dash lengths and gap lengths in user-space units;
     * an empty array restores a solid line. $phase specifies the distance into
     * the pattern at which to start. For example, ([3, 5], 6) begins 6 units
     * into a pattern of 3-unit dashes and 5-unit gaps.
     *
     * @param list<float> $dashArray
     */
    public function setDashPattern(array $dashArray, float $phase): self
    {
        $this->operations[] = new Operation\SetDashPattern($dashArray, $phase);

        return $this;
    }

    /**
     * Sets the color rendering intent for CIE-based color conversions.
     *
     * Valid values: AbsoluteColorimetric, RelativeColorimetric, Saturation,
     * Perceptual. Use to control how out-of-gamut colors are mapped when
     * converting between color spaces, typically for print workflows. Requires
     * PDF 1.1.
     */
    public function setRenderingIntent(string $intent): self
    {
        $this->operations[] = new Operation\SetRenderingIntent($intent);

        return $this;
    }

    /**
     * Sets the flatness tolerance for rendering curved paths.
     *
     * Controls the maximum allowed deviation (in device pixels) when
     * approximating curved path segments with straight lines. A value of 0
     * requests the device's default tolerance. Increase this on low-resolution
     * devices to improve rendering speed at the cost of curve smoothness.
     */
    public function setFlatnessTolerance(float $flatness): self
    {
        $this->operations[] = new Operation\SetFlatnessTolerance($flatness);

        return $this;
    }

    /**
     * Applies a named graphics state parameter dictionary to the current state.
     *
     * $name must match an entry in the page's ExtGState resource dictionary.
     * Use this to set graphics state parameters that cannot be expressed with
     * individual operators, such as opacity, blend mode, soft masks, or
     * overprint settings. Requires PDF 1.2.
     */
    public function setGraphicsStateParameters(string $name): self
    {
        $this->operations[] = new Operation\SetGraphicsStateParameters($name);

        return $this;
    }

    // -------------------------------------------------------------------------
    // Path Construction
    // -------------------------------------------------------------------------

    /**
     * Begins a new subpath at the given coordinates without drawing anything.
     *
     * Sets the current point to (x, y) and starts a new subpath. Must be
     * called before any other path construction operator (lineTo(), curveTo(),
     * etc.). If a previous subpath was open it is ended implicitly, but not
     * closed - use closePath() first if a closing line is needed.
     */
    public function moveTo(float $x, float $y): self
    {
        $this->operations[] = new Operation\BeginSubpath($x, $y);

        return $this;
    }

    /**
     * Appends a straight line segment from the current point to (x, y).
     *
     * Updates the current point to (x, y). Must be preceded by moveTo() or
     * another path construction call. Chain multiple lineTo() calls to build
     * polygons or polylines before painting with stroke() or fill().
     */
    public function lineTo(float $x, float $y): self
    {
        $this->operations[] = new Operation\AppendLine($x, $y);

        return $this;
    }

    /**
     * Appends a cubic Bézier curve from the current point to (x3, y3).
     *
     * (x1, y1) is the first control point (tangent at the start) and (x2, y2)
     * is the second control point (tangent at the end). The current point is
     * updated to (x3, y3). Use for smooth curves where both tangent directions
     * are known.
     */
    public function curveTo(float $x1, float $y1, float $x2, float $y2, float $x3, float $y3): self
    {
        $this->operations[] = new Operation\AppendCubicBezier($x1, $y1, $x2, $y2, $x3, $y3);

        return $this;
    }

    /**
     * Appends a cubic Bézier curve where the first control point is the current point.
     *
     * (x2, y2) is the second control point and (x3, y3) is the endpoint. Use
     * when the curve must leave the current point tangentially (i.e. the
     * starting direction is already determined by the preceding segment).
     */
    public function curveToReplicateInitial(float $x2, float $y2, float $x3, float $y3): self
    {
        $this->operations[] = new Operation\AppendCubicBezierReplicateInitial($x2, $y2, $x3, $y3);

        return $this;
    }

    /**
     * Appends a cubic Bézier curve where the second control point equals the endpoint.
     *
     * (x1, y1) is the first control point and (x3, y3) is both the second
     * control point and the endpoint. Use when the curve must arrive at its
     * endpoint tangentially from a fixed direction (e.g. horizontal or vertical
     * arrival).
     */
    public function curveToReplicateFinal(float $x1, float $y1, float $x3, float $y3): self
    {
        $this->operations[] = new Operation\AppendCubicBezierReplicateFinal($x1, $y1, $x3, $y3);

        return $this;
    }

    /**
     * Closes the current subpath by appending a straight line to its starting point.
     *
     * After closing, the current point becomes the starting point of the
     * subpath. Call this before stroke() or fill() when the path needs a
     * closing edge. Has no effect if the subpath is already closed or consists
     * of a single point.
     */
    public function closePath(): self
    {
        $this->operations[] = new Operation\CloseSubpath();

        return $this;
    }

    /**
     * Appends a complete closed rectangular subpath.
     *
     * (x, y) is the lower-left corner of the rectangle. Equivalent to
     * moveTo(x, y) → lineTo(x+w, y) → lineTo(x+w, y+h) → lineTo(x, y+h) →
     * closePath(). Use as a concise shorthand when drawing rectangles,
     * borders, or highlight boxes.
     */
    public function rectangle(float $x, float $y, float $width, float $height): self
    {
        $this->operations[] = new Operation\AppendRectangle($x, $y, $width, $height);

        return $this;
    }

    /**
     * Appends a closed circular subpath centred at (cx, cy) with radius r.
     *
     * Approximated by four cubic Bézier arcs; the maximum radial error is less
     * than 0.03 %. Paint it with stroke(), fill(), or fillAndStroke().
     */
    public function circle(float $cx, float $cy, float $r): self
    {
        return $this->ellipse($cx, $cy, $r, $r);
    }

    /**
     * Appends a closed elliptical subpath centred at (cx, cy) with horizontal
     * radius rx and vertical radius ry.
     *
     * Approximated by four cubic Bézier arcs. Paint it with stroke(), fill(),
     * or fillAndStroke().
     */
    public function ellipse(float $cx, float $cy, float $rx, float $ry): self
    {
        // Cubic Bézier approximation constant for a quarter-circle arc.
        $k = 0.5522847498; // 4*(sqrt(2)-1)/3
        $kx = $rx * $k;
        $ky = $ry * $k;

        return $this
            ->moveTo($cx + $rx, $cy)
            ->curveTo($cx + $rx, $cy + $ky, $cx + $kx, $cy + $ry, $cx, $cy + $ry) // Q1
            ->curveTo($cx - $kx, $cy + $ry, $cx - $rx, $cy + $ky, $cx - $rx, $cy) // Q2
            ->curveTo($cx - $rx, $cy - $ky, $cx - $kx, $cy - $ry, $cx, $cy - $ry) // Q3
            ->curveTo($cx + $kx, $cy - $ry, $cx + $rx, $cy - $ky, $cx + $rx, $cy) // Q4
            ->closePath();
    }

    /**
     * Appends a closed rectangle subpath with rounded corners of radius r.
     *
     * (x, y) is the bottom-left corner. r is clamped to half the shorter side
     * to prevent degenerate shapes. Paint it with stroke(), fill(), or
     * fillAndStroke().
     */
    public function roundedRectangle(float $x, float $y, float $width, float $height, float $r): self
    {
        $r = min($r, $width / 2, $height / 2);
        $k = 0.5522847498;
        $kr = $r * $k;

        return $this
            ->moveTo($x + $r, $y)
            ->lineTo($x + $width - $r, $y)
            ->curveTo($x + $width - $r + $kr, $y, $x + $width, $y + $r - $kr, $x + $width, $y + $r)
            ->lineTo($x + $width, $y + $height - $r)
            ->curveTo(
                $x + $width,
                $y + $height - $r + $kr,
                $x + $width - $r + $kr,
                $y + $height,
                $x + $width - $r,
                $y + $height,
            )
            ->lineTo($x + $r, $y + $height)
            ->curveTo($x + $r - $kr, $y + $height, $x, $y + $height - $r + $kr, $x, $y + $height - $r)
            ->lineTo($x, $y + $r)
            ->curveTo($x, $y + $r - $kr, $x + $r - $kr, $y, $x + $r, $y)
            ->closePath();
    }

    // -------------------------------------------------------------------------
    // Path Painting
    // -------------------------------------------------------------------------

    /**
     * Strokes the current path and then clears it.
     *
     * Paints a line along the path using the current stroking color, line
     * width, cap, join, and dash settings. Use after constructing an open or
     * closed path when only the outline should be drawn.
     */
    public function stroke(): self
    {
        $this->operations[] = new Operation\StrokePath();

        return $this;
    }

    /**
     * Closes the current subpath and then strokes the path.
     *
     * Equivalent to closePath() followed by stroke(). Use when the path is
     * open and a closing edge is needed before stroking.
     */
    public function closeAndStroke(): self
    {
        $this->operations[] = new Operation\CloseAndStroke();

        return $this;
    }

    /**
     * Fills the current path using the nonzero winding number rule.
     *
     * Paints the interior of the path with the current non-stroking color.
     * The nonzero rule treats all enclosed regions as "inside" for most
     * typical shapes. Use fillEvenOdd() instead when self-intersecting paths
     * should create "holes".
     */
    public function fill(): self
    {
        $this->operations[] = new Operation\FillPath();

        return $this;
    }

    /**
     * Fills the current path using the even-odd rule.
     *
     * Regions enclosed an even number of times by the path are treated as
     * outside and left unpainted, creating holes. Use for self-intersecting
     * shapes such as stars or donuts where the winding direction is
     * inconsistent.
     */
    public function fillEvenOdd(): self
    {
        $this->operations[] = new Operation\FillPathEvenOdd();

        return $this;
    }

    /**
     * Fills and then strokes the current path using the nonzero winding rule.
     *
     * More efficient than calling fill() and stroke() separately, as the path
     * is painted in a single operation. The fill uses the non-stroking color
     * and the stroke uses the stroking color.
     */
    public function fillAndStroke(): self
    {
        $this->operations[] = new Operation\FillAndStroke();

        return $this;
    }

    /**
     * Fills (even-odd rule) and then strokes the current path.
     *
     * Combines fillEvenOdd() and stroke() in one operation. Use for
     * self-intersecting shapes that need both an outlined border and
     * even-odd interior rendering.
     */
    public function fillAndStrokeEvenOdd(): self
    {
        $this->operations[] = new Operation\FillAndStrokeEvenOdd();

        return $this;
    }

    /**
     * Closes, fills (nonzero rule), and strokes the current path.
     *
     * Equivalent to closePath() followed by fillAndStroke(). Use when the
     * path is open and needs closing before the combined paint operation.
     */
    public function closeFillAndStroke(): self
    {
        $this->operations[] = new Operation\CloseFillAndStroke();

        return $this;
    }

    /**
     * Closes, fills (even-odd rule), and strokes the current path.
     *
     * Equivalent to closePath() followed by fillAndStrokeEvenOdd().
     */
    public function closeFillAndStrokeEvenOdd(): self
    {
        $this->operations[] = new Operation\CloseFillAndStrokeEvenOdd();

        return $this;
    }

    /**
     * Ends the current path without painting it.
     *
     * Discards the path without filling or stroking. Use after a clipping
     * operator (clip() or clipEvenOdd()) to apply the clipping region without
     * marking the page, or to abandon a path that is no longer needed.
     */
    public function endPath(): self
    {
        $this->operations[] = new Operation\EndPath();

        return $this;
    }

    // -------------------------------------------------------------------------
    // Clipping Path
    // -------------------------------------------------------------------------

    /**
     * Intersects the current clipping path with the current path (nonzero rule).
     *
     * Must be called after the path is constructed but before the path-painting
     * or path-ending operator (stroke(), fill(), endPath(), etc.) that
     * terminates the path. The new clipping region is the intersection of the
     * existing clip and the filled area of the current path. Use
     * saveGraphicsState() before this call to restore the previous clip later.
     */
    public function clip(): self
    {
        $this->operations[] = new Operation\SetClippingPath();

        return $this;
    }

    /**
     * Intersects the current clipping path with the current path (even-odd rule).
     *
     * Same as clip() but uses the even-odd rule to determine which regions
     * of the current path are considered inside. Use when the path is
     * self-intersecting.
     */
    public function clipEvenOdd(): self
    {
        $this->operations[] = new Operation\SetClippingPathEvenOdd();

        return $this;
    }

    // -------------------------------------------------------------------------
    // Text Object
    // -------------------------------------------------------------------------

    /**
     * Opens a text object, initialising the text matrix and text line matrix.
     *
     * All text state operators and text showing operators must be called within
     * a text object. Text objects cannot be nested. Always pair with a matching
     * endText(). Path construction operators are not permitted between
     * beginText() and endText().
     */
    public function beginText(): self
    {
        $this->operations[] = new Operation\BeginText();

        return $this;
    }

    /**
     * Closes the current text object.
     *
     * Discards the text matrix. Must be paired with a preceding beginText().
     */
    public function endText(): self
    {
        $this->operations[] = new Operation\EndText();

        return $this;
    }

    // -------------------------------------------------------------------------
    // Text State
    // -------------------------------------------------------------------------

    /**
     * Sets additional spacing inserted after each glyph.
     *
     * Positive values widen the gaps between characters; negative values
     * tighten them. Applied in addition to the glyph's own advance width.
     * Default is 0. Use for letter-spacing effects or to justify text.
     */
    public function setCharacterSpacing(float $spacing): self
    {
        $this->operations[] = new Operation\SetCharacterSpacing($spacing);

        return $this;
    }

    /**
     * Sets additional spacing inserted after each ASCII space character (0x20).
     *
     * Applies only to simple (non-composite) fonts. Positive values increase
     * the space width; negative values decrease it. Default is 0. Use to
     * justify lines by expanding inter-word gaps.
     */
    public function setWordSpacing(float $spacing): self
    {
        $this->operations[] = new Operation\SetWordSpacing($spacing);

        return $this;
    }

    /**
     * Sets the horizontal scaling of text as a percentage of its normal width.
     *
     * 100 is normal width. Values below 100 condense the text; values above
     * 100 expand it. This scaling is applied after the character spacing and
     * glyph width. Use for condensed or expanded typographic effects.
     */
    public function setHorizontalTextScaling(float $scale): self
    {
        $this->operations[] = new Operation\SetHorizontalTextScaling($scale);

        return $this;
    }

    /**
     * Sets the vertical distance between baselines for line-advancing operators.
     *
     * Defines the distance moveToNextLine(), moveTextPositionAndSetLeading(),
     * and the single-quote text showing operator will move down when advancing
     * to the next line. Must be set before using those operators. Default is 0.
     */
    public function setTextLeading(float $leading): self
    {
        $this->operations[] = new Operation\SetTextLeading($leading);

        return $this;
    }

    /**
     * Sets the font and size for subsequent text showing operations.
     *
     * $font must match a key in the current page's Font resource dictionary.
     * $size is the font size in text-space units (typically the same as
     * user-space units unless the text matrix applies scaling). Must be called
     * at least once inside a text object before any text showing operator.
     */
    public function setFont(string $font, float $size): self
    {
        $this->currentFont = $font;
        $this->operations[] = new Operation\SetFont($font, $size);

        return $this;
    }

    /**
     * Sets how glyphs are rendered.
     *
     * Controls whether glyphs are filled, stroked, used as a clipping path,
     * or a combination:
     * 0 = fill, 1 = stroke, 2 = fill then stroke, 3 = invisible,
     * 4 = fill + add to clip, 5 = stroke + add to clip,
     * 6 = fill + stroke + add to clip, 7 = add to clip only.
     * Use modes 4–7 to clip subsequent drawing to the shape of glyphs.
     */
    public function setTextRenderingMode(int $mode): self
    {
        $this->operations[] = new Operation\SetTextRenderingMode($mode);

        return $this;
    }

    /**
     * Shifts the text baseline up (positive) or down (negative).
     *
     * The rise is applied in unscaled text-space units. Does not affect the
     * text position used by line-advancing operators. Use for superscripts,
     * subscripts, or manually aligning text on a non-horizontal baseline.
     */
    public function setTextRise(float $rise): self
    {
        $this->operations[] = new Operation\SetTextRise($rise);

        return $this;
    }

    // -------------------------------------------------------------------------
    // Text Positioning
    // -------------------------------------------------------------------------

    /**
     * Moves the text position by (tx, ty) relative to the start of the current line.
     *
     * Updates both the text matrix and the text line matrix. Use this to
     * position text relative to the previous line origin - for example, to
     * indent or offset a run of text. For absolute positioning, prefer
     * setTextMatrix(1, 0, 0, 1, x, y) instead.
     */
    public function moveTextPosition(float $tx, float $ty): self
    {
        $this->operations[] = new Operation\MoveTextPosition($tx, $ty);

        return $this;
    }

    /**
     * Moves the text position by (tx, ty) and sets the text leading to -ty.
     *
     * Equivalent to setTextLeading(-ty) followed by moveTextPosition(tx, ty).
     * Use at the start of each new line when the line height equals the
     * vertical offset, so that subsequent moveToNextLine() calls automatically
     * advance by the same distance.
     */
    public function moveTextPositionAndSetLeading(float $tx, float $ty): self
    {
        $this->operations[] = new Operation\MoveTextPositionAndSetLeading($tx, $ty);

        return $this;
    }

    /**
     * Sets the text matrix and text line matrix directly.
     *
     * Replaces both matrices with the supplied transform, overriding any
     * previous positioning. Use Matrix::translate(x, y) to place text at an
     * absolute position, Matrix::rotate() to angle it, or compose multiple
     * transforms with then(). Prefer this over moveTextPosition() when placing
     * text at coordinates derived from layout logic.
     *
     * Example — position text at (72, 720):
     *   $stream->setTextMatrix(Matrix::translate(72, 720))
     *
     * Example — 30° rotated text at (200, 400):
     *   $stream->setTextMatrix(Matrix::rotate(30)->then(Matrix::translate(200, 400)))
     */
    public function setTextMatrix(Matrix $matrix): self
    {
        $this->operations[] = new Operation\SetTextMatrix($matrix);

        return $this;
    }

    /**
     * Advances to the start of the next line.
     *
     * Moves the text position down by the current text leading value. Requires
     * setTextLeading() to have been called with a non-zero value beforehand,
     * otherwise the text position does not move. Use for rendering multi-line
     * paragraphs where a fixed line height is established upfront.
     */
    public function moveToNextLine(): self
    {
        $this->operations[] = new Operation\MoveToNextLine();

        return $this;
    }

    // -------------------------------------------------------------------------
    // Text Showing
    // -------------------------------------------------------------------------

    /**
     * Shows a text string at the current text position.
     *
     * The string is interpreted using the current font's encoding. After
     * rendering, the text position advances by the total glyph advance width
     * (including character spacing). Use for single runs of text in a
     * consistent style.
     */
    public function showText(string $text): self
    {
        $this->operations[] = new Operation\ShowText($this->encodedString($text));

        return $this;
    }

    /**
     * Shows text with individual glyph-level position adjustments.
     *
     * $elements is an array alternating between strings (text to show) and
     * floats (horizontal position adjustments in thousandths of a text-space
     * unit, where negative values move glyphs closer together). Use for
     * precise kerning, optical margin alignment, or when the font's built-in
     * kerning pairs are insufficient.
     *
     * @param list<string|float> $elements
     */
    public function showTextWithPositioning(array $elements): self
    {
        $encoded = [];

        foreach ($elements as $element) {
            $encoded[] = is_string($element)
                ? $this->encodedString($element)
                : $element;
        }

        $this->operations[] = new Operation\ShowTextWithPositioning($encoded);

        return $this;
    }

    /**
     * Advances to the next line and shows a text string.
     *
     * Equivalent to moveToNextLine() followed by showText(). Requires
     * setTextLeading() to have been set. Use as a compact operator when
     * rendering sequential lines of text at a fixed line height.
     */
    public function moveToNextLineAndShowText(string $text): self
    {
        $this->operations[] = new Operation\MoveToNextLineAndShowText($this->encodedString($text));

        return $this;
    }

    /**
     * Sets word and character spacing, advances to the next line, and shows text.
     *
     * Combines setWordSpacing(), setCharacterSpacing(), moveToNextLine(), and
     * showText() into one operator. Use when rendering justified text line by
     * line where each line requires different inter-word and inter-character
     * spacing values.
     */
    public function setSpacingMoveToNextLineAndShowText(float $wordSpacing, float $charSpacing, string $text): self
    {
        $this->operations[] = new Operation\SetSpacingMoveToNextLineAndShowText(
            $wordSpacing,
            $charSpacing,
            $this->encodedString($text),
        );

        return $this;
    }

    // -------------------------------------------------------------------------
    // Color
    // -------------------------------------------------------------------------

    /**
     * Sets the stroking colour using a Color value object.
     *
     * Dispatches to the appropriate device-colour operator (G, RG, or K)
     * based on the colour's model. This is the preferred way to set colours
     * when working with the Color class:
     *
     *   $stream->strokeColor(Color::fromHex('#cc2200'))
     *          ->strokeColor(Color::blue()->lighter(0.3))
     *          ->strokeColor(Color::cmyk(0, 0.8, 0.8, 0))
     */
    public function strokeColor(Color $color): self
    {
        $this->operations[] = match ($color->getType()) {
            ColorType::Gray => new Operation\SetStrokingGray($color->getComponents()[0]),
            ColorType::Rgb => new Operation\SetStrokingRgbColor(...$color->getComponents()),
            ColorType::Cmyk => new Operation\SetStrokingCmykColor(...$color->getComponents()),
        };

        return $this;
    }

    /**
     * Sets the fill (non-stroking) colour using a Color value object.
     *
     * Dispatches to the appropriate device-colour operator (g, rg, or k).
     *
     *   $stream->fillColor(Color::orange())
     *          ->fillColor(Color::fromHex('#f0f0f0'))
     *          ->fillColor(Color::red()->mix(Color::blue(), 0.5))
     */
    public function fillColor(Color $color): self
    {
        $this->operations[] = match ($color->getType()) {
            ColorType::Gray => new Operation\SetNonStrokingGray($color->getComponents()[0]),
            ColorType::Rgb => new Operation\SetNonStrokingRgbColor(...$color->getComponents()),
            ColorType::Cmyk => new Operation\SetNonStrokingCmykColor(...$color->getComponents()),
        };

        return $this;
    }

    /**
     * Sets the color space for stroking operations.
     *
     * $name must reference an entry in the page's ColorSpace resource
     * dictionary (or one of the device color space names: DeviceGray,
     * DeviceRGB, DeviceCMYK). Must be called before setStrokingColor() or
     * setStrokingColorExtended() when using a non-device color space. Requires
     * PDF 1.2.
     */
    public function setStrokingColorSpace(string $name): self
    {
        $this->operations[] = new Operation\SetStrokingColorSpace($name);

        return $this;
    }

    /**
     * Sets the color space for non-stroking (fill) operations.
     *
     * See setStrokingColorSpace() for usage. Applies to fill(), fillEvenOdd(),
     * and combined fill-and-stroke operators. Requires PDF 1.2.
     */
    public function setNonStrokingColorSpace(string $name): self
    {
        $this->operations[] = new Operation\SetNonStrokingColorSpace($name);

        return $this;
    }

    /**
     * Sets the stroking color in the currently active stroking color space.
     *
     * The number of components must match the active color space: 1 for
     * DeviceGray, 3 for DeviceRGB, 4 for DeviceCMYK. Call
     * setStrokingColorSpace() first when using a non-device color space. For
     * Pattern, Separation, or DeviceN color spaces, use
     * setStrokingColorExtended() instead. Requires PDF 1.2.
     */
    public function setStrokingColor(float ...$components): self
    {
        $this->operations[] = new Operation\SetStrokingColor(...$components);

        return $this;
    }

    /**
     * Sets the non-stroking (fill) color in the currently active non-stroking color space.
     *
     * See setStrokingColor() for usage. Requires PDF 1.2.
     */
    public function setNonStrokingColor(float ...$components): self
    {
        $this->operations[] = new Operation\SetNonStrokingColor(...$components);

        return $this;
    }

    /**
     * Sets the stroking color for Pattern, Separation, DeviceN, or ICCBased color spaces.
     *
     * For Pattern color spaces, pass an empty $components array and the
     * pattern resource name in $patternName. For Separation and DeviceN, pass
     * the tint components and omit $patternName. Requires PDF 1.2.
     *
     * @param list<float> $components
     */
    public function setStrokingColorExtended(array $components, ?string $patternName = null): self
    {
        $this->operations[] = new Operation\SetStrokingColorExtended($components, $patternName);

        return $this;
    }

    /**
     * Sets the non-stroking color for Pattern, Separation, DeviceN, or ICCBased color spaces.
     *
     * See setStrokingColorExtended() for usage. Requires PDF 1.2.
     *
     * @param list<float> $components
     */
    public function setNonStrokingColorExtended(array $components, ?string $patternName = null): self
    {
        $this->operations[] = new Operation\SetNonStrokingColorExtended($components, $patternName);

        return $this;
    }

    // -------------------------------------------------------------------------
    // XObject
    // -------------------------------------------------------------------------

    /**
     * Invokes a named XObject from the page's XObject resource dictionary.
     *
     * For image XObjects this paints the image into the current user space,
     * scaled to a 1×1 unit square - apply concatenateMatrix() beforehand to
     * control position and size. For form XObjects (reusable content streams)
     * this executes the form's content stream in its own graphics state
     * sandbox. $name must match a key in the XObject resource dictionary.
     */
    public function invokeXObject(string $name): self
    {
        $this->operations[] = new Operation\InvokeXObject($name);

        return $this;
    }

    /**
     * Paints an image at the given position and size.
     *
     * This is a convenience wrapper around the standard PDF sequence:
     * saveGraphicsState → concatenateMatrix → Do → restoreGraphicsState.
     *
     * $name must match a key registered with PdfPageBuilder::useImage().
     * (x, y) is the bottom-left corner; (width, height) is the rendered size,
     * both in page-coordinate points (72 points = 1 inch).
     *
     * Example:
     *   $stream->drawImage('Logo', x: 72, y: 600, width: 200, height: 150)
     */
    public function drawImage(string $name, float $x, float $y, float $width, float $height): self
    {
        return $this
            ->saveGraphicsState()
            ->concatenateMatrix(Matrix::scale($width, $height)->then(Matrix::translate($x, $y)))
            ->invokeXObject($name)
            ->restoreGraphicsState();
    }

    /**
     * Draws an SVG registered with PdfPageBuilder::useSvg() at a given position and size.
     *
     * This is a convenience wrapper around the standard PDF sequence:
     * saveGraphicsState → concatenateMatrix → Do → restoreGraphicsState.
     *
     * $name must match a key registered with PdfPageBuilder::useSvg().
     * (x, y) is the bottom-left corner; (width, height) is the rendered size,
     * both in page-coordinate points (72 points = 1 inch).
     *
     * Example:
     *   $stream->drawSvg('Logo', x: 72, y: 600, width: 200, height: 150)
     */
    public function drawSvg(string $name, float $x, float $y, float $width, float $height): self
    {
        return $this->drawImage($name, $x, $y, $width, $height);
    }

    /**
     * Places an imported page registered with PdfPageBuilder::useImportedPage().
     *
     * Unlike drawImage() and drawSvg(), which map content to a 1×1 unit square,
     * an imported page already lives in page coordinate space (e.g. 0–595 × 0–842
     * for A4). $scale controls uniform scaling (1.0 = original size); (x, y) is
     * the bottom-left placement offset in the current page's coordinate space.
     *
     * $name must match a key registered with PdfPageBuilder::useImportedPage().
     *
     * Example — place at full size, bottom-left of page:
     *   $stream->drawImportedPage('TPL')
     *
     * Example — place at 50% scale, offset by 50 points:
     *   $stream->drawImportedPage('TPL', x: 50, y: 50, scale: 0.5)
     */
    public function drawImportedPage(string $name, float $x = 0.0, float $y = 0.0, float $scale = 1.0): self
    {
        return $this
            ->saveGraphicsState()
            ->concatenateMatrix(Matrix::scale($scale)->then(Matrix::translate($x, $y)))
            ->invokeXObject($name)
            ->restoreGraphicsState();
    }

    /**
     * Renders a linear (1-D) barcode as a series of filled black rectangles.
     *
     * The barcode is drawn as solid black bars on a white background. A quiet
     * zone of $quietZone narrow modules is added to the left and right
     * automatically. When $fontName and $metrics are provided, the barcode's
     * human-readable text is centred below the bars.
     *
     * ($x, $y) is the bottom-left corner of the symbol (including quiet zone).
     *
     * Example — Code 128:
     *   $bc = Code128::encode('ABC-1234');
     *   $stream->drawBarcode($bc, x: 72, y: 600, height: 40, moduleWidth: 1.2);
     *
     * Example — EAN-13 with text:
     *   $bc = EAN13::encode('5901234123457');
     *   $stream->drawBarcode($bc, x: 72, y: 600, height: 40, moduleWidth: 1.0,
     *                        fontName: 'F1', fontSize: 8, metrics: $helv);
     */
    public function drawBarcode(
        LinearBarcode $barcode,
        float $x,
        float $y,
        float $height,
        float $moduleWidth = 1.0,
        float $quietZone = 10.0,
        string $fontName = '',
        float $fontSize = 8.0,
        ?FontMetrics $metrics = null,
    ): self {
        $bars = $barcode->getBars();
        $totalBars = (int) array_sum($bars);
        $totalW = ($totalBars + 2 * $quietZone) * $moduleWidth;

        // White background covering quiet zones and bars
        $this->saveGraphicsState()
             ->fillColor(Color::gray(1.0))
             ->rectangle($x, $y, $totalW, $height)
             ->fill()
             ->restoreGraphicsState();

        // Draw bars (black filled rectangles)
        $this->saveGraphicsState()->fillColor(Color::gray(0.0));

        $cx = $x + $quietZone * $moduleWidth;
        $isBar = true;

        foreach ($bars as $width) {
            if ($isBar) {
                $this->rectangle($cx, $y, $width * $moduleWidth, $height)->fill();
            }

            $cx += $width * $moduleWidth;
            $isBar = !$isBar;
        }

        $this->restoreGraphicsState();

        // Human-readable text centred below the barcode
        $text = $barcode->getText();

        if ($text !== '' && $fontName !== '' && $metrics !== null) {
            $textW = $metrics->stringWidth($text) * $fontSize / 1000;
            $textX = $x + ($totalW - $textW) / 2;
            $textY = $y - $fontSize - 1.5;

            $this->beginText()
                 ->setFont($fontName, $fontSize)
                 ->fillColor(Color::gray(0.0))
                 ->setTextMatrix(Matrix::translate($textX, $textY))
                 ->showText($text)
                 ->endText();
        }

        return $this;
    }

    /**
     * Renders a QR code as a grid of filled black rectangles on a white background.
     *
     * ($x, $y) is the bottom-left corner of the quiet zone in page coordinates
     * (points, origin at bottom-left of the page). $moduleSize is the side
     * length of one module in points — 2.0 pt is a good default for print
     * (≈ 0.7 mm at 72 DPI); use larger values for better scannability.
     *
     * The spec requires a quiet zone of at least four modules on all sides
     * ($quietZone defaults to 4). A white rectangle is drawn first so the code
     * remains readable regardless of the page background.
     *
     * Consecutive dark modules in each row are merged into a single wider
     * rectangle to keep the PDF stream compact.
     *
     * Example — 2 pt modules, top-left at (72, 600):
     *   $qr = QrCode::encode('https://example.com');
     *   $stream->drawQrCode($qr, x: 72, y: 600, moduleSize: 2.0);
     */
    // phpcs:ignore SlevomatCodingStandard.Complexity.Cognitive.ComplexityTooHigh
    public function drawQrCode(QrCode $qr, float $x, float $y, float $moduleSize = 2.0, int $quietZone = 4,): self
    {
        $n = $qr->getSize();
        $totalSize = ($n + 2 * $quietZone) * $moduleSize;
        $ox = $x + $quietZone * $moduleSize;
        $oy = $y + $quietZone * $moduleSize;

        // White background (quiet zone included)
        $this->saveGraphicsState()
             ->fillColor(Color::gray(1.0))
             ->rectangle($x, $y, $totalSize, $totalSize)
             ->fill()
             ->restoreGraphicsState();

        // Dark modules
        $this->saveGraphicsState()
             ->fillColor(Color::gray(0.0));

        for ($row = 0; $row < $n; $row++) {
            $ry = $oy + ($n - 1 - $row) * $moduleSize;
            $runStart = null;
            $runLength = 0;

            for ($col = 0; $col <= $n; $col++) {
                if ($col < $n && $qr->isDark($row, $col)) {
                    if ($runStart === null) {
                        $runStart = $col;
                        $runLength = 1;
                    } else {
                        $runLength++;
                    }
                } elseif ($runStart !== null) {
                    $this->rectangle($ox + $runStart * $moduleSize, $ry, $runLength * $moduleSize, $moduleSize)->fill();
                    $runStart = null;
                    $runLength = 0;
                }
            }
        }

        $this->restoreGraphicsState();

        return $this;
    }

    /**
     * Draws a TextBox — a pre-wrapped block of text — at (x, y).
     *
     * $x / $y mark the baseline of the first line (y increases upward in PDF
     * coordinates, so subsequent lines are drawn at y − lineHeight, y − 2×lineHeight, …).
     * The text object is opened and closed automatically.
     *
     * Text alignment (Left / Center / Right) is applied per-line using the
     * metrics stored in the TextBox. Only lines that would fit within an
     * optional $maxHeight constraint are drawn; pass INF (default) to draw all.
     *
     * Example:
     *
     *   $metrics = Type1FontMetrics::helvetica();
     *   $box = TextBox::create('Lorem ipsum…', $metrics, fontSize: 12, maxWidth: 300);
     *   $stream->drawTextBox($box, fontName: 'F1', x: 72, y: 720);
     */
    // phpcs:ignore SlevomatCodingStandard.Complexity.Cognitive.ComplexityTooHigh
    public function drawTextBox(TextBox $box, string $fontName, float $x, float $y, float $maxHeight = INF,): self
    {
        $lines = $maxHeight === INF
            ? $box->getLines()
            : $box->linesFor($maxHeight);

        if ($lines === []) {
            return $this;
        }

        $this->beginText()->setFont($fontName, $box->getFontSize());

        $align = $box->getAlign();
        $lastIndex = count($lines) - 1;
        $currentY = $y;
        $currentTw = 0.0; // tracks the Tw value currently in the stream

        foreach ($lines as $i => $line) {
            $lineX = $x;
            $tw = 0.0;

            if ($line !== '') {
                $lineWidthPt = $box->lineWidthPt($line);

                if ($align === TextAlign::Justify) {
                    // Justify every line except the last in each paragraph and
                    // single-word lines (nothing to distribute space between).
                    $isLastInParagraph = ($i === $lastIndex)
                        || ($i < $lastIndex && $lines[$i + 1] === '');
                    $wordCount = substr_count($line, ' ') + 1;

                    if (!$isLastInParagraph && $wordCount > 1) {
                        // lineWidthPt already includes normal space advances, so
                        // tw is the extra amount (in points) added per word gap.
                        $tw = ($box->getMaxWidth() - $lineWidthPt) / ($wordCount - 1);
                    }
                } else {
                    $lineX = match ($align) {
                        TextAlign::Center => $x + ($box->getMaxWidth() - $lineWidthPt) / 2,
                        TextAlign::Right => $x + $box->getMaxWidth() - $lineWidthPt,
                        default => $x,
                    };
                }
            }

            // Emit Tw only when the value changes — avoids redundant operators.
            if ($tw !== $currentTw) {
                $this->setWordSpacing($tw);
                $currentTw = $tw;
            }

            $this->setTextMatrix(Matrix::translate($lineX, $currentY));

            if ($line !== '') {
                $this->showText($line);
            }

            $currentY -= $box->getLineHeight();
        }

        $this->endText();

        // Reset word spacing so subsequent text objects start clean.
        // @codeCoverageIgnoreStart
        // The last iteration always resets currentTw to 0.0 (the final line is
        // always "last in paragraph", so tw = 0.0 and setWordSpacing is called
        // inside the loop when needed). This branch is kept as a safety net.
        if ($currentTw !== 0.0) {
            $this->setWordSpacing(0.0);
        }

        // @codeCoverageIgnoreEnd

        return $this;
    }

    /**
     * Draws a RichTextBox — a pre-wrapped block of text with per-span fonts — at (x, y).
     *
     * $x / $y mark the baseline of the first line. The font is switched between
     * spans as needed; adjacent same-font spans on a line are drawn consecutively
     * without reopening a text object. Alignment (Left / Center / Right / Justify) is
     * applied per-line using the total width of all spans on that line.
     *
     * For Justify, inter-word spacing is distributed via the PDF Tw (word spacing)
     * operator. Each span's x position is advanced by widthPt() plus the extra
     * spacing accumulated from space characters in that span, so multi-font lines
     * remain correctly positioned.
     *
     * Only lines that would fit within an optional $maxHeight constraint are drawn.
     *
     * Example:
     *
     *   $box = RichTextBox::create([
     *       TextSpan::create('Invoice: ', 'F1', 10, $regular),
     *       TextSpan::create('INV-001', 'F2', 10, $bold),
     *   ], maxWidth: 200);
     *   $stream->drawRichTextBox($box, x: 72, y: 720);
     */
    // phpcs:ignore SlevomatCodingStandard.Complexity.Cognitive.ComplexityTooHigh
    public function drawRichTextBox(RichTextBox $box, float $x, float $y, float $maxHeight = INF,): self
    {
        $lines = $maxHeight === INF
            ? $box->getLines()
            : $box->linesFor($maxHeight);

        if ($lines === []) {
            return $this;
        }

        $align = $box->getAlign();
        $lastIndex = count($lines) - 1;
        $currentY = $y;
        $currentTw = 0.0;

        $this->beginText();

        $currentFontKey = '';

        foreach ($lines as $i => $line) {
            if ($line === []) {
                $currentY -= $box->getLineHeight();

                continue;
            }

            $lineWidthPt = $box->lineWidthPt($line);
            $tw = 0.0;
            $lineX = $x;

            if ($align === TextAlign::Justify) {
                $isLastLine = ($i === $lastIndex)
                    || ($i < $lastIndex && $lines[$i + 1] === []);

                if (!$isLastLine) {
                    $spaceCount = 0;

                    foreach ($line as $span) {
                        $spaceCount += substr_count($span->getText(), ' ');
                    }

                    if ($spaceCount > 0) {
                        $tw = ($box->getMaxWidth() - $lineWidthPt) / $spaceCount;
                    }
                }
            } else {
                $lineX = match ($align) {
                    TextAlign::Center => $x + ($box->getMaxWidth() - $lineWidthPt) / 2,
                    TextAlign::Right => $x + $box->getMaxWidth() - $lineWidthPt,
                    default => $x,
                };
            }

            if ($tw !== $currentTw) {
                $this->setWordSpacing($tw);
                $currentTw = $tw;
            }

            $currentX = $lineX;

            foreach ($line as $span) {
                if ($span->getText() === '') {
                    continue;
                }

                $fontKey = $span->getFontName() . "\x00" . $span->getFontSize();

                if ($currentFontKey !== $fontKey) {
                    $this->setFont($span->getFontName(), $span->getFontSize());
                    $currentFontKey = $fontKey;
                }

                $this->setTextMatrix(Matrix::translate($currentX, $currentY));
                $this->showText($span->getText());

                $currentX += $span->widthPt() + substr_count($span->getText(), ' ') * $tw;
            }

            $currentY -= $box->getLineHeight();
        }

        $this->endText();

        // @codeCoverageIgnoreStart
        if ($currentTw !== 0.0) {
            $this->setWordSpacing(0.0);
        }

        // @codeCoverageIgnoreEnd

        return $this;
    }

    /**
     * Renders a bullet or numbered list at (x, y).
     *
     * $x / $y mark the baseline of the first item's first line. Subsequent
     * lines and items are drawn downward automatically.
     *
     * The marker (bullet character or number label) is drawn at x; body text
     * starts at x + ListBox::getIndent(). Multi-line items indent continuation
     * lines to the same body column. An optional per-item spacing gap is
     * inserted between items when ListBox::getItemSpacing() > 0.
     *
     * Example — bullet list:
     *
     *   $metrics = Type1FontMetrics::helvetica();
     *   $list = ListBox::bullet(
     *       items: ['First item', 'Second item with a long body that wraps.'],
     *       metrics: $metrics,
     *       fontSize: 11,
     *       maxWidth: 300,
     *   );
     *   $stream->drawListBox($list, fontName: 'F1', x: 72, y: 700);
     *
     * Example — numbered list:
     *
     *   $list = ListBox::numbered(
     *       items: ['First step', 'Second step', 'Third step'],
     *       metrics: $metrics,
     *       fontSize: 11,
     *       maxWidth: 300,
     *       itemSpacing: 3.0,
     *   );
     *   $stream->drawListBox($list, fontName: 'F1', x: 72, y: 700);
     */
    // phpcs:ignore SlevomatCodingStandard.Complexity.Cognitive.ComplexityTooHigh
    public function drawListBox(ListBox $list, string $fontName, float $x, float $y): self
    {
        if ($list->getItems() === []) {
            return $this;
        }

        $indent = $list->getIndent();
        $lineHeight = $list->getLineHeight();
        $itemCount = count($list->getItems());

        $this->beginText()->setFont($fontName, $list->getFontSize());

        $currentY = $y;

        foreach ($list->getItems() as $i => $item) {
            // Marker — drawn at x, aligned to the first line baseline
            $this->setTextMatrix(Matrix::translate($x, $currentY));
            $this->showText($item->marker);

            // Body lines — all start at x + indent
            foreach ($item->lines as $line) {
                $this->setTextMatrix(Matrix::translate($x + $indent, $currentY));

                if ($line !== '') {
                    $this->showText($line);
                }

                $currentY -= $lineHeight;
            }

            // Inter-item gap (not after the last item)
            if ($i >= $itemCount - 1) {
                continue;
            }

            $currentY -= $list->getItemSpacing();
        }

        $this->endText();

        return $this;
    }

    // -------------------------------------------------------------------------
    // Shading
    // -------------------------------------------------------------------------

    /**
     * Paints the current clipping region using a named shading pattern.
     *
     * $name must match an entry in the page's Shading resource dictionary.
     * The shading is painted directly without constructing a path; the current
     * clipping path determines the painted area. Use for smooth color gradients
     * such as linear or radial fills. Requires PDF 1.3.
     */
    public function paintShading(string $name): self
    {
        $this->operations[] = new Operation\PaintShading($name);

        return $this;
    }

    // -------------------------------------------------------------------------
    // Marked Content
    // -------------------------------------------------------------------------

    /**
     * Opens a marked-content sequence with the given tag.
     *
     * Marks a range of content stream operators with a semantic role (e.g.
     * 'Span', 'P', 'Figure'). Use this when no associated property dictionary
     * is needed. Must be closed with endMarkedContent(). Sequences can be
     * nested. Requires PDF 1.2.
     */
    public function beginMarkedContent(string $tag): self
    {
        $this->operations[] = new Operation\BeginMarkedContent($tag);

        return $this;
    }

    /**
     * Opens a marked-content sequence with a tag and a property list reference.
     *
     * $properties must match a key in the page's Properties resource
     * dictionary, which maps to a dictionary of metadata for this sequence.
     * Use when the marked content must carry structured attributes (e.g.
     * language, alternate text, or ActualText for accessibility). Must be
     * closed with endMarkedContent(). Requires PDF 1.2.
     */
    public function beginMarkedContentWithProperties(string $tag, string $properties): self
    {
        $this->operations[] = new Operation\BeginMarkedContentWithProperties($tag, $properties);

        return $this;
    }

    /**
     * Closes the most recently opened marked-content sequence.
     *
     * Must be paired with a preceding beginMarkedContent() or
     * beginMarkedContentWithProperties(). Requires PDF 1.2.
     */
    public function endMarkedContent(): self
    {
        $this->operations[] = new Operation\EndMarkedContent();

        return $this;
    }

    /**
     * Marks a single point in the content stream with a tag.
     *
     * Unlike beginMarkedContent(), this designates a point rather than a range
     * and requires no corresponding end operator. Use to mark a specific
     * location in the stream, such as a note anchor or an artifact boundary.
     * Requires PDF 1.2.
     */
    public function defineMarkedContentPoint(string $tag): self
    {
        $this->operations[] = new Operation\DefineMarkedContentPoint($tag);

        return $this;
    }

    /**
     * Marks a single point with a tag and a property list reference.
     *
     * $properties must match a key in the page's Properties resource
     * dictionary. Use when the marked point must carry metadata. Requires
     * PDF 1.2.
     */
    public function defineMarkedContentPointWithProperties(string $tag, string $properties): self
    {
        $this->operations[] = new Operation\DefineMarkedContentPointWithProperties($tag, $properties);

        return $this;
    }

    // -------------------------------------------------------------------------
    // Compatibility
    // -------------------------------------------------------------------------

    /**
     * Opens a compatibility section for operators not understood by older viewers.
     *
     * PDF processors that do not recognise operators encountered between
     * beginCompatibilitySection() and endCompatibilitySection() must ignore
     * them without error. Use when embedding operators from a newer PDF version
     * into a document targeting an older version, where the unknown operators
     * are purely additive and safe to skip. Requires PDF 1.1.
     */
    public function beginCompatibilitySection(): self
    {
        $this->operations[] = new Operation\BeginCompatibilitySection();

        return $this;
    }

    /**
     * Closes the compatibility section opened by beginCompatibilitySection().
     *
     * Requires PDF 1.1.
     */
    public function endCompatibilitySection(): self
    {
        $this->operations[] = new Operation\EndCompatibilitySection();

        return $this;
    }

    // -------------------------------------------------------------------------
    // Type 3 Font (only valid inside a glyph description stream)
    // -------------------------------------------------------------------------

    /**
     * Sets the glyph width for the current glyph in a Type 3 font.
     *
     * Must be the first operator in a Type 3 glyph description stream. $wx is
     * the horizontal advance width and $wy is the vertical advance (typically
     * 0 for horizontal writing). Use this variant - rather than
     * setGlyphWidthAndBoundingBox() - when the glyph uses the current color
     * and therefore has no fixed bounding box.
     */
    public function setGlyphWidth(float $wx, float $wy): self
    {
        $this->operations[] = new Operation\SetGlyphWidth($wx, $wy);

        return $this;
    }

    /**
     * Sets the glyph width and bounding box for the current glyph in a Type 3 font.
     *
     * Must be the first operator in a Type 3 glyph description stream. $wx/$wy
     * are the advance widths (see setGlyphWidth()). ($llx, $lly) and ($urx,
     * $ury) define the lower-left and upper-right corners of the glyph's
     * bounding box in glyph space. Providing the bounding box allows PDF
     * viewers to cache the glyph bitmap and skip re-rendering on subsequent
     * occurrences.
     */
    public function setGlyphWidthAndBoundingBox(
        float $wx,
        float $wy,
        float $llx,
        float $lly,
        float $urx,
        float $ury
    ): self {
        $this->operations[] = new Operation\SetGlyphWidthAndBoundingBox($wx, $wy, $llx, $lly, $urx, $ury);

        return $this;
    }

    // -------------------------------------------------------------------------
    // Encoding helpers
    // -------------------------------------------------------------------------

    /**
     * Builds and returns a PdfContentStream from the accumulated operations.
     *
     * Each call returns a new PdfContentStream instance containing the
     * operations added so far. The builder itself is not reset, so calling
     * build() multiple times produces independent streams that share the same
     * operation sequence.
     */
    public function build(): PdfContentStream
    {
        return new PdfContentStream($this->operations);
    }





    /**
     * Encodes a UTF-8 string into the appropriate PDF string notation for the
     * current font and returns the complete operand (including delimiters):
     *   - Embedded font → hex string: <0041004200430044>
     *   - Type1 font → literal: (Hello) with Windows-1252 bytes
     */
    private function encodedString(string $text): string
    {
        $font = $this->embeddedFonts[$this->currentFont] ?? null;

        if ($font !== null) {
            return $this->encodeCid($font, $text);
        }

        return $this->encodeWinAnsi($text);
    }

    private function encodeCid(TrueTypeFont $font, string $text): string
    {
        $hex = '';
        $len = mb_strlen($text, 'UTF-8');

        for ($i = 0; $i < $len; $i++) {
            $cp = mb_ord(mb_substr($text, $i, 1, 'UTF-8'), 'UTF-8');
            $gid = $font->getGlyphId($cp);
            $hex .= sprintf('%04X', $gid);

            if ($gid === 0) {
                continue;
            }

            $this->usedGlyphs[$this->currentFont][$gid] = $cp;
        }

        return '<' . $hex . '>';
    }

    private function encodeWinAnsi(string $text): string
    {
        $encoded = mb_convert_encoding($text, 'Windows-1252', 'UTF-8');

        $escaped = str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\\(', '\\)'],
            $encoded,
        );

        return '(' . $escaped . ')';
    }

    // -------------------------------------------------------------------------
    // Build
    // -------------------------------------------------------------------------
}
