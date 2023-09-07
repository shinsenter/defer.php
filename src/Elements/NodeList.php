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

/**
 * @mixin ElementNode
 */
final class NodeList extends \ArrayIterator
{
    /**
     * @param string $method
     * @param array  $parameters
     */
    public function __call($method, $parameters)
    {
        foreach ($this as $node) {
            /** @var ElementNode $node */
            $callee = [$node, $method];

            if (is_callable($callee)) {
                call_user_func_array($callee, $parameters);
            }
        }

        return $this;
    }

    /**
     * |-----------------------------------------------------------------------
     * | Helper methods
     * |-----------------------------------------------------------------------.
     */
    /**
     * Get the first node in the list.
     *
     * @return \DOMNode|null
     */
    public function first()
    {
        $this->rewind();

        if (!$this->valid()) {
            return null;
        }

        return $this->current();
    }

    /**
     * Loop the list and perform action.
     *
     * @param callable $function
     *
     * @return self
     */
    public function each($function)
    {
        foreach ($this as $index => $node) {
            $result = $function($node, $index);

            if ($result === false) {
                break;
            }
        }

        return $this;
    }
}
