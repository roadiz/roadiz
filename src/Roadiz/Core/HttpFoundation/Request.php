<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file Request.php
 * @author Maxime Constantinian
 */
namespace RZ\Roadiz\Core\HttpFoundation;

use Symfony\Component\HttpFoundation\Request as BaseRequest;
use RZ\Roadiz\Core\Entities\Theme;

/**
 * Roadiz Request extending Symfony to be able to store current
 * Theme in it.
 */
class Request extends BaseRequest
{
    protected $theme = null;

    public function setTheme(Theme $theme = null)
    {
        $this->theme = $theme;
        return $this;
    }

    /**
     * @return RZ\Roadiz\Core\Entities\Theme
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Resolve current front controller URL.
     *
     * This method is the base of every URL building methods in RZ-CMS.
     * Be careful with handling it.
     *
     * @return string
     */
    public function getResolvedBaseUrl()
    {
        if ($this->server->get('SERVER_NAME')) {
            // Remove everything after index.php in php_self
            // when using PHP dev servers
            $url = pathinfo(substr(
                $this->server->get('PHP_SELF'),
                0,
                strpos($this->server->get('PHP_SELF'), '.php')
            ));

            // Protocol
            $pageURL = 'http';
            if ($this->server->get('HTTPS') &&
                $this->server->get('HTTPS') == "on") {
                $pageURL .= "s";
            }
            $pageURL .= "://";
            // Port
            if ($this->server->get('SERVER_PORT') &&
                $this->server->get('SERVER_PORT') != "80") {
                $pageURL .= $this->server->get('SERVER_NAME') .
                ":" .
                $this->server->get('SERVER_PORT');
            } else {
                $pageURL .= $this->server->get('SERVER_NAME');
            }
            // Non root folder
            if (!empty($url["dirname"]) &&
                $url["dirname"] != '/') {
                $pageURL .= $url["dirname"];
            }

            return $pageURL;
        } else {
            return false;
        }
    }

    /**
     * Resolve current front controller path.
     *
     * @return string
     */
    public function getResolvedBasePath()
    {
        if ($this->server->get('SERVER_NAME')) {
            // Remove everything after index.php in php_self
            // when using PHP dev servers
            $url = pathinfo(substr(
                $this->server->get('PHP_SELF'),
                0,
                strpos($this->server->get('PHP_SELF'), '.php')
            ));

            // Non root folder
            if (!empty($url["dirname"]) &&
                $url["dirname"] != '/') {
                return $url["dirname"];
            }
        }
        return null;
    }
}
