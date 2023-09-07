<?php

/**
 * Defer.php aims to help you concentrate on web performance optimization.
 * (c) 2019-2023 SHIN Company https://shin.company
 *
 * PHP Version >=5.6
 *
 * @category  Web_Performance_Optimization
 * @package   AppSeeds
 * @author    Mai Nhut Tan <shin@shin.company>
 * @copyright 2019-2023 SHIN Company
 * @license   https://code.shin.company/defer.php/blob/master/LICENSE MIT
 * @link      https://code.shin.company/defer.php
 * @see       https://code.shin.company/defer.php/blob/master/README.md
 */

namespace AppSeeds\Elements;

use Symfony\Component\CssSelector\CssSelectorConverter;

trait CommonDomTraits
{
    /**
     * @return \DOMDocument|null
     */
    public function document()
    {
        if ($this instanceof \DOMDocument) {
            return $this;
        }

        return $this->ownerDocument;
    }

    /**
     * @param string      $selector
     * @param string|null $prefix
     *
     * @return NodeList
     */
    public function find($selector, $prefix = null)
    {
        static $converter;

        if (!isset($converter)) {
            $converter = new CssSelectorConverter();
        }

        return $this->findXPath($converter->toXPath($selector, $prefix ?: 'descendant::'));
    }

    /**
     * @param string $query
     *
     * @return NodeList
     */
    public function findXPath($query)
    {
        if ($this->isRemoved()) {
            return new NodeList([]);
        }

        $dom = $this->document();

        if ($dom === null) {
            return new NodeList([]);
        }

        /** @var DocumentNode $dom */
        $xpath   = new \DOMXPath($dom);
        $results = $xpath->query($query, $this);

        if ($results === false) {
            return new NodeList([]);
        }

        $nodes = [];
        foreach ($results as $node) {
            $nodes[] = $node;
        }

        return new NodeList($nodes);
    }

    public function isRemoved()
    {
        if ($this instanceof DocumentNode) {
            return false;
        }

        return $this->parentNode == null;
    }

    public function contents()
    {
        return new NodeList((array) $this->childNodes);
    }

    public function getHtml()
    {
        if ($this instanceof DocumentNode) {
            return $this->saveHTML();
        }

        $output = [];

        foreach ($this->contents() as $node) {
            $output[] = $node->getOuterHtml();
        }

        return implode('', $output);
    }

    public function getOuterHtml()
    {
        if ($this instanceof DocumentNode) {
            return $this->saveHTML();
        }

        $dom = $this->document();

        return $dom !== null ? $dom->saveHTML($this) : '';
    }

    public function detach()
    {
        if ($this->parentNode instanceof \DOMNode) {
            $this->parentNode->removeChild($this);
        }

        return $this;
    }

    /**
     * @param string $class
     */
    public function addClass($class)
    {
        $this->_pushAttrValue('class', $class, true);

        return $this;
    }

    /**
     * @param string $class
     */
    public function removeClass($class)
    {
        $this->_pushAttrValue('class', $class);

        return $this;
    }

    /**
     * @param string $class
     *
     * @return bool
     */
    public function hasClass($class)
    {
        if ($this instanceof ElementNode) {
            $attr = $this->getAttribute('class');

            if (!empty($class) && !empty($attr)) {
                return strstr(' ' . $attr . ' ', ' ' . $class . ' ') != false;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->textContent;
    }

    /**
     * @param string|\DOMNode|NodeList|null $input
     *
     * @return self
     */
    public function setText($input)
    {
        if (is_string($input)) {
            $input = new TextNode($input);
        }

        if ($input instanceof \DOMNode) {
            $this->_empty()->appendWith($input);
        }

        return $this;
    }

    /**
     * @param string|\DOMNode|NodeList|null $input
     *
     * @return self
     */
    public function precede($input)
    {
        if ($input) {
            if (!$input instanceof NodeList) {
                $input = new NodeList([$input]);
            }

            if ($this->parentNode instanceof \DOMNode) {
                foreach ($input as $node) {
                    $node = $this->_safeNode($node);
                    if ($node === false) {
                        continue;
                    }

                    $this->parentNode->insertBefore($node, $this);
                }
            }
        }

        return $this;
    }

    /**
     * @param string|\DOMNode|NodeList|null $input
     *
     * @return self
     */
    public function follow($input)
    {
        if ($input) {
            if (!$input instanceof NodeList) {
                $input = new NodeList([$input]);
            }

            if ($this->parentNode instanceof \DOMNode) {
                foreach ($input as $node) {
                    $node = $this->_safeNode($node);
                    if ($node === false) {
                        continue;
                    }

                    if (is_null($this->nextSibling)) {
                        $this->parentNode->appendChild($node);
                    } else {
                        $this->parentNode->insertBefore($node, $this->nextSibling);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @param string|\DOMNode|NodeList|null $input
     *
     * @return self
     */
    public function prependWith($input)
    {
        if ($input && $this->firstChild !== null) {
            if (!$input instanceof NodeList) {
                $input = new NodeList([$input]);
            }

            foreach ($input as $node) {
                $node = $this->_safeNode($node);
                if ($node === false) {
                    continue;
                }

                $this->insertBefore($node, $this->firstChild);
            }
        }

        return $this;
    }

    /**
     * @param string|\DOMNode|NodeList|null $input
     *
     * @return self
     */
    public function appendWith($input)
    {
        if ($input) {
            if (!$input instanceof NodeList) {
                $input = new NodeList([$input]);
            }

            foreach ($input as $node) {
                $node = $this->_safeNode($node);
                if ($node === false) {
                    continue;
                }

                $this->appendChild($node);
            }
        }

        return $this;
    }

    /**
     * @return self
     */
    public function _empty()
    {
        foreach ($this->contents() as $node) {
            $node->detach();
        }

        return $this;
    }

    /**
     * @internal
     *
     * @param string $value
     * @param string $name
     * @param bool   $addValue
     */
    private function _pushAttrValue($name, $value, $addValue = false)
    {
        if ($this instanceof ElementNode) {
            $attr = $this->getAttribute($name);

            // Remove any existing instances of the value, or empty values.
            $values = array_filter(explode(' ', $attr), static function ($_value) use ($value) {
                if (strcasecmp($_value, $value) == 0) {
                    return false;
                }

                return !empty($_value);
            });

            // If required add attr value to array
            if ($addValue) {
                $values[] = $value;
            }

            // Set the attr if we either have values, or the attr already
            //  existed (we might be removing classes).
            //
            // Don't set the attr if it doesn't already exist.
            if ($values !== [] || $this->hasAttribute($name)) {
                $this->setAttribute($name, implode(' ', $values));
            }
        }
    }

    /**
     * @internal
     *
     * @param \DOMNode|string $input
     *
     * @return \DOMNode|false
     */
    private function _safeNode($input)
    {
        if ($input instanceof \DOMNode) {
            return $input;
        }

        /** @var DocumentNode $dom */
        $dom      = $this->document();
        $fragment = $dom->createDocumentFragment();

        if ($fragment instanceof \DOMNode) {
            $fragment->appendXML($input);
        }

        return $fragment;
    }
}
