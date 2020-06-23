<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\OpenId\Authentication\OAuth2AuthenticationListener;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\OpenId\Discovery;
use RZ\Roadiz\Utils\MediaFinders\SplashbasePictureFinder;
use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGeneratorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Themes\Rozier\Forms\LoginType;
use Themes\Rozier\RozierApp;
use Twig_Error_Runtime;

/**
 * Class LoginController
 *
 * @package Themes\Rozier\Controllers
 */
class LoginController extends RozierApp
{
    /**
     * @param Request $request
     *
     * @return Response
     * @throws Twig_Error_Runtime
     */
    public function indexAction(Request $request)
    {
        if ($this->isGranted(Role::ROLE_BACKEND_USER)) {
            return $this->redirect($this->generateUrl('adminHomePage'));
        }

        $form = $this->createForm(LoginType::class, null, [
            'urlGenerator' => $this->get('urlGenerator'),
            'requestStack' => $this->get('requestStack'),
        ]);
        $this->assignation['form'] = $form->createView();

        $helper = $this->get('securityAuthenticationUtils');

        $this->assignation['last_username'] = $helper->getLastUsername();
        $this->assignation['error'] = $helper->getLastAuthenticationError();

        /** @var Discovery|null $discovery */
        $discovery = $this->get(Discovery::class);

        if (null !== $discovery && in_array('code', $discovery->get('response_types_supported'))) {
            /** @var CsrfTokenManagerInterface $csrfTokenManager */
            $csrfTokenManager = $this->get('csrfTokenManager');
            $state = $csrfTokenManager->getToken(OAuth2AuthenticationListener::OAUTH_STATE_TOKEN);
            $this->assignation['openid_button_label'] = $this->get('settingsBag')->get('openid_button_label');
            $this->assignation['openid'] = $discovery->get('authorization_endpoint') . '?' . http_build_query([
                'response_type' => 'code',
                'hd' => $this->get('settingsBag')->get('openid_hd', null),
                'state' => $state->getValue(),
                'nonce' => 'test', // TODO: generate a nonce
                'login_hint' => $request->get('email', null),
                'scope' => implode(' ', $discovery->get('scopes_supported')),
                'client_id' => $this->get('settingsBag')->get('oauth_client_id', null),
                'redirect_uri' => $this->generateUrl('loginCheckPage', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);
        }

        return $this->render('login/login.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws Twig_Error_Runtime
     */
    public function checkAction(Request $request)
    {
        return $this->render('login/check.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws Twig_Error_Runtime
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
            if ($document instanceof Document) {
                /** @var DocumentUrlGeneratorInterface $documentUrlGenerator */
                $documentUrlGenerator = $this->get('document.url_generator');
                $documentUrlGenerator->setDocument($document);
                $documentUrlGenerator->setOptions([
                    'noProcess' => true
                ]);
                $response->setData([
                    'url' => $documentUrlGenerator->getUrl()
                ]);
                return $response;
            }
        }
        $splash = new SplashbasePictureFinder();
        $feed = $splash->getRandomBySearch('road');
        if (false === $feed) {
            throw new ResourceNotFoundException();
        }
        $response->setData($feed);

        return $response;
    }
}
