<?php

namespace Artack\DOMQuery;

use Symfony\Component\CssSelector\CssSelector;

/**
 * Class DOMQuery
 * @package Artack\DOMQuery
 */
class DOMQuery implements \IteratorAggregate, \Countable
{
    /**
     * @var array
     */
    private $cache = array();

    /**
     * @var \DOMNode[]
     */
    private $nodes;

    /**
     * Create DomQuery by given DOMQuery instance, DOMNodeList instance, DOMNode instance, html string, or null
     *
     * @param null|string|\DOMNodeList|\DOMNode|DOMQuery $content
     * @return self
     *
     * @throws \InvalidArgumentException if content is a invalid type
     */
    public static function create($content = null)
    {
        // null
        if(null === $content) {
            return new self();
        }

        // DOMQuery instance
        if($content instanceof DOMQuery) {
            return self::createFromSelf($content);
        }

        // DOMNode instance
        if($content instanceof \DOMNode) {
            return self::createFromNode($content);
        }

        // DOMNodeList instance
        if($content instanceof \DOMNodeList) {
            return self::createFromNodeList($content);
        }

        // html string
        if(is_string($content)) {
            return self::createFromString($content);
        }

        throw new \InvalidArgumentException(sprintf('Expecting a DOMQuery instance, DOMNodeList instance,
        DOMNode instance, html string, or null, but got "%s".',
            is_object($content) ? get_class($content) : gettype($content)));
    }

    /**
     * Create DomQuery by given DOMQuery instance
     *
     * @return self
     * @param DOMQuery $self
     * @return DOMQuery
     */
    public static function createFromSelf(DOMQuery $self)
    {
        return new self($self->nodes);
    }

    /**
     * Create DomQuery by given DOMNodeList instance
     *
     * @param \DOMNodeList $nodeList
     * @return DOMQuery
     */
    public static function createFromNodeList(\DOMNodeList $nodeList)
    {
        $nodes = array();
        foreach($nodeList as $node) {
            if($node) {
                $nodes[] = $node;
            }
        }

        return new self($nodes);
    }

    /**
     * Create DomQuery by given DOMNode instance
     *
     * @param \DOMNode $node
     * @return DOMQuery
     */
    public static function createFromNode(\DOMNode $node)
    {
        return new self(array($node));
    }

    /**
     * Create DomQuery by given string
     *
     * @param $string
     * @param null|string $baseUrl
     * @param null|string $charset
     * @return DOMQuery
     */
    public static function createFromString($string, $baseUrl = null, $charset = null)
    {
        $tag     = null;

        if(preg_match('/<(!DOCTYPE|html[ >]|head[ >]|body[ >])/siU', trim($string), $match)) {
            $tag = strtolower(trim($match[1]));
            $tag = str_replace(array('>',' '), '', $tag);

            if (preg_match('/\<meta[^\>]+charset *= *["\']?([a-zA-Z\-0-9]+)/i', $string, $matches)) {
                $charset = $matches[1];
            }

            if (preg_match('/\<base[^\>]+href *= *["\']?([^"\'>]+)["\']?/i', $string, $matches)) {
                $baseUrl = $matches[1];
            }
        } else {
            $string = '<!DOCTYPE html>
                        <html>
                            <head><meta http-equiv="content-type" content="text/html; charset='.$charset.'"></head>
                            <body>'.$string.'</body>
                        </html>';
        }

        if(null === $charset) {
            $charset = 'UTF-8';
        }

        if (null !== $charset && function_exists('mb_convert_encoding') && in_array(strtolower($charset), array_map('strtolower', mb_list_encodings()))) {
            $string = mb_convert_encoding($string, 'HTML-ENTITIES', $charset);
        }

        $current = libxml_use_internal_errors(true);
        $disableEntities = libxml_disable_entity_loader(true);

        $dom = new \DOMDocument('1.0', $charset);
        $dom->validateOnParse = true;
        @$dom->loadHTML($string);

        libxml_use_internal_errors($current);
        libxml_disable_entity_loader($disableEntities);

        $nodes = array();

        if(null === $tag) {
            foreach($dom->getElementsByTagName('body')->item(0)->childNodes as $node) {
                $nodes[] = $node;
            }
        } elseif($tag === '!doctype') {
            $nodes[] = $dom;
        } else{
            $node = $dom->getElementsByTagName($tag)->item(0);

            if($node) {
                $nodes[] = $node;
            }
        }



        $domQuery = new self($nodes);

        if(count($domQuery->nodes)) {
            $domQuery->loadDOMDocument(reset($domQuery->nodes))->formatOutput = true;
        }

        if(null !== $baseUrl){

            foreach($domQuery->find('frame, iframe, img, input, script') as $el) {
                if($el->getAttribute('src')) {
                    $el->setAttribute('src', Url::combine($baseUrl, $el->getAttribute('src')));
                }
            }
            foreach($domQuery->find('a, area, link') as $el) {
                $el->setAttribute('href', Url::combine($baseUrl, $el->getAttribute('href')));
            }
            foreach($domQuery->find('form') as $el) {
                $el->setAttribute('action', Url::combine($baseUrl, $el->getAttribute('action')));
            }
        }

        return $domQuery;
    }

    /**
     * Get the descendants of each node in the current set of matched nodes, filtered by a selector
     *
     * This method allows us to search through the descendants of these nodes in the DOM tree and
     * return a new DOMQuery instance from the matching nodes
     *
     * @param $selector string
     * @return self
     */
    public function find($selector)
    {
        $expression = CssSelector::toXPath($selector);
        $nodes      = array();

        foreach($this->nodes as $node) {
            $domX = new \DOMXPath($this->loadDOMDocument($node));

            foreach($domX->query($expression, $node) as $foundNode) {
                if($foundNode !== $node) {
                    $nodes[] = $foundNode;
                }
            }
        }

        return new self($nodes);
    }

    /**
     * Get the document as DOMQuery instance
     *
     * The document it self return a empty DOMQuery instance
     *
     * @return self
     */
    public function getDocument()
    {
        foreach($this->nodes as $node) {
            if($node->ownerDocument) {
                return new self(array($node->ownerDocument));
            } else {
                return new self(array());
            }
        }

        return new self(array());
    }

    /**
     * Get the children of each node in the set of matched nodes as a DOMQuery instance
     *
     * @return self
     */
    public function getChildren()
    {
        $nodes = array();

        foreach($this->nodes as $node) {
            if($node->childNodes) {
                foreach($node->childNodes as $childNode) {
                    $nodes[] = $childNode;
                }
            }

        }

        return new self($nodes);
    }

    /**
     * Get the first node in the set of matched nodes as a new DOMQuery instance
     *
     * @return self
     */
    public function getFirst()
    {
        return $this->getSlice(0, 1);
    }

    /**
     * Get the last node in the set of matched nodes as a new DOMQuery instance
     *
     * @return self
     */
    public function getLast()
    {
        return $this->getSlice(-1, 1);
    }

    /**
     * Reduce the set of matched nodes to the one at the specified index.
     *
     * @param $index
     * @return self
     */
    public function get($index)
    {
        return $this->getSlice($index, 1);
    }

    /**
     * Get the slice of set of matched nodes
     *
     * @param $offset
     * @param $length
     * @return self
     */
    public function getSlice($offset, $length = null)
    {
        return new self(array_slice($this->nodes, $offset, $length));
    }

    /**
     * Get the parent of each node in the current set of matched nodes
     *
     * @return self
     */
    public function getParent()
    {
        $nodes = array();

        foreach($this->nodes as $node) {
            if($node->parentNode) {
                $nodes[] = $node->parentNode;
            }
        }

        return new self($nodes);
    }

    /**
     * Reduce the set of matched nodes to those that match the selector
     *
     * @param $selector string
     * @return self
     */
    public function filter($selector)
    {
        $expression = CssSelector::toXPath($selector);
        $nodes      = array();

        foreach($this->nodes as $node) {
            $domX   = new \DOMXPath($this->loadDOMDocument($node));
            $match  = false;

            foreach($domX->query($expression, $node) as $foundNode) {
                if($foundNode === $node) {
                    $match = true;
                    break;
                }
            }

            if(true === $match) {
                $nodes[] = $node;
            }
        }

        return new self($nodes);
    }

    /**
     * Reduce the set of matched nodes to those that does not match the selector
     *
     * @param $selector string
     * @return self
     */
    public function filterNot($selector)
    {
        $expression = CssSelector::toXPath($selector);
        $nodes      = array();

        foreach($this->nodes as $node) {
            $domX = new \DOMXPath($this->loadDOMDocument($node));
            $match = false;

            foreach($domX->query($expression, $node->parentNode) as $foundNode) {
                if($foundNode === $node) {
                    $match = true;
                    break;
                }
            }

            if(false === $match) {
                $nodes[] = $node;
            }
        }

        return new self($nodes);
    }

    /**
     * Iterate over the matched nodes, executing a callback for each node as a new DOMQuery instance
     *
     * @param \Closure $callback
     * @return $this
     */
    public function each(\Closure $callback)
    {
        foreach($this->nodes as $node) {
            $callback(new self(array($node)));
        }

        return $this;
    }

    /**
     * Check the matched set of nodes against a selector and return true if at least one of these nodes matches
     * the given arguments
     *
     * @param $selector
     * @return boolean
     */
    public function is($selector)
    {
        return 1 <= $this->filter($selector)->count();
    }

    /**
     * Check the matched set of nodes against a selector and return true if at least one of these nodes does not
     * matches the given arguments
     *
     * @param $selector
     * @return boolean
     */
    public function isNot($selector)
    {
        return 1 <= $this->filterNot($selector)->count();
    }

    /**
     * Get the node name of the first node in the set of matched nodes
     *
     * @return string
     */
    public function getName()
    {
        foreach($this->nodes as $node) {
            return $node->nodeName;
        }

        return null;
    }

    /**
     * Get the HTML of the first node in the set of matched nodes
     *
     * @return string
     */
    public function getHtml()
    {
        foreach($this->nodes as $node) {
            return trim($this->loadDOMDocument($node)->saveHtml($node));
        }

        return '';
    }

    /**
     * Get the inner HTML of the first node in the set of matched nodes
     *
     * @return string
     */
    public function getInnerHtml()
    {
        foreach($this->nodes as $node) {
            $content = '';

            foreach($node->childNodes as $childNode) {
                $content .= $this->loadDOMDocument($childNode)->saveHtml($childNode);
            }

            return trim($content);
        }

        return '';
    }


    /**
     * Remove each node of the set of matched nodes
     *
     * @return $this
     */
    public function remove()
    {
        foreach($this->nodes as $node) {
            if($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
        }

        $this->nodes = array();

        return $this;
    }

    /**
     * Replace content of each node in the set of matched nodes with the provided
     * DOMQuery instance, DOMNodeList instance, DOMNode instance, html string, or null
     *
     * @param null|string|\DOMNodeList|\DOMNode|DOMQuery $content
     * @return $this
     */
    public function replace($content = null)
    {
        $insertNodes = self::createNodes($content);
        $lastNode    = array_pop($insertNodes);
        $insertNodes = array_reverse($insertNodes);

        $currentNodes = $this->nodes;
        $this->nodes  = array();

        foreach($currentNodes as $node) {
            if(null !== $lastNode) {
                $beforeNode = $this->loadDOMDocument($node)->importNode($lastNode, true);
                $node->parentNode->replaceChild($beforeNode, $node);

                $this->nodes[] = $beforeNode;

                foreach($insertNodes as $insertNode) {
                    $this->nodes[] = $insertNode = $this->loadDOMDocument($node)->importNode($insertNode, true);
                    $beforeNode->parentNode->insertBefore($insertNode, $beforeNode);
                }
            } else {
                $node->parentNode->removeChild($node);
            }
        }

        return $this;
    }

    /**
     * Replace inner content of each node in the set of matched nodes with the provided
     * DOMQuery instance, DOMNodeList instance, DOMNode instance, html string or null
     *
     * @param null| string|\DOMNodeList|\DOMNode|DOMQuery $content
     * @return $this
     */
    public function replaceInner($content = null)
    {
        foreach($this->nodes as $node) {
            while ($node->childNodes->length > 0) {
                $node->removeChild($node->childNodes->item(0));
            }
        }

        return $this->append($content);
    }

    /**
     * Insert content to the end of each node in the set of matched nodes
     *
     * @param string|\DOMNodeList|\DOMNode|DOMQuery $content
     * @return $this
     */
    public function append($content)
    {
        $appendNodes = self::createNodes($content);

        foreach($this->nodes as $node) {
            foreach($appendNodes as $appendNode) {
                $appendNode = $this->loadDOMDocument($node)->importNode($appendNode, true);
                $node->appendChild($appendNode);
            }
        }

        return $this;
    }

    /**
     * Insert content to the beginning of each node in the set of matched nodes
     *
     * @param $content
     * @return $this
     */
    public function prepend($content)
    {
        $prependNodes = self::createNodes($content);

        foreach($this->nodes as $node) {
            $beforeNode = $node->childNodes->item(0);

            foreach($prependNodes as $prependNode) {
                $node->insertBefore($this->loadDOMDocument($node)->importNode($prependNode, true), $beforeNode);
            }
        }

        return $this;
    }

    /**
     * Get the value of an attribute for the first node in the set of matched nodes
     *
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function setAttribute($name, $value)
    {
        foreach($this->nodes as $node) {
            if( ! $node instanceof \DOMElement) {
                if(null !== $attr = $node->attributes->getNamedItem($name)) {
                    $attr->value = $value;
                }

                continue;
            }

            //remove cache
            if(isset($this->cache[spl_object_hash($node)]['attribute'][$name])) {
                unset($this->cache[spl_object_hash($node)]['attribute'][$name]);
            }

            $node->setAttribute($name, $value);
        }

        return $this;
    }

    /**
     * Get all attribute for the first node in the set of matched nodes as a array
     *     *
     * @return array
     */
    public function getAttributes()
    {
        foreach($this->nodes as $node) {
            $attr = array();

            if($node->attributes) {
                foreach($node->attributes as $name => $attribute) {
                    $attr[$name] = $attribute->value;
                }
            }

            return $attr;
        }

        return array();
    }

    /**
     * Set the value an attribute for every matched nodes
     *
     * @param string $name
     * @return null|string
     */
    public function getAttribute($name)
    {
        foreach($this->nodes as $node) {
            if( ! $node instanceof \DOMElement) {
                return $node->attributes->getNamedItem($name);
            }

            return $node->getAttribute($name);
        }

        return null;
    }

    /**
     * Remove an attribute for every matched nodes
     *
     * @param string $name
     * @return $this
     */
    public function removeAttribute($name)
    {
        foreach($this->nodes as $node) {
            if( ! $node instanceof \DOMElement) {
                continue;
            }

            //remove cache
            if(isset($this->cache[spl_object_hash($node)]['attribute'][$name])) {
                unset($this->cache[spl_object_hash($node)]['attribute'][$name]);
            }

            $node->removeAttribute($name);
        }

        return $this;
    }

    /**
     * Determine whether any of the matched node has the given attribute name
     *
     * @param string $name
     * @return bool
     */
    public function hasAttribute($name)
    {
        foreach($this->nodes as $node) {
            if( ! $node instanceof \DOMElement) {
                return null !== $node->attributes->getNamedItem($name);
            }

            if(true === $node->hasAttribute($name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set the value of style property for every matched nodes
     *
     * @param string $property
     * @param string $value
     * @return $this
     */
    public function setStyle($property, $value)
    {
        foreach($this->nodes as $node) {
            $this->loadStyleAttribute($node)->set($property, $value);
        }

        return $this;
    }

    /**
     * Get all style property/value for the first node in the set of matched nodes
     *
     * @return array
     */
    public function getStyles()
    {
        foreach($this->nodes as $node) {
            return $this->loadStyleAttribute($node)->all();
        }

        return array();
    }

    /**
     * Get the value of style property for the first node in the set of matched nodes
     *
     * @param string $property
     * @return string|null
     */
    public function getStyle($property)
    {
        foreach($this->nodes as $node) {
            return $this->loadStyleAttribute($node)->get($property);
        }

        return null;
    }

    /**
     * Remove the style property for every matched nodes
     *
     * @param string $property
     * @return $this
     */
    public function removeStyle($property)
    {
        foreach($this->nodes as $node) {
            $this->loadStyleAttribute($node)->remove($property);
        }

        return $this;
    }

    /**
     * Determine whether any of the matched node has the given style property
     *
     * @param string $property
     * @return bool
     */
    public function hasStyle($property)
    {
        foreach($this->nodes as $node) {
            if(true === $this->loadStyleAttribute($node)->has($property)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove given class for every matched nodes
     *
     * @param string $class
     * @return $this
     */
    public function removeClass($class)
    {
        foreach($this->nodes as $node) {
            $this->loadClassAttribute($node)->remove($class);
        }

        return $this;
    }

    /**
     * Get all class for the first node in the set of matched nodes
     *
     * @return array
     */
    public function getClasses()
    {
        foreach($this->nodes as $node) {
            return $this->loadClassAttribute($node)->all();
        }

        return array();
    }

    /**
     * Add class for every matched nodes
     *
     * @param string $class
     * @return $this
     */
    public function addClass($class)
    {
        foreach($this->nodes as $node) {
            $this->loadClassAttribute($node)->add($class);
        }

        return $this;
    }

    /**
     * Determine whether any of the matched node has the given class
     *
     * @param string $class
     * @return bool
     */
    public function hasClass($class)
    {
        foreach($this->nodes as $node) {
            if(true === $this->loadClassAttribute($node)->has($class)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return \DOMNode[]
     */
    public function getNodes()
    {
        return $this->nodes;
    }

    /**
     * Join all matched node as a html string
     *
     * @return string
     */
    public function __toString()
    {
        $content = '';

        foreach($this as $queryNode){
            $content .= $queryNode->getHtml();
        }

        return $content;
    }


    /**
     * Constructor
     *
     * @internal
     *
     * @param \DOMNode[] $nodes
     */
    private function __construct($nodes = array())
    {
        $oid = array();
        $this->nodes = array_values(array_filter($nodes, function(\DOMNode $node) use(&$oid) {
            if( ! isset($oid[spl_object_hash($node)])) {
                return $oid[spl_object_hash($node)] = true;
            } else {
                return false;
            }
        }));
    }

    /**
     * Get the node DOM document
     *
     * @internal
     *
     * @param \DOMNode $node
     * @return \DOMDocument
     */
    private function loadDOMDocument(\DOMNode $node)
    {
        if($node instanceof \DOMDocument) {
            return $node;
        }

        return $node->ownerDocument;
    }

    /**
     * Load the style attribute instance
     *
     * @internal
     *
     * @param \DOMNode $node
     * @return StyleAttribute
     */
    private function loadStyleAttribute(\DOMNode $node)
    {
        if( ! isset($this->cache[spl_object_hash($node)]['attribute']['style'])) {
            return $this->cache[spl_object_hash($node)]['attribute']['style'] = new StyleAttribute($node);
        }

        return $this->cache[spl_object_hash($node)]['attribute']['style'];
    }

    /**
     * Load the class attribute instance
     *
     * @internal
     *
     * @param \DOMNode $node
     * @return ClassAttribute
     */
    private function loadClassAttribute(\DOMNode $node)
    {
        if( ! isset($this->cache[spl_object_hash($node)]['attribute']['class'])) {
            return $this->cache[spl_object_hash($node)]['attribute']['class'] = new ClassAttribute($node);
        }

        return $this->cache[spl_object_hash($node)]['attribute']['class'];
    }

    /**
     * Create nodes by given DOMQuery instance, DOMNodeList instance, DOMNode instance, html string, or null
     *
     * @internal
     *
     * @param null|string|\DOMNodeList|\DOMNode|DOMQuery $content
     * @return \DOMNode[]
     *
     * @throws \InvalidArgumentException if content is a invalid type
     */
    private static function createNodes($content = null)
    {
        return self::create($content)->nodes;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Retrieve an external iterator
     * @link http://php.net/manual/en/\IteratorAggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     */
    public function getIterator()
    {
        $queryNodes = array();

        foreach($this->nodes as $node) {
            $queryNodes[] = new self(array($node));
        }

        return new \ArrayIterator($queryNodes);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Count elements of an object
     * @link http://php.net/manual/en/\Countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     */
    public function count()
    {
        return count($this->nodes);
    }
}

/**
 * Class ClassAttribute
 * @package Artack\DOMQuery
 *
 * @internal
 */
class ClassAttribute
{
    /**
     * @var \DomNode
     */
    private $node;

    /**
     * @var array
     */
    private $classes;

    /**
     * Constructor
     *
     * @param \DOMNode $node
     */
    public function __construct(\DOMNode $node)
    {
        $this->node = $node;
        $this->load();
    }

    /**
     * Get all
     *
     * @return array
     */
    public function all()
    {
        return array_values($this->classes);
    }

    /**
     * Has class
     *
     * @param string $class
     * @return bool
     */
    public function has($class)
    {
        foreach(explode(" ", $class) as $item) {
            if(false === isset($this->classes[$class])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Add class
     *
     * @param string $class
     */
    public function add($class)
    {
        foreach(explode(" ", $class) as $item) {
            $this->classes[$item] = $item;
        }

        $this->store();
    }

    /**
     * Remove class
     *
     * @param string $class
     */
    public function remove($class)
    {
        foreach(explode(" ", $class) as $item) {
            unset($this->classes[$item]);
        }

        $this->store();
    }

    /**
     * load classes
     */
    private function load()
    {
        $this->classes = array();
        $content = $this->node instanceof \DOMElement ? $this->node->getAttribute('class') : '';

        foreach(explode(" ", $content) as $class) {
            if(trim($class)) {
                $this->classes[trim($class)] = trim($class);
            }
        }
    }

    /**
     * store classes
     */
    private function store()
    {
        $content = trim(implode(" ", $this->classes));

        if( $this->node instanceof \DOMElement) {
            if($content) {
                $this->node->setAttribute('class', $content);
            } else {
                $this->node->removeAttribute('class');
            }
        }
    }
}

/**
 * Class StyleAttribute
 * @package Artack\DOMQuery
 *
 * @internal
 */
class StyleAttribute
{
    /**
     * @var \DOMNode
     */
    private $node;

    /**
     * @var array
     */
    private $styles;

    /**
     * Constructor
     *
     * @param \DomNode $node
     */
    public function __construct(\DomNode $node)
    {
        $this->node = $node;
        $this->load();
    }

    /**
     * Has style
     *
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return isset($this->styles[$name]);
    }

    /**
     * Get style
     *
     * @param $name
     * @return null
     */
    public function get($name)
    {
        return $this->has($name) ? $this->styles[$name] : null;
    }

    /**
     * Get all
     *
     * @return array
     */
    public function all()
    {
        return $this->styles;
    }

    /**
     * Remove style
     *
     * @param string $name
     */
    public function remove($name)
    {
        unset($this->styles[$name]);
        $this->store();
    }

    /**
     * Set style
     *
     * @param string $name
     * @param string $value
     */
    public function set($name, $value)
    {
        $this->styles[$name] = $value;
        $this->store();
    }

    /**
     * Load style
     */
    private function load()
    {
        $this->styles = array();

        $content = $this->node instanceof \DOMElement ? $this->node->getAttribute('style') : '';

        foreach(explode(";", $content) as $style) {
            $data = explode(':', $style);

            if(count($data) === 2 && trim($data[0]) && trim($data[1])) {
                $this->styles[trim($data[0])] = trim($data[1]);
            }
        }

    }

    /**
     * Store style
     */
    private function store()
    {
        $content = '';
        foreach($this->styles as $key => $value) {
            if($key && $value) {
                $content .= trim($key).':'.$value.';';
            }
        }

        if( $this->node instanceof \DOMElement) {
            if($content) {
                $this->node->setAttribute('style', $content);
            } else {
                $this->node->removeAttribute('style');
            }
        }
    }
}

/**
 * Class URL
 * @package Artack\DOMQuery
 *
 * @internal
 */
class Url
{
    /**
     * combine base url with given url
     *
     * @param $baseUrl
     * @param $url
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function combine($baseUrl, $url)
    {
        $baseUrl = trim($baseUrl);
        $url     = trim($url);

        if (!in_array(substr($baseUrl, 0, 4), array('http', 'file'))) {
            throw new \InvalidArgumentException(sprintf('Current URI must be an absolute URL ("%s").', $baseUrl));
        }

        //email
        if (0 === strpos($url, 'mailto:')) {
            return $url;
        }

        // absolute URL?
        if (0 === strpos($url, 'http')) {
            return $url;
        }

        // empty URI
        if (!$url) {
            return $baseUrl;
        }

        // only an anchor
        if ('#' ===  $url[0]) {
            if (false !== $pos = strpos($baseUrl, '#')) {
                $baseUrl = substr($baseUrl, 0, $pos);
            }

            return $baseUrl.$url;
        }

        // only a query string
        if ('?' === $url[0]) {
            // remove the query string from the current url
            if (false !== $pos = strpos($baseUrl, '?')) {
                $baseUrl = substr($baseUrl, 0, $pos);
            }

            return $baseUrl.$url;
        }

        // absolute path
        if ('/' === $url[0]) {
            return preg_replace('#^(.*?//[^/]+)(?:\/.*)?$#', '$1', $baseUrl).$url;
        }

        // relative path
        return substr($baseUrl, 0, strrpos($baseUrl, '/') + 1).$url;
    }
}