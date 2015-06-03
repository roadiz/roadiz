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
use RZ\Roadiz\Core\Bags\SettingsBag;

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
     * Get absolute base url containing Hostname and
     * baseUrl.
     *
     * @return string
     */
    public function getAbsoluteBaseUrl()
    {
        $schemeAuthority = '';
        $port = '';
        $scheme = $this->getScheme();

        if ('http' === $scheme && 80 != $this->getPort()) {
            $port = ':' . $this->getPort();
        } elseif ('https' === $scheme && 443 != $this->getPort()) {
            $port = ':' . $this->getHttpsPort();
        }

        $schemeAuthority = $scheme . '://';
        $schemeAuthority .= $this->getHost() . $port;

        return $schemeAuthority . $this->getBaseUrl();
    }

    /**
     * @param  string $url Absolute Url with primary domain.
     *
     * @return string      Absolute Url with static domain.
     */
    public function convertUrlToStaticDomainUrl($url)
    {
        $staticDomain = SettingsBag::get('static_domain_name');

        if (!empty($staticDomain)) {
            return preg_replace('#://([^:^/]+)#', '://' . $staticDomain, $url);
        } else {
            return $url;
        }
    }

    /**
     * Get a FQDN base url for static resources.
     *
     * You should fill “static_domain_name” setting after your
     * static domain name. Do not forget to create a virtual host
     * for this domain to serve the same content as your primary domain.
     *
     * If “static_domain_name” is empty, this method returns baseUrl
     *
     * @return string
     */
    public function getStaticBaseUrl()
    {
        $staticDomain = SettingsBag::get('static_domain_name');
        if (!empty($staticDomain)) {
            return $this->convertUrlToStaticDomainUrl($this->getAbsoluteBaseUrl());
        } else {
            return $this->getBaseUrl();
        }
    }
}
