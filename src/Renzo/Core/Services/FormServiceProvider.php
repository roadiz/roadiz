<?php

namespace RZ\Renzo\Core\Services;

use Pimple\Container;

use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\Csrf\CsrfProvider\SessionCsrfProvider;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Validator\Validation;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Handlers\UserProvider;
use RZ\Renzo\Core\Handlers\UserHandler;

/**
 * Register form services for dependency injection container.
 */
class FormServiceProvider implements \Pimple\ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container['session'] = function ($c) {
            $session = new Session();
            Kernel::getInstance()->getRequest()->setSession($session);
            return $session;
        };

        $container['csrfProvider'] = function ($c) {
            $csrfSecret = $c['config']["security"]['secret'];
            return new SessionCsrfProvider(
                $c['session'],
                $csrfSecret
            );
        };

        $container['formValidator'] = function ($c) {
            return Validation::createValidator();
        };

        $container['formFactory'] = function ($c) {
            return Forms::createFormFactoryBuilder()
                ->addExtension(new CsrfExtension($c['csrfProvider']))
                ->addExtension(new ValidatorExtension($c['formValidator']))
                ->getFormFactory();
        };
    }
}
