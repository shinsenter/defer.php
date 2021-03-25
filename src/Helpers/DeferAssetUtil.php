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

namespace AppSeeds\Helpers;

class DeferAssetUtil
{
    /**
     * Get image size from URL
     *
     * @param  string      $url
     * @return array|false [width, height]
     */
    public static function getImageSizeFromUrl($url)
    {
        // TODO: implement better method
        return false;
    }

    /**
     * Get SVG image placeholder
     *
     * @param  string $url
     * @param  mixed  $width
     * @param  mixed  $height
     * @return string
     */
    public static function getSvgImage($width, $height)
    {
        if ($width < 1) {
            $width = 1;
        }

        if ($height < 1) {
            $height = 1;
        }

        return sprintf(DeferConstant::TEMPLATE_SVG_PLACEHOLDER, $width, $height);
    }

    /**
     * Get CSS style for adding random background color
     *
     * @param  bool   $grey
     * @return string
     */
    public static function getBgColorStyle($grey = false)
    {
        if ($grey) {
            return sprintf(DeferConstant::TEMPLATE_CSS_GREY, rand(91, 99));
        }

        return sprintf(DeferConstant::TEMPLATE_CSS_COLORFUL, rand(1, 360));
    }

    /**
     * This normalises URI's based on the specification RFC 3986
     *
     * @param  string $url
     * @return string
     */
    public static function normalizeUrl($url)
    {
        if (preg_match('#^\/\/[^\/]#', $url)) {
            $url = 'https:' . $url;
        }

        return $url;
    }

    /**
     * Get normalized URL origin
     *
     * @param  string $url
     * @return string
     */
    public static function normalizeUrlOrigin($url)
    {
        if (preg_match('#^(https?:)?//#i', $url)) {
            return preg_replace('#^(https?:)?(//[^/]+).*?.*#i', '$2', $url);
        }

        return null;
    }

    /**
     * Check a resource is a third-party
     *
     * @param  string $url
     * @param  array  $lookup
     * @return bool
     */
    public static function isThirdParty($url, $lookup)
    {
        $host = '';

        if (!empty(($_SERVER['HTTP_HOST']))) {
            $host = '//' . $_SERVER['HTTP_HOST'];
        }

        if ($lookup instanceof DeferOptions) {
            $lookup = $lookup->getWellKnown3rd();
        } elseif (empty($lookup) || !is_array($lookup)) {
            $lookup = DeferConstant::WELL_KNOWN_THIRDPARTY;
        }

        foreach ($lookup as $pattern) {
            // If server origin esists in
            // third-party list, always returns false
            if (strstr($host, $pattern) !== false) {
                return false;
            }

            // Else check the URL if it is a third-party
            if (strstr($url, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }
}
