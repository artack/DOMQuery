<?php

namespace Artack\DOMQuery\Tests;

use Artack\DOMQuery\DOMQuery;

class DOMQueryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateDOMQueryWithNullContent()
    {
        $this->assertCount(0, DOMQuery::create());
    }

    public function testCreateDOMQueryWithDOMDocument()
    {
        $this->assertCount(1, DOMQuery::create($this->createDOMDocument()));
    }

    public function testCreateDOMQueryWithDOMElement()
    {
        $this->assertCount(1, DOMQuery::create($this->createDOMElement()));
    }

    public function testCreateDOMQueryWithDOMNodeList()
    {
        $this->assertCount(3, DOMQuery::create($this->createDOMNodeList()));
    }

    public function testCreateDOMQueryWithSingleHtmlElement()
    {
        $this->assertCount(1, DOMQuery::create('<span></span>'));
    }

    public function testCreateDOMQueryWithMultipleHtmlElement()
    {
        $this->assertCount(3, DOMQuery::create('<span></span><span></span><span></span>'));
    }

    public function testCreateDOMQueryWithMultipleHtmlElementComplex()
    {
        $this->assertCount(2, DOMQuery::create('<span><i></i></span><span></span>'));
    }

    public function testCreateDOMQueryWithOnlyText()
    {
        $this->assertCount(1, DOMQuery::create('Text'));
    }

    public function testCreateDOMQueryWithOnlyBody()
    {
        $this->assertCount(1, DOMQuery::create('<body></body>'));
    }

    public function testCreateDOMQueryWithComplexBody()
    {
        $this->assertCount(1, DOMQuery::create('<html><body><span></span></body></html>'));
    }

    public function testGetHtmlWithNullContent()
    {
        $this->assertEmpty(DOMQuery::create()->getHtml());
    }

    public function testGetHtmlWithDOMDocument()
    {
        $this->assertEquals('<!DOCTYPE html>', DOMQuery::create($this->createDOMDocument())->getHtml());
    }

    public function testGetHtmlWithDOMElement()
    {
        $this->assertEquals(
            '<span></span>',
            DOMQuery::create($this->createDOMElement())->getHtml()
        );
    }

    public function testGetHtmlWithDOMNodeList()
    {
        $this->assertEquals(
            '<span></span>',
            DOMQuery::create($this->createDOMNodeList())->getHtml()
        );
    }

    public function testToStringWithDOMNodeList()
    {
        $this->assertEquals(
            '<span></span><span></span><span></span>',
            (string)DOMQuery::create($this->createDOMNodeList())
        );
    }

    public function testGetHtmlWithSingleHtmlElement()
    {
        $this->assertEquals(
            '<span></span>',
            DOMQuery::create('<span></span>')->getHtml()
        );
    }

    public function testToStringWithSingleHtmlElement()
    {
        $this->assertEquals(
            '<span></span>',
            (string)DOMQuery::create('<span></span>')
        );
    }

    public function testGetHtmlWithMultipleHtmlElement()
    {
        $this->assertEquals(
            '<span></span>',
            DOMQuery::create('<span></span><span></span><span></span>')->getHtml()
        );
    }

    public function testGetHtmlWithMultipleHtmlElementComplex()
    {
        $this->assertEquals(
            '<span><i></i></span>',
            DOMQuery::create('<span><i></i></span><span></span>')->getHtml()
        );
    }

    public function testToStringWithMultipleHtmlElement()
    {
        $this->assertEquals(
            '<span></span><span></span><span></span>',
            (string)DOMQuery::create('<span></span><span></span><span></span>')
        );
    }

    public function testToStringWithMultipleHtmlElementComplex()
    {
        $this->assertEquals(
            '<span><i></i></span><span></span>',
            (string)DOMQuery::create('<span><i></i></span><span></span>')
        );
    }

    public function testGetHtmlWithTextAndHtmlElement()
    {
        $this->assertEquals(
            'Text',
            DOMQuery::create('Text<span></span>')->getHtml()
        );
    }

    public function testToStringWithTextAndHtmlElement()
    {
        $this->assertEquals(
            'Text<span></span>',
            (string) DOMQuery::create('Text<span></span>')
        );
    }

    public function testGetHtmlNodeWithBody()
    {
        $this->assertEquals(
            '<body></body>',
            DOMQuery::create('<body></body>')->getHtml()
        );
    }

    public function testGetHtmlNodeWithBodyComplex()
    {
        $this->assertEquals(
            '<html><body><span></span></body></html>',
            DOMQuery::create('<html><body><span></span></body></html>')->getHtml()
        );
    }

    public function testGetFirst()
    {
        $this->assertEquals('<span class="first"></span>',
            (string)
            DOMQuery::create('<span class="first"></span><span class="second"></span><span class="last"></span>')
                ->getFirst());
    }

    public function testGetFirstEmptyNode()
    {
        $this->assertEquals('', (string) DOMQuery::create()->getFirst());
    }

    public function testGetLast()
    {
        $this->assertEquals('<span class="last"></span>',
            (string)
            DOMQuery::create('<span class="first"></span><span class="second"></span><span class="last"></span>')
                ->getLast());
    }

    public function testGetLastEmptyNode()
    {
        $this->assertEquals('', (string) DOMQuery::create()->getLast());
    }

    public function testGet()
    {
        $this->assertEquals('<span class="second"></span>',
            (string)
            DOMQuery::create('<span class="first"></span><span class="second"></span><span class="last"></span>')
                ->get(1));
    }

    public function testGetEmptyNode()
    {
        $this->assertEquals('', (string) DOMQuery::create()->getLast());
    }

    public function testGetSlice()
    {
        $this->assertEquals('<span class="second"></span><span class="last"></span>',
            (string)
            DOMQuery::create('<span class="first"></span><span class="second"></span><span class="last"></span>')
                ->getSlice(1, 2));
    }

    public function testGetSliceEmptyNode()
    {
        $this->assertEquals('', (string) DOMQuery::create()->getSlice(1));
    }

    public function testFindSimple()
    {
        $this->assertCount(2, DOMQuery::create('<span><i></i><i></i></span>')->find('i'));
    }

    public function testFindComplex()
    {
        $this->assertCount(3, DOMQuery::create('<span><i></i><i></i></span><span><i></i></span>')->find('i'));
    }

    public function testFindComplex2()
    {
        $this->assertCount(1, DOMQuery::create('<span><span></span></span><span><i></i></span>')->find('span'));
    }

    public function testFindWithNoMatch()
    {
        $this->assertCount(0, DOMQuery::create('<span><span></span></span><span></span>')->find('i'));
    }

    public function testGetDocument()
    {
        $this->assertCount(1, DOMQuery::create('<span></span>')->getDocument());
    }

    public function testGetDocumentWithTheDocument()
    {
        $this->assertCount(0, DOMQuery::create('<span></span>')->getDocument()->getDocument());
    }

    public function testGetDocumentWithEmpty()
    {
        $this->assertCount(0, DOMQuery::create()->getDocument());
    }

    public function testGetChildren()
    {
        $this->assertCount(2, DOMQuery::create('<span><i></i><i></i></span>')->getChildren());
    }

    public function testGetChildrenWithNoChildren()
    {
        $this->assertCount(0, DOMQuery::create('<span></span>')->getChildren());
    }

    public function testGetChildrenWithMultipleNode()
    {
        $this->assertCount(3, DOMQuery::create('<span><i></i><i></i></span><span><i></i></span>')->getChildren());
    }

    public function testGetParent()
    {
        $this->assertCount(1, DOMQuery::create('<span></span>')->getParent());
    }

    public function testGetParentWithMultipleNode()
    {
        $this->assertCount(1, DOMQuery::create('<span><i></i></span><span><i></i></span>')->getParent());
    }

    public function testGetParentWithDocument()
    {
        $this->assertCount(0, DOMQuery::create('<span></span>')->getDocument()->getParent());
    }

    public function testGetParentAndGetChildren()
    {
        $this->assertCount(2, DOMQuery::create('<span><i></i></span><span><i></i></span>')
            ->getChildren()->getParent());
    }

    public function testFilter()
    {
        $this->assertCount(1, DOMQuery::create('<span></span><i></i><span></span>')->filter('i'));
    }

    public function testFilterWithNoMatch()
    {
        $this->assertCount(0, DOMQuery::create('<span></span><span></span>')->filter('i'));
    }

    public function testFilterWithSameChildrenTag()
    {
        $this->assertCount(2, DOMQuery::create('<span><span></span></span><span><span></span></span>')
            ->filter('span'));
    }

    public function testFilterNot()
    {
        $this->assertCount(2, DOMQuery::create('<span></span><i></i><span></span>')->filterNot('i'));
    }

    public function testFilterNotWithNoMatch()
    {
        $this->assertCount(0, DOMQuery::create('<i></i><i></i><i></i>')->filterNot('i'));
    }

    public function testEach()
    {
        $content = '';
        DOMQuery::create('<span></span><i></i><span></span>')->each(function(DOMQuery $node) use(&$content) {
            $content .= $node->getName();
        });

        $this->assertEquals('spanispan', $content);
    }

    public function testIs()
    {
        $this->assertTrue(DOMQuery::create('<span></span>')->is('span'));
    }

    public function testIsNot()
    {
        $this->assertFalse(DOMQuery::create('<span></span>')->isNot('span'));
    }

    public function testGetName()
    {
        $this->assertEquals('span', DOMQuery::create('<span></span>')->getName());
    }

    public function testGetInnerHtml()
    {
        $this->assertEquals('<i></i>', DOMQuery::create('<span><i></i></span>')->getInnerHtml());

    }
    public function testGetInnerHtmlWithNoContent()
    {
        $this->assertEquals('', DOMQuery::create('<body></body>')->getInnerHtml());
    }

    public function testGetInnerHtmlWithTextContent()
    {
        $this->assertEquals('test', DOMQuery::create('<body>test</body>')->getInnerHtml());
    }

    public function testRemove()
    {
        $q = DOMQuery::create('<div><span></span>/div>');
        $q->getChildren()->remove();

        $this->assertEquals('<div></div>', $q->getHtml());
    }

    public function testRemoveWithMultipleNode()
    {
        $q = DOMQuery::create('<div><span></span><span></span>/div>');
        $q->getChildren()->remove();

        $this->assertEquals('<div></div>', $q->getHtml());
    }

    public function testReplace()
    {
        $this->assertEquals('<div></div>', DOMQuery::create('<span></span>')->replace('<div>')->getHtml());
    }

    public function testReplaceWithMultipleNode()
    {
        $this->assertEquals('<body>
<div></div>
<span></span><div></div>
<span></span>
</body>',
            DOMQuery::create('<body><span><i></i></span><span></span></body>')
                ->getChildren()->replace('<div></div><span></span>')->getParent()->getHtml());
    }

    public function testReplaceMultipleNode()
    {
        $this->assertEquals('<body>
<div></div>
<div></div>
</body>',
            DOMQuery::create('<body><span><i></i></span><span></span></body>')->getChildren()->replace('<div>')
                ->getParent()->getHtml());
    }

    public function testReplaceInner()
    {
        $this->assertEquals('<div><div></div></div>', DOMQuery::create('<div>><span></span></div>')
            ->replaceInner('<div>')->getHtml());
    }

    public function testReplaceInnerMultipleNode()
    {
        $this->assertEquals('<body><div></div></body>',
            DOMQuery::create('<body><span><i></i></span><span></span></body>')->replaceInner('<div>')->getHtml());
    }

    public function testReplaceInnerWithMultipleNode()
    {
        $this->assertEquals('<body>
<div></div>
<span></span>
</body>',
            DOMQuery::create('<body><span><i></i></span><span></span></body>')
                ->replaceInner('<div></div><span></span>')->getHtml());
    }

    public function testAppend()
    {
        $this->assertEquals('<ul>
<li>start</li>
<li>append1</li>
<li>append2</li>
</ul>',
            DOMQuery::create('<ul><li>start</li></ul>')->append('<li>append1</li><li>append2</li>')->getHtml());

    }

    public function testAppendWithNoChildren()
    {
        $this->assertEquals('<ul>
<li>append1</li>
<li>append2</li>
</ul>',
            DOMQuery::create('<ul></ul>')->append('<li>append1</li><li>append2</li>')->getHtml());
    }

    public function testPrepend()
    {
        $this->assertEquals('<ul>
<li>prepend1</li>
<li>prepend2</li>
<li>start</li>
</ul>',
            DOMQuery::create('<ul><li>start</li></ul>')->prepend('<li>prepend1</li><li>prepend2</li>')->getHtml());
    }

    public function testPrependWithNoChildren()
    {
        $this->assertEquals('<ul>
<li>prepend1</li>
<li>prepend2</li>
</ul>',
            DOMQuery::create('<ul></ul>')->prepend('<li>prepend1</li><li>prepend2</li>')->getHtml());
    }

    public function testSetAttribute()
    {
        $this->assertEquals('<img height="12">', DOMQuery::create('<img>')->setAttribute('height', 12)->getHtml());
    }

    public function testSetAttributeAsChange()
    {
        $this->assertEquals('<img height="12">', DOMQuery::create('<img height="34">')
            ->setAttribute('height', 12)->getHtml());
    }

    public function testSetAttributeWithMultipleNodes()
    {
        $this->assertEquals('<img height="12"><img height="12">',
            (string)DOMQuery::create('<img><img>')->setAttribute('height', 12));
    }

    public function testGetAttribute()
    {
        $this->assertEquals('12', DOMQuery::create('<img height="12">')->getAttribute('height'));
    }

    public function testGetAttributeEmptyNode()
    {
        $this->assertEquals(null, DOMQuery::create()->getAttribute('height'));
    }

    public function testGetAttributes()
    {
        $this->assertEquals(array('width' => 15, 'height' => 12), DOMQuery::create('<img width="15" height="12">')->getAttributes());
    }

    public function testGetAttributesEmptyNode()
    {
        $this->assertEquals(array(), DOMQuery::create()->getAttributes());
    }

    public function testGetAttributesDocumentNode()
    {
        $this->assertEquals(array(), DOMQuery::create($this->createDOMDocument())->getAttributes());
    }

    public function testGetAttributeWithNoValue()
    {
        $this->assertEquals(null, DOMQuery::create('<img>')->getAttribute('height'));
    }

    public function testHasAttribute()
    {
        $this->assertTrue(DOMQuery::create('<img height="12">')->hasAttribute('height'));
    }

    public function testHasAttributeOnNoValue()
    {
        $this->assertFalse(DOMQuery::create('<img>')->hasAttribute('height'));
    }

    public function testRemoveAttribute()
    {
        $this->assertEquals('<img>', DOMQuery::create('<img height="12">')->removeAttribute('height')->getHtml());
    }

    public function testRemoveAttributeOnMultipleNodes()
    {
        $this->assertEquals('<img><img>',
            DOMQuery::create('<img height="12"><img height="12">')->removeAttribute('height'));
    }

    public function testSetStyle()
    {
        $this->assertEquals('<div style="height:12px;"></div>', DOMQuery::create('<div></div>')
            ->setStyle('height', '12px')->getHtml());
    }

    public function testSetStyleOnMulipleNodes()
    {
        $this->assertEquals('<div style="height:12px;"></div><div style="height:12px;"></div>',
            (string)DOMQuery::create('<div></div><div></div>')->setStyle('height', '12px'));
    }

    public function testSetStyleWithExistingStyle()
    {
        $this->assertEquals(
            '<div style="width:13px;height:12px;"></div>',
            DOMQuery::create('<div style="width:13px;"></div>')->setStyle('height', '12px')->getHtml()
        );
    }

    public function testSetStyleAndSetAttribute()
    {
        $this->assertEquals(
            '<div style="display:none;"></div>',
            DOMQuery::create('<div></div>')->setStyle('height', '12px')->setAttribute('style', '')
                ->setStyle('display', 'none')->getHtml()
        );
    }

    public function testAddStyleAndRemoveAttribute()
    {
        $this->assertEquals('<div style="width:15px;"></div>',
            DOMQuery::create('<div>')->setStyle('height', '12px')->removeAttribute('style')
                ->setStyle('width', '15px')->getHtml());
    }

    public function testRemoveStyle()
    {
        $this->assertEquals('<div></div>', DOMQuery::create('<div style="height:12px;"></div>')
            ->removeStyle('height')->getHtml());
    }

    public function testRemoveStyleOnMultipleNodes()
    {
        $this->assertEquals(
            '<div style="width:13px;"></div>',
            DOMQuery::create('<div style="width:13px;height:12px;"></div>')->removeStyle('height')->getHtml()
        );
    }

    public function testRemoveStyleAndSetAttribute()
    {
        $this->assertEquals(
            '<div></div>',
            DOMQuery::create('<div></div>')->setAttribute('style', 'display:none;')->removeStyle('display')->getHtml()
        );
    }

    public function testGetStyle()
    {
        $this->assertEquals('12px', DOMQuery::create('<div style="height:12px;"></div>')->getStyle('height'));
    }

    public function testStyleEmptyNode()
    {
        $this->assertEquals(null, DOMQuery::create()->getStyle('height'));
    }

    public function testStyleDocumentNode()
    {
        $this->assertEquals(null, DOMQuery::create($this->createDOMDocument())->getStyle('height'));
    }

    public function testStyles()
    {
        $this->assertEquals(array('width' => '15px', 'height' => '12px'), DOMQuery::create('<div style="width:15px;height:12px;"></div>')->getStyles());
    }

    public function testStylesEmptyNode()
    {
        $this->assertEquals(array(), DOMQuery::create()->getStyles());
    }

    public function testStylesDocumentNode()
    {
        $this->assertEquals(array(), DOMQuery::create($this->createDOMDocument())->getStyles());
    }

    public function testSetStyleWithNoValue()
    {
        $this->assertEquals(null, DOMQuery::create('<div style="width:13px;"></div>')->getStyle('height'));
    }

    public function testSetStyleWithNoStyle()
    {
        $this->assertEquals(null, DOMQuery::create()->getStyle('height'));
    }

    public function testHasStyle()
    {
        $this->assertTrue(DOMQuery::create('<div style="height:12px;"></div>')->hasStyle('height'));
    }

    public function testHasStyleWithNoValue()
    {
        $this->assertFalse(DOMQuery::create('<div style="width:13px;"></div>')->hasStyle('height'));
    }

    public function testHasStyleWithNoStyle()
    {
        $this->assertFalse(DOMQuery::create()->hasStyle('height'));
    }

    public function testAddClass()
    {
        $this->assertEquals('<div class="class1 class2"></div>', DOMQuery::create('<div class="class1">')
            ->addClass('class2')->getHtml());
    }

    public function testAddClassOnNoExistingClass()
    {

        $this->assertEquals('<div class="class2"></div>',
            DOMQuery::create('<div>')->addClass('class1')->removeAttribute('class')
                ->addClass('class2')->getHtml());
    }

    public function testAddClassWithSameClass()
    {
        $this->assertEquals('<div class="class1"></div>', DOMQuery::create('<div class="class1">')
            ->addClass('class1')->getHtml());
    }

    public function testAddClassAndSetAttribute()
    {
        $this->assertEquals('<div class="test class2"></div>',
            DOMQuery::create('<div>')->addClass('class1')->setAttribute('class', 'test')
                ->addClass('class2')->getHtml());
    }

    public function testAddClassAndRemoveAttribute()
    {
        $this->assertEquals('<div class="class2"></div>',
            DOMQuery::create('<div>')->addClass('class1')->removeAttribute('class')
                ->addClass('class2')->getHtml());
    }

    public function testRemoveClass()
    {
        $this->assertEquals('<div></div>', DOMQuery::create('<div class="class1">')
            ->removeClass('class1')->getHtml());
    }

    public function testRemoveClassOnExistingClass()
    {
        $this->assertEquals('<div class="class2"></div>',
            DOMQuery::create('<div class="class2 class1">')->removeClass('class1')->getHtml());
    }

    public function testGetClasses()
    {
        $this->assertEquals(array('class1', 'class2', 'class3'),
            DOMQuery::create('<div class="class1 class2 class3"></div>')->getClasses());
    }

    public function testGetClassesEmptyNode()
    {
        $this->assertEquals(array(),
            DOMQuery::create()->getClasses());
    }

    public function testGetClassesDocumentNode()
    {
        $this->assertEquals(array(),
            DOMQuery::create($this->createDOMDocument())->getClasses());
    }

    public function testHasClass()
    {
        $this->assertTrue(DOMQuery::create('<div class="class1 class2 class3"></div>')->hasClass('class1'));
    }

    public function testHasClassWithNoValue()
    {
        $this->assertFalse(DOMQuery::create('<div class="class2 class3"></div>')->hasClass('class1'));
    }

    public function testHasClassWithNoClass()
    {
        $this->assertFalse(DOMQuery::create()->hasClass('class1'));
    }

    private function createDOMDocument()
    {
        $DOM = new \DOMDocument('1.0', 'UTF-8');
        $DOM->loadHTML('<!DOCTYPE html>');
        return $DOM;
    }

    private function createDOMElement($name = 'span')
    {
        return $this->createDOMDocument()->createElement($name);
    }

    private function createDOMNodeList($count = 3, $name = 'span')
    {
        $DOM = $this->createDOMDocument();

        $DOM->appendChild($body = $DOM->createElement('body'));

        for($i = 0; $i < $count; $i++) {
            $body->appendChild($DOM->createElement($name));
        }

        return $body->childNodes;
    }
}