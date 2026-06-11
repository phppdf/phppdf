<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\HtmlTableData;

use PhpPdf\Html\Internal\HtmlTableData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlTableData::class)]
#[CoversMethod(HtmlTableData::class, 'setPaddingTop')]
final class SetPaddingTopTest extends TestCase
{
    #[Test]
    public function storesPaddingTop(): void
    {
        // Arrange
        $table = new HtmlTableData();

        // Act
        $table->setPaddingTop(8.0);

        // Assert
        self::assertSame(8.0, $table->getPaddingTop());
    }
}
