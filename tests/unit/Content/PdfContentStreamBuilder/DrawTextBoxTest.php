<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Matrix;
use PhpPdf\Content\Operation\BeginText;
use PhpPdf\Content\Operation\EndText;
use PhpPdf\Content\Operation\SetFont;
use PhpPdf\Content\Operation\SetTextMatrix;
use PhpPdf\Content\Operation\SetWordSpacing;
use PhpPdf\Content\Operation\ShowText;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Font\Type1FontMetrics;
use PhpPdf\Object\PdfContentStream;
use PhpPdf\Text\TextAlign;
use PhpPdf\Text\TextBox;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'drawTextBox')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(BeginText::class)]
#[UsesClass(EndText::class)]
#[UsesClass(Matrix::class)]
#[UsesClass(SetFont::class)]
#[UsesClass(SetTextMatrix::class)]
#[UsesClass(SetWordSpacing::class)]
#[UsesClass(ShowText::class)]
#[UsesClass(TextAlign::class)]
#[UsesClass(TextBox::class)]
#[UsesClass(Type1FontMetrics::class)]
final class DrawTextBoxTest extends TestCase
{
    #[Test]
    public function drawTextBoxReturnsEarlyForEmptyLines(): void
    {
        // skip(999) produces a TextBox with getLines() === []
        $box = TextBox::create('Hello', self::metrics(), 12, 200)->skip(999);
        $builder = new PdfContentStreamBuilder();
        $result = $builder->drawTextBox($box, 'F1', 72.0, 720.0);

        self::assertSame($builder, $result);
        self::assertSame([], $builder->build()->getOperations());
    }

    #[Test]
    public function drawTextBoxReturnsSelf(): void
    {
        $box = TextBox::create('Hello', self::metrics(), 12, 200);
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->drawTextBox($box, 'F1', 72.0, 720.0));
    }

    #[Test]
    public function drawTextBoxUsesLinesForWhenMaxHeightProvided(): void
    {
        // Three lines of text; constrain to first two with maxHeight.
        $box = TextBox::create("Line1\nLine2\nLine3", self::metrics(), 12, 500, 14.0);
        $builder = new PdfContentStreamBuilder();
        // maxHeight 14 → linesFor(14) → max(1, floor(14/14)) = 1 line
        $builder->drawTextBox($box, 'F1', 0.0, 0.0, maxHeight: 14.0);
        $ops = $builder->build()->getOperations();
        // BeginText + SetFont + SetTextMatrix + ShowText + EndText = 5
        self::assertCount(5, $ops);
    }

    #[Test]
    public function drawTextBoxWithLeftAlignmentCoversEmptyLine(): void
    {
        // "Hello\n\nWorld" → ["Hello", "", "World"]
        // Empty middle line exercises the $line === '' branch (no showText for that line).
        $box = TextBox::create("Hello\n\nWorld", self::metrics(), 12, 300, 14.0, TextAlign::Left);
        $builder = new PdfContentStreamBuilder();
        $builder->drawTextBox($box, 'F1', 72.0, 720.0);
        $ops = $builder->build()->getOperations();
        $types = array_map(static fn ($op) => $op::class, $ops);

        self::assertContains(BeginText::class, $types);
        self::assertContains(ShowText::class, $types);
        self::assertContains(EndText::class, $types);
        // SetTextMatrix is called for EVERY line (including empty one)
        $setTextMatrixCount = count(array_filter($types, static fn ($t) => $t === SetTextMatrix::class));
        self::assertSame(3, $setTextMatrixCount);
    }

    #[Test]
    public function drawTextBoxWithCenterAlignment(): void
    {
        $box = TextBox::create('Hello', self::metrics(), 12, 300, 14.0, TextAlign::Center);
        $builder = new PdfContentStreamBuilder();
        $builder->drawTextBox($box, 'F1', 72.0, 720.0);
        $ops = $builder->build()->getOperations();
        self::assertContains(ShowText::class, array_map(static fn ($op) => $op::class, $ops));
    }

    #[Test]
    public function drawTextBoxWithRightAlignment(): void
    {
        $box = TextBox::create('Hello', self::metrics(), 12, 300, 14.0, TextAlign::Right);
        $builder = new PdfContentStreamBuilder();
        $builder->drawTextBox($box, 'F1', 72.0, 720.0);
        $ops = $builder->build()->getOperations();
        self::assertContains(ShowText::class, array_map(static fn ($op) => $op::class, $ops));
    }

    #[Test]
    public function drawTextBoxJustifyAppliesWordSpacingToMultiWordNonLastLine(): void
    {
        // "Hello World\nFoo Bar\nTest" → three lines; first two are non-last
        // multi-word lines → tw > 0 applied to first two, reset on last.
        $box = TextBox::create(
            "Hello World\nFoo Bar\nTest",
            self::metrics(),
            12,
            500.0,
            14.0,
            TextAlign::Justify,
        );
        $builder = new PdfContentStreamBuilder();
        $builder->drawTextBox($box, 'F1', 72.0, 720.0);
        $ops = $builder->build()->getOperations();
        $types = array_map(static fn ($op) => $op::class, $ops);

        // At least one SetWordSpacing should appear (for non-zero tw)
        self::assertContains(SetWordSpacing::class, $types);
    }

    #[Test]
    public function drawTextBoxJustifyLastLineNotJustified(): void
    {
        // With a single line, isLastInParagraph=true → no SetWordSpacing
        $box = TextBox::create('Hello World', self::metrics(), 12, 500.0, 14.0, TextAlign::Justify);
        $builder = new PdfContentStreamBuilder();
        $builder->drawTextBox($box, 'F1', 0.0, 0.0);
        $types = array_map(static fn ($op) => $op::class, $builder->build()->getOperations());
        self::assertNotContains(SetWordSpacing::class, $types);
    }

    #[Test]
    public function drawTextBoxJustifyNextLineBlankMakesCurrentLineLastInParagraph(): void
    {
        // "Hello World\n\nTest" → ["Hello World", "", "Test"]
        // Line 0: next is '' → isLastInParagraph=true → no tw for "Hello World"
        $box = TextBox::create(
            "Hello World\n\nTest",
            self::metrics(),
            12,
            500.0,
            14.0,
            TextAlign::Justify,
        );
        $builder = new PdfContentStreamBuilder();
        $builder->drawTextBox($box, 'F1', 0.0, 0.0);
        $types = array_map(static fn ($op) => $op::class, $builder->build()->getOperations());
        // No line is justified (all are last-in-paragraph or empty)
        self::assertNotContains(SetWordSpacing::class, $types);
    }

    #[Test]
    public function drawTextBoxJustifySingleWordNonLastLineNotJustified(): void
    {
        // "Hello\nWorld\nFoo" → three single-word lines; first two non-last
        // but wordCount=1 so no justify applied.
        $box = TextBox::create(
            "Hello\nWorld\nFoo",
            self::metrics(),
            12,
            500.0,
            14.0,
            TextAlign::Justify,
        );
        $builder = new PdfContentStreamBuilder();
        $builder->drawTextBox($box, 'F1', 0.0, 0.0);
        $types = array_map(static fn ($op) => $op::class, $builder->build()->getOperations());
        self::assertNotContains(SetWordSpacing::class, $types);
    }

    private static function metrics(): Type1FontMetrics
    {
        return Type1FontMetrics::helvetica();
    }
}
