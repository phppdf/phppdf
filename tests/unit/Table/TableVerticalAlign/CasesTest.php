<?php

declare(strict_types=1);

namespace PhpPdf\Table\TableVerticalAlign;

use PhpPdf\Table\TableVerticalAlign;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TableVerticalAlign::class)]
final class CasesTest extends TestCase
{
    #[Test]
    public function enumHasExpectedCases(): void
    {
        // Arrange / Act
        $cases = TableVerticalAlign::cases();

        // Assert
        self::assertCount(3, $cases);
        self::assertContains(TableVerticalAlign::Top, $cases);
        self::assertContains(TableVerticalAlign::Middle, $cases);
        self::assertContains(TableVerticalAlign::Bottom, $cases);
    }
}
