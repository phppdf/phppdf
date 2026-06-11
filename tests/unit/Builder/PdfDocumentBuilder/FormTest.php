<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfDocumentBuilder;

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfFormBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocument;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfBoolean;
use PhpPdf\Object\PdfContentStream;
use PhpPdf\Object\PdfContentStreamData;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectObject;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Object\PdfRawStreamData;
use PhpPdf\Object\PdfReal;
use PhpPdf\Object\PdfStream;
use PhpPdf\Object\PdfString;
use PhpPdf\Svg\SvgRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentBuilder::class)]
#[CoversMethod(PdfDocumentBuilder::class, 'form')]
#[UsesClass(PdfDocument::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfBoolean::class)]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(PdfContentStreamBuilder::class)]
#[UsesClass(PdfContentStreamData::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfFormBuilder::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfObjectRegistry::class)]
#[UsesClass(PdfPageBuilder::class)]
#[UsesClass(PdfRawStreamData::class)]
#[UsesClass(PdfReal::class)]
#[UsesClass(PdfStream::class)]
#[UsesClass(PdfString::class)]
#[UsesClass(SvgRenderer::class)]
final class FormTest extends TestCase
{
    #[Test]
    public function formReturnsSelf(): void
    {
        // Arrange
        $builder = new PdfDocumentBuilder();

        // Act
        $result = $builder->form(static fn () => null);

        // Assert
        self::assertSame($builder, $result);
    }

    #[Test]
    public function formWithNoFieldsSkipsAcroFormRegistration(): void
    {
        // Arrange — empty form builder: compileForm returns early when $fieldRefs === []
        $document = (new PdfDocumentBuilder())
            ->form(static fn (PdfFormBuilder $f) => null)
            ->page(static fn () => null)
            ->build();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $document);
    }

    #[Test]
    public function formWithTextFieldBuildsDocument(): void
    {
        // Arrange — minimal text field: no value, no tooltip, single-line, not read-only
        $document = (new PdfDocumentBuilder())
            ->form(static function (PdfFormBuilder $f): void {
                $f->textField('name', 72, 700, 200, 20);
            })
            ->page(static fn () => null)
            ->build();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $document);
    }

    #[Test]
    public function formWithTextFieldWithValueAndTooltipBuildsDocument(): void
    {
        // Arrange — text field with value and tooltip covers both non-empty string branches
        $document = (new PdfDocumentBuilder())
            ->form(static function (PdfFormBuilder $f): void {
                $f->textField('name', 72, 700, 200, 20, 0, 'John Doe', 'Enter your name');
            })
            ->page(static fn () => null)
            ->build();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $document);
    }

    #[Test]
    public function formWithTextAreaBuildsDocument(): void
    {
        // Arrange — text area sets the multi flag (bit 13 of Ff)
        $document = (new PdfDocumentBuilder())
            ->form(static function (PdfFormBuilder $f): void {
                $f->textArea('notes', 72, 600, 200, 80);
            })
            ->page(static fn () => null)
            ->build();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $document);
    }

    #[Test]
    public function formWithReadOnlyTextFieldBuildsDocument(): void
    {
        // Arrange — readOnly=true sets bit 1 of Ff
        $document = (new PdfDocumentBuilder())
            ->form(static function (PdfFormBuilder $f): void {
                $f->textField('locked', 72, 700, 200, 20, 0, '', '', 10.0, true);
            })
            ->page(static fn () => null)
            ->build();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $document);
    }

    #[Test]
    public function formWithUncheckedCheckboxBuildsDocument(): void
    {
        // Arrange — unchecked checkbox: state = 'Off', no tooltip
        $document = (new PdfDocumentBuilder())
            ->form(static function (PdfFormBuilder $f): void {
                $f->checkbox('agree', 72, 700);
            })
            ->page(static fn () => null)
            ->build();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $document);
    }

    #[Test]
    public function formWithCheckedCheckboxAndTooltipBuildsDocument(): void
    {
        // Arrange — checked=true covers 'Yes' state; tooltip covers the tooltip branch
        $document = (new PdfDocumentBuilder())
            ->form(static function (PdfFormBuilder $f): void {
                $f->checkbox('agree', 72, 700, 12.0, 0, true, 'I agree to the terms');
            })
            ->page(static fn () => null)
            ->build();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $document);
    }

    #[Test]
    public function formWithComboBoxBuildsDocument(): void
    {
        // Arrange — minimal combo box: no value, no tooltip, not read-only
        $document = (new PdfDocumentBuilder())
            ->form(static function (PdfFormBuilder $f): void {
                $f->comboBox('colour', 72, 700, 120, 20, ['Red', 'Green', 'Blue']);
            })
            ->page(static fn () => null)
            ->build();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $document);
    }

    #[Test]
    public function formWithComboBoxWithValueAndTooltipBuildsDocument(): void
    {
        // Arrange — value and tooltip cover both non-empty string branches in compileComboBox
        $document = (new PdfDocumentBuilder())
            ->form(static function (PdfFormBuilder $f): void {
                $f->comboBox('colour', 72, 700, 120, 20, ['Red', 'Green', 'Blue'], 0, 'Red', 'Pick a colour');
            })
            ->page(static fn () => null)
            ->build();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $document);
    }

    #[Test]
    public function formWithReadOnlyComboBoxBuildsDocument(): void
    {
        // Arrange — readOnly=true sets bit 1 of Ff in compileComboBox
        $document = (new PdfDocumentBuilder())
            ->form(static function (PdfFormBuilder $f): void {
                $f->comboBox('colour', 72, 700, 120, 20, ['Red', 'Green', 'Blue'], 0, '', '', 10.0, true);
            })
            ->page(static fn () => null)
            ->build();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $document);
    }
}
