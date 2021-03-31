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

use AppSeeds\Helpers\DeferConstant;
use DOMNode;
use DOMText;

class TextNode extends DOMText
{
    use CommonDomTraits;

    /**
     * {@inheritdoc}
     */
    public function normalize()
    {
        // Strip whitespaces
        parent::normalize();
        $this->normalizeWhitespaces();
    }

    /**
     * Strip whitespaces around text
     *
     * @return void
     */
    private function normalizeWhitespaces()
    {
        $parent = $this->parentNode;

        if (!($parent instanceof DOMNode) || in_array($parent->nodeName, DeferConstant::DOM_SPACE_IN)) {
            return;
        }

        $value = strtr($this->nodeValue, ["\r" => ' ', "\n" => ' ', "\t" => ' ']);

        while (strstr($value, '  ') !== false) {
            $value = str_replace('  ', ' ', $value);
        }

        if (!in_array($parent->nodeName, DeferConstant::DOM_SPACE_AROUND)) {
            if (!($this->previousSibling
                && in_array(
                    $this->previousSibling->nodeName,
                    DeferConstant::DOM_SPACE_AROUND
                ))) {
                $value = ltrim($value);
            }

            if (!($this->nextSibling
                && in_array(
                    $this->nextSibling->nodeName,
                    DeferConstant::DOM_SPACE_AROUND
                ))) {
                $value = rtrim($value);
            }
        }

        if (strlen($value) == 0) {
            $parent->removeChild($this);
        } else {
            $this->nodeValue = $value;
        }
    }
}
