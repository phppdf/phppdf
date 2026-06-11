<?php

declare(strict_types=1);

namespace PhpPdf\Encryption;

/**
 * PDF permission flags for the standard security handler (R=4).
 *
 * Bits 1–2 and 7–8 are reserved and must be 0; bits 13–32 must be 1.
 * The resulting 32-bit value is written as a signed integer to the /P entry.
 *
 * Start from PdfPermissions::none() and enable what you need, or use
 * PdfPermissions::all() to allow everything.
 *
 * Example — allow printing but prevent copying or editing:
 *
 *   PdfPermissions::none()->allowPrinting()
 */
final class PdfPermissions
{
    // bit 3
    private const int BIT_PRINT = 0x0004;

    // bit 4
    private const int BIT_MODIFY = 0x0008;

    // bit 5
    private const int BIT_COPY = 0x0010;

    // bit 6
    private const int BIT_ANNOTATE = 0x0020;

    // bit 9
    private const int BIT_FORMS = 0x0100;

    // bit 10
    private const int BIT_ACCESSIBILITY = 0x0200;

    // bit 11
    private const int BIT_ASSEMBLE = 0x0400;

    // bit 12
    private const int BIT_PRINT_HQ = 0x0800;

    // Bits 13–32 must be 1; bits 1–2 and 7–8 must be 0.
    private const int REQUIRED_BITS = 0xFFFFF0C0;

    private function __construct(private int $flags)
    {
    }

    /** No content permissions — view only. */
    public static function none(): self
    {
        return new self(self::REQUIRED_BITS);
    }

    /** All permissions granted. */
    public static function all(): self
    {
        return new self(self::REQUIRED_BITS
            | self::BIT_PRINT | self::BIT_PRINT_HQ
            | self::BIT_MODIFY | self::BIT_COPY
            | self::BIT_ANNOTATE | self::BIT_FORMS
            | self::BIT_ACCESSIBILITY | self::BIT_ASSEMBLE);
    }

    /** Allow printing. Pass false to restrict to low-quality (draft) printing only. */
    public function allowPrinting(bool $highQuality = true): self
    {
        $clone = clone $this;
        $clone->flags |= self::BIT_PRINT;

        if ($highQuality) {
            $clone->flags |= self::BIT_PRINT_HQ;
        }

        return $clone;
    }

    public function allowModification(): self
    {
        $clone = clone $this;
        $clone->flags |= self::BIT_MODIFY;

        return $clone;
    }

    public function allowCopying(): self
    {
        $clone = clone $this;
        $clone->flags |= self::BIT_COPY | self::BIT_ACCESSIBILITY;

        return $clone;
    }

    public function allowAnnotations(): self
    {
        $clone = clone $this;
        $clone->flags |= self::BIT_ANNOTATE;

        return $clone;
    }

    public function allowFormFilling(): self
    {
        $clone = clone $this;
        $clone->flags |= self::BIT_FORMS;

        return $clone;
    }

    public function allowAssembly(): self
    {
        $clone = clone $this;
        $clone->flags |= self::BIT_ASSEMBLE;

        return $clone;
    }

    /**
     * Returns the signed 32-bit integer for the encryption dictionary /P entry.
     */
    public function toInt(): int
    {
        $unsigned = $this->flags & 0xFFFFFFFF;

        // Convert unsigned 32-bit to signed 32-bit (PHP ints are 64-bit).
        return $unsigned >= 0x80000000
            ? $unsigned - 0x100000000
            : $unsigned;
    }
}
