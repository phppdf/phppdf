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
use PhpPdf\Text\RichTextBox;
use PhpPdf\Text\TextAlign;
use PhpPdf\Text\TextSpan;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'drawRichTextBox')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(BeginText::class)]
#[UsesClass(EndText::class)]
#[UsesClass(Matrix::class)]
#[UsesClass(SetFont::class)]
#[UsesClass(SetTextMatrix::class)]
#[UsesClass(SetWordSpacing::class)]
#[UsesClass(ShowText::class)]
#[UsesClass(RichTextBox::class)]
#[UsesClass(TextAlign::class)]
#[UsesClass(TextSpan::class)]
#[UsesClass(Type1FontMetrics::class)]
final class DrawRichTextBoxTest extends TestCase
{
    private Type1FontMetrics $metrics;

    #[Test]
    public function drawRichTextBoxReturnsSelf(): void
    {
        // Arrange
        $box = RichTextBox::create(
            spans: [TextSpan::create('Hello World', 'F1', 12.0, $this->metrics)],
            maxWidth: 200.0,
        );
        $stream = new PdfContentStreamBuilder();

        // Act
        $result = $stream->drawRichTextBox($box, x: 72.0, y: 720.0);

        // Assert
        self::assertSame($stream, $result);
    }

    #[Test]
    public function drawRichTextBoxEmitsBeginTextSetFontSetTextMatrixShowTextEndText(): void
    {
        // Arrange
        $box = RichTextBox::create(
            spans: [TextSpan::create('Hello', 'F1', 12.0, $this->metrics)],
            maxWidth: 200.0,
        );
        $stream = new PdfContentStreamBuilder();

        // Act
        $stream->drawRichTextBox($box, x: 72.0, y: 720.0);
        $types = array_map(static fn($op) => $op::class, $stream->build()->getOperations());

        // Assert
        self::assertContains(BeginText::class, $types);
        self::assertContains(SetFont::class, $types);
        self::assertContains(SetTextMatrix::class, $types);
        self::assertContains(ShowText::class, $types);
        self::assertContains(EndText::class, $types);
    }

    // =========================================================================
    // Empty line list — early return ($lines === [])
    // =========================================================================

    #[Test]
    public function drawRichTextBoxReturnsEarlyForEmptyLineList(): void
    {
        // Arrange — skip(999) produces a RichTextBox whose getLines() returns []
        $base = RichTextBox::create(
            spans: [TextSpan::create('Hello', 'F1', 12.0, $this->metrics)],
            maxWidth: 200.0,
        );
        $empty = $base->skip(999);
        $stream = new PdfContentStreamBuilder();

        // Act
        $result = $stream->drawRichTextBox($empty, x: 72.0, y: 720.0);

        // Assert — nothing emitted, self returned
        self::assertSame($stream, $result);
        self::assertSame([], $stream->build()->getOperations());
    }

    // =========================================================================
    // Finite maxHeight — linesFor() path
    // =========================================================================

    #[Test]
    public function drawRichTextBoxWithMaxHeightUsesLinesForPath(): void
    {
        // Arrange — narrow maxWidth forces wrapping to many lines; maxHeight=14 limits to 1
        $box = RichTextBox::create(
            spans: [TextSpan::create(str_repeat('word ', 10), 'F1', 12.0, $this->metrics)],
            maxWidth: 50.0,
            lineHeight: 14.0,
        );
        $stream = new PdfContentStreamBuilder();

        // Act — linesFor(14.0): max(1, floor(14/14)) = 1 line rendered
        $stream->drawRichTextBox($box, x: 0.0, y: 0.0, maxHeight: 14.0);
        $types = array_map(static fn($op) => $op::class, $stream->build()->getOperations());

        // Assert — BeginText + SetFont + SetTextMatrix + ShowText + EndText = 5 ops (1 line)
        self::assertCount(5, $types);
    }

    // =========================================================================
    // Empty inner line ($line === []) — blank line skips rendering
    // =========================================================================

    #[Test]
    public function drawRichTextBoxSkipsEmptyLineAndStillEmitsBeginEndText(): void
    {
        // Arrange — whitespace-only span produces tokens=[] → getLines() = [[]] (one empty line)
        $box = RichTextBox::create(
            spans: [TextSpan::create('   ', 'F1', 12.0, $this->metrics)],
            maxWidth: 200.0,
            lineHeight: 14.0,
        );
        $stream = new PdfContentStreamBuilder();

        // Act
        $stream->drawRichTextBox($box, x: 72.0, y: 720.0);
        $types = array_map(static fn($op) => $op::class, $stream->build()->getOperations());

        // Assert — BeginText/EndText wrap the loop, but no SetFont or ShowText
        self::assertContains(BeginText::class, $types);
        self::assertContains(EndText::class, $types);
        self::assertNotContains(SetFont::class, $types);
        self::assertNotContains(ShowText::class, $types);
    }

    // =========================================================================
    // Alignment — Center and Right
    // =========================================================================

    #[Test]
    public function drawRichTextBoxWithCenterAlignment(): void
    {
        // Arrange
        $box = RichTextBox::create(
            spans: [TextSpan::create('Hello', 'F1', 12.0, $this->metrics)],
            maxWidth: 300.0,
            align: TextAlign::Center,
        );
        $stream = new PdfContentStreamBuilder();

        // Act / Assert — Center branch exercised without exception
        $stream->drawRichTextBox($box, x: 72.0, y: 720.0);
        $types = array_map(static fn($op) => $op::class, $stream->build()->getOperations());
        self::assertContains(SetTextMatrix::class, $types);
    }

    #[Test]
    public function drawRichTextBoxWithRightAlignment(): void
    {
        // Arrange
        $box = RichTextBox::create(
            spans: [TextSpan::create('Hello', 'F1', 12.0, $this->metrics)],
            maxWidth: 300.0,
            align: TextAlign::Right,
        );
        $stream = new PdfContentStreamBuilder();

        // Act / Assert — Right branch exercised without exception
        $stream->drawRichTextBox($box, x: 72.0, y: 720.0);
        $types = array_map(static fn($op) => $op::class, $stream->build()->getOperations());
        self::assertContains(SetTextMatrix::class, $types);
    }

    // =========================================================================
    // Empty span text — span with getText() === '' is skipped
    // =========================================================================

    #[Test]
    public function drawRichTextBoxSkipsSpanWithEmptyText(): void
    {
        // The public API never produces empty-text spans; use reflection to inject
        // a preLines array containing one to exercise the defensive guard.
        $emptySpan = TextSpan::create('', 'F1', 12.0, $this->metrics);
        $normalSpan = TextSpan::create('Hello', 'F1', 12.0, $this->metrics);

        $rc = new ReflectionClass(RichTextBox::class);
        $box = $rc->newInstanceWithoutConstructor();
        (new ReflectionProperty(RichTextBox::class, 'maxWidth'))->setValue($box, 200.0);
        (new ReflectionProperty(RichTextBox::class, 'lineHeight'))->setValue($box, 14.4);
        (new ReflectionProperty(RichTextBox::class, 'align'))->setValue($box, TextAlign::Left);
        (new ReflectionProperty(RichTextBox::class, 'lines'))->setValue($box, [[$emptySpan, $normalSpan]]);

        $stream = new PdfContentStreamBuilder();

        // Act
        $stream->drawRichTextBox($box, x: 72.0, y: 720.0);
        $types = array_map(static fn($op) => $op::class, $stream->build()->getOperations());

        // Assert — ShowText called once (for 'Hello' only; empty span is skipped)
        $showTextCount = count(array_filter($types, static fn($t) => $t === ShowText::class));
        self::assertSame(1, $showTextCount);
    }

    // =========================================================================
    // Justify alignment — SetWordSpacing emitted for non-last lines
    // =========================================================================

    #[Test]
    public function drawRichTextBoxWithJustifyAlignmentEmitsWordSpacingForNonLastLines(): void
    {
        // Arrange — narrow column forces wrapping into multiple lines;
        // all lines except the last must receive a Tw (SetWordSpacing) operator.
        $box = RichTextBox::create(
            spans: [TextSpan::create(str_repeat('word ', 8), 'F1', 12.0, $this->metrics)],
            maxWidth: 80.0,
            lineHeight: 14.4,
            align: TextAlign::Justify,
        );
        $stream = new PdfContentStreamBuilder();

        // Act
        $stream->drawRichTextBox($box, x: 0.0, y: 200.0);
        $types = array_map(static fn($op) => $op::class, $stream->build()->getOperations());

        // Assert — at least one SetWordSpacing must appear (for the non-last line)
        self::assertContains(SetWordSpacing::class, $types);
    }

    #[Test]
    public function drawRichTextBoxJustifyLastLineIsNotSpread(): void
    {
        // Arrange — force exactly two lines by choosing maxWidth that fits ~half the words.
        // Line 1 (non-last) gets Tw > 0; line 2 (last) resets Tw to 0.
        $box = RichTextBox::create(
            spans: [TextSpan::create(str_repeat('word ', 4), 'F1', 12.0, $this->metrics)],
            maxWidth: 80.0,
            lineHeight: 14.4,
            align: TextAlign::Justify,
        );
        $stream = new PdfContentStreamBuilder();

        // Act
        $stream->drawRichTextBox($box, x: 0.0, y: 200.0);
        $ops = $stream->build()->getOperations();

        // Assert — exactly two SetWordSpacing ops: one to set Tw>0 and one to reset to 0
        $twOps = array_filter($ops, static fn($op) => $op instanceof SetWordSpacing);
        self::assertCount(2, array_values($twOps));
    }

    // =========================================================================
    // Font-key caching — setFont skipped when the same font continues across lines
    // =========================================================================

    #[Test]
    public function drawRichTextBoxSkipsSetFontWhenSameFontContinuesAcrossLines(): void
    {
        // Arrange — narrow maxWidth forces wrapping to multiple lines all in the same
        // font. After line 1's span sets $currentFontKey, subsequent lines with the
        // same font do NOT call setFont again.
        $box = RichTextBox::create(
            spans: [TextSpan::create(str_repeat('word ', 20), 'F1', 12.0, $this->metrics)],
            maxWidth: 80.0,
            lineHeight: 14.4,
        );
        $stream = new PdfContentStreamBuilder();

        // Act
        $stream->drawRichTextBox($box, x: 72.0, y: 720.0);
        $types = array_map(static fn($op) => $op::class, $stream->build()->getOperations());

        $lineCount = count($box->getLines());
        $setFontCount = count(array_filter($types, static fn($t) => $t === SetFont::class));

        // Assert — must have wrapped to multiple lines; setFont called only once
        self::assertGreaterThan(1, $lineCount);
        self::assertSame(1, $setFontCount);
    }

    protected function setUp(): void
    {
        $this->metrics = Type1FontMetrics::helvetica();
    }
}
