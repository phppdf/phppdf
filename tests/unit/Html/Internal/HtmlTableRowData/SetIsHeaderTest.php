<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\HtmlTableRowData;

use PhpPdf\Html\Internal\HtmlTableRowData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlTableRowData::class)]
#[CoversMethod(HtmlTableRowData::class, 'setIsHeader')]
final class SetIsHeaderTest extends TestCase
{
    #[Test]
    public function storesIsHeader(): void
    {
        // Arrange
        $row = new HtmlTableRowData();

        // Act
        $row->setIsHeader(true);

        // Assert
        self::assertTrue($row->isHeader());
    }
}
