<?php

declare(strict_types=1);

namespace PhpPdf\Text\RichTextBox;

use PhpPdf\Text\RichTextBox;
use PhpPdf\Text\TextAlign;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RichTextBox::class)]
#[CoversMethod(RichTextBox::class, 'getAlign')]
final class GetAlignTest extends TestCase
{
    #[Test]
    public function getAlignDefaultsToLeft(): void
    {
        // Arrange / Act
        $box = RichTextBox::create([], maxWidth: 200);

        // Assert
        self::assertSame(TextAlign::Left, $box->getAlign());
    }

    #[Test]
    public function getAlignReturnsSuppliedAlignment(): void
    {
        // Arrange / Act
        $box = RichTextBox::create([], maxWidth: 200, align: TextAlign::Center);

        // Assert
        self::assertSame(TextAlign::Center, $box->getAlign());
    }
}
