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
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Client;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Themes\Rozier\RozierApp;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class AboutController extends RozierApp
{
    const UPDATE_WITH_GIT = true;
    /**
     * Destination folder for updated files.
     * When doing test set this as /testDir or something
     * else than "/".
     */
    const UPDATE_DEST_DIR = "/testDir";

    static $filesToUpload = [
        'vendor',
        'index.php',
        'src',
        'themes/Install',
        'themes/Rozier',
        'themes/DefaultTheme',
        'bootstrap.php',
        'bin',
        'cli-config.php',
        'tests',
    ];

    protected function getGithubReleases()
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

    protected function canAutomaticUpdate()
    {
        $fs = new Filesystem();

        if (!static::UPDATE_WITH_GIT && $fs->exists(ROADIZ_ROOT . '/.git')) {
            throw new \Exception("cannot_update_roadiz.using_git", 1);
        }
        if (is_link(ROADIZ_ROOT . '/vendor') ||
            is_link(ROADIZ_ROOT . '/src')) {
            throw new \Exception("cannot_update_roadiz.using_symlink", 1);
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


    public function updateAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_SUPERADMIN');

        try {
            $this->assignation['errors'] = [];

            $this->canAutomaticUpdate();

            $this->assignation['nextStepRoute'] = $this->generateUrl('aboutUpdateDownloadPage');
        } catch (\Exception $e) {
            $this->publishErrorMessage($request, $e->getMessage());
            $this->assignation['errors'][] = $e->getMessage();
        }

        return $this->render('about/update/index.html.twig', $this->assignation);
    }

    /**
     * Download latest Roadiz release archive.
     *
     * @param  Request $request
     *
     * @return JsonResponse
     */
    public function downloadAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_SUPERADMIN');

        $lastRelease = $this->getLatestRelease();

        if (null === $lastRelease) {
            throw new \Exception("cannot_update_roadiz.no_release_available", 1);
        }
        if (!isset($lastRelease->assets[0]) || empty($lastRelease->assets[0]->browser_download_url)) {
            throw new \Exception("cannot_update_roadiz.no_archive_to_download", 1);
        }

        $downloadUrl = $lastRelease->assets[0]->browser_download_url;


        $tmpFile = tempnam(sys_get_temp_dir(), "roadiz_update.zip");
        $resource = fopen($tmpFile, 'w');

        $client = new Client();
        $response = $client->get($downloadUrl, [
            'body' => $resource
        ]);

        $request->getSession()->set('roadiz_update_archive', $tmpFile);

        return new JsonResponse([
            'tmpFile' => $tmpFile,
            'nextStepRoute' => $this->generateUrl('aboutUpdateUnarchivePage')
        ]);
    }

    /**
     * Unarchive temporary roadiz archive.
     *
     * @param  Request $request
     *
     * @return JsonResponse
     */
    public function unarchiveAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_SUPERADMIN');

        if ($request->getSession()->get('roadiz_update_archive') !== "") {
            $tmpFile = $request->getSession()->get('roadiz_update_archive');
            $fs = new Filesystem();

            if (!$fs->exists($tmpFile) || !is_readable($tmpFile)) {
                throw new \Exception("cannot_update_roadiz.temp_archive_does_not_exist", 1);
            }



        } else {
            throw new \Exception("cannot_update_roadiz.temp_archive_does_not_exist", 1);
        }

        return new JsonResponse([
            'nextStepRoute' => $this->generateUrl('aboutUpdateMovePage')
        ]);
    }
    public function moveFilesAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_SUPERADMIN');

        /*
         * Define a files root that can be different than real root
         * for testing purposes.
         */
        $rootPath = ROADIZ_ROOT . static::UPDATE_DEST_DIR;



        return new JsonResponse([
            'nextStepRoute' => $this->generateUrl('aboutUpdateMoveNewPage')
        ]);
    }

}
