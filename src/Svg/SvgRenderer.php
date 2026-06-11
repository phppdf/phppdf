<?php

declare(strict_types=1);

namespace PhpPdf\Svg;

use DOMElement;

use const PREG_SET_ORDER;

/**
 * Translates an SvgDocument into a PDF content-stream string suitable for use
 * as the body of a Form XObject.
 *
 * The stream uses SVG coordinate space (origin top-left, y increases downward).
 * The Form XObject's Matrix entry flips the y-axis to PDF space, so no
 * coordinate manipulation is needed here.
 *
 * Supported elements: svg, g, rect, circle, ellipse, line, polyline, polygon, path.
 * Supported style properties: fill, stroke, stroke-width, fill-rule,
 * display, visibility.
 * Supported transforms: translate, scale, rotate, matrix, skewX, skewY.
 *
 * Opacity (fill-opacity, stroke-opacity, opacity) requires ExtGState resources
 * and is not currently supported.
 */
final class SvgRenderer
{
    private SvgPathParser $pathParser;

    public function __construct()
    {
        $this->pathParser = new SvgPathParser();
    }

    public function render(SvgDocument $svg): string
    {
        $root = $svg->getDom()->documentElement;

        if ($root === null) {
            return '';
        }

        return $this->renderChildren($root, $this->defaultStyle());
    }

    // -------------------------------------------------------------------------
    // Element dispatch
    // -------------------------------------------------------------------------

    /** @param array<string, string> $style */
    private function renderChildren(DOMElement $parent, array $style): string
    {
        $out = '';

        foreach ($parent->childNodes as $child) {
            if (!($child instanceof DOMElement)) {
                continue;
            }

            $out .= $this->renderElement($child, $style);
        }

        return $out;
    }

    /** @param array<string, string> $inherited */
    private function renderElement(DOMElement $el, array $inherited): string
    {
        $style = $this->mergeStyle($inherited, $el);

        if (($style['display'] ?? '') === 'none' || ($style['visibility'] ?? '') === 'hidden') {
            return '';
        }

        return match ($el->localName) {
            'g' => $this->renderGroup($el, $style),
            'rect' => $this->renderRect($el, $style),
            'circle' => $this->renderCircle($el, $style),
            'ellipse' => $this->renderEllipse($el, $style),
            'line' => $this->renderLine($el, $style),
            'polyline' => $this->renderPoly($el, $style, false),
            'polygon' => $this->renderPoly($el, $style, true),
            'path' => $this->renderPath($el, $style),
            default => $this->renderChildren($el, $style),
        };
    }

    // -------------------------------------------------------------------------
    // Shape renderers
    // -------------------------------------------------------------------------

    /** @param array<string, string> $style */
    private function renderGroup(DOMElement $el, array $style): string
    {
        $inner = $this->renderChildren($el, $style);

        if ($inner === '') {
            return '';
        }

        $transform = $el->getAttribute('transform');
        $out = "q\n";

        if ($transform !== '') {
            $out .= $this->transformToCm($transform);
        }

        $out .= $inner . "Q\n";

        return $out;
    }

    /** @param array<string, string> $style */
    private function renderRect(DOMElement $el, array $style): string
    {
        $x = $this->fa($el, 'x');
        $y = $this->fa($el, 'y');
        $w = $this->fa($el, 'width');
        $h = $this->fa($el, 'height');
        $rx = $this->fa($el, 'rx', -1.0);
        $ry = $this->fa($el, 'ry', -1.0);

        if ($w <= 0 || $h <= 0) {
            return '';
        }

        if ($rx < 0 && $ry < 0) {
            $rx = $ry = 0.0;
        } elseif ($rx < 0) {
            $rx = $ry;
        } elseif ($ry < 0) {
            $ry = $rx;
        }

        $rx = min($rx, $w / 2);
        $ry = min($ry, $h / 2);

        $path = $rx <= 0 && $ry <= 0
            ? $this->f($x) . ' ' . $this->f($y) . ' '
              . $this->f($w) . ' ' . $this->f($h) . " re\n"
            : $this->roundedRectPath($x, $y, $w, $h, $rx, $ry);

        return $this->wrapShape($el, $style, $path);
    }

    /** @param array<string, string> $style */
    private function renderCircle(DOMElement $el, array $style): string
    {
        $cx = $this->fa($el, 'cx');
        $cy = $this->fa($el, 'cy');
        $r = $this->fa($el, 'r');

        if ($r <= 0) {
            return '';
        }

        return $this->wrapShape($el, $style, $this->ellipsePath($cx, $cy, $r, $r));
    }

    /** @param array<string, string> $style */
    private function renderEllipse(DOMElement $el, array $style): string
    {
        $cx = $this->fa($el, 'cx');
        $cy = $this->fa($el, 'cy');
        $rx = $this->fa($el, 'rx');
        $ry = $this->fa($el, 'ry');

        if ($rx <= 0 || $ry <= 0) {
            return '';
        }

        return $this->wrapShape($el, $style, $this->ellipsePath($cx, $cy, $rx, $ry));
    }

    /** @param array<string, string> $style */
    private function renderLine(DOMElement $el, array $style): string
    {
        $x1 = $this->fa($el, 'x1');
        $y1 = $this->fa($el, 'y1');
        $x2 = $this->fa($el, 'x2');
        $y2 = $this->fa($el, 'y2');

        $path = $this->f($x1) . ' ' . $this->f($y1) . " m\n"
              . $this->f($x2) . ' ' . $this->f($y2) . " l\n";

        // Lines are always stroked, never filled
        $strokeStyle = $style;
        $strokeStyle['fill'] = 'none';

        return $this->wrapShape($el, $strokeStyle, $path);
    }

    /** @param array<string, string> $style */
    private function renderPoly(DOMElement $el, array $style, bool $closed): string
    {
        $pts = $this->parsePointsList($el->getAttribute('points'));

        if (count($pts) < 2) {
            return '';
        }

        $path = $this->f($pts[0][0]) . ' ' . $this->f($pts[0][1]) . " m\n";

        for ($i = 1; $i < count($pts); $i++) {
            $path .= $this->f($pts[$i][0]) . ' ' . $this->f($pts[$i][1]) . " l\n";
        }

        if ($closed) {
            $path .= "h\n";
        }

        $drawStyle = $closed
            ? $style
            : array_merge($style, ['fill' => 'none']);

        return $this->wrapShape($el, $drawStyle, $path);
    }

    /** @param array<string, string> $style */
    private function renderPath(DOMElement $el, array $style): string
    {
        $d = trim($el->getAttribute('d'));

        if ($d === '') {
            return '';
        }

        $pathOps = $this->pathParser->parse($d);

        if ($pathOps === '') {
            return '';
        }

        return $this->wrapShape($el, $style, $pathOps);
    }

    // -------------------------------------------------------------------------
    // Shape wrapper: graphics state + path + paint + restore
    // -------------------------------------------------------------------------

    /**
     * Wraps a path string in: q [transform] [color state] {path} {paint} Q

     * @param array<string, string> $style
     */
    private function wrapShape(DOMElement $el, array $style, string $pathOps): string
    {
        $out = "q\n";

        $transform = $el->getAttribute('transform');

        if ($transform !== '') {
            $out .= $this->transformToCm($transform);
        }

        // Stroke width
        $sw = (float) ($style['stroke-width'] ?? 1.0);

        if ($sw !== 1.0) {
            $out .= $this->f($sw) . " w\n";
        }

        $fill = $style['fill'] ?? 'black';
        $stroke = $style['stroke'] ?? 'none';

        // Fill color
        if ($fill !== 'none') {
            $rgb = $this->parseColor($fill);

            if ($rgb !== null) {
                $out .= $this->f($rgb[0]) . ' ' . $this->f($rgb[1]) . ' ' . $this->f($rgb[2]) . " rg\n";
            } else {
                $fill = 'none'; // could not parse → treat as no fill
            }
        }

        // Stroke color
        if ($stroke !== 'none') {
            $rgb = $this->parseColor($stroke);

            if ($rgb !== null) {
                $out .= $this->f($rgb[0]) . ' ' . $this->f($rgb[1]) . ' ' . $this->f($rgb[2]) . " RG\n";
            } else {
                $stroke = 'none';
            }
        }

        $out .= $pathOps;
        $out .= $this->paintOp($fill, $stroke, $style['fill-rule'] ?? 'nonzero');
        $out .= "Q\n";

        return $out;
    }

    private function paintOp(string $fill, string $stroke, string $fillRule): string
    {
        $hasFill = $fill !== 'none';
        $hasStroke = $stroke !== 'none';
        $evenOdd = $fillRule === 'evenodd';

        return match (true) {
            $hasFill && $hasStroke => $evenOdd ? "B*\n" : "B\n",
            $hasFill => $evenOdd ? "f*\n" : "f\n",
            $hasStroke => "S\n",
            default => "n\n",
        };
    }

    // -------------------------------------------------------------------------
    // Transforms
    // -------------------------------------------------------------------------

    private function transformToCm(string $transform): string
    {
        $out = '';
        preg_match_all('/(\w+)\s*\(([^)]*)\)/', $transform, $matches, PREG_SET_ORDER);

        foreach ($matches as $m) {
            $args = array_map('floatval', preg_split('/[\s,]+/', trim($m[2])) ?: []);
            $matrix = $this->svgTransformMatrix($m[1], $args);

            if ($matrix === null) {
                continue;
            }

            $out .= implode(' ', array_map($this->f(...), $matrix)) . " cm\n";
        }

        return $out;
    }

    /**
     * @param list<float> $args
     * @return list<float>|null [a, b, c, d, e, f]
     */
    private function svgTransformMatrix(string $func, array $args): ?array
    {
        return match ($func) {
            'translate' => [1, 0, 0, 1, $args[0] ?? 0.0, $args[1] ?? 0.0],
            'scale' => [$args[0] ?? 1.0, 0, 0, $args[1] ?? $args[0] ?? 1.0, 0, 0],
            'rotate' => $this->rotateMatrix($args),
            'matrix' => count($args) === 6 ? $args : null,
            'skewX' => [1, 0, tan(deg2rad($args[0] ?? 0)), 1, 0, 0],
            'skewY' => [1, tan(deg2rad($args[0] ?? 0)), 0, 1, 0, 0],
            default => null,
        };
    }

    /**
     * @param list<float> $args
     * @return list<float>
     */
    private function rotateMatrix(array $args): array
    {
        $rad = deg2rad($args[0] ?? 0.0);
        $cos = cos($rad);
        $sin = sin($rad);
        $cx = $args[1] ?? 0.0;
        $cy = $args[2] ?? 0.0;

        if ($cx !== 0.0 || $cy !== 0.0) {
            return [
                $cos, $sin, -$sin, $cos,
                $cx - $cos * $cx + $sin * $cy,
                $cy - $sin * $cx - $cos * $cy,
            ];
        }

        return [$cos, $sin, -$sin, $cos, 0.0, 0.0];
    }

    // -------------------------------------------------------------------------
    // Style parsing
    // -------------------------------------------------------------------------

    /** @return array<string, string> */
    private function defaultStyle(): array
    {
        return [
            'fill' => 'black',
            'fill-opacity' => '1',
            'fill-rule' => 'nonzero',
            'opacity' => '1',
            'stroke' => 'none',
            'stroke-opacity' => '1',
            'stroke-width' => '1',
        ];
    }

    /**
     * @param array<string, string> $parent
     * @return array<string, string>
     */
    private function mergeStyle(array $parent, DOMElement $el): array
    {
        $style = $parent;

        // Presentation attributes (lower priority than inline style)
        $presentationProps = [
            'fill','fill-opacity','fill-rule','stroke','stroke-width',
            'stroke-opacity','opacity','display','visibility',
        ];

        foreach ($presentationProps as $prop) {
            $val = $el->getAttribute($prop);

            if ($val === '') {
                continue;
            }

            $style[$prop] = $val;
        }

        // Inline style attribute (highest priority)
        $inlineStyle = $el->getAttribute('style');

        if ($inlineStyle !== '') {
            foreach (explode(';', $inlineStyle) as $decl) {
                $parts = explode(':', $decl, 2);

                if (count($parts) !== 2) {
                    continue;
                }

                $k = trim($parts[0]);
                $v = trim($parts[1]);

                if ($k === '') {
                    continue;
                }

                $style[$k] = $v;
            }
        }

        // Resolve 'inherit' keyword
        foreach ($style as $k => $v) {
            if ($v !== 'inherit') {
                continue;
            }

            $style[$k] = $parent[$k] ?? '';
        }

        return $style;
    }

    /** @return array{float, float, float}|null [r, g, b] each in 0..1 */
    private function parseColor(string $color): ?array
    {
        $color = strtolower(trim($color));

        if ($color === '' || $color === 'none' || $color === 'transparent') {
            return null;
        }

        // url(...) gradients — not supported, skip
        if (str_starts_with($color, 'url(')) {
            return null;
        }

        // currentColor — not supported (no parent color context), fall back to black
        if ($color === 'currentcolor') {
            return [0.0, 0.0, 0.0];
        }

        // #rrggbb
        if (preg_match('/^#([0-9a-f]{6})$/', $color, $m)) {
            return [
                hexdec(substr($m[1], 0, 2)) / 255,
                hexdec(substr($m[1], 2, 2)) / 255,
                hexdec(substr($m[1], 4, 2)) / 255,
            ];
        }

        // #rgb
        if (preg_match('/^#([0-9a-f]{3})$/', $color, $m)) {
            return [
                hexdec($m[1][0] . $m[1][0]) / 255,
                hexdec($m[1][1] . $m[1][1]) / 255,
                hexdec($m[1][2] . $m[1][2]) / 255,
            ];
        }

        // rgb(r, g, b)
        if (preg_match('/^rgb\(\s*([\d.]+)(%?)\s*,\s*([\d.]+)(%?)\s*,\s*([\d.]+)(%?)\s*\)$/', $color, $m)) {
            $r = $m[2] === '%'
                ? (float) $m[1] / 100
                : (float) $m[1] / 255;
            $g = $m[4] === '%'
                ? (float) $m[3] / 100
                : (float) $m[3] / 255;
            $b = $m[6] === '%'
                ? (float) $m[5] / 100
                : (float) $m[5] / 255;

            return [min(1.0, $r), min(1.0, $g), min(1.0, $b)];
        }

        // Named colors (CSS Level 1 + common extensions)
        /** @var array<string, array{float, float, float}> $named */
        static $named = [
            'aqua' => [0,1,1],
            'black' => [0,0,0],
            'blue' => [0,0,1],
            'brown' => [0.647,0.165,0.165],
            'coral' => [1,0.498,0.314],
            'crimson' => [0.863,0.078,0.235],
            'cyan' => [0,1,1],
            'darkblue' => [0,0,0.545],
            'darkgreen' => [0,0.392,0],
            'darkorange' => [1,0.549,0],
            'darkred' => [0.545,0,0],
            'fuchsia' => [1,0,1],
            'gold' => [1,0.843,0],
            'gray' => [0.502,0.502,0.502],
            'green' => [0,0.502,0],
            'grey' => [0.502,0.502,0.502],
            'indigo' => [0.294,0,0.51],
            'lightblue' => [0.678,0.847,0.902],
            'lightgray' => [0.827,0.827,0.827],
            'lightgreen' => [0.565,0.933,0.565],
            'lightgrey' => [0.827,0.827,0.827],
            'lightpink' => [1,0.714,0.757],
            'lightyellow' => [1,1,0.878],
            'lime' => [0,1,0],
            'magenta' => [1,0,1],
            'maroon' => [0.502,0,0],
            'navy' => [0,0,0.502],
            'olive' => [0.502,0.502,0],
            'orange' => [1,0.647,0],
            'pink' => [1,0.753,0.796],
            'purple' => [0.502,0,0.502],
            'red' => [1,0,0],
            'salmon' => [0.98,0.502,0.447],
            'silver' => [0.753,0.753,0.753],
            'skyblue' => [0.529,0.808,0.922],
            'steelblue' => [0.275,0.510,0.706],
            'teal' => [0,0.502,0.502],
            'tomato' => [1,0.388,0.278],
            'turquoise' => [0.251,0.878,0.816],
            'violet' => [0.933,0.51,0.933],
            'wheat' => [0.961,0.871,0.702],
            'white' => [1,1,1],
            'yellow' => [1,1,0],
            'yellowgreen' => [0.604,0.804,0.196],
        ];

        return $named[$color] ?? null;
    }

    // -------------------------------------------------------------------------
    // Geometry helpers
    // -------------------------------------------------------------------------

    private function ellipsePath(float $cx, float $cy, float $rx, float $ry): string
    {
        $k = 0.5522847498;
        $kx = $rx * $k;
        $ky = $ry * $k;

        return $this->f($cx + $rx) . ' ' . $this->f($cy) . " m\n"
             . $this->f($cx + $rx) . ' ' . $this->f($cy - $ky) . ' '
               . $this->f($cx + $kx) . ' ' . $this->f($cy - $ry) . ' '
               . $this->f($cx) . ' ' . $this->f($cy - $ry) . " c\n"
             . $this->f($cx - $kx) . ' ' . $this->f($cy - $ry) . ' '
               . $this->f($cx - $rx) . ' ' . $this->f($cy - $ky) . ' '
               . $this->f($cx - $rx) . ' ' . $this->f($cy) . " c\n"
             . $this->f($cx - $rx) . ' ' . $this->f($cy + $ky) . ' '
               . $this->f($cx - $kx) . ' ' . $this->f($cy + $ry) . ' '
               . $this->f($cx) . ' ' . $this->f($cy + $ry) . " c\n"
             . $this->f($cx + $kx) . ' ' . $this->f($cy + $ry) . ' '
               . $this->f($cx + $rx) . ' ' . $this->f($cy + $ky) . ' '
               . $this->f($cx + $rx) . ' ' . $this->f($cy) . " c\n"
             . "h\n";
    }

    private function roundedRectPath(float $x, float $y, float $w, float $h, float $rx, float $ry): string
    {
        $k = 0.5522847498;
        $krx = $rx * $k;
        $kry = $ry * $k;

        return $this->f($x + $rx) . ' ' . $this->f($y) . " m\n"
             . $this->f($x + $w - $rx) . ' ' . $this->f($y) . " l\n"
             . $this->f($x + $w - $rx + $krx) . ' ' . $this->f($y) . ' '
               . $this->f($x + $w) . ' ' . $this->f($y + $ry - $kry) . ' '
               . $this->f($x + $w) . ' ' . $this->f($y + $ry) . " c\n"
             . $this->f($x + $w) . ' ' . $this->f($y + $h - $ry) . " l\n"
             . $this->f($x + $w) . ' ' . $this->f($y + $h - $ry + $kry) . ' '
               . $this->f($x + $w - $rx + $krx) . ' ' . $this->f($y + $h) . ' '
               . $this->f($x + $w - $rx) . ' ' . $this->f($y + $h) . " c\n"
             . $this->f($x + $rx) . ' ' . $this->f($y + $h) . " l\n"
             . $this->f($x + $rx - $krx) . ' ' . $this->f($y + $h) . ' '
               . $this->f($x) . ' ' . $this->f($y + $h - $ry + $kry) . ' '
               . $this->f($x) . ' ' . $this->f($y + $h - $ry) . " c\n"
             . $this->f($x) . ' ' . $this->f($y + $ry) . " l\n"
             . $this->f($x) . ' ' . $this->f($y + $ry - $kry) . ' '
               . $this->f($x + $rx - $krx) . ' ' . $this->f($y) . ' '
               . $this->f($x + $rx) . ' ' . $this->f($y) . " c\n"
             . "h\n";
    }

    /** @return list<array{float, float}> */
    private function parsePointsList(string $points): array
    {
        $nums = array_map('floatval', preg_split('/[\s,]+/', trim($points)) ?: []);
        $pts = [];

        for ($i = 0; $i + 1 < count($nums); $i += 2) {
            $pts[] = [$nums[$i], $nums[$i + 1]];
        }

        return $pts;
    }

    private function fa(DOMElement $el, string $attr, float $default = 0.0): float
    {
        $val = $el->getAttribute($attr);

        if ($val === '') {
            return $default;
        }

        return (float) preg_replace('/[^0-9.\-]/', '', $val);
    }

    private function f(float $v): string
    {
        return rtrim(rtrim(sprintf('%.6F', $v), '0'), '.');
    }
}
