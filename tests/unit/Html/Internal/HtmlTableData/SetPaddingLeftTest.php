<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\HtmlTableData;

use PhpPdf\Html\Internal\HtmlTableData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlTableData::class)]
#[CoversMethod(HtmlTableData::class, 'setPaddingLeft')]
final class SetPaddingLeftTest extends TestCase
{
    #[Test]
    public function storesPaddingLeft(): void
    {
        // Arrange
        $table = new HtmlTableData();

        // Act
        $table->setPaddingLeft(10.0);

        // Assert
        self::assertSame(10.0, $table->getPaddingLeft());
    }
}
