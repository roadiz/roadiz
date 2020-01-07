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
 * @file FormServiceProvider.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Rollerworks\Component\PasswordStrength\Blacklist\ArrayProvider;
use Rollerworks\Component\PasswordStrength\Blacklist\BlacklistProviderInterface;
use Rollerworks\Component\PasswordStrength\Blacklist\LazyChainProvider;
use Rollerworks\Component\PasswordStrength\Validator\Constraints\BlacklistValidator;
use RZ\Roadiz\CMS\Forms\Extension\ContainerFormExtension;
use RZ\Roadiz\CMS\Forms\Extension\HelpAndGroupExtension;
use RZ\Roadiz\Utils\Security\Blacklist\Top500Provider;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\Type\RepeatedTypeValidatorExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\ContainerConstraintValidatorFactory;
use Symfony\Component\Validator\Validation;

/**
 * Register form services for dependency injection container.
 */
class FormServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container[BlacklistProviderInterface::class] = function (Container $c) {
            return new LazyChainProvider(new \Pimple\Psr11\Container($c), [
                ArrayProvider::class,
                Top500Provider::class,
            ]);
        };

        $container[ArrayProvider::class] = function () {
            return new ArrayProvider([
                'root',
                'test',
                'testtest',
                'azerty',
                'Azerty',
                'azertyuiop',
                'qwerty',
                'motdepasse',
                'Motdepasse'
            ]);
        };
        $container[Top500Provider::class] = function () {
            return new Top500Provider();
        };

        $container[BlacklistValidator::class] = function (Container $c) {
            return new BlacklistValidator($c[BlacklistProviderInterface::class]);
        };

        $container['formValidator'] = function (Container $c) {
            $constraintFactory = new ContainerConstraintValidatorFactory(new \Pimple\Psr11\Container($c));

            return Validation::createValidatorBuilder()
                        ->setConstraintValidatorFactory($constraintFactory)
                        ->setTranslationDomain(null)
                        ->setTranslator($c['translator'])
                        ->getValidator();
        };

        $container['formFactory'] = function (Container $c) {
            $formFactoryBuilder = Forms::createFormFactoryBuilder();
            $formFactoryBuilder->addExtensions($c['form.extensions']);
            $formFactoryBuilder->addTypeExtensions($c['form.type.extensions']);
            return $formFactoryBuilder->getFormFactory();
        };

        $container['form.extensions'] = function (Container $c) {
            return [
                new HttpFoundationExtension(),
                new CsrfExtension($c['csrfTokenManager']),
                new ValidatorExtension($c['formValidator']),
                new ContainerFormExtension($c)
            ];
        };

        $container['form.type.extensions'] = function () {
            return [
                new HelpAndGroupExtension(),
                new RepeatedTypeValidatorExtension(),
            ];
        };

        return $container;
    }
}
