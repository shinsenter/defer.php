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

use AppSeeds\Helpers\DeferConstant;

final class TextNode extends \DOMText
{
    use CommonDomTraits;

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function normalize()
    {
        parent::normalize();
        $this->normalizeWhitespaces();
    }

    /**
     * Strip whitespaces around text.
     */
    private function normalizeWhitespaces()
    {
        $parent = $this->parentNode;
        if (!$parent instanceof \DOMNode) {
            return;
        }

        if (empty($this->nodeValue)) {
            return;
        }

        if (in_array($parent->nodeName, DeferConstant::DOM_SPACE_IN)) {
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
