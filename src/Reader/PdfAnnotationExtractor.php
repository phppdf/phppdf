<?php

declare(strict_types=1);

namespace PhpPdf\Reader;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfBoolean;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfReal;
use PhpPdf\Object\PdfString;

/**
 * Reads annotation dictionaries from each page's /Annots array.
 *
 * Supports: Text (sticky note), Link (URI and GoTo), Highlight, Underline,
 * StrikeOut, Squiggly, Square, Circle. Any other /Subtype is returned with
 * type Unknown so callers can still access the geometry.
 *
 * All coordinates are in PDF user units (points) with the origin at the
 * bottom-left of the page.
 */
final class PdfAnnotationExtractor
{
    public function __construct(private readonly PdfReadDocument $document,)
    {
    }

    /**
     * Returns every annotation on the given zero-based page.
     *
     * @return list<\PhpPdf\Reader\PdfAnnotation>
     */
    public function getAnnotationsForPage(int $pageIndex): array
    {
        $page = $this->document->getPage($pageIndex);

        return $this->extractFromPage($page);
    }

    /**
     * Returns all annotations in the document, keyed by zero-based page index.
     *
     * @return array<int, list<\PhpPdf\Reader\PdfAnnotation>>
     */
    public function getAllAnnotations(): array
    {
        $result = [];

        for ($i = 0; $i < $this->document->getPageCount(); $i++) {
            $anns = $this->getAnnotationsForPage($i);

            if (empty($anns)) {
                continue;
            }

            $result[$i] = $anns;
        }

        return $result;
    }

    // -------------------------------------------------------------------------

    /** @return list<\PhpPdf\Reader\PdfAnnotation> */
    private function extractFromPage(PdfReadPage $page): array
    {
        $annotsEntry = $page->getDictionary()->get('Annots');

        if ($annotsEntry === null) {
            return [];
        }

        $annotsArray = $this->document->resolveObject($annotsEntry);

        if (!$annotsArray instanceof PdfArray) {
            return [];
        }

        $annotations = [];

        foreach ($annotsArray->getItems() as $item) {
            $dict = $this->document->resolveObject($item);

            if (!$dict instanceof PdfDictionary) {
                continue;
            }

            $annotations[] = $this->buildAnnotation($dict);
        }

        return $annotations;
    }

    private function buildAnnotation(PdfDictionary $dict): PdfAnnotation
    {
        $type = PdfAnnotationType::fromPdfName($this->nameValue($dict, 'Subtype'));

        [$x, $y, $w, $h] = $this->readRect($dict);

        $contents = $this->stringValue($dict, 'Contents');
        $title = $this->stringValue($dict, 'T');
        $color = $this->readColor($dict, 'C');
        $ic = $this->readColor($dict, 'IC');
        $open = $this->boolValue($dict, 'Open');
        $bw = $this->readBorderWidth($dict);
        $qp = $this->readQuadPoints($dict);
        $uri = $type === PdfAnnotationType::Link
            ? $this->readUri($dict)
            : null;

        return new PdfAnnotation(
            type: $type,
            x: $x,
            y: $y,
            width: $w,
            height: $h,
            contents: $contents,
            title: $title,
            color: $color,
            interiorColor: $ic,
            quadPoints: $qp,
            uri: $uri,
            open: $open,
            borderWidth: $bw,
        );
    }

    // -------------------------------------------------------------------------
    // Field extractors
    // -------------------------------------------------------------------------

    /** @return array{float, float, float, float} [x, y, width, height] */
    private function readRect(PdfDictionary $dict): array
    {
        $entry = $dict->get('Rect');

        if ($entry === null) {
            return [0.0, 0.0, 0.0, 0.0];
        }

        $arr = $this->document->resolveObject($entry);

        if (!$arr instanceof PdfArray) {
            return [0.0, 0.0, 0.0, 0.0];
        }

        $items = $arr->getItems();
        $x1 = $this->floatItem($items[0] ?? null) ?? 0.0;
        $y1 = $this->floatItem($items[1] ?? null) ?? 0.0;
        $x2 = $this->floatItem($items[2] ?? null) ?? 0.0;
        $y2 = $this->floatItem($items[3] ?? null) ?? 0.0;

        // PDF rects are [llx lly urx ury] but some generators write them swapped.
        $x = min($x1, $x2);
        $y = min($y1, $y2);

        return [$x, $y, abs($x2 - $x1), abs($y2 - $y1)];
    }

    /** @return array{float,float,float}|null */
    private function readColor(PdfDictionary $dict, string $key): ?array
    {
        $entry = $dict->get($key);

        if ($entry === null) {
            return null;
        }

        $arr = $this->document->resolveObject($entry);

        if (!$arr instanceof PdfArray) {
            return null;
        }

        $items = $arr->getItems();

        return match (count($items)) {
            1 => (function () use ($items): array {
                $g = $this->floatItem($items[0]) ?? 0.0;

                return [$g, $g, $g];
            })(),
            3 => [
                $this->floatItem($items[0]) ?? 0.0,
                $this->floatItem($items[1]) ?? 0.0,
                $this->floatItem($items[2]) ?? 0.0,
            ],
            4 => (function () use ($items): array {
                // CMYK → approximate RGB
                $c = $this->floatItem($items[0]) ?? 0.0;
                $m = $this->floatItem($items[1]) ?? 0.0;
                $yw = $this->floatItem($items[2]) ?? 0.0;
                $k = $this->floatItem($items[3]) ?? 0.0;

                return [
                    (1 - $c) * (1 - $k),
                    (1 - $m) * (1 - $k),
                    (1 - $yw) * (1 - $k),
                ];
            })(),
            default => null,
        };
    }

    /** @return list<float>|null */
    private function readQuadPoints(PdfDictionary $dict): ?array
    {
        $entry = $dict->get('QuadPoints');

        if ($entry === null) {
            return null;
        }

        $arr = $this->document->resolveObject($entry);

        if (!$arr instanceof PdfArray) {
            return null;
        }

        $pts = [];

        foreach ($arr->getItems() as $item) {
            $v = $this->floatItem($item);

            if ($v === null) {
                continue;
            }

            $pts[] = $v;
        }

        return empty($pts)
            ? null
            : $pts;
    }

    private function readUri(PdfDictionary $dict): ?string
    {
        $aEntry = $dict->get('A');

        if ($aEntry === null) {
            return null;
        }

        $action = $this->document->resolveObject($aEntry);

        if (!$action instanceof PdfDictionary) {
            return null;
        }

        if ($this->nameValue($action, 'S') !== 'URI') {
            return null;
        }

        return $this->stringValue($action, 'URI');
    }

    private function readBorderWidth(PdfDictionary $dict): float
    {
        $bs = $dict->get('BS');

        if ($bs !== null) {
            $bsDict = $this->document->resolveObject($bs);

            if ($bsDict instanceof PdfDictionary) {
                $w = $bsDict->get('W');

                return $this->floatItem($w) ?? 0.0;
            }
        }

        // Legacy /Border [rx ry width] array
        $border = $dict->get('Border');

        if ($border !== null) {
            $arr = $this->document->resolveObject($border);

            if ($arr instanceof PdfArray) {
                $items = $arr->getItems();

                return $this->floatItem($items[2] ?? null) ?? 0.0;
            }
        }

        return 0.0;
    }

    // -------------------------------------------------------------------------
    // Low-level helpers
    // -------------------------------------------------------------------------

    private function nameValue(PdfDictionary $dict, string $key): ?string
    {
        $v = $dict->get($key);

        return $v instanceof PdfName
            ? $v->getValue()
            : null;
    }

    private function stringValue(PdfDictionary $dict, string $key): ?string
    {
        $v = $dict->get($key);
        $resolved = $v !== null
            ? $this->document->resolveObject($v)
            : null;

        return $resolved instanceof PdfString
            ? $resolved->getValue()
            : null;
    }

    private function boolValue(PdfDictionary $dict, string $key): bool
    {
        $v = $dict->get($key);

        return $v instanceof PdfBoolean && $v->getValue();
    }

    private function floatItem(mixed $item): ?float
    {
        if ($item instanceof PdfReal) {
            return $item->getValue();
        }

        if ($item instanceof PdfInteger) {
            return (float) $item->getValue();
        }

        return null;
    }
}
