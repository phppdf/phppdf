<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfFormBuilder;

use PhpPdf\Builder\PdfFormBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfFormBuilder::class)]
#[CoversMethod(PdfFormBuilder::class, 'comboBox')]
final class ComboBoxTest extends TestCase
{
    #[Test]
    public function comboBoxReturnsSelf(): void
    {
        // Arrange
        $form = new PdfFormBuilder();

        // Act
        $result = $form->comboBox('color', 10, 20, 100, 20, ['Red', 'Green', 'Blue']);

        // Assert
        self::assertSame($form, $result);
    }

    #[Test]
    public function comboBoxStoresFieldWithCorrectType(): void
    {
        // Arrange
        $form = new PdfFormBuilder();

        // Act
        $form->comboBox('color', 10, 20, 100, 20, ['Red', 'Green', 'Blue']);

        // Assert
        $fields = $form->getFields();
        self::assertCount(1, $fields);
        self::assertSame('combo', $fields[0]['type']);
        self::assertSame('color', $fields[0]['name']);
    }

    #[Test]
    public function comboBoxStoresOptions(): void
    {
        // Arrange
        $form = new PdfFormBuilder();
        $options = ['Red', 'Green', 'Blue'];

        // Act
        $form->comboBox('color', 10, 20, 100, 20, $options);

        // Assert
        self::assertSame($options, $form->getFields()[0]['options']);
    }
}
