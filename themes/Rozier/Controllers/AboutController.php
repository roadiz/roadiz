<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\Rozier\RozierApp;

class AboutController extends RozierApp
{
    protected function getGithubReleases()
    {
        try {
            $url = "https://api.github.com/repos/roadiz/roadiz/releases";
            $client = new Client();
            $response = $client->get($url);

            if (Response::HTTP_OK == $response->getStatusCode()) {
                return json_decode($response->getBody()->getContents());
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
     * About action to display some useful information.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_SUPERADMIN');
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
