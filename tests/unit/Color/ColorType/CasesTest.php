<?php

declare(strict_types=1);

namespace PhpPdf\Color\ColorType;

use PhpPdf\Color\ColorType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ColorType::class)]
final class CasesTest extends TestCase
{
    #[Test]
    public function enumHasThreeCases(): void
    {
        self::assertCount(3, ColorType::cases());
    }

    #[Test]
    public function casesExist(): void
    {
        self::assertSame(ColorType::Gray, ColorType::Gray);
        self::assertSame(ColorType::Rgb, ColorType::Rgb);
        self::assertSame(ColorType::Cmyk, ColorType::Cmyk);
    }
}
