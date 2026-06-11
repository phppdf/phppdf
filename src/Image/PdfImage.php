<?php

declare(strict_types=1);

namespace PhpPdf\Image;

use RuntimeException;

/**
 * A raster image ready for embedding in a PDF as an image XObject.
 *
 * Supported formats:
 *   - JPEG (.jpg / .jpeg) — embedded as-is with the DCTDecode filter; no GD
 *     dependency. Grayscale, RGB and CMYK JPEG are all supported.
 *   - PNG (.png) — pixel data extracted with GD (ext-gd required). Transparent
 *     PNG images preserve the alpha channel as a separate greyscale soft-mask
 *     (/SMask) XObject, which PDF viewers use for compositing.
 *
 * Usage:
 *   $image = PdfImage::fromFile('/path/to/photo.jpg');
 *   $image = PdfImage::fromFile('/path/to/logo.png');
 *
 * Then register it on a page with PdfPageBuilder::useImage() and paint it
 * with PdfContentStreamBuilder::drawImage().
 */
final class PdfImage
{
    private function __construct(
        private readonly int $width,
        private readonly int $height,
        private readonly string $colorSpace, // DeviceRGB, DeviceGray, DeviceCMYK
        private readonly string $data, // JPEG bytes or raw pixel bytes
        private readonly bool $isJpeg,
        private readonly ?string $maskData, // 8-bit grey alpha channel, or null
    ) {
    }

    public static function fromFile(string $path): self
    {
        if (!is_readable($path)) {
            throw new RuntimeException("Image file not readable: {$path}");
        }

        return self::fromData(file_get_contents($path) ?: '');
    }

    public static function fromData(string $data): self
    {
        // Identify by magic bytes.
        if (substr($data, 0, 3) === "\xFF\xD8\xFF") {
            return self::fromJpeg($data);
        }

        if (substr($data, 0, 8) === "\x89PNG\r\n\x1a\n") {
            return self::fromPng($data);
        }

        throw new RuntimeException('Unsupported image format. Only JPEG and PNG are currently supported.');
    }

    // -------------------------------------------------------------------------
    // Getters — consumed by PdfPageBuilder when building the XObject stream
    // -------------------------------------------------------------------------

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getColorSpace(): string
    {
        return $this->colorSpace;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function isJpeg(): bool
    {
        return $this->isJpeg;
    }

    public function getMaskData(): ?string
    {
        return $this->maskData;
    }

    public function hasMask(): bool
    {
        return $this->maskData !== null;
    }

    // -------------------------------------------------------------------------
    // Format-specific parsers
    // -------------------------------------------------------------------------

    private static function fromJpeg(string $data): self
    {
        [$width, $height, $colorSpace] = self::parseJpegSof($data);

        return new self($width, $height, $colorSpace, $data, true, null);
    }

    private static function fromPng(string $data): self
    {
        if (!function_exists('imagecreatefromstring')) { // @codeCoverageIgnore
            throw new RuntimeException( // @codeCoverageIgnore
                'PNG support requires the GD extension (ext-gd). ' // @codeCoverageIgnore
                . 'Install it or use JPEG images instead.', // @codeCoverageIgnore
            ); // @codeCoverageIgnore
        } // @codeCoverageIgnore

        // PNG IHDR: signature(8) + chunk length(4) + "IHDR"(4) + width(4) + height(4) + bitDepth(1) + colorType(1)
        //           = colorType at byte offset 25
        $colorType = strlen($data) > 25
            ? ord($data[25])
            : 2;

        $isGray = in_array($colorType, [0, 4], true); // 0=grey, 4=grey+alpha
        $hasAlpha = in_array($colorType, [4, 6], true); // 4=grey+alpha, 6=RGBA

        $gd = @imagecreatefromstring($data);

        if ($gd === false) {
            throw new RuntimeException('Failed to decode PNG image data.');
        }

        // Preserve alpha channel during pixel extraction.
        imagealphablending($gd, false);
        imagesavealpha($gd, true);

        $width = imagesx($gd);
        $height = imagesy($gd);

        $pixelData = '';
        $alphaData = $hasAlpha
            ? ''
            : null;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $c = imagecolorat($gd, $x, $y);

                if ($hasAlpha) {
                    // GD 7-bit alpha: 0=opaque, 127=transparent.
                    // PDF 8-bit alpha (SMask): 255=opaque, 0=transparent.
                    $gdAlpha = ($c >> 24) & 0x7F;
                    $alphaData .= chr((int) round((127 - $gdAlpha) * 255 / 127) & 0xFF);
                }

                if ($isGray) {
                    // GD converts greyscale to truecolor (R=G=B); use any channel.
                    $pixelData .= chr(($c >> 16) & 0xFF);
                } else {
                    $pixelData .= chr(($c >> 16) & 0xFF); // R
                    $pixelData .= chr(($c >> 8) & 0xFF); // G
                    $pixelData .= chr($c & 0xFF); // B
                }
            }
        }

        return new self($width, $height, $isGray ? 'DeviceGray' : 'DeviceRGB', $pixelData, false, $alphaData);
    }

    /**
     * Walks JPEG markers to find a SOF segment, which contains the image
     * dimensions and component count. No GD dependency.
     *
     * @return array{int, int, string} [width, height, colorSpace]
     */
    private static function parseJpegSof(string $data): array
    {
        $len = strlen($data);
        $pos = 2; // skip SOI (FF D8)

        while ($pos < $len - 1) {
            if (ord($data[$pos]) !== 0xFF) {
                throw new RuntimeException('Malformed JPEG: expected marker byte.');
            }

            $marker = ord($data[$pos + 1]);

            // SOF0–SOF3, SOF5–SOF7, SOF9–SOF11 (all image-bearing SOF types)
            if (
                ($marker >= 0xC0 && $marker <= 0xC3)
                || ($marker >= 0xC5 && $marker <= 0xC7)
                || ($marker >= 0xC9 && $marker <= 0xCB)
            ) {
                if ($pos + 9 >= $len) {
                    break;
                }

                $height = (ord($data[$pos + 5]) << 8) | ord($data[$pos + 6]);
                $width = (ord($data[$pos + 7]) << 8) | ord($data[$pos + 8]);
                $components = ord($data[$pos + 9]);

                $colorSpace = match ($components) {
                    1 => 'DeviceGray',
                    4 => 'DeviceCMYK',
                    default => 'DeviceRGB',
                };

                return [$width, $height, $colorSpace];
            }

            // Standalone markers without a length field.
            if ($marker === 0xD8 || $marker === 0xD9 || ($marker >= 0xD0 && $marker <= 0xD7)) {
                $pos += 2;

                continue;
            }

            if ($pos + 3 >= $len) {
                break;
            }

            $segLen = (ord($data[$pos + 2]) << 8) | ord($data[$pos + 3]);
            $pos += 2 + $segLen;
        }

        throw new RuntimeException('Could not find JPEG SOF marker — the file may be corrupt.');
    }
}
