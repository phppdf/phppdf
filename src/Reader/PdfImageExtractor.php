<?php

declare(strict_types=1);

namespace PhpPdf\Reader;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfBoolean;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfStream;
use PhpPdf\Serialization\PdfStreamSerializer;

/**
 * Extracts image XObjects from pages of a parsed PDF document.
 *
 * Images are identified in each page's /Resources /XObject dictionary.
 * Only /Subtype /Image XObjects are returned; form XObjects (/Subtype /Form)
 * and image masks (/ImageMask true) are skipped.
 *
 * The same physical image referenced on multiple pages appears only once in
 * the result from getAllImages() — deduplication is done by xref object number.
 *
 * Stream data is already decoded by the PDF parser (FlateDecode is inflated,
 * DCTDecode is left as-is since it is already a valid JPEG byte stream).
 * The resulting PdfExtractedImage therefore contains either raw JPEG bytes
 * (isJpeg() === true) or flat raw pixel bytes that toPng() can wrap into a PNG.
 */
final class PdfImageExtractor
{
    /** @var array<int, true> xref object numbers already collected this call */
    private array $seen = [];

    public function __construct(private readonly PdfReadDocument $document,)
    {
    }

    /**
     * Returns all image XObjects on the given zero-based page index.
     *
     * @return list<\PhpPdf\Reader\PdfExtractedImage>
     */
    public function getImagesForPage(int $pageIndex): array
    {
        $this->seen = [];

        return $this->extractFromPage($pageIndex);
    }

    /**
     * Returns all unique image XObjects across every page.
     * Images shared between pages are returned only once.
     *
     * @return list<\PhpPdf\Reader\PdfExtractedImage>
     */
    public function getAllImages(): array
    {
        $this->seen = [];
        $images = [];

        for ($i = 0; $i < $this->document->getPageCount(); $i++) {
            foreach ($this->extractFromPage($i) as $image) {
                $images[] = $image;
            }
        }

        return $images;
    }

    // -------------------------------------------------------------------------

    /** @return list<\PhpPdf\Reader\PdfExtractedImage> */
    private function extractFromPage(int $pageIndex): array
    {
        $page = $this->document->getPage($pageIndex);
        $resources = $page->getResources();

        $xobjectEntry = $resources->get('XObject');

        if ($xobjectEntry === null) {
            return [];
        }

        $xobjectDict = $this->document->resolveObject($xobjectEntry);

        if (!$xobjectDict instanceof PdfDictionary) {
            return [];
        }

        $images = [];

        foreach ($xobjectDict->getEntries() as $name => $ref) {
            $objNum = $ref instanceof PdfIndirectReference
                ? $ref->getObjectNumber()
                : 0;

            if ($objNum > 0 && isset($this->seen[$objNum])) {
                continue;
            }

            $xobj = $this->document->resolveObject($ref);

            if (!$xobj instanceof PdfStream) {
                continue;
            }

            $dict = $xobj->getDictionary();

            // Skip non-Image XObjects (e.g. /Form).
            if ($this->getNameValue($dict, 'Subtype') !== 'Image') {
                continue;
            }

            // Skip 1-bit stencil masks — they carry no independent pixel data.
            $imageMask = $dict->get('ImageMask');

            if ($imageMask instanceof PdfBoolean && $imageMask->getValue()) {
                continue;
            }

            if ($objNum > 0) {
                $this->seen[$objNum] = true;
            }

            $image = $this->buildImage($name, $objNum, $dict, $xobj);

            if ($image === null) {
                continue;
            }

            $images[] = $image;
        }

        return $images;
    }

    private function buildImage(string $name, int $objNum, PdfDictionary $dict, PdfStream $stream,): ?PdfExtractedImage
    {
        $width = $this->getIntValue($dict, 'Width');
        $height = $this->getIntValue($dict, 'Height');

        if ($width === null || $height === null || $width === 0 || $height === 0) {
            return null;
        }

        $colorSpace = $this->resolveColorSpaceName($dict);
        $bpc = $this->getIntValue($dict, 'BitsPerComponent') ?? 8;

        $serializer = new PdfStreamSerializer();
        $data = $stream->getData()->serialize($serializer);

        // Resolve the soft-mask (alpha channel) if present.
        $smaskData = null;
        $smaskRef = $dict->get('SMask');

        if ($smaskRef !== null) {
            $smaskStream = $this->document->resolveObject($smaskRef);

            if ($smaskStream instanceof PdfStream) {
                $smaskData = $smaskStream->getData()->serialize($serializer);
            }
        }

        return new PdfExtractedImage(
            objectNumber: $objNum,
            name: $name,
            width: $width,
            height: $height,
            colorSpace: $colorSpace,
            bitsPerComponent: $bpc,
            data: $data,
            smaskData: $smaskData,
        );
    }

    private function resolveColorSpaceName(PdfDictionary $dict): string
    {
        $cs = $dict->get('ColorSpace');

        if ($cs === null) {
            return 'DeviceRGB';
        }

        $resolved = $this->document->resolveObject($cs);

        if ($resolved instanceof PdfName) {
            return $resolved->getValue();
        }

        if ($resolved instanceof PdfArray) {
            $items = $resolved->getItems();
            $first = $items[0] ?? null;

            if ($first instanceof PdfName) {
                return $first->getValue(); // e.g., 'Indexed', 'ICCBased'
            }
        }

        return 'DeviceRGB';
    }

    private function getNameValue(PdfDictionary $dict, string $key): ?string
    {
        $val = $dict->get($key);

        return $val instanceof PdfName
            ? $val->getValue()
            : null;
    }

    private function getIntValue(PdfDictionary $dict, string $key): ?int
    {
        $val = $dict->get($key);

        return $val instanceof PdfInteger
            ? $val->getValue()
            : null;
    }
}
