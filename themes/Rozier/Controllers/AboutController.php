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
 *
 * @file AboutController.php
 * @author Maxime Constantinian
 */
namespace Themes\Rozier\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\Rozier\RozierApp;

class AboutController extends RozierApp
{
    /**
     * Allow upgrade even if roadiz has been
     * setuped with Git.
     *
     * For prod environment, set this to false.
     */
    const UPDATE_WITH_GIT = false;
    const UPDATE_STEPS = 5;
    /**
     * Destination folder for updated files.
     * When doing test set this as /testDir or something
     * else than "".
     *
     * For prod environment, set this to "" (empty).
     */
    const UPDATE_DEST_DIR = "";
    /**
     * Trash folder in which old files will be moved
     * and kept until an other upgrade request is performed.
     */
    const UPDATE_TRASH_DIR = "/old";
    /**
     * This is the folder name when archive is extracted.
     *
     * roadiz.zip
     *  |__ /roadiz-master
     *       |__ files…
     */
    const UPDATE_ZIP_FOLDER = "/roadiz-master";

    protected static $filesToUpgrade = [
        'index.php',
        'vendor',
        'src',
        'bin',
        'tests',
    ];
    protected static $themesToUpgrade = [
        'themes/Install',
        'themes/Rozier',
        'themes/DefaultTheme',
    ];

    protected function getGithubReleases()
    {
        try {
            $url = "https://api.github.com/repos/roadiz/roadiz/releases";

            $client = new Client(['defaults' => ['debug' => false]]);
            $response = $client->get($url);

            if (Response::HTTP_OK == $response->getStatusCode()) {
                return json_decode($response->getBody());
            } else {
                return false;
            }
        } catch (RequestException $e) {
            return false;
        }
    }

    protected function getLatestRelease()
    {
        $releases = $this->getGithubReleases();

        if (false !== $releases) {
            foreach ($releases as $release) {
                if ($release->draft === false && $release->tag_name[0] == 'v') {
                    return $release;
                }
            }
        }

        return null;
    }

    /**
     * About action to display some useful informations.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_SUPERADMIN');
        $lastRelease = $this->getLatestRelease();

        if ($lastRelease !== null) {
            $lastVersion = substr($lastRelease->tag_name, 1);
            $this->assignation["newVersion"] = version_compare(Kernel::$cmsVersion, $lastVersion, "<");
            if (isset($lastRelease->assets[0])) {
                $this->assignation["downloadUrl"] = $lastRelease->assets[0]->browser_download_url;
            }
            $this->assignation["lastRelease"] = $lastRelease;
            $this->assignation["changelog"] = $lastRelease->body;
            $this->assignation["currentVersion"] = Kernel::$cmsVersion;
            $this->assignation["lastVersion"] = $lastVersion;
        }
        return $this->render('about/index.html.twig', $this->assignation);
    }
}
