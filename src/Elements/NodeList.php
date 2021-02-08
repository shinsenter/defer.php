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

use ArrayIterator;

class NodeList extends ArrayIterator
{
    public function __construct($node_list = [], $flags = 0)
    {
        $nodes = [];

        foreach ($node_list as $node) {
            $nodes[] = $node;
        }

        return parent::__construct($nodes, $flags);
    }

    public function __call($method, $parameters)
    {
        foreach ($this as $node) {
            if (method_exists($node, $method)) {
                $node->{$method}(...$parameters);
            }
        }

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Helper methods
    |--------------------------------------------------------------------------
     */

    /**
     * Get the first node in the list
     *
     * @return DOMNode
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
     * Loop the list and perform action
     *
     * @return self
     */
    public function each(callable $function)
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
