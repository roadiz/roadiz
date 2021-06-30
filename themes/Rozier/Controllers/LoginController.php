<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\OpenId\Exception\DiscoveryNotAvailableException;
use RZ\Roadiz\OpenId\OAuth2LinkGenerator;
use RZ\Roadiz\Utils\MediaFinders\RandomImageFinder;
use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGeneratorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Themes\Rozier\Forms\LoginType;
use Themes\Rozier\RozierApp;

/**
 * @package Themes\Rozier\Controllers
 */
class LoginController extends RozierApp
{
    /**
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        if ($this->isGranted(Role::ROLE_BACKEND_USER)) {
            return $this->redirect($this->generateUrl('adminHomePage'));
        }

        $form = $this->createForm(LoginType::class);
        $this->assignation['form'] = $form->createView();

        $helper = $this->get('securityAuthenticationUtils');

        $this->assignation['last_username'] = $helper->getLastUsername();
        $this->assignation['error'] = $helper->getLastAuthenticationError();

        try {
            /** @var OAuth2LinkGenerator $oauth2LinkGenerator */
            $oauth2LinkGenerator = $this->get(OAuth2LinkGenerator::class);
            if ($oauth2LinkGenerator->isSupported($request)) {
                $this->assignation['openid_button_label'] = $this->get('settingsBag')->get('openid_button_label');
                $this->assignation['openid'] = $oauth2LinkGenerator->generate(
                    $request,
                    $this->generateUrl('loginCheckPage', [], UrlGeneratorInterface::ABSOLUTE_URL)
                );
            }
        } catch (DiscoveryNotAvailableException $exception) {
            $this->get('logger')->error($exception->getMessage());
        }

        return $this->render('login/login.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function checkAction(Request $request)
    {
        return $this->render('login/check.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function logoutAction(Request $request)
    {
        return $this->render('login/check.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function imageAction(Request $request)
    {
        $response = new JsonResponse();
        if (null !== $document = $this->get('settingsBag')->getDocument('login_image')) {
            if ($document instanceof Document && $document->isProcessable()) {
                /** @var DocumentUrlGeneratorInterface $documentUrlGenerator */
                $documentUrlGenerator = $this->get('document.url_generator');
                $documentUrlGenerator->setDocument($document);
                $documentUrlGenerator->setOptions([
                    'width' => 1920,
                    'height' => 1920,
                    'quality' => 80,
                    'sharpen' => 5,
                ]);
                $response->setData([
                    'url' => $documentUrlGenerator->getUrl()
                ]);
                return $this->makeResponseCachable($request, $response, 60, true);
            }
        }
        /** @var RandomImageFinder $randomFinder */
        $randomFinder = $this->get(RandomImageFinder::class);
        $feed = $randomFinder->getRandomBySearch('road');
        $url = null;

        if (null !== $feed) {
            $url = $feed['url'] ?? $feed['urls']['regular'] ?? $feed['urls']['full'] ?? $feed['urls']['raw'] ?? null;
        }
        $response->setData([
            'url' => $url ?? $this->get('assetPackages')->getUrl('themes/Rozier/static/assets/img/default_login.jpg')
        ]);
        return $this->makeResponseCachable($request, $response, 60, true);
    }
}
