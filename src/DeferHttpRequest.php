<?php

/**
 * A PHP helper class to efficiently defer JavaScript for your website.
 * (c) 2019 AppSeeds https://appseeds.net/
 *
 * @package   shinsenter/defer.php
 * @since     1.0.0
 * @author    Mai Nhut Tan <shin@shin.company>
 * @copyright 2019 AppSeeds
 * @see       https://github.com/shinsenter/defer.php/blob/develop/README.md
 */

namespace shinsenter;

class DeferHttpRequest
{
    protected $request;
    protected $response;
    protected $httpcache;

    /**
     * Class constructor
     *
     * @since  1.0.0
     */
    public function __construct()
    {
        if (class_exists('Symfony\Component\HttpFoundation\Request')) {
            $this->request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
        }

        if (class_exists('Symfony\Component\HttpFoundation\Response')) {
            $this->response = new \Symfony\Component\HttpFoundation\Response();
        }
    }

    /**
     * Get the Request instance for current request
     *
     * @since  1.0.0
     * @return Request/false
     */
    public function request()
    {
        return $this->request ?: false;
    }

    /**
     * Get the Response instance for current request
     *
     * @since  1.0.0
     * @return Response/false
     */
    public function response()
    {
        return $this->response ?: false;
    }
}
