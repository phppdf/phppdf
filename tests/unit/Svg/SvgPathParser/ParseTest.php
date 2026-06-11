<?php

declare(strict_types=1);

namespace PhpPdf\Svg\SvgPathParser;

use PhpPdf\Svg\SvgPathParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SvgPathParser::class)]
#[CoversMethod(SvgPathParser::class, 'parse')]
final class ParseTest extends TestCase
{
    private SvgPathParser $parser;

    #[Test]
    public function parseAbsoluteMoveto(): void
    {
        // Arrange / Act
        $out = $this->parser->parse('M 10 20');

        // Assert
        self::assertStringContainsString('10 20 m', $out);
    }

    #[Test]
    public function parseRelativeMoveto(): void
    {
        // Arrange / Act
        $out = $this->parser->parse('M 5 5 m 3 4');

        // Assert — second M is relative: 5+3=8, 5+4=9
        self::assertStringContainsString('8 9 m', $out);
    }

    #[Test]
    public function parseMovetoWithImplicitLineto(): void
    {
        // Arrange — subsequent coords after M become implicit L
        // Act
        $out = $this->parser->parse('M 0 0 10 20');

        // Assert — "10 20 l" should appear (implicit lineto)
        self::assertStringContainsString('10 20 l', $out);
    }

    // -------------------------------------------------------------------------
    // L / l — lineto
    // -------------------------------------------------------------------------

    #[Test]
    public function parseAbsoluteLineto(): void
    {
        $out = $this->parser->parse('M 0 0 L 50 60');

        self::assertStringContainsString('50 60 l', $out);
    }

    #[Test]
    public function parseRelativeLineto(): void
    {
        $out = $this->parser->parse('M 10 10 l 5 5');

        self::assertStringContainsString('15 15 l', $out);
    }

    // -------------------------------------------------------------------------
    // H / h — horizontal lineto
    // -------------------------------------------------------------------------

    #[Test]
    public function parseAbsoluteHorizontalLineto(): void
    {
        $out = $this->parser->parse('M 0 20 H 100');

        // x=100, y stays at 20
        self::assertStringContainsString('100 20 l', $out);
    }

    #[Test]
    public function parseRelativeHorizontalLineto(): void
    {
        $out = $this->parser->parse('M 10 20 h 30');

        // x=10+30=40, y=20
        self::assertStringContainsString('40 20 l', $out);
    }

    // -------------------------------------------------------------------------
    // V / v — vertical lineto
    // -------------------------------------------------------------------------

    #[Test]
    public function parseAbsoluteVerticalLineto(): void
    {
        $out = $this->parser->parse('M 20 0 V 100');

        // x stays 20, y=100
        self::assertStringContainsString('20 100 l', $out);
    }

    #[Test]
    public function parseRelativeVerticalLineto(): void
    {
        $out = $this->parser->parse('M 20 10 v 30');

        // x=20, y=10+30=40
        self::assertStringContainsString('20 40 l', $out);
    }

    // -------------------------------------------------------------------------
    // C / c — cubic Bézier
    // -------------------------------------------------------------------------

    #[Test]
    public function parseAbsoluteCubicBezier(): void
    {
        $out = $this->parser->parse('M 0 0 C 10 20 30 40 50 60');

        self::assertStringContainsString('10 20 30 40 50 60 c', $out);
    }

    #[Test]
    public function parseRelativeCubicBezier(): void
    {
        $out = $this->parser->parse('M 10 10 c 5 5 10 10 15 15');

        // all relative to 10,10: cp1=15,15 cp2=20,20 end=25,25
        self::assertStringContainsString('15 15 20 20 25 25 c', $out);
    }

    // -------------------------------------------------------------------------
    // S / s — smooth cubic (reflects previous control point)
    // -------------------------------------------------------------------------

    #[Test]
    public function parseSmoothCubicAfterC(): void
    {
        // C sets pcpx/pcpy; S should reflect them
        $out = $this->parser->parse('M 0 0 C 10 20 30 40 50 60 S 70 80 90 100');

        // Reflected cp1: 2*50-30=70, 2*60-40=80
        self::assertStringContainsString('70 80 70 80 90 100 c', $out);
    }

    #[Test]
    public function parseSmoothCubicWithoutPriorC(): void
    {
        // No prior C/S — cp1 should equal current point
        $out = $this->parser->parse('M 10 20 S 30 40 50 60');

        // cp1 = current point (10,20) since prevIsC is false
        self::assertStringContainsString('10 20 30 40 50 60 c', $out);
    }

    // -------------------------------------------------------------------------
    // Q / q — quadratic Bézier
    // -------------------------------------------------------------------------

    #[Test]
    public function parseAbsoluteQuadraticBezier(): void
    {
        $out = $this->parser->parse('M 0 0 Q 50 100 100 0');

        // Quad-to-cubic: cx1 = 0 + 2/3*(50-0) = 33.333..., cy1 = 0 + 2/3*(100-0) = 66.666...
        // cx2 = 100 + 2/3*(50-100) = 66.666..., cy2 = 0 + 2/3*(100-0) = 66.666...
        self::assertStringContainsString('c', $out);
        self::assertStringContainsString('100', $out);
    }

    #[Test]
    public function parseRelativeQuadraticBezier(): void
    {
        $out = $this->parser->parse('M 0 0 q 50 100 100 0');

        self::assertStringContainsString('c', $out);
    }

    // -------------------------------------------------------------------------
    // T / t — smooth quadratic
    // -------------------------------------------------------------------------

    #[Test]
    public function parseSmoothQuadraticAfterQ(): void
    {
        // Q sets pcpx/pcpy; T should reflect them
        $out = $this->parser->parse('M 0 0 Q 50 100 100 0 T 200 0');

        // prevIsQ=true: qx1=2*100-50=150, qy1=2*0-100=-100
        self::assertStringContainsString('c', $out);
    }

    #[Test]
    public function parseSmoothQuadraticWithoutPriorQ(): void
    {
        // No prior Q/T — control point equals current point
        $out = $this->parser->parse('M 10 20 T 50 60');

        // prevIsQ=false: qx1=cx=10, qy1=cy=20
        self::assertStringContainsString('c', $out);
    }

    // -------------------------------------------------------------------------
    // A / a — arc
    // -------------------------------------------------------------------------

    #[Test]
    public function parseAbsoluteArcProducesCubicCurves(): void
    {
        $out = $this->parser->parse('M 0 0 A 50 50 0 0 1 100 0');

        self::assertStringContainsString('c', $out);
    }

    #[Test]
    public function parseRelativeArc(): void
    {
        $out = $this->parser->parse('M 0 0 a 50 50 0 0 1 100 0');

        self::assertStringContainsString('c', $out);
    }

    #[Test]
    public function parseArcWithZeroRxBecomesLine(): void
    {
        // rx=0 → degenerate arc → line
        $out = $this->parser->parse('M 0 0 A 0 50 0 0 1 100 50');

        self::assertStringContainsString('100 50 l', $out);
    }

    #[Test]
    public function parseArcWithZeroRyBecomesLine(): void
    {
        // ry=0 → degenerate arc → line
        $out = $this->parser->parse('M 0 0 A 50 0 0 0 1 100 0');

        self::assertStringContainsString('100 0 l', $out);
    }

    #[Test]
    public function parseArcWithSameStartEndBecomesLine(): void
    {
        // x1=x2, y1=y2 → degenerate arc → line to same point
        $out = $this->parser->parse('M 50 50 A 25 25 0 0 1 50 50');

        self::assertStringContainsString('50 50 l', $out);
    }

    #[Test]
    public function parseArcWithLargeRadiusScaling(): void
    {
        // Lambda > 1 triggers radius scaling
        $out = $this->parser->parse('M 0 0 A 1 1 0 0 1 100 0');

        self::assertStringContainsString('c', $out);
    }

    #[Test]
    public function parseArcLargeArcEqualsSweed(): void
    {
        // largeArc=sweep=1 → sq = -sq branch
        $out = $this->parser->parse('M 0 0 A 50 50 0 1 1 100 0');

        self::assertStringContainsString('c', $out);
    }

    #[Test]
    public function parseArcSweepZeroWithPositiveDTheta(): void
    {
        // sweep=0 && dTheta>0 → dTheta -= 2*PI
        $out = $this->parser->parse('M 100 0 A 50 50 0 0 0 0 100');

        self::assertStringContainsString('c', $out);
    }

    #[Test]
    public function parseArcSweepOneWithNegativeDTheta(): void
    {
        // sweep=1 && dTheta<0 → dTheta += 2*PI
        $out = $this->parser->parse('M 0 100 A 50 50 0 0 1 100 0');

        self::assertStringContainsString('c', $out);
    }

    #[Test]
    public function parseArcSweepOneWithNegativeDThetaAddsFullTurn(): void
    {
        // sweep=1 && dTheta<0 → dTheta += 2*PI (line 254)
        $out = $this->parser->parse('M 0 0 A 80 30 0 1 1 100 50');

        self::assertStringContainsString('c', $out);
    }

    #[Test]
    public function parseArcProducesMultipleSegments(): void
    {
        // Large arc (largeArc=1) produces up to 4 Bézier segments
        $out = $this->parser->parse('M 0 0 A 50 50 0 1 0 1 0');

        // Multiple "c" operators expected
        self::assertGreaterThan(1, substr_count($out, ' c'));
    }

    // -------------------------------------------------------------------------
    // Z / z — closepath
    // -------------------------------------------------------------------------

    #[Test]
    public function parseAbsoluteClosepath(): void
    {
        $out = $this->parser->parse('M 0 0 L 100 0 L 100 100 Z');

        self::assertStringContainsString("h\n", $out);
    }

    #[Test]
    public function parseLowercaseClosepath(): void
    {
        $out = $this->parser->parse('M 0 0 L 100 0 z');

        self::assertStringContainsString("h\n", $out);
    }

    // -------------------------------------------------------------------------
    // Tokenizer edge cases
    // -------------------------------------------------------------------------

    #[Test]
    public function parseHandlesNegativeNumbers(): void
    {
        // Minus signs acting as separators between numbers
        $out = $this->parser->parse('M 10-20 L 30-40');

        self::assertStringContainsString('10', $out);
        self::assertStringContainsString('-20', $out);
    }

    #[Test]
    public function parseHandlesCommaDelimiters(): void
    {
        $out = $this->parser->parse('M 10,20 L 30,40');

        self::assertStringContainsString('10 20 m', $out);
        self::assertStringContainsString('30 40 l', $out);
    }

    #[Test]
    public function parseHandlesExponentNotation(): void
    {
        // e+ should NOT be split; minus after 'e' is exponent sign, not separator
        $out = $this->parser->parse('M 1e2 2e1');

        // 1e2 = 100, 2e1 = 20
        self::assertStringContainsString('100 20 m', $out);
    }

    #[Test]
    public function parseEmptyStringReturnsEmptyString(): void
    {
        $out = $this->parser->parse('');

        self::assertSame('', $out);
    }

    #[Test]
    public function parsePathStartingWithNonAlphaTokenBreaksImmediately(): void
    {
        // Arrange — first token is a number, not a command letter → break on line 37
        $out = $this->parser->parse('1 2 3');

        // Assert — no output produced
        self::assertSame('', $out);
    }

    #[Test]
    public function parseResetsStateOnEachCall(): void
    {
        // First call leaves state at 50,50
        $this->parser->parse('M 50 50');

        // Second call should start fresh from 0,0
        $out = $this->parser->parse('M 10 20');

        self::assertStringContainsString('10 20 m', $out);
        self::assertStringNotContainsString('50', $out);
    }

    #[Test]
    public function parseMultipleCommandsInSequence(): void
    {
        $out = $this->parser->parse('M 0 0 L 10 0 L 10 10 L 0 10 Z');

        self::assertStringContainsString('0 0 m', $out);
        self::assertStringContainsString('10 0 l', $out);
        self::assertStringContainsString('10 10 l', $out);
        self::assertStringContainsString('0 10 l', $out);
        self::assertStringContainsString("h\n", $out);
    }

    #[Test]
    public function parseRelativeSmoothCubicAfterS(): void
    {
        // S after S: prev='S' is still in ['C','c','S','s'] → prevIsC=true
        $out = $this->parser->parse('M 0 0 C 10 20 30 40 50 60 S 70 80 90 100 S 110 120 130 140');

        self::assertStringContainsString('c', $out);
    }

    #[Test]
    public function parseSmoothQuadraticAfterT(): void
    {
        // T after T: prev='T' is in ['Q','q','T','t'] → prevIsQ=true
        $out = $this->parser->parse('M 0 0 Q 50 100 100 0 T 200 0 T 300 0');

        self::assertStringContainsString('c', $out);
    }

    protected function setUp(): void
    {
        $this->parser = new SvgPathParser();
    }
}
