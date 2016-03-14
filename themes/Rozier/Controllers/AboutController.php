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

use Doctrine\ORM\Tools\SchemaTool;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Cache\CacheStorage;
use GuzzleHttp\Subscriber\Cache\CacheSubscriber;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Clearer\DoctrineCacheClearer;
use RZ\Roadiz\Utils\Clearer\OPCacheClearer;
use RZ\Roadiz\Utils\Clearer\RoutingCacheClearer;
use RZ\Roadiz\Utils\Clearer\TemplatesCacheClearer;
use RZ\Roadiz\Utils\Clearer\TranslationsCacheClearer;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        } catch (RequestException $e) {
            return false;
        }
    }

    protected function canAutomaticUpdate()
    {
        $fs = new Filesystem();

        if (!static::UPDATE_WITH_GIT && $fs->exists(ROADIZ_ROOT . '/.git')) {
            throw new \Exception($this->getTranslator()->trans('cannot_update_roadiz.using_git'), 1);
        }
        if (is_link(ROADIZ_ROOT . '/vendor') ||
            is_link(ROADIZ_ROOT . '/src')) {
            throw new \Exception($this->getTranslator()->trans('cannot_update_roadiz.using_symlink'), 1);
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

    /**
     * Update action is the upgrade tunnel entry point.
     *
     * Every following action will be performed with Ajax requests.
     *
     * @param  Request $request
     *
     * @return Response
     */
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
     * @throws \Exception
     */
    public function downloadAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_SUPERADMIN');
        $this->canAutomaticUpdate();

        $lastRelease = $this->getLatestRelease();

        if (null === $lastRelease) {
            throw new \Exception($this->getTranslator()->trans('cannot_update_roadiz.no_release_available'), 1);
        }
        if (!isset($lastRelease->assets[0]) || empty($lastRelease->assets[0]->browser_download_url)) {
            throw new \Exception($this->getTranslator()->trans('cannot_update_roadiz.no_archive_to_download'), 1);
        }

        $downloadUrl = $lastRelease->assets[0]->browser_download_url;

        $tmpFile = tempnam(sys_get_temp_dir(), "roadiz_update.zip");
        $resource = fopen($tmpFile, 'w');
        $stream = Stream::factory($resource);

        $client = new Client();
        $client->get($downloadUrl, [
            'save_to' => $stream,
        ]);

        $request->getSession()->set('roadiz_update_archive', $tmpFile);

        return new JsonResponse([
            'downloadUrl' => $downloadUrl,
            'tmpFile' => $tmpFile,
            'progress' => (100 / static::UPDATE_STEPS) * 1,
            'nextStepRoute' => $this->generateUrl('aboutUpdateUnarchivePage'),
            'nextStepDescription' => $this->getTranslator()->trans('update_roadiz.unarchive'),
        ]);
    }

    /**
     * Unarchive temporary roadiz archive.
     *
     * @param  Request $request
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function unarchiveAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_SUPERADMIN');
        $this->canAutomaticUpdate();

        if ($request->getSession()->get('roadiz_update_archive') !== "") {
            $tmpFile = $request->getSession()->get('roadiz_update_archive');
            $fs = new Filesystem();

            if (!$fs->exists($tmpFile) || !is_readable($tmpFile)) {
                throw new \Exception($this->getTranslator()->trans('cannot_update_roadiz.temp_archive_does_not_exist'), 1);
            }

            $zip = new \ZipArchive();

            if (!$zip->open($tmpFile)) {
                throw new \Exception($this->getTranslator()->trans('cannot_update_roadiz.cant_open_archive'), 1);
            }

            $dir = $this->mktemp();
            $zip->extractTo($dir . '/');
            $zip->close();

            $request->getSession()->set('roadiz_update_folder', $dir);

        } else {
            throw new \Exception($this->getTranslator()->trans('cannot_update_roadiz.temp_archive_does_not_exist'), 1);
        }

        return new JsonResponse([
            'tmpFolder' => $dir,
            'progress' => (100 / static::UPDATE_STEPS) * 2,
            'nextStepRoute' => $this->generateUrl('aboutUpdateMovePage'),
            'nextStepDescription' => $this->getTranslator()->trans('update_roadiz.move_new_files'),
        ]);
    }

    /**
     * Move files from temporary folder to their dest folder.
     *
     * Old files are kept in old/ folder.
     *
     * @param  Request $request
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function moveFilesAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_SUPERADMIN');
        $this->canAutomaticUpdate();

        /*
         * Define a files root that can be different than real root
         * for testing purposes.
         */
        $rootPath = ROADIZ_ROOT . static::UPDATE_DEST_DIR;
        $fs = new Filesystem();

        $tmpDir = $request->getSession()->get('roadiz_update_folder');
        // look into wrapper folder
        $tmpDir .= static::UPDATE_ZIP_FOLDER;

        $filesLog = [];

        if (!$fs->exists($tmpDir)) {
            throw new \Exception($this->getTranslator()->trans('cannot_update_roadiz.temp_archive_does_not_exist'), 1);
        }
        if (!$fs->exists($rootPath)) {
            throw new \Exception($this->getTranslator()->trans('cannot_update_roadiz.destination_folder_does_not_exist'), 1);
        }
        if (!is_writable($rootPath)) {
            throw new \Exception($this->getTranslator()->trans('cannot_update_roadiz.destination_folder_is_not_writable'), 1);
        }

        $trashDir = $rootPath . static::UPDATE_TRASH_DIR;
        // Remove trash folder if exists
        if ($fs->exists($trashDir)) {
            $fs->remove($trashDir);
        }
        $fs->mkdir($trashDir);

        /*
         * Upgrade root level files
         */
        $rootFilesLog = $this->replaceFiles(static::$filesToUpgrade, $tmpDir, $rootPath, $trashDir);
        $filesLog = array_merge($filesLog, $rootFilesLog);

        /*
         * Upgrade theme level files
         */
        // Create themes/ folder if not exist
        if (!$fs->exists($rootPath . '/themes')) {
            $fs->mkdir($rootPath . '/themes');
        }
        if (!$fs->exists($trashDir . '/themes')) {
            $fs->mkdir($trashDir . '/themes');
        }
        $themeFilesLog = $this->replaceFiles(static::$themesToUpgrade, $tmpDir, $rootPath, $trashDir);
        $filesLog = array_merge($filesLog, $themeFilesLog);

        return new JsonResponse([
            'files' => $filesLog,
            'progress' => (100 / static::UPDATE_STEPS) * 3,
            'nextStepRoute' => $this->generateUrl('aboutUpdateClearCachePage'),
            'nextStepDescription' => $this->getTranslator()->trans('update_roadiz.clear_cache'),
        ]);
    }

    /**
     * Clear all cache before upgrading database schema.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function clearCacheAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_SUPERADMIN');
        $this->canAutomaticUpdate();

        $clearers = [
            new DoctrineCacheClearer($this->getService('em')),
            new TranslationsCacheClearer($this->getService('kernel')->getCacheDir()),
            new RoutingCacheClearer($this->getService('kernel')->getCacheDir()),
            new TemplatesCacheClearer($this->getService('kernel')->getCacheDir()),
            new TemplatesCacheClearer($this->getService('kernel')->getCacheDir()),
            new OPCacheClearer(),
        ];
        foreach ($clearers as $clearer) {
            $clearer->clear();
        }

        return new JsonResponse([
            'progress' => (100 / static::UPDATE_STEPS) * 4,
            'nextStepRoute' => $this->generateUrl('aboutUpdateSchemaPage'),
            'nextStepDescription' => $this->getTranslator()->trans('update_roadiz.update_schema'),
        ]);
    }

    /**
     * Upgrade doctrine database schema.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function updateSchemaAction(Request $request)
    {
        $this->validateAccessForRole('ROLE_SUPERADMIN');
        $this->canAutomaticUpdate();

        $schemaTool = new SchemaTool($this->getService('em'));
        $metadatas = $this->getService('em')->getMetadataFactory()->getAllMetadata();
        $schemaTool->updateSchema($metadatas, true);

        return new JsonResponse([
            'progress' => (100 / static::UPDATE_STEPS) * 5,
            'nextStepRoute' => null,
            'nextStepDescription' => $this->getTranslator()->trans('update_roadiz.update_complete'),
            'complete' => true,
        ]);
    }

    protected function mktemp()
    {
        $fs = new Filesystem();
        $tempfile = tempnam(sys_get_temp_dir(), 'roadiz_update');

        if ($fs->exists($tempfile)) {
            unlink($tempfile);
        }
        $fs->mkdir($tempfile);

        if (is_dir($tempfile)) {
            return $tempfile;
        } else {
            throw new \Exception($this->getTranslator()->trans('cannot_update_roadiz.cant_create_temp_dir'), 1);
        }
    }

    protected function replaceOneFile($file, $sourceDir, $destDir, $trashDir)
    {
        $fs = new Filesystem();
        $tempFile = $sourceDir . "/" . $file;
        $newFile = $destDir . "/" . $file;
        $trashFile = $trashDir . "/" . $file;

        /*
         * Move old files to trash
         */
        if ($fs->exists($newFile)) {
            $fs->rename($newFile, $trashFile);
            $this->getService('logger')->notice('Trash file', [
                'src' => $newFile,
                'dest' => $trashFile,
            ]);
        } else {
            $this->getService('logger')->notice('File does not exist', [
                'src' => $newFile,
            ]);
        }

        /*
         * Move new files in place
         */
        if ($fs->exists($tempFile)) {
            $fs->rename($tempFile, $newFile);
            $this->getService('logger')->notice('Moved file', [
                'src' => $tempFile,
                'dest' => $newFile,
            ]);
        }
    }

    /**
     *
     * @param  array $array
     * @param  string $tmpDir
     * @param  string $rootPath
     * @param  string $trashDir
     *
     * @return array
     */
    protected function replaceFiles($array, $tmpDir, $rootPath, $trashDir)
    {
        $filesLog = [];
        $fs = new Filesystem();

        foreach ($array as $file) {
            if ($fs->exists($tmpDir . '/' . $file)) {
                $this->replaceOneFile($file, $tmpDir, $rootPath, $trashDir);
                $filesLog[] = [
                    'tmp' => $tmpDir . '/' . $file,
                    'dest' => $rootPath . '/' . $file,
                ];
            }
        }

        return $filesLog;
    }
}
