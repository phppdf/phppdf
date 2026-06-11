<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal;

use DOMElement;
use DOMNode;
use PhpPdf\Html\HtmlConverterConfig;
use PhpPdf\Text\TextAlign;

/**
 * Resolves the computed style for a DOM element.
 *
 * Applies styles in order of increasing specificity:
 *   1. Browser default styles for the element's tag name
 *   2. Universal selector (*) from <style> tags
 *   3. Element selectors from <style> tags
 *   4. Class selectors from <style> tags
 *   5. The element's inline style attribute
 *
 * Inheritable properties (font-family, font-size, font-weight, font-style,
 * color, text-align, line-height) are seeded from the parent ComputedStyle.
 * Box properties (margins, padding, background-color) always start at zero.
 */
final class StyleResolver
{
    /**
     * @param array<string, array<string, string>> $rules Selector → declarations map
     *                                                     produced by CssParser::parseStylesheet().
     */
    public function __construct(
        private readonly array $rules,
        private readonly HtmlConverterConfig $config,
    ) {
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Produces the ComputedStyle for $node, inheriting from $parent.
     *
     * Non-DOM nodes (text nodes, comments) are returned with the parent style
     * and all box properties reset to zero.
     */
    public function resolve(DOMNode $node, ComputedStyle $parent): ComputedStyle
    {
        // Start with a copy of the parent (handles CSS inheritance).
        $style = clone $parent;

        // Reset non-inherited box properties.
        $style->setMarginTop(0.0);
        $style->setMarginBottom(0.0);
        $style->setMarginLeft(0.0);
        $style->setPaddingLeft(0.0);
        $style->setBackgroundColor(null);
        $style->setLineHeight(0.0);

        if (!$node instanceof DOMElement) {
            return $style;
        }

        $tag = strtolower($node->tagName);

        // 1. Browser defaults
        $this->applyDefaults($tag, $style);

        // 2. Universal selector from stylesheet (lower specificity than element selectors)
        if (isset($this->rules['*'])) {
            $this->applyDeclarations($this->rules['*'], $style);
        }

        // 3. Element selector from stylesheet
        if (isset($this->rules[$tag])) {
            $this->applyDeclarations($this->rules[$tag], $style);
        }

        // 4. Class selectors from stylesheet
        $classAttr = $node->getAttribute('class');
        if ($classAttr !== '') {
            foreach (explode(' ', $classAttr) as $cls) {
                $key = '.' . trim($cls);
                if ($key !== '.' && isset($this->rules[$key])) {
                    $this->applyDeclarations($this->rules[$key], $style);
                }
            }
        }

        // 5. Inline style attribute (highest specificity)
        $inlineStyle = $node->getAttribute('style');
        if ($inlineStyle !== '') {
            $this->applyDeclarations(CssParser::parseInline($inlineStyle), $style);
        }

        return $style;
    }

    // -------------------------------------------------------------------------
    // Browser defaults
    // -------------------------------------------------------------------------

    private function applyDefaults(string $tag, ComputedStyle $style): void
    {
        $b = $this->config->getBaseFontSize();

        switch ($tag) {
            case 'h1':
                $style->setFontSize(round($b * 2.2, 2));
                $style->setBold(true);
                $style->setMarginTop(round($b * 1.4, 2));
                $style->setMarginBottom(round($b * 0.7, 2));
                break;
            case 'h2':
                $style->setFontSize(round($b * 1.75, 2));
                $style->setBold(true);
                $style->setMarginTop(round($b * 1.2, 2));
                $style->setMarginBottom(round($b * 0.6, 2));
                break;
            case 'h3':
                $style->setFontSize(round($b * 1.4, 2));
                $style->setBold(true);
                $style->setMarginTop(round($b * 1.0, 2));
                $style->setMarginBottom(round($b * 0.5, 2));
                break;
            case 'h4':
                $style->setFontSize(round($b * 1.15, 2));
                $style->setBold(true);
                $style->setMarginTop(round($b * 0.8, 2));
                $style->setMarginBottom(round($b * 0.4, 2));
                break;
            case 'h5':
                $style->setFontSize($b);
                $style->setBold(true);
                $style->setMarginTop(round($b * 0.7, 2));
                $style->setMarginBottom(round($b * 0.3, 2));
                break;
            case 'h6':
                $style->setFontSize(round($b * 0.9, 2));
                $style->setBold(true);
                $style->setMarginTop(round($b * 0.6, 2));
                $style->setMarginBottom(round($b * 0.3, 2));
                break;
            case 'p':
                $style->setMarginBottom(round($b * 0.7, 2));
                break;
            case 'strong':
            case 'b':
                $style->setBold(true);
                break;
            case 'em':
            case 'i':
                $style->setItalic(true);
                break;
            case 'ul':
            case 'ol':
                $style->setMarginBottom(round($b * 0.7, 2));
                $style->setPaddingLeft(round($b * 2.0, 2));
                break;
            case 'blockquote':
                $style->setMarginTop(round($b * 0.7, 2));
                $style->setMarginBottom(round($b * 0.7, 2));
                $style->setMarginLeft(round($b * 2.0, 2));
                $style->setItalic(true);
                break;
        }
    }

    // -------------------------------------------------------------------------
    // Declaration application
    // -------------------------------------------------------------------------

    /**
     * Merges a set of CSS declarations into $style.
     *
     * @param array<string, string> $decls
     */
    private function applyDeclarations(array $decls, ComputedStyle $style): void
    {
        $baseFontSize = $this->config->getBaseFontSize();

        foreach ($decls as $property => $value) {
            switch ($property) {
                case 'font-family':
                    $resolved = $this->config->resolveFontFamilyName($value);
                    if ($resolved !== null) {
                        $style->setFontFamily($resolved);
                    }
                    break;

                case 'font-size':
                    $pt = CssParser::parseLength($value, $style->getFontSize(), $baseFontSize);
                    if ($pt !== null && $pt > 0.0) {
                        $style->setFontSize($pt);
                    }
                    break;

                case 'font-weight':
                    $v = strtolower($value);
                    $style->setBold($v === 'bold' || $v === 'bolder'
                        || (is_numeric($v) && (int) $v >= 700));
                    break;

                case 'font-style':
                    $v = strtolower($value);
                    $style->setItalic($v === 'italic' || $v === 'oblique');
                    break;

                case 'color':
                    $c = CssParser::parseColor($value);
                    if ($c !== null) {
                        $style->setColor($c);
                    }
                    break;

                case 'background-color':
                    $style->setBackgroundColor(CssParser::parseColor($value));
                    break;

                case 'text-align':
                    $style->setTextAlign(match (strtolower($value)) {
                        'center'  => TextAlign::Center,
                        'right'   => TextAlign::Right,
                        'justify' => TextAlign::Justify,
                        default   => TextAlign::Left,
                    });
                    break;

                case 'line-height':
                    // Unitless multiplier (e.g. "1.5") or a length value.
                    $v = trim($value);
                    if (is_numeric($v)) {
                        $style->setLineHeight((float) $v * $style->getFontSize());
                    } else {
                        $pt = CssParser::parseLength($v, $style->getFontSize(), $baseFontSize);
                        if ($pt !== null) {
                            $style->setLineHeight($pt);
                        }
                    }
                    break;

                case 'margin':
                    $this->applyShorthandMargin($value, $style);
                    break;

                case 'margin-top':
                    $pt = CssParser::parseLength($value, $style->getFontSize(), $baseFontSize);
                    if ($pt !== null) {
                        $style->setMarginTop(max(0.0, $pt));
                    }
                    break;

                case 'margin-bottom':
                    $pt = CssParser::parseLength($value, $style->getFontSize(), $baseFontSize);
                    if ($pt !== null) {
                        $style->setMarginBottom(max(0.0, $pt));
                    }
                    break;

                case 'margin-left':
                    $pt = CssParser::parseLength($value, $style->getFontSize(), $baseFontSize);
                    if ($pt !== null) {
                        $style->setMarginLeft(max(0.0, $pt));
                    }
                    break;

                case 'padding':
                    $this->applyShorthandPadding($value, $style);
                    break;

                case 'padding-left':
                    $pt = CssParser::parseLength($value, $style->getFontSize(), $baseFontSize);
                    if ($pt !== null) {
                        $style->setPaddingLeft(max(0.0, $pt));
                    }
                    break;
            }
        }
    }

    /**
     * Applies the `margin` shorthand (1–4 values).
     * CSS order: top | top/bottom left/right | top right bottom left
     */
    private function applyShorthandMargin(string $value, ComputedStyle $style): void
    {
        $b     = $this->config->getBaseFontSize();
        $parts = preg_split('/\s+/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
        if ($parts === false) {
            return;
        }

        switch (count($parts)) {
            case 1:
                $pt = CssParser::parseLength($parts[0], $style->getFontSize(), $b);
                if ($pt !== null) {
                    $v = max(0.0, $pt);
                    $style->setMarginTop($v);
                    $style->setMarginBottom($v);
                    $style->setMarginLeft($v);
                }
                break;
            case 2:
                $ptV = CssParser::parseLength($parts[0], $style->getFontSize(), $b);
                $ptH = CssParser::parseLength($parts[1], $style->getFontSize(), $b);
                if ($ptV !== null) {
                    $v = max(0.0, $ptV);
                    $style->setMarginTop($v);
                    $style->setMarginBottom($v);
                }
                if ($ptH !== null) {
                    $style->setMarginLeft(max(0.0, $ptH));
                }
                break;
            case 3:
                $ptT = CssParser::parseLength($parts[0], $style->getFontSize(), $b);
                $ptH = CssParser::parseLength($parts[1], $style->getFontSize(), $b);
                $ptB = CssParser::parseLength($parts[2], $style->getFontSize(), $b);
                if ($ptT !== null) {
                    $style->setMarginTop(max(0.0, $ptT));
                }
                if ($ptH !== null) {
                    $style->setMarginLeft(max(0.0, $ptH));
                }
                if ($ptB !== null) {
                    $style->setMarginBottom(max(0.0, $ptB));
                }
                break;
            case 4:
                $ptT = CssParser::parseLength($parts[0], $style->getFontSize(), $b);
                $ptB = CssParser::parseLength($parts[2], $style->getFontSize(), $b);
                $ptL = CssParser::parseLength($parts[3], $style->getFontSize(), $b);
                if ($ptT !== null) {
                    $style->setMarginTop(max(0.0, $ptT));
                }
                if ($ptB !== null) {
                    $style->setMarginBottom(max(0.0, $ptB));
                }
                if ($ptL !== null) {
                    $style->setMarginLeft(max(0.0, $ptL));
                }
                break;
        }
    }

    /**
     * Applies the `padding` shorthand, tracking only padding-left.
     */
    private function applyShorthandPadding(string $value, ComputedStyle $style): void
    {
        $b     = $this->config->getBaseFontSize();
        $parts = preg_split('/\s+/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
        if ($parts === false) {
            return;
        }

        $leftIndex = match (count($parts)) {
            1 => 0,
            4 => 3,
            default => null,
        };

        if ($leftIndex !== null) {
            $pt = CssParser::parseLength($parts[$leftIndex], $style->getFontSize(), $b);
            if ($pt !== null) {
                $style->setPaddingLeft(max(0.0, $pt));
            }
        }
    }
}
