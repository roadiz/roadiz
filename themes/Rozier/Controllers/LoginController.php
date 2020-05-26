<?php
declare(strict_types=1);

namespace Themes\Rozier\Controllers;

use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Role;
use RZ\Roadiz\Utils\MediaFinders\SplashbasePictureFinder;
use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGenerator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Themes\Rozier\Forms\LoginType;
use Themes\Rozier\RozierApp;

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
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Twig_Error_Runtime
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

        return $this->render('login/login.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function checkAction(Request $request)
    {
        return $this->render('login/check.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function logoutAction(Request $request)
    {
        return $this->render('login/check.html.twig', $this->assignation);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function imageAction(Request $request)
    {
        $response = new JsonResponse();

        if (null !== $document = $this->get('settingsBag')->getDocument('login_image')) {
            if ($document instanceof Document) {
                /** @var DocumentUrlGenerator $documentUrlGenerator */
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
