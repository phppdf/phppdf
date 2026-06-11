<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\HtmlLayoutEngine;

use DOMDocument;
use DOMElement;
use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Color\Color;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Font\Type1FontMetrics;
use PhpPdf\Html\HtmlConverterConfig;
use PhpPdf\Html\HtmlFontFamily;
use PhpPdf\Html\Internal\ComputedStyle;
use PhpPdf\Html\Internal\CssParser;
use PhpPdf\Html\Internal\HtmlLayoutEngine;
use PhpPdf\Html\Internal\HtmlTableCellData;
use PhpPdf\Html\Internal\HtmlTableData;
use PhpPdf\Html\Internal\HtmlTableRowData;
use PhpPdf\Html\Internal\LayoutBlock;
use PhpPdf\Html\Internal\MeasuredBlock;
use PhpPdf\Html\Internal\StyleResolver;
use PhpPdf\Table\TableBuilder;
use PhpPdf\Table\TableCell;
use PhpPdf\Table\TableRow;
use PhpPdf\Text\TextBox;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlLayoutEngine::class)]
#[CoversMethod(HtmlLayoutEngine::class, 'collect')]
#[UsesClass(HtmlConverterConfig::class)]
#[UsesClass(HtmlFontFamily::class)]
#[UsesClass(StyleResolver::class)]
#[UsesClass(ComputedStyle::class)]
#[UsesClass(CssParser::class)]
#[UsesClass(LayoutBlock::class)]
#[UsesClass(MeasuredBlock::class)]
#[UsesClass(HtmlTableData::class)]
#[UsesClass(HtmlTableRowData::class)]
#[UsesClass(HtmlTableCellData::class)]
#[UsesClass(Type1FontMetrics::class)]
#[UsesClass(PdfDocumentBuilder::class)]
#[UsesClass(PdfPageBuilder::class)]
#[UsesClass(PdfContentStreamBuilder::class)]
#[UsesClass(TextBox::class)]
#[UsesClass(Color::class)]
#[UsesClass(TableBuilder::class)]
#[UsesClass(TableCell::class)]
#[UsesClass(TableRow::class)]
final class CollectTest extends TestCase
{
    private HtmlConverterConfig $config;
    private HtmlLayoutEngine $engine;
    private PdfDocumentBuilder $builder;

    #[Test]
    public function emptyDocumentProducesNoPages(): void
    {
        // Arrange
        $dom = $this->loadHtml('');

        // Act
        $this->engine->collect($dom);
        $this->engine->applyToBuilder($this->builder);

        // Assert
        self::assertSame(0, $this->builder->getPageCount());
    }

    #[Test]
    public function paragraphProducesOnePage(): void
    {
        // Arrange / Act
        $result = $this->collectAndApply('<p>Hello World</p>');

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function headingsAreCollected(): void
    {
        // Arrange / Act
        $result = $this->collectAndApply('<h1>H1</h1><h2>H2</h2><h3>H3</h3><h4>H4</h4><h5>H5</h5><h6>H6</h6>');

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function unorderedListProducesBlock(): void
    {
        // Arrange / Act
        $result = $this->collectAndApply('<ul><li>Apple</li><li>Banana</li></ul>');

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function orderedListProducesBlock(): void
    {
        // Arrange / Act
        $result = $this->collectAndApply('<ol><li>First</li><li>Second</li></ol>');

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function emptyListProducesNoBlock(): void
    {
        // Arrange — ul with no li children (only whitespace text nodes)
        $dom = $this->loadHtml('<ul></ul>');

        // Act
        $this->engine->collect($dom);
        $this->engine->applyToBuilder($this->builder);

        // Assert — empty list creates no block, so no pages
        self::assertSame(0, $this->builder->getPageCount());
    }

    #[Test]
    public function horizontalRuleProducesBlock(): void
    {
        // Arrange / Act
        $result = $this->collectAndApply('<p>Before</p><hr><p>After</p>');

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function lineBreakProducesBlock(): void
    {
        // Arrange / Act — br at the body level (block context)
        $result = $this->collectAndApply('<br>');

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function tableProducesBlock(): void
    {
        // Arrange / Act
        $result = $this->collectAndApply('<table><tr><td>Cell</td></tr></table>');

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function tableWithBorderAttributeSetsBorders(): void
    {
        // Arrange / Act — collect the table so the HtmlTableData is configured
        $result = $this->collectAndApply('<table border="1"><tr><td>Cell</td></tr></table>');

        // Assert — table rendered without error
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function tableWithBorderZeroDoesNotSetBorders(): void
    {
        // Arrange / Act
        $result = $this->collectAndApply('<table border="0"><tr><td>Cell</td></tr></table>');

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function skippedTagsProduceNoBlocks(): void
    {
        // Arrange
        $html = '<head><title>Title</title></head>'
            . '<script>alert(1);</script>'
            . '<style>p{color:red}</style>'
            . '<img src="x.png">';

        $dom = $this->loadHtml($html);

        // Act
        $this->engine->collect($dom);
        $this->engine->applyToBuilder($this->builder);

        // Assert — skipped tags contribute no renderable blocks
        self::assertSame(0, $this->builder->getPageCount());
    }

    #[Test]
    public function inlineBoldPropagatesFromStrongTag(): void
    {
        // Arrange / Act — strong inside paragraph
        $result = $this->collectAndApply('<p>Normal <strong>bold</strong> text</p>');

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function inlineBoldPropagatesFromBTag(): void
    {
        // Arrange / Act
        $result = $this->collectAndApply('<p><b>Bold</b></p>');

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function inlineItalicPropagatesFromEmTag(): void
    {
        // Arrange / Act
        $result = $this->collectAndApply('<p>Normal <em>italic</em></p>');

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function inlineItalicPropagatesFromITag(): void
    {
        // Arrange / Act
        $result = $this->collectAndApply('<p><i>Italic</i></p>');

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function inlineScriptAndStyleChildrenAreSkipped(): void
    {
        // Arrange / Act
        $result = $this->collectAndApply('<p>Text<script>alert(1)</script><style>p{}</style></p>');

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function brInsideParagraphAddsNewline(): void
    {
        // Arrange / Act
        $result = $this->collectAndApply('<p>Line1<br>Line2</p>');

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function whitespaceOnlyTextNodesAreSkipped(): void
    {
        // Arrange
        $dom = $this->loadHtml("   \n   ");

        // Act
        $this->engine->collect($dom);
        $this->engine->applyToBuilder($this->builder);

        // Assert
        self::assertSame(0, $this->builder->getPageCount());
    }

    #[Test]
    public function blockWithBlockLevelChildrenWalksChildren(): void
    {
        // Arrange — div containing p elements (block has block-level children)
        $result = $this->collectAndApply('<div><p>Para 1</p><p>Para 2</p></div>');

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function blockWithEmptyTextProducesNoBlock(): void
    {
        // Arrange — paragraph with only whitespace content
        $dom = $this->loadHtml('<p>   </p>');

        // Act
        $this->engine->collect($dom);
        $this->engine->applyToBuilder($this->builder);

        // Assert — empty block not added
        self::assertSame(0, $this->builder->getPageCount());
    }

    #[Test]
    public function inlineElementsUseParentStyle(): void
    {
        // Arrange — span is inline, uses parent style
        $result = $this->collectAndApply('<p><span>Text</span></p>');

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function blockquoteProducesBlock(): void
    {
        // Arrange / Act
        $result = $this->collectAndApply('<blockquote>A quote.</blockquote>');

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function tableWithTheadAndTbody(): void
    {
        // Arrange / Act
        $result = $this->collectAndApply(
            '<table>'
            . '<thead><tr><th>Name</th><th>Age</th></tr></thead>'
            . '<tbody><tr><td>Alice</td><td>30</td></tr></tbody>'
            . '</table>',
        );

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function tableWithTfoot(): void
    {
        // Arrange / Act
        $result = $this->collectAndApply(
            '<table>'
            . '<tbody><tr><td>Data</td></tr></tbody>'
            . '<tfoot><tr><td>Total</td></tr></tfoot>'
            . '</table>',
        );

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function tableWithBareTrNoSectionWrapper(): void
    {
        // Arrange / Act — tr directly inside table (no thead/tbody)
        $result = $this->collectAndApply('<table><tr><td>Cell</td></tr></table>');

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function tableEmptyRowsProducesNoBlock(): void
    {
        // Arrange — table with no td/th cells in any row
        $dom = $this->loadHtml('<table><tr></tr></table>');

        // Act
        $this->engine->collect($dom);
        $this->engine->applyToBuilder($this->builder);

        // Assert — no renderable cells → no table block
        self::assertSame(0, $this->builder->getPageCount());
    }

    #[Test]
    public function tableRowWithInlineBackgroundColor(): void
    {
        // Arrange / Act
        $result = $this->collectAndApply(
            '<table><tr style="background-color: #cccccc;"><td>Cell</td></tr></table>',
        );

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function theadRowGetsDefaultGrayBackground(): void
    {
        // Arrange / Act
        $result = $this->collectAndApply('<table><thead><tr><th>Header</th></tr></thead></table>');

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function tableCellWithColspan(): void
    {
        // Arrange / Act
        $result = $this->collectAndApply('<table><tr><td colspan="2">Spans two</td><td>Normal</td></tr></table>');

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function tableCellWithRowspan(): void
    {
        // Arrange / Act
        $result = $this->collectAndApply(
            '<table>'
            . '<tr><td rowspan="2">Tall</td><td>A</td></tr>'
            . '<tr><td>B</td></tr>'
            . '</table>',
        );

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function tableCellWithInlineColor(): void
    {
        // Arrange / Act
        $result = $this->collectAndApply('<table><tr><td style="color: red;">Red text</td></tr></table>');

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function tableCellWithInlineBackgroundColor(): void
    {
        // Arrange / Act
        $result = $this->collectAndApply('<table><tr><td style="background-color: blue;">Cell</td></tr></table>');

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function tableCellWithInlineTextAlign(): void
    {
        // Arrange / Act
        $result = $this->collectAndApply(
            '<table><tr>'
            . '<td style="text-align: center;">Center</td>'
            . '<td style="text-align: right;">Right</td>'
            . '<td style="text-align: justify;">Justify</td>'
            . '</tr></table>',
        );

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function tableCellWithInlineFontWeight(): void
    {
        // Arrange / Act
        $result = $this->collectAndApply('<table><tr><td style="font-weight: bold;">Bold cell</td></tr></table>');

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function tableCellWithInlineFontStyle(): void
    {
        // Arrange / Act
        $result = $this->collectAndApply(
            '<table><tr><td style="font-style: italic;">Italic cell</td></tr></table>',
        );

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function inlineSpanWithFontFamilyUpdatesStyle(): void
    {
        // Arrange / Act — span with a known font-family inside a paragraph
        $result = $this->collectAndApply('<p>Text <span style="font-family: courier;">mono</span> more</p>');

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function inlineSpanWithUnknownFontFamilyIsIgnored(): void
    {
        // Arrange / Act — font-family that is not registered; no crash
        $result = $this->collectAndApply('<p>Text <span style="font-family: unknownfont;">text</span></p>');

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function listItemNonElementChildrenAreSkipped(): void
    {
        // Arrange — non-li element inside ul (e.g. a div) is skipped
        $result = $this->collectAndApply('<ul><li>Item</li><div>Not an item</div></ul>');

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function collectResetsPreviousBlocks(): void
    {
        // Arrange — first collect adds blocks
        $this->engine->collect($this->loadHtml('<p>First</p>'));
        $this->engine->applyToBuilder($this->builder);
        $pageCountAfterFirst = $this->builder->getPageCount();

        // Act — second collect on empty DOM resets blocks
        $emptyBuilder = new PdfDocumentBuilder();
        $this->engine->collect($this->loadHtml(''));
        $this->engine->applyToBuilder($emptyBuilder);

        // Assert — first collect produced pages, second collect produced none
        self::assertGreaterThan(0, $pageCountAfterFirst);
        self::assertSame(0, $emptyBuilder->getPageCount());
    }

    #[Test]
    public function bareTextDirectlyInBodyProducesTextBlock(): void
    {
        // Arrange — text node as direct body child triggers walkNode DOMText branch
        // (and normaliseWhitespace)
        $result = $this->collectAndApply('Bare text in body');

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function whitespaceOnlyBareTextInBodyProducesNoBlock(): void
    {
        // Arrange — whitespace-only DOMText: normaliseWhitespace returns '' → no block
        $dom = $this->loadHtml('   ');
        $this->engine->collect($dom);
        $this->engine->applyToBuilder($this->builder);

        // Assert
        self::assertSame(0, $this->builder->getPageCount());
    }

    #[Test]
    public function inlineElementDirectlyInBodyIsWalked(): void
    {
        // Arrange — an inline <a> element directly in body exercises the
        // "inline/unknown → walkChildren with parent style" path (line 179)
        $result = $this->collectAndApply('<a href="#">Link text</a>');

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function nonTdThElementInsideTrIsSkipped(): void
    {
        // Arrange — a <div> inside <tr> is not td/th → skipped (line 300)
        $result = $this->collectAndApply('<table><tr><div>Not a cell</div><td>Real cell</td></tr></table>');

        // Assert — table still rendered with the real cell
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function tableCellTextAlignLeftUsesDefaultArm(): void
    {
        // Arrange — text-align: left hits the match default arm (line 344)
        $result = $this->collectAndApply('<table><tr><td style="text-align: left;">Left</td></tr></table>');

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function commentNodeInsideTableBodyIsSkipped(): void
    {
        // Arrange — DOMComment inside <table> triggers non-DOMElement guard (L223)
        $dom = $this->loadHtml('<table><tr><td>Cell</td></tr></table>');
        $table = $dom->getElementsByTagName('table')->item(0);
        self::assertInstanceOf(DOMElement::class, $table);
        $table->insertBefore($dom->createComment('comment'), $table->firstChild);

        // Act
        $this->engine->collect($dom);
        $this->engine->applyToBuilder($this->builder);

        // Assert
        self::assertSame(1, $this->builder->getPageCount());
    }

    #[Test]
    public function commentNodeInsideTheadIsSkipped(): void
    {
        // Arrange — DOMComment inside <thead> triggers non-DOMElement guard (L233)
        $dom = $this->loadHtml('<table><thead><tr><th>H</th></tr></thead></table>');
        $thead = $dom->getElementsByTagName('thead')->item(0);
        self::assertInstanceOf(DOMElement::class, $thead);
        $thead->insertBefore($dom->createComment('comment'), $thead->firstChild);

        // Act
        $this->engine->collect($dom);
        $this->engine->applyToBuilder($this->builder);

        // Assert
        self::assertSame(1, $this->builder->getPageCount());
    }

    #[Test]
    public function commentNodeInsideTrIsSkipped(): void
    {
        // Arrange — DOMComment inside <tr> triggers non-DOMElement guard (L295)
        $dom = $this->loadHtml('<table><tr><td>Cell</td></tr></table>');
        $tr = $dom->getElementsByTagName('tr')->item(0);
        self::assertInstanceOf(DOMElement::class, $tr);
        $tr->insertBefore($dom->createComment('comment'), $tr->firstChild);

        // Act
        $this->engine->collect($dom);
        $this->engine->applyToBuilder($this->builder);

        // Assert
        self::assertSame(1, $this->builder->getPageCount());
    }

    #[Test]
    public function commentNodeInsideParagraphIsSkipped(): void
    {
        // Arrange — DOMComment inside inline content triggers gatherText guard (L398)
        $dom = $this->loadHtml('<p>Text</p>');
        $p = $dom->getElementsByTagName('p')->item(0);
        self::assertInstanceOf(DOMElement::class, $p);
        $p->insertBefore($dom->createComment('comment'), $p->firstChild);

        // Act
        $this->engine->collect($dom);
        $this->engine->applyToBuilder($this->builder);

        // Assert
        self::assertSame(1, $this->builder->getPageCount());
    }

    #[Test]
    public function tableRowWithFewerCellsTriggersBreak(): void
    {
        // Arrange — row 2 has 1 cell but column count is 2; triggers break at L704
        $result = $this->collectAndApply('<table><tr><td>A</td><td>B</td></tr><tr><td>C</td></tr></table>');

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    #[Test]
    public function tallRowspanCellTriggersPhaseBAdjustment(): void
    {
        // Arrange — a rowspan=2 cell with tall content forces Phase B height adjustment (L769)
        $tallContent = str_repeat('Word ', 40);
        $result = $this->collectAndApply(
            "<table><tr><td rowspan=\"2\">{$tallContent}</td><td>Short</td></tr>"
            . '<tr><td>Short</td></tr></table>',
        );

        // Assert
        self::assertSame(1, $result->getPageCount());
    }

    protected function setUp(): void
    {
        $this->config = new HtmlConverterConfig();
        $resolver = new StyleResolver([], $this->config);
        $this->engine = new HtmlLayoutEngine($this->config, $resolver);
        $this->builder = new PdfDocumentBuilder();
    }

    private function loadHtml(string $html): DOMDocument
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();

        return $dom;
    }

    private function collectAndApply(string $html): PdfDocumentBuilder
    {
        $this->engine->collect($this->loadHtml($html));

        return $this->engine->applyToBuilder($this->builder);
    }
}
