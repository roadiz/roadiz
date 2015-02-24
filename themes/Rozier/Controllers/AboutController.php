<?php
/*
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
 *
 * @file AboutController.php
 * @author Maxime Constantinia
 */
namespace Themes\Rozier\Controllers;

use GuzzleHttp\Subscriber\Cache\CacheStorage;
use GuzzleHttp\Subscriber\Cache\CacheSubscriber;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\Rozier\RozierApp;

class AboutController extends RozierApp
{
    protected function getGithubLatestRelease()
    {
        try {
            $url = "https://api.github.com/repos/roadiz/roadiz/releases";

            $client = new \GuzzleHttp\Client(['defaults' => ['debug' => false]]);

            // needs a composer require guzzlehttp/cache-subscriber
            CacheSubscriber::attach($client, array(
                'storage' => new CacheStorage($this->getService('em')->getConfiguration()->getResultCacheImpl(), "rozier_github", 3600),
                'validate' => false,
            ));

            $response = $client->get($url);

            if (Response::HTTP_OK == $response->getStatusCode()) {
                return json_decode($response->getBody());
            } else {
                return false;
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return false;
        }
    }

    public function indexAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_SUPERADMIN');

        $releases = $this->getGithubLatestRelease();
        $lastRelease = null;
        foreach ($releases as $release) {
            $lastRelease = $release;
            if ($release->draft === false && $lastRelease->tag_name[0] == 'v') {
                break;
            }
        }
        if ($lastRelease !== null) {
            $lastVersion = substr($lastRelease->tag_name, 1);
            $this->assignation["newVersion"] = version_compare(Kernel::$cmsVersion, $lastVersion, "<");
            if (isset($lastRelease->assets[0])) {
                $this->assignation["downloadUrl"] = $lastRelease->assets[0]->browser_download_url;
            }
            $this->assignation["changelog"] = $lastRelease->body;
            $this->assignation["currentVersion"] = Kernel::$cmsVersion;
            $this->assignation["lastVersion"] = $lastVersion;
        }
        return $this->render('about/index.html.twig', $this->assignation);
    }
}
