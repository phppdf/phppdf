<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\HtmlTableCellData;

use PhpPdf\Html\Internal\HtmlTableCellData;
use PhpPdf\Text\TextAlign;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlTableCellData::class)]
final class ConstructTest extends TestCase
{
    #[Test]
    public function hasExpectedDefaults(): void
    {
        // Arrange / Act
        $data = new HtmlTableCellData();

        // Assert
        self::assertSame('', $data->getText());
        self::assertSame(1, $data->getColspan());
        self::assertSame(1, $data->getRowspan());
        self::assertFalse($data->isBold());
        self::assertFalse($data->isItalic());
        self::assertNull($data->getColor());
        self::assertNull($data->getBackgroundColor());
        self::assertSame(TextAlign::Left, $data->getTextAlign());
    }
}
