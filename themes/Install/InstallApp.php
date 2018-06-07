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
 * @file InstallApp.php
 * @author Ambroise Maupate
 */
namespace Themes\Install;

use Pimple\Container;
use RZ\Roadiz\CMS\Controllers\AppController;
use RZ\Roadiz\CMS\Forms\SeparatorType;
use RZ\Roadiz\CMS\Forms\ThemesType;
use RZ\Roadiz\Console\RoadizApplication;
use RZ\Roadiz\Console\Tools\Fixtures;
use RZ\Roadiz\Console\Tools\Requirements;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Core\Events\CacheEvents;
use RZ\Roadiz\Core\Events\FilterCacheEvent;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Utils\Clearer\ConfigurationCacheClearer;
use RZ\Roadiz\Utils\Clearer\DoctrineCacheClearer;
use RZ\Roadiz\Utils\Clearer\OPCacheClearer;
use RZ\Roadiz\Utils\Clearer\RoutingCacheClearer;
use RZ\Roadiz\Utils\Clearer\TranslationsCacheClearer;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;

/**
 * Installation application
 */
class InstallApp extends AppController
{
    protected static $themeName = 'Install theme';
    protected static $themeAuthor = 'Ambroise Maupate';
    protected static $themeCopyright = 'REZO ZERO';
    protected static $themeDir = 'Install';
    protected static $backendTheme = false;

    /**
     * Append objects to the global dependency injection container.
     *
     * @param Container $container
     */
    public static function setupDependencyInjection(Container $container)
    {
        parent::setupDependencyInjection($container);

        $locale = $container['session']->get('_locale', 'en');
        $container['request']->setLocale($locale);
        \Locale::setDefault($locale);
    }

    /**
     * @return $this
     */
    public function prepareBaseAssignation()
    {
        $this->assignation = [
            'request' => $this->getRequest(),
            'head' => [
                'siteTitle' => 'welcome.title',
                'ajax' => $this->getRequest()->isXmlHttpRequest(),
                'devMode' => false,
                'baseUrl' => $this->getRequest()->getSchemeAndHttpHost() . $this->getRequest()->getBasePath(),
                'filesUrl' => $this->getRequest()->getBaseUrl() . $this->get('kernel')->getPublicFilesBasePath(),
                'resourcesUrl' => $this->getStaticResourcesUrl(),
                'ajaxToken' => $this->get('csrfTokenManager')->getToken(static::AJAX_TOKEN_INTENTION),
                'fontToken' => $this->get('csrfTokenManager')->getToken(static::FONT_TOKEN_INTENTION),
            ],
            'session' => [
                'id' => $this->getRequest()->getSession()->getId(),
                'locale' => $this->getRequest()->getSession()->get('_locale', 'en'),
            ],
        ];

        $this->assignation['head']['grunt'] = include dirname(__FILE__) . '/static/public/config/assets.config.php';

        return $this;
    }

    /**
     * Welcome screen.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $form = $this->buildLanguageForm($request);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $locale = $form->getData()['language'];
            $request->setLocale($locale);
            $this->get('session')->set('_locale', $locale);
            /*
             * Force redirect to avoid resending form when refreshing page
             */
            return $this->redirect($this->generateUrl(
                'installHomePage'
            ));
        }

        $this->assignation['form'] = $form->createView();

        return $this->render('steps/hello.html.twig', $this->assignation);
    }

    /**
     * Welcome screen redirect.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function redirectIndexAction(Request $request)
    {
        return $this->redirect($this->generateUrl(
            'installHomePage'
        ));
    }

    /**
     * Check requirement screen.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function requirementsAction(Request $request)
    {
        $requirements = new Requirements($this->get('kernel'));
        $this->assignation['requirements'] = $requirements->getRequirements();
        $this->assignation['totalSuccess'] = $requirements->isTotalSuccess();
        return $this->render('steps/requirements.html.twig', $this->assignation);
    }

    /**
     * User creation screen.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function userAction(Request $request)
    {
        $userForm = $this->buildUserForm($request);

        if ($userForm !== null) {
            $userForm->handleRequest($request);

            if ($userForm->isValid()) {
                /*
                 * Create user
                 */
                try {
                    /** @var Kernel $kernel */
                    $kernel = $this->get('kernel');
                    $fixtures = new Fixtures(
                        $this->get('em'),
                        $kernel->getCacheDir(),
                        $kernel->getRootDir() . '/conf/config.yml',
                        $kernel->getRootDir(),
                        $kernel->isDebug(),
                        $request
                    );
                    $fixtures->createDefaultUser($userForm->getData());
                    /*
                     * Force redirect to avoid resending form when refreshing page
                     */
                    $user = $this->get('em')
                        ->getRepository(User::class)
                        ->findOneBy(['username' => $userForm->getData()['username']]);

                    return $this->redirect($this->generateUrl(
                        'installUserSummaryPage',
                        ["userId" => $user->getId()]
                    ));
                } catch (\Exception $e) {
                    $this->assignation['error'] = true;
                    $this->assignation['errorMessage'] = $e->getMessage();
                }
            }
            $this->assignation['userForm'] = $userForm->createView();
        }

        return $this->render('steps/user.html.twig', $this->assignation);
    }

    /**
     * User information screen.
     *
     * @param Request $request
     * @param int     $userId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function userSummaryAction(Request $request, $userId)
    {
        $user = $this->get('em')->find(User::class, $userId);
        $this->assignation['name'] = $user->getUsername();
        $this->assignation['email'] = $user->getEmail();
        return $this->render('steps/userSummary.html.twig', $this->assignation);
    }

    /**
     * Install success screen.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function doneAction(Request $request)
    {
        $doneForm = $this->buildDoneForm($request);

        if ($doneForm !== null) {
            $doneForm->handleRequest($request);

            if ($doneForm->isValid() &&
                $doneForm->getData()['action'] == 'quit_install') {
                /*
                 * Save information
                 */
                try {
                    /*
                     * Close Session for security and temp translation
                     */
                    $this->get('session')->invalidate();

                    /** @var EventDispatcher $dispatcher */
                    $dispatcher = $this->get('dispatcher');
                    // Get real kernel class if Standard edition
                    $kernelClass = get_class($this->get('kernel'));

                    // Clear cache for install
                    $installEvent = new FilterCacheEvent($this->get('kernel'));
                    $dispatcher->dispatch(CacheEvents::PURGE_REQUEST, $installEvent);

                    // Clear cache for prod
                    /** @var Kernel $prodKernel */
                    $prodKernel = new $kernelClass('prod', false);
                    $prodKernel->boot();
                    $prodEvent = new FilterCacheEvent($prodKernel);
                    $dispatcher->dispatch(CacheEvents::PURGE_REQUEST, $prodEvent);

                    // Clear cache for prod preview
                    /** @var Kernel $prodPreviewKernel */
                    $prodPreviewKernel = new $kernelClass('prod', false, true);
                    $prodPreviewKernel->boot();
                    $prodPreviewEvent = new FilterCacheEvent($prodPreviewKernel);
                    $dispatcher->dispatch(CacheEvents::PURGE_REQUEST, $prodPreviewEvent);

                    /*
                     * Force redirect to avoid resending form when refreshing page
                     */
                    return $this->redirect($this->generateUrl('installAfterDonePage'));
                } catch (\Exception $e) {
                    $this->assignation['error'] = true;
                    $this->assignation['errorMessage'] = $e->getMessage() . PHP_EOL . $e->getTraceAsString();
                }
            }
            $this->assignation['doneForm'] = $doneForm->createView();
        }

        return $this->render('steps/done.html.twig', $this->assignation);
    }

    /**
     * @param string $env
     * @param bool $debug
     * @param bool $preview
     */
    protected function callClearCacheCommands($env = 'prod', $debug = false, $preview = false)
    {
        /*
         * Very important, when using standard-edition,
         * Kernel class is AppKernel or DevAppKernel.
         */
        $kernelClass = get_class($this->get('kernel'));
        $application = new RoadizApplication(new $kernelClass($env, $debug, $preview));
        $application->setAutoExit(false);

        $input = new ArrayInput(array(
            'command' => 'cache:clear'
        ));
        // You can use NullOutput() if you don't need the output
        $output = new BufferedOutput();
        $application->run($input, $output);

        $inputFpm = new ArrayInput(array(
            'command' => 'cache:clear-fpm'
        ));
        // You can use NullOutput() if you don't need the output
        $outputFpm = new BufferedOutput();
        $application->run($inputFpm, $outputFpm);
    }

    /**
     * After done and clearing caches.
     *
     * @param  Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function afterDoneAction(Request $request)
    {
        /*
         * This can take some time to execute.
         */
        $this->callClearCacheCommands('prod');
        $this->callClearCacheCommands('prod', false, true);
        $this->callClearCacheCommands('dev', true);

        return $this->render('steps/after-done.html.twig', $this->assignation);
    }

    /**
     * Build forms.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildLanguageForm(Request $request)
    {
        $builder = $this->createFormBuilder()
            ->add('language', 'choice', [
                'choices' => [
                    'English' => 'en',
                    'Español' => 'es',
                    'Français' => 'fr',
                    'Русский язык' => 'ru',
                    'Türkçe' => 'tr',
                ],
                'choices_as_values' => true,
                'constraints' => [
                    new NotBlank(),
                ],
                'label' => 'choose.a.language',
                'attr' => [
                    "id" => "language",
                ],
                'data' => $request->getLocale(),
            ]);

        return $builder->getForm();
    }

    /**
     * Build forms
     *
     * @param Request $request
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildUserForm(Request $request)
    {
        $builder = $this->createFormBuilder()
            ->add('username', 'text', [
                'required' => true,
                'label' => $this->getTranslator()->trans('username'),
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('email', 'email', [
                'required' => true,
                'label' => $this->getTranslator()->trans('email'),
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('password', 'repeated', [
                'type' => 'password',
                'invalid_message' => 'password.must_match',
                'first_options' => ['label' => 'password'],
                'second_options' => ['label' => 'password.verify'],
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                ],
            ]);

        return $builder->getForm();
    }

    /**
     * Build forms
     *
     * @param Request $request
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function buildDoneForm(Request $request)
    {
        $builder = $this->createFormBuilder()
            ->add('action', 'hidden', [
                'data' => 'quit_install',
            ]);

        return $builder->getForm();
    }
}
