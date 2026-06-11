<?php

declare(strict_types=1);

namespace PhpPdf\Image;

use RuntimeException;

/**
 * Generates minimal synthetic JPEG and PNG binaries for unit tests.
 *
 * Only the bytes actually read by PdfImage are correctly encoded; everything
 * else is zeroed or placeholder data.
 */
final class ImageFixtures
{
    // -------------------------------------------------------------------------
    // JPEG builders
    // -------------------------------------------------------------------------

    /**
     * Builds a minimal valid JPEG containing one SOF0 segment.
     *
     * @param int $width      Image width (pixels)
     * @param int $height     Image height (pixels)
     * @param int $components Number of colour components: 1=gray, 3=RGB, 4=CMYK
     */
    public static function buildJpeg(int $width = 10, int $height = 10, int $components = 3): string
    {
        // SOI marker
        $jpeg = "\xFF\xD8";

        // APP0 marker (JFIF, 16 bytes payload)
        $jpeg .= "\xFF\xE0"
               . pack('n', 16) // length (includes 2 length bytes)
               . "JFIF\x00" // identifier
               . "\x01\x01" // version 1.1
               . "\x00" // aspect ratio units
               . "\x00\x01\x00\x01" // pixel aspect ratio 1:1
               . "\x00\x00"; // thumbnail dimensions (none)

        // SOF0 marker (Start Of Frame, baseline)
        // Length = 8 + 3 * nComponents
        $sofLen = 8 + 3 * $components;
        $jpeg .= "\xFF\xC0"
               . pack('n', $sofLen) // length
               . "\x08" // bit precision = 8
               . pack('n', $height) // image height
               . pack('n', $width) // image width
               . chr($components & 0xFF); // number of components

        // Component specifications (3 bytes each)
        for ($i = 1; $i <= $components; $i++) {
            $jpeg .= chr($i & 0xFF) . "\x11\x00";
        }

        // EOI marker
        $jpeg .= "\xFF\xD9";

        return $jpeg;
    }

    /** Builds a JPEG where a standalone RST marker (FF D0) precedes the SOF. */
    public static function buildJpegWithStandaloneMarker(): string
    {
        $jpeg = "\xFF\xD8"; // SOI
        $jpeg .= "\xFF\xD0"; // RST0 (standalone marker, no length)
        // SOF0
        $sofLen = 8 + 3 * 3;
        $jpeg .= "\xFF\xC0" . pack('n', $sofLen) . "\x08"
               . pack('n', 10) . pack('n', 10) . chr(3)
               . "\x01\x11\x00\x02\x11\x00\x03\x11\x00";
        $jpeg .= "\xFF\xD9"; // EOI

        return $jpeg;
    }

    /** Builds a JPEG with a custom marker before the SOF (tests the segLen skip). */
    public static function buildJpegWithCustomMarkerBeforeSof(): string
    {
        $jpeg = "\xFF\xD8"; // SOI
        // Custom APP1 marker (FF E1), payload = "Hello" (5 bytes), length = 7
        $payload = "Hello";
        $jpeg .= "\xFF\xE1" . pack('n', strlen($payload) + 2) . $payload;
        // SOF0
        $sofLen = 8 + 3 * 3;
        $jpeg .= "\xFF\xC0" . pack('n', $sofLen) . "\x08"
               . pack('n', 10) . pack('n', 10) . chr(3)
               . "\x01\x11\x00\x02\x11\x00\x03\x11\x00";
        $jpeg .= "\xFF\xD9";

        return $jpeg;
    }

    /**
     * Builds a JPEG where a subsequent marker position lands on a non-0xFF byte,
     * triggering the "Malformed JPEG: expected marker byte." RuntimeException.
     *
     * Structure:
     *   SOI (FF D8)
     *   APP0 marker (FF E0) with length=4 (2 length bytes + 2 data bytes)
     *   2 data bytes (\x00\x00)
     *   non-FF byte at next marker position (\xAB)
     *
     * The magic check sees substr(0,3) = "\xFF\xD8\xFF" → enters fromJpeg().
     * After advancing past APP0, pos=8 → byte=\xAB → "Malformed JPEG".
     */
    public static function buildMalformedJpeg(): string
    {
        return "\xFF\xD8" // SOI
             . "\xFF\xE0" // APP0 marker
             . "\x00\x04" // length = 4 (2-byte length field + 2 data bytes)
             . "\x00\x00" // APP0 payload (2 bytes)
             . "\xAB\xC0"; // next marker position: \xAB is not \xFF → Malformed JPEG
    }

    /**
     * Builds a JPEG-looking binary with no SOF marker at all.
     * The loop will exhaust without finding SOF → "Could not find SOF" RuntimeException.
     */
    public static function buildJpegWithoutSof(): string
    {
        $jpeg = "\xFF\xD8"; // SOI
        // A DQT segment (0xFF 0xDB) with a short payload
        $payload = str_repeat("\x00", 64);
        $jpeg .= "\xFF\xDB" . pack('n', strlen($payload) + 2) . $payload;
        $jpeg .= "\xFF\xD9"; // EOI — standalone 0xD9 → $pos += 2 then loop ends

        return $jpeg;
    }

    /**
     * Builds a JPEG where the SOF0 segment is too close to the end of the buffer
     * (pos + 9 >= len → breaks before reading dimensions).
     */
    public static function buildJpegWithTruncatedSof(): string
    {
        $jpeg = "\xFF\xD8"; // SOI
        // SOF0 marker but NO payload — immediately followed by EOI
        $jpeg .= "\xFF\xC0"; // SOF0 marker only (no length byte → pos+9 >= len)

        return $jpeg;
    }

    /**
     * Builds a JPEG where a non-SOF, non-standalone marker (DQT) appears with
     * no length bytes following it (pos + 3 >= len → breaks before reading
     * the segment length).
     */
    public static function buildJpegWithMarkerTooCloseToEnd(): string
    {
        $jpeg = "\xFF\xD8"; // SOI
        $jpeg .= "\xFF\xDB"; // DQT marker only, no length bytes follow

        return $jpeg;
    }

    // -------------------------------------------------------------------------
    // PNG builders
    // -------------------------------------------------------------------------

    /**
     * Builds a minimal valid RGB PNG (colorType=2).
     * Uses GD for correctness.
     *
     * @param positive-int $width
     * @param positive-int $height
     */
    public static function buildRgbPng(int $width = 2, int $height = 2): string
    {
        $img = imagecreatetruecolor($width, $height);
        $red = imagecolorallocate($img, 255, 0, 0);
        imagefill($img, 0, 0, self::requireColor($red));
        ob_start();
        imagepng($img);
        $data = ob_get_clean();
        imagedestroy($img);

        return self::requireString($data);
    }

    /**
     * Builds a minimal valid RGBA PNG (colorType=6, hasAlpha=true).
     * Uses GD for correctness.
     *
     * @param positive-int $width
     * @param positive-int $height
     */
    public static function buildRgbaPng(int $width = 2, int $height = 2): string
    {
        $img = imagecreatetruecolor($width, $height);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        $color = imagecolorallocatealpha($img, 255, 0, 0, 64);
        imagefill($img, 0, 0, self::requireColor($color));
        ob_start();
        imagepng($img);
        $data = ob_get_clean();
        imagedestroy($img);

        return self::requireString($data);
    }

    /**
     * Builds a minimal valid grayscale PNG (colorType=0).
     * Built manually using PHP's gzcompress.
     */
    public static function buildGrayscalePng(int $width = 2, int $height = 2): string
    {
        $png = self::pngSignature();

        // IHDR: width, height, bitDepth=8, colorType=0 (gray)
        $ihdrData = pack('NN', $width, $height) . "\x08\x00\x00\x00\x00";
        $png .= self::pngChunk('IHDR', $ihdrData);

        // IDAT: one filter byte (0x00=None) per row + pixel bytes
        $raw = '';

        for ($y = 0; $y < $height; $y++) {
            $raw .= "\x00" . str_repeat(chr(128), $width);
        }

        $png .= self::pngChunk('IDAT', self::requireString(gzcompress($raw, 6)));

        // IEND
        $png .= self::pngChunk('IEND', '');

        return $png;
    }

    /**
     * Builds a minimal valid grayscale+alpha PNG (colorType=4).
     * Built manually using PHP's gzcompress.
     */
    public static function buildGrayAlphaPng(int $width = 2, int $height = 2): string
    {
        $png = self::pngSignature();

        // IHDR: colorType=4 (gray+alpha)
        $ihdrData = pack('NN', $width, $height) . "\x08\x04\x00\x00\x00";
        $png .= self::pngChunk('IHDR', $ihdrData);

        // IDAT: filter + gray + alpha per pixel
        $raw = '';

        for ($y = 0; $y < $height; $y++) {
            $raw .= "\x00"; // filter=none

            for ($x = 0; $x < $width; $x++) {
                $raw .= chr(128) . chr(200); // gray=128, alpha=200
            }
        }

        $png .= self::pngChunk('IDAT', self::requireString(gzcompress($raw, 6)));
        $png .= self::pngChunk('IEND', '');

        return $png;
    }

    /**
     * Builds PNG-magic-bytes followed by garbage, so imagecreatefromstring()
     * returns false → "Failed to decode PNG image data."
     * Length is >25 so the colorType read still executes.
     */
    public static function buildCorruptedPng(): string
    {
        return self::pngSignature() // 8 bytes (valid magic)
             . str_repeat("\x00", 20); // 20 bytes of garbage (total=28, >25)
    }

    /**
     * Builds PNG-magic-bytes followed by too little data to reach the colorType
     * byte at offset 25, so the default colorType (2 = RGB) is used. GD still
     * fails to decode it → "Failed to decode PNG image data."
     * Length is <=25 so the default-colorType branch executes.
     */
    public static function buildTruncatedPng(): string
    {
        return self::pngSignature() // 8 bytes (valid magic)
             . str_repeat("\x00", 10); // 10 bytes of garbage (total=18, <=25)
    }

    /** Returns binary data that is neither JPEG nor PNG. */
    public static function buildUnsupportedImageData(): string
    {
        return 'GIF87a' . "\x00\x01\x00\x01" . "\x00\x00\x00";
    }

    private static function pngSignature(): string
    {
        return "\x89PNG\r\n\x1A\n";
    }

    private static function pngChunk(string $type, string $data): string
    {
        $raw = $type . $data;

        return pack('N', strlen($data)) . $raw . pack('N', crc32($raw) & 0xFFFFFFFF);
    }

    private static function requireString(string|false $value): string
    {
        if ($value === false) {
            throw new RuntimeException('Expected string, got false.');
        }

        return $value;
    }

    private static function requireColor(int|false $value): int
    {
        if ($value === false) {
            throw new RuntimeException('Expected color allocation to succeed.');
        }

        return $value;
    }
}
