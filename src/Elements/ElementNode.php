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

use AppSeeds\Contracts\DeferOptimizable;
use AppSeeds\Helpers\DeferOptimizer;
use AppSeeds\Helpers\DeferOptions;

final class ElementNode extends \DOMElement implements DeferOptimizable
{
    use CommonDomTraits;

    /**
     * Flag to mark that the element should not be optimized.
     *
     * @var bool
     */
    private $optimized = false;

    /**
     * {@inheritdoc}
     *
     * @param DeferOptions $options
     */
    public function optimize($options)
    {
        if (!$this->optimized) {
            // Call DeferOptimizer
            DeferOptimizer::optimizeElement($this, $options);

            // Update optimized flag
            $this->optimized = true;
        }

        return $this;
    }

    /**
     * Set attribute value, if empty then remove the attribute.
     *
     * @param string $attr
     * @param string $value
     * @param bool   $delete
     *
     * @return self
     */
    #[\ReturnTypeWillChange]
    public function setAttrOrRemove($attr, $value = '', $delete = true)
    {
        if (!empty($value)) {
            $this->setAttribute($attr, $value);
        } elseif ($delete) {
            $this->removeAttribute($attr);
        }

        return $this;
    }
}
