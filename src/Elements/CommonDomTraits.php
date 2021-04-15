<?php

/**
 * Defer.php aims to help you concentrate on web performance optimization.
 * (c) 2021 AppSeeds https://appseeds.net/
 *
 * PHP Version >=5.6
 *
 * @category  Web_Performance_Optimization
 * @package   AppSeeds
 * @author    Mai Nhut Tan <shin@shin.company>
 * @copyright 2021 AppSeeds
 * @license   https://code.shin.company/defer.php/blob/master/LICENSE MIT
 * @link      https://code.shin.company/defer.php
 * @see       https://code.shin.company/defer.php/blob/master/README.md
 */

namespace AppSeeds\Elements;

use DOMElement;
use DOMNode;
use DOMText;
use DOMXPath;
use Symfony\Component\CssSelector\CssSelectorConverter;

trait CommonDomTraits
{
    /**
     * @return \DOMDocument
     */
    public function document()
    {
        if ($this instanceof DocumentNode) {
            return $this;
        }

        return $this->ownerDocument;
    }

    /**
     * @param  mixed      $selector
     * @param  null|mixed $prefix
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
     * @param  mixed    $query
     * @return NodeList
     */
    public function findXPath($query)
    {
        if ($this->isRemoved()) {
            return new NodeList([]);
        }

        $xpath  = new DOMXPath($this->document());
        $result = $xpath->query($query, $this);

        if ($result === false) {
            return new NodeList([]);
        }

        return new NodeList($result);
    }

    public function isRemoved()
    {
        return !isset($this->nodeType);
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

        return $this->document()->saveHTML($this);
    }

    public function detach()
    {
        if ($this->parentNode instanceof DOMNode) {
            $this->parentNode->removeChild($this);
        }

        return $this;
    }

    /**
     * @param callable|string $class
     */
    public function addClass($class)
    {
        $this->_pushAttrValue('class', $class, true);

        return $this;
    }

    /**
     * @param callable|string $class
     */
    public function removeClass($class)
    {
        $this->_pushAttrValue('class', $class);

        return $this;
    }

    /**
     * @param  callable|string $class
     * @return bool
     */
    public function hasClass($class)
    {
        $attr = (string) $this->getAttribute('class');

        if (!empty($class) && !empty($attr)) {
            return strstr(' ' . $attr . ' ', ' ' . $class . ' ') != false;
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
     * @param \DOMNode|NodeList $input
     *
     * @return self
     */
    public function setText($input)
    {
        if (is_string($input)) {
            $input = new DOMText($input);
        }

        $this->_empty()->appendWith($input);

        return $this;
    }

    /**
     * @param \DOMNode|NodeList $input
     *
     * @return self
     */
    public function precede($input)
    {
        if ($input) {
            if (!($input instanceof NodeList)) {
                $input = new NodeList([$input]);
            }

            if ($this->parentNode instanceof DOMNode) {
                foreach ($input as $node) {
                    $this->parentNode->insertBefore($this->_safeNode($node), $this);
                }
            }
        }

        return $this;
    }

    /**
     * @param \DOMNode|NodeList $input
     *
     * @return self
     */
    public function follow($input)
    {
        if ($input) {
            if (!($input instanceof NodeList)) {
                $input = new NodeList([$input]);
            }

            if ($this->parentNode instanceof DOMNode) {
                foreach ($input as $node) {
                    $node = $this->_safeNode($node);

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
     * @param \DOMNode|NodeList $input
     *
     * @return self
     */
    public function prependWith($input)
    {
        if ($input) {
            if (!($input instanceof NodeList)) {
                $input = new NodeList([$input]);
            }

            foreach ($input as $node) {
                $this->insertBefore($this->_safeNode($node), $this->firstChild);
            }
        }

        return $this;
    }

    /**
     * @param \DOMNode|NodeList $input
     *
     * @return self
     */
    public function appendWith($input)
    {
        if ($input) {
            if (!($input instanceof NodeList)) {
                $input = new NodeList([$input]);
            }

            foreach ($input as $node) {
                $this->appendChild($this->_safeNode($node));
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
     * @param mixed  $name
     * @param mixed  $addValue
     */
    private function _pushAttrValue($name, $value, $addValue = false)
    {
        if ($this instanceof DOMElement) {
            $attr = $this->getAttribute($name);

            // Remove any existing instances of the value, or empty values.
            $values = array_filter(explode(' ', $attr), function ($_value) use ($value) {
                if (strcasecmp($_value, $value) == 0 || empty($_value)) {
                    return false;
                }

                return true;
            });

            // If required add attr value to array
            if ($addValue) {
                $values[] = $value;
            }

            // Set the attr if we either have values, or the attr already
            //  existed (we might be removing classes).
            //
            // Don't set the attr if it doesn't already exist.
            if (!empty($values) || $this->hasAttribute($name)) {
                $this->setAttribute($name, implode(' ', $values));
            }
        }
    }

    /**
     * @internal
     *
     * @param  \DOMNode|string   $input
     * @return \DOMNode
     */
    private function _safeNode($input)
    {
        if ($input instanceof DOMNode) {
            return $input;
        }

        $fragment = $this->document()->createDocumentFragment();
        $fragment->appendXML($input);

        return $fragment;
    }
}
