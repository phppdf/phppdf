<?php

declare(strict_types=1);

namespace PhpPdf\Reader;

/**
 * An image extracted from a page's XObject resources.
 *
 * `$data` holds the decoded stream bytes:
 *   - JPEG images (DCTDecode): the raw JPEG file bytes — write as-is to a .jpg file.
 *   - All other images: flat raw pixel bytes, row-major, top-to-bottom, no padding.
 *     Call toPng() to get a valid PNG file from these bytes.
 *
 * `$smaskData` holds the decoded soft-mask (alpha channel) bytes when present — one
 * byte per pixel, 255 = opaque. toPng() interleaves it automatically as alpha.
 */
final class PdfExtractedImage
{
    public function __construct(
        public readonly int $objectNumber,
        public readonly string $name,
        public readonly int $width,
        public readonly int $height,
        public readonly string $colorSpace,
        public readonly int $bitsPerComponent,
        public readonly string $data,
        public readonly ?string $smaskData = null,
    ) {
    }

    public function isJpeg(): bool
    {
        return str_starts_with($this->data, "\xFF\xD8\xFF");
    }

    public function getFileExtension(): string
    {
        return $this->isJpeg()
            ? 'jpg'
            : 'png';
    }

    /**
     * Returns the image as ready-to-write file bytes.
     * For JPEG images this returns the JPEG bytes directly.
     * For all others it builds a PNG via toPng().
     */
    public function toFileBytes(): string
    {
        return $this->isJpeg()
            ? $this->data
            : $this->toPng();
    }

    /**
     * Builds a PNG file from the raw pixel data in $data.
     * Not meaningful for JPEG images — call isJpeg() first if unsure.
     *
     * Supports DeviceRGB, DeviceGray, and DeviceRGB + SMask (RGBA output).
     * For other color spaces the pixel bytes are written as-is with an RGB
     * color type, which may produce an incorrectly-coloured image.
     *
     * Requires the zlib extension (standard in all PHP distributions).
     */
    public function toPng(): string
    {
        [$colorType, $bytesPerPixel, $pixelData] = $this->preparePixelData();

        $rowBytes = $this->width * $bytesPerPixel;
        $filtered = '';

        for ($y = 0; $y < $this->height; $y++) {
            $filtered .= "\x00"; // filter type 0 = None
            $filtered .= substr($pixelData, $y * $rowBytes, $rowBytes);
        }

        // gzcompress() produces ZLIB-wrapped DEFLATE (RFC 1950), exactly what PNG IDAT expects.
        $idat = gzcompress($filtered) ?: '';

        $ihdr = pack('NN', $this->width, $this->height)
              . chr($this->bitsPerComponent & 0xFF)
              . chr($colorType & 0xFF)
              . "\x00\x00\x00"; // compression method, filter method, interlace

        return "\x89PNG\r\n\x1a\n"
             . $this->pngChunk('IHDR', $ihdr)
             . $this->pngChunk('IDAT', $idat)
             . $this->pngChunk('IEND', '');
    }

    // -------------------------------------------------------------------------

    /** @return array{int, int, string} [PNG color type, bytes per pixel, pixel bytes] */
    private function preparePixelData(): array
    {
        if ($this->colorSpace === 'DeviceGray' && $this->smaskData === null) {
            return [0, 1, $this->data]; // PNG color type 0 = grayscale
        }

        if ($this->colorSpace === 'DeviceRGB' && $this->smaskData !== null) {
            // Interleave RGB + alpha → PNG color type 6 = RGBA.
            $pixelCount = $this->width * $this->height;
            $result = '';

            for ($i = 0; $i < $pixelCount; $i++) {
                $result .= substr($this->data, $i * 3, 3);
                $result .= $this->smaskData[$i] ?? "\xFF";
            }

            return [6, 4, $result];
        }

        // DeviceRGB (no alpha) or unknown — treat as RGB.
        return [2, 3, $this->data];
    }

    private function pngChunk(string $type, string $data): string
    {
        return pack('N', strlen($data))
             . $type
             . $data
             . pack('N', crc32($type . $data));
    }
}
