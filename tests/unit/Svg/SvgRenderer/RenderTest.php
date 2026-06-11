<?php

declare(strict_types=1);

namespace PhpPdf\Svg\SvgRenderer;

use PhpPdf\Svg\SvgDocument;
use PhpPdf\Svg\SvgPathParser;
use PhpPdf\Svg\SvgRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SvgRenderer::class)]
#[CoversMethod(SvgRenderer::class, 'render')]
#[UsesClass(SvgDocument::class)]
#[UsesClass(SvgPathParser::class)]
final class RenderTest extends TestCase
{
    private SvgRenderer $renderer;

    #[Test]
    public function renderEmptySvgReturnsEmptyString(): void
    {
        // Arrange
        $svg = SvgDocument::fromString('<svg xmlns="http://www.w3.org/2000/svg"/>');

        // Act
        $out = $this->renderer->render($svg);

        // Assert
        self::assertSame('', $out);
    }

    #[Test]
    public function renderSkipsNonElementChildNodes(): void
    {
        // Arrange — SVG with a text node child (whitespace); non-DOMElement nodes
        // trigger the continue on line 49 of SvgRenderer
        $svg = SvgDocument::fromString(
            "<svg xmlns=\"http://www.w3.org/2000/svg\">\n"
            . "  <rect x=\"0\" y=\"0\" width=\"10\" height=\"10\"/>\n"
            . '</svg>',
        );

        // Act — renders successfully, text nodes are skipped silently
        $out = $this->renderer->render($svg);

        // Assert — the rect is still rendered
        self::assertStringContainsString(' re', $out);
    }

    #[Test]
    public function renderPathWithNonAlphaOnlyDReturnsEmpty(): void
    {
        // Arrange — d starts with a number, so pathParser->parse() returns ''
        // which exercises the "pathOps === ''" branch on line 197 of SvgRenderer
        $svg = SvgDocument::fromString('<svg xmlns="http://www.w3.org/2000/svg">' . '<path d="1 2 3"/>' . '</svg>');

        // Act
        $out = $this->renderer->render($svg);

        // Assert
        self::assertSame('', $out);
    }

    #[Test]
    public function renderSkipsDisplayNoneElement(): void
    {
        // Arrange
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" display="none"/>'
            . '</svg>',
        );

        // Act
        $out = $this->renderer->render($svg);

        // Assert
        self::assertSame('', $out);
    }

    #[Test]
    public function renderSkipsVisibilityHiddenElement(): void
    {
        // Arrange
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" visibility="hidden"/>'
            . '</svg>',
        );

        // Act
        $out = $this->renderer->render($svg);

        // Assert
        self::assertSame('', $out);
    }

    #[Test]
    public function renderUnknownElementDelegatesRenderChildren(): void
    {
        // Arrange — unknown element containing a rect
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<unknown><rect x="0" y="0" width="10" height="10"/></unknown>'
            . '</svg>',
        );

        // Act
        $out = $this->renderer->render($svg);

        // Assert — the child rect is rendered
        self::assertStringContainsString('re', $out);
    }

    // =========================================================================
    // renderGroup() — g element
    // =========================================================================

    #[Test]
    public function renderGroupWithEmptyChildrenReturnsEmpty(): void
    {
        // Arrange
        $svg = SvgDocument::fromString('<svg xmlns="http://www.w3.org/2000/svg"><g/></svg>');

        // Act
        $out = $this->renderer->render($svg);

        // Assert
        self::assertSame('', $out);
    }

    #[Test]
    public function renderGroupWithChildrenWrapsInQQ(): void
    {
        // Arrange
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<g><rect x="0" y="0" width="10" height="10"/></g>'
            . '</svg>',
        );

        // Act
        $out = $this->renderer->render($svg);

        // Assert
        self::assertStringContainsString("q\n", $out);
        self::assertStringContainsString("Q\n", $out);
    }

    #[Test]
    public function renderGroupWithTransformEmitsCm(): void
    {
        // Arrange
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<g transform="translate(10,20)">'
            . '<rect x="0" y="0" width="5" height="5"/>'
            . '</g>'
            . '</svg>',
        );

        // Act
        $out = $this->renderer->render($svg);

        // Assert
        self::assertStringContainsString('cm', $out);
    }

    // =========================================================================
    // renderRect()
    // =========================================================================

    #[Test]
    public function renderRectProducesReOperator(): void
    {
        // Arrange
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="10" y="20" width="100" height="50"/>'
            . '</svg>',
        );

        // Act
        $out = $this->renderer->render($svg);

        // Assert — plain rect uses "re" operator
        self::assertStringContainsString(' re', $out);
    }

    #[Test]
    public function renderRectWithZeroWidthReturnsEmpty(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="0" height="10"/>'
            . '</svg>',
        );

        self::assertSame('', $this->renderer->render($svg));
    }

    #[Test]
    public function renderRectWithZeroHeightReturnsEmpty(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="0"/>'
            . '</svg>',
        );

        self::assertSame('', $this->renderer->render($svg));
    }

    #[Test]
    public function renderRoundedRectWithRxAndRy(): void
    {
        // Arrange — both rx and ry present → rounded rect path
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="100" height="50" rx="10" ry="5"/>'
            . '</svg>',
        );

        // Act
        $out = $this->renderer->render($svg);

        // Assert — rounded rect uses cubic bezier, not "re"
        self::assertStringContainsString(' c', $out);
        self::assertStringNotContainsString(' re', $out);
    }

    #[Test]
    public function renderRoundedRectWithRxOnly(): void
    {
        // Arrange — rx present but ry missing (ry defaults to -1) → ry = rx
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="100" height="50" rx="10"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        self::assertStringContainsString(' c', $out);
    }

    #[Test]
    public function renderRoundedRectWithRyOnly(): void
    {
        // Arrange — ry present but rx missing (rx defaults to -1) → rx = ry
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="100" height="50" ry="10"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        self::assertStringContainsString(' c', $out);
    }

    // =========================================================================
    // renderCircle()
    // =========================================================================

    #[Test]
    public function renderCircleProducesEllipsePath(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<circle cx="50" cy="50" r="25"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        // Ellipse path uses "m" + "c" operators
        self::assertStringContainsString(' c', $out);
        self::assertStringContainsString("h\n", $out);
    }

    #[Test]
    public function renderCircleWithZeroRadiusReturnsEmpty(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<circle cx="50" cy="50" r="0"/>'
            . '</svg>',
        );

        self::assertSame('', $this->renderer->render($svg));
    }

    #[Test]
    public function renderCircleWithNegativeRadiusReturnsEmpty(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<circle cx="50" cy="50" r="-5"/>'
            . '</svg>',
        );

        self::assertSame('', $this->renderer->render($svg));
    }

    // =========================================================================
    // renderEllipse()
    // =========================================================================

    #[Test]
    public function renderEllipseProducesPath(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<ellipse cx="50" cy="50" rx="30" ry="20"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        self::assertStringContainsString(' c', $out);
    }

    #[Test]
    public function renderEllipseWithZeroRxReturnsEmpty(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<ellipse cx="50" cy="50" rx="0" ry="20"/>'
            . '</svg>',
        );

        self::assertSame('', $this->renderer->render($svg));
    }

    #[Test]
    public function renderEllipseWithZeroRyReturnsEmpty(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<ellipse cx="50" cy="50" rx="20" ry="0"/>'
            . '</svg>',
        );

        self::assertSame('', $this->renderer->render($svg));
    }

    // =========================================================================
    // renderLine()
    // =========================================================================

    #[Test]
    public function renderLineProducesMoveThenLine(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<line x1="0" y1="0" x2="100" y2="100"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        self::assertStringContainsString(' m', $out);
        self::assertStringContainsString(' l', $out);
    }

    // =========================================================================
    // renderPoly() — polyline / polygon
    // =========================================================================

    #[Test]
    public function renderPolylineWithFewerThanTwoPointsReturnsEmpty(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<polyline points="10,10"/>'
            . '</svg>',
        );

        self::assertSame('', $this->renderer->render($svg));
    }

    #[Test]
    public function renderPolylineIsOpen(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<polyline points="0,0 10,10 20,0"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        self::assertStringContainsString(' l', $out);
        // polyline should NOT contain "h\n" (not closed)
        self::assertStringNotContainsString("h\n", $out);
    }

    #[Test]
    public function renderPolygonIsClosed(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<polygon points="0,0 10,10 20,0"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        self::assertStringContainsString("h\n", $out);
    }

    // =========================================================================
    // renderPath()
    // =========================================================================

    #[Test]
    public function renderPathWithEmptyDReturnsEmpty(): void
    {
        $svg = SvgDocument::fromString('<svg xmlns="http://www.w3.org/2000/svg">' . '<path d=""/>' . '</svg>');

        self::assertSame('', $this->renderer->render($svg));
    }

    #[Test]
    public function renderPathWithValidD(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<path d="M 0 0 L 10 10 Z"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        self::assertStringContainsString(' m', $out);
        self::assertStringContainsString(' l', $out);
    }

    // =========================================================================
    // wrapShape() — transform on element, stroke-width, colors
    // =========================================================================

    #[Test]
    public function wrapShapeEmitsTransformWhenPresent(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" transform="translate(5,5)"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        self::assertStringContainsString('cm', $out);
    }

    #[Test]
    public function wrapShapeEmitsStrokeWidthWhenNotOne(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" stroke-width="2"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        self::assertStringContainsString('2 w', $out);
    }

    #[Test]
    public function wrapShapeDoesNotEmitStrokeWidthWhenOne(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" stroke-width="1"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        self::assertStringNotContainsString(" w\n", $out);
    }

    #[Test]
    public function wrapShapeWithFillColorEmitsRg(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" fill="red"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        self::assertStringContainsString(' rg', $out);
    }

    #[Test]
    public function wrapShapeWithFillNoneNoRg(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" fill="none"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        self::assertStringNotContainsString(' rg', $out);
    }

    #[Test]
    public function wrapShapeWithUnparseableFillTreatedAsNone(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" fill="notacolor"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        // Unparseable fill → treated as none → no "rg"
        self::assertStringNotContainsString(' rg', $out);
    }

    #[Test]
    public function wrapShapeWithStrokeColorEmitsRG(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" stroke="blue"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        self::assertStringContainsString(' RG', $out);
    }

    #[Test]
    public function wrapShapeWithUnparseableStrokeTreatedAsNone(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" stroke="notacolor"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        self::assertStringNotContainsString(' RG', $out);
    }

    // =========================================================================
    // paintOp() — 8 combinations
    // =========================================================================

    #[Test]
    public function paintOpFillAndStrokeNonzeroEmitsB(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" fill="red" stroke="blue"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        self::assertStringContainsString("B\n", $out);
    }

    #[Test]
    public function paintOpFillAndStrokeEvenOddEmitsBStar(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" fill="red" stroke="blue" fill-rule="evenodd"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        self::assertStringContainsString("B*\n", $out);
    }

    #[Test]
    public function paintOpFillOnlyNonzeroEmitsF(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" fill="red" stroke="none"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        self::assertStringContainsString("f\n", $out);
    }

    #[Test]
    public function paintOpFillOnlyEvenOddEmitsFStar(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" fill="red" stroke="none" fill-rule="evenodd"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        self::assertStringContainsString("f*\n", $out);
    }

    #[Test]
    public function paintOpStrokeOnlyEmitsS(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" fill="none" stroke="blue"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        self::assertStringContainsString("S\n", $out);
    }

    #[Test]
    public function paintOpNeitherFillNorStrokeEmitsN(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" fill="none" stroke="none"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        self::assertStringContainsString("n\n", $out);
    }

    // =========================================================================
    // transformToCm() / svgTransformMatrix()
    // =========================================================================

    #[Test]
    public function transformTranslateEmitsCm(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" transform="translate(30,40)"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        // translate(30,40) → matrix [1 0 0 1 30 40] → "1 0 0 1 30 40 cm"
        self::assertStringContainsString('1 0 0 1 30 40 cm', $out);
    }

    #[Test]
    public function transformTranslateOneArgUsesZeroY(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" transform="translate(30)"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        self::assertStringContainsString('1 0 0 1 30 0 cm', $out);
    }

    #[Test]
    public function transformScaleEmitsCm(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" transform="scale(2,3)"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        self::assertStringContainsString('2 0 0 3 0 0 cm', $out);
    }

    #[Test]
    public function transformScaleOneArgUsesUniformScale(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" transform="scale(2)"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        self::assertStringContainsString('2 0 0 2 0 0 cm', $out);
    }

    #[Test]
    public function transformRotateWithoutPivot(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" transform="rotate(0)"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        // rotate(0) → [1 0 0 1 0 0]
        self::assertStringContainsString('1 0 0 1 0 0 cm', $out);
    }

    #[Test]
    public function transformRotateWithPivot(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" transform="rotate(0,50,50)"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        // cx=50,cy=50 non-zero → pivot formula
        self::assertStringContainsString('cm', $out);
    }

    #[Test]
    public function transformMatrixWith6Args(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" transform="matrix(1,0,0,1,10,20)"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        self::assertStringContainsString('1 0 0 1 10 20 cm', $out);
    }

    #[Test]
    public function transformMatrixWithWrongArgCountReturnsNull(): void
    {
        // matrix with fewer than 6 args → null → no cm
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" transform="matrix(1,0,0)"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        // matrix is ignored (wrong arg count), but rect is still rendered
        self::assertStringContainsString(' re', $out);
        self::assertStringNotContainsString('cm', $out);
    }

    #[Test]
    public function transformSkewX(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" transform="skewX(0)"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        // skewX(0) → [1 0 tan(0) 1 0 0] = [1 0 0 1 0 0]
        self::assertStringContainsString('1 0 0 1 0 0 cm', $out);
    }

    #[Test]
    public function transformSkewY(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" transform="skewY(0)"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        // skewY(0) → [1 tan(0) 0 1 0 0] = [1 0 0 1 0 0]
        self::assertStringContainsString('1 0 0 1 0 0 cm', $out);
    }

    #[Test]
    public function transformUnknownFuncProducesNoCm(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" transform="unknown(1,2,3)"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        // unknown transform → null → no cm
        self::assertStringNotContainsString('cm', $out);
    }

    // =========================================================================
    // mergeStyle() — presentation attrs, inline style, inherit
    // =========================================================================

    #[Test]
    public function mergeStyleInlineStyleOverridesPresentationAttr(): void
    {
        // Inline style="fill:blue" overrides fill="red" presentation attr
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" fill="red" style="fill:blue"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        // blue = rgb(0,0,1) → "0 0 1 rg"
        self::assertStringContainsString('0 0 1 rg', $out);
    }

    #[Test]
    public function mergeStyleInheritKeywordFallsBackToParent(): void
    {
        // Child has fill="inherit"; parent (svg root) has defaultStyle fill=black
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" fill="inherit"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        // inherit → black → "0 0 0 rg"
        self::assertStringContainsString('0 0 0 rg', $out);
    }

    #[Test]
    public function mergeStyleSkipsMalformedDeclarationWithoutColon(): void
    {
        // The "invalid" declaration has no ':' and is skipped (count !== 2);
        // the valid "fill:blue" declaration that follows is still applied.
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" fill="red" style="invalid;fill:blue"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        // blue = rgb(0,0,1) → "0 0 1 rg"
        self::assertStringContainsString('0 0 1 rg', $out);
    }

    #[Test]
    public function mergeStyleSkipsDeclarationWithEmptyPropertyName(): void
    {
        // The leading ":blue" declaration trims to an empty key and is skipped;
        // the valid "fill:green" declaration that follows is still applied.
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" fill="red" style=" :blue;fill:green"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        // green → [0, 0.502, 0] → "0 0.502 0 rg"
        self::assertStringContainsString('0 0.502 0 rg', $out);
    }

    // =========================================================================
    // parseColor() — all branches
    // =========================================================================

    #[Test]
    public function parseColorEmptyStringTreatedAsNone(): void
    {
        // No fill attr → defaults to 'black', so force none explicitly
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" fill="none" stroke=""/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        // stroke="" → not set → 'none'; fill=none; should produce "n\n"
        self::assertStringContainsString("n\n", $out);
    }

    #[Test]
    public function parseColorTransparentTreatedAsNone(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" fill="transparent" stroke="none"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        // transparent → null → fill treated as none → "n\n"
        self::assertStringContainsString("n\n", $out);
    }

    #[Test]
    public function parseColorUrlSkipped(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" fill="url(#grad)" stroke="none"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        self::assertStringNotContainsString(' rg', $out);
    }

    #[Test]
    public function parseColorCurrentColorFallsBackToBlack(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" fill="currentColor" stroke="none"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        // currentColor → [0,0,0] → "0 0 0 rg"
        self::assertStringContainsString('0 0 0 rg', $out);
    }

    #[Test]
    public function parseColorHexSixDigits(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" fill="#ff0000" stroke="none"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        self::assertStringContainsString('1 0 0 rg', $out);
    }

    #[Test]
    public function parseColorHexThreeDigits(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" fill="#f00" stroke="none"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        self::assertStringContainsString('1 0 0 rg', $out);
    }

    #[Test]
    public function parseColorRgbAbsolute(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" fill="rgb(255,0,0)" stroke="none"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        self::assertStringContainsString('1 0 0 rg', $out);
    }

    #[Test]
    public function parseColorRgbPercent(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" fill="rgb(100%,0%,0%)" stroke="none"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        self::assertStringContainsString('1 0 0 rg', $out);
    }

    #[Test]
    public function parseColorNamedColor(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" fill="blue" stroke="none"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        self::assertStringContainsString('0 0 1 rg', $out);
    }

    #[Test]
    public function parseColorUnknownReturnsNull(): void
    {
        $svg = SvgDocument::fromString(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<rect x="0" y="0" width="10" height="10" fill="unknownxyz" stroke="none"/>'
            . '</svg>',
        );

        $out = $this->renderer->render($svg);
        // unknown → null → fill treated as none → no rg
        self::assertStringNotContainsString(' rg', $out);
    }

    protected function setUp(): void
    {
        $this->renderer = new SvgRenderer();
    }
}
