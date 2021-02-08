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

use AppSeeds\Contracts\DeferOptimizable;
use AppSeeds\Helpers\DeferOptimizer;
use AppSeeds\Helpers\DeferOptions;
use DOMElement;
use DOMWrap\Element;

class ElementNode extends DOMElement implements DeferOptimizable
{
    use CommonDomTraits;

    /**
     * Flag to mark that the element should not be optimized
     * @property bool $optimized
     */
    private $optimized = false;

    /**
     * {@inheritdoc}
     */
    public function optimize(DeferOptions $options)
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
     * Set attribute value, if empty then remove the attribute
     *
     * @param  string $attr
     * @param  string $value
     * @param  bool   $delete
     * @return self
     */
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
