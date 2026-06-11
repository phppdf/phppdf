<?php

declare(strict_types=1);

namespace PhpPdf\Text\TextAlign;

use PhpPdf\Text\TextAlign;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TextAlign::class)]
final class CasesTest extends TestCase
{
    #[Test]
    public function enumHasExpectedCases(): void
    {
        // Arrange / Act
        $cases = TextAlign::cases();

        // Assert
        self::assertCount(4, $cases);
        self::assertContains(TextAlign::Left, $cases);
        self::assertContains(TextAlign::Center, $cases);
        self::assertContains(TextAlign::Right, $cases);
        self::assertContains(TextAlign::Justify, $cases);
    }
}
