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

namespace AppSeeds\Helpers;

use AppSeeds\Contracts\DeferLazyable;
use AppSeeds\Contracts\DeferMinifyable;
use AppSeeds\Contracts\DeferNormalizable;
use AppSeeds\Contracts\DeferPreloadable;
use AppSeeds\Contracts\DeferReorderable;
use AppSeeds\Elements\DocumentNode;
use AppSeeds\Elements\ElementNode;
use AppSeeds\Resolvers\DeferResolver;
use AppSeeds\Resolvers\LinkResolver;

final class DeferOptimizer
{
    /**
     * Optimize a DocumentNode.
     *
     * @param DocumentNode $doc
     * @param DeferOptions $options
     */
    public static function optimizeDocument(&$doc, &$options)
    {
        // Normalize entire document
        $doc->normalize();

        // Check if AMP symbol exists
        $isAmp = $doc->isAmpHtml();

        /** @var ElementNode $html */
        $html = $doc->root();

        /** @var ElementNode $body */
        $body = $doc->body();

        /** @var ElementNode $head */
        $head = $doc->head();

        // Set AMP options if it is an AMP page
        if ($isAmp) {
            $options->backup();
            $options->forAmp();
        }

        // Optimize performance by filtering data-ignore nodes
        $ignore  = ':not([' . DeferConstant::ATTR_IGNORE . '])';
        $skipped = null;

        // Ignore elements that match ignore_lazyload_css_selectors
        $blacklist = $options->ignore_lazyload_css_selectors;

        if ($blacklist !== []) {
            $selector = implode(',', $blacklist);

            try {
                $skipped = $body->find($selector);
                $skipped->setAttribute(DeferConstant::ATTR_NOLAZY, 'selector');
            } finally {
                // Skipped
            }
        }

        // Preload key requests
        if ($options->enable_dns_prefetch || $options->enable_preloading) {
            $html->find(
                'link[rel="preload"]' . $ignore
                . ',link[rel="preconnect"]' . $ignore
                . ',link[rel="prefetch"]' . $ignore
                . ',link[rel="dns-prefetch"]' . $ignore
            )->optimize($options);
        }

        // Lazy-load offscreen and hidden iframes
        if ($options->optimize_iframes) {
            $body->find(
                'iframe' . $ignore
                . ',frame' . $ignore
                . ',embed' . $ignore
            )->optimize($options);
        }

        // Lazy-load offscreen and hidden images and videos
        if ($options->optimize_images) {
            $body->find(
                'input[type="image"]' . $ignore
                . ',img' . $ignore
                . ',picture' . $ignore
                . ',source' . $ignore
                . ',video' . $ignore
                . ',audio' . $ignore
            )->optimize($options);
        }

        // Lazy-load CSS background images
        if ($options->optimize_background) {
            $body->find('[style*="url("]' . $ignore)->optimize($options);
        }

        // Reduce the impact of JavaScript
        if ($options->optimize_scripts) {
            $html->find('script' . $ignore)->optimize($options);
        }

        // Defer non-critical CSS requests
        if ($options->optimize_css) {
            $html->find(
                'style' . $ignore
                . ',link[rel="stylesheet"]' . $ignore
            )->optimize($options);
        }

        // Fix unsafe links to cross-origin destinations
        if ($options->optimize_anchors) {
            $body->find('a[target]' . $ignore)->optimize($options);
        }

        // Add fade-in effect
        if ($options->use_css_fadein_effects) {
            $html->addClass(DeferConstant::CLASS_DEFER_FADED);
        }

        // Add splashscreen
        if ($options->custom_splash_screen !== '') {
            $body->prependWith(sprintf(
                DeferConstant::TEMPLATE_SPLASH_ENABLE,
                $options->custom_splash_screen
            ));
        }

        // Move all meta to bottom of <head> tag
        $head->find('meta' . $ignore)->optimize($options);

        // Fix missing meta tags
        if ($options->add_missing_meta_tags) {
            $doc->addMissingMeta();
        }

        // Remove nolazy attribute from the first step
        if (!empty($skipped)) {
            $skipped->each(static function ($node) {
                if ($node->getAttribute(DeferConstant::ATTR_NOLAZY) == 'selector') {
                    $node->removeAttribute(DeferConstant::ATTR_NOLAZY);
                }
            });
            unset($skipped);
        }

        // Minify HTML output
        if ($options->minify_output_html) {
            $doc->minify();
        }

        // Restore options
        if ($isAmp) {
            $options->restore();
        }

        LinkResolver::reset();
        @gc_collect_cycles();
    }

    /**
     * Optimize an ElementNode.
     *
     * @param ElementNode  $node
     * @param DeferOptions $options
     */
    public static function optimizeElement(&$node, &$options)
    {
        $original = null;

        // Normalizes HTML node
        $node->normalize();

        // Get resolver for the node
        $resolver = DeferResolver::resolver($node, $options);
        $fallback = null;

        // Normalizes the element attributes
        if ($resolver instanceof DeferNormalizable) {
            $resolver->normalize();
        }

        // Checks attribute to ignore optimizing the element
        if ($resolver->shouldIgnore()) {
            return;
        }

        // START debug
        if ($options->debug_mode) {
            $original = $node->getOuterHtml();
        }

        // Init fallback element
        if ($resolver instanceof DeferLazyable && $options->optimize_fallback) {
            $node->removeClass(DeferConstant::CLASS_HAS_FALLBACK);
            $fallback = $resolver->resolveNoScript();
        }

        // Defer non-critical requests
        if ($resolver instanceof DeferReorderable) {
            $resolver->reposition();
        }

        // Preload resources
        // See: https://3perf.com/blog/link-rels/
        if (($node->parentNode instanceof \DOMNode) && $resolver instanceof DeferPreloadable) {
            $push = [];

            // Prefetch key requests
            if ($options->enable_dns_prefetch) {
                $push[] = $resolver->getDnsPrefetchNode();
                $push[] = $resolver->getPreconnectNode();
            }

            // Preload key requests
            if ($options->enable_preloading) {
                $push[] = $resolver->getPrefetchNode();
                $push[] = $resolver->getPreloadNode();
            }

            $push = array_filter($push);

            foreach ($push as $preload_node) {
                if (!$preload_node->isSameNode($node)) {
                    $preload_node->optimize($options);
                }
            }
        }

        // Lazy-load the element
        if (($node->parentNode instanceof \DOMNode)
            && $resolver instanceof DeferLazyable
            && $resolver->shouldLazyload()) {
            // Apply lazy-load
            $lazied = $resolver->lazyload();

            if ($lazied && !empty($fallback)) {
                $fallback->detach();
                $node->follow($fallback);
                $node->addClass(DeferConstant::CLASS_HAS_FALLBACK);
            }
        }

        // Minify HTML output
        if (($node->parentNode instanceof \DOMNode)
            && $resolver instanceof DeferMinifyable
            && $options->minify_output_html) {
            $resolver->minify();
        }

        // END debug
        if ($options->debug_mode && $node->getOuterHtml() != $original) {
            $debug_id    = $resolver->uid();
            $comment_txt = ' ' . DeferConstant::TXT_DEBUG . sprintf(' Original #%s from %s ', $debug_id, $original);
            $comment     = $node->document()->createComment($comment_txt);
            $node->setAttribute(DeferConstant::ATTR_DEBUG, $debug_id);
            $node->follow($comment);
        }

        // Cleanup
        unset($resolver);
        @gc_collect_cycles();
    }
}
