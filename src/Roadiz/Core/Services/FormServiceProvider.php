<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Rollerworks\Component\PasswordStrength\Blacklist\ArrayProvider;
use Rollerworks\Component\PasswordStrength\Blacklist\BlacklistProviderInterface;
use Rollerworks\Component\PasswordStrength\Blacklist\LazyChainProvider;
use Rollerworks\Component\PasswordStrength\Validator\Constraints\BlacklistValidator;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueEntityValidator;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueNodeNameValidator;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueNodeTypeNameValidator;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueTagNameValidator;
use RZ\Roadiz\CMS\Forms\DocumentCollectionType;
use RZ\Roadiz\CMS\Forms\Extension\ContainerFormExtension;
use RZ\Roadiz\CMS\Forms\Extension\HelpAndGroupExtension;
use RZ\Roadiz\CMS\Forms\RolesType;
use RZ\Roadiz\CMS\Forms\TranslationsType;
use RZ\Roadiz\CMS\Forms\UrlAliasType;
use RZ\Roadiz\Utils\Security\Blacklist\Top500Provider;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\Type\RepeatedTypeValidatorExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\ContainerConstraintValidatorFactory;
use Symfony\Component\Validator\Validation;
use Themes\Rozier\Forms\FolderCollectionType;

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

        $container[UniqueEntityValidator::class] = function (Container $c) {
            return new UniqueEntityValidator($c['em']);
        };

        $container[UniqueNodeTypeNameValidator::class] = function (Container $c) {
            return new UniqueNodeTypeNameValidator($c['em']);
        };

        $container[UniqueNodeNameValidator::class] = function (Container $c) {
            return new UniqueNodeNameValidator($c['em']);
        };

        $container[UniqueTagNameValidator::class] = function (Container $c) {
            return new UniqueTagNameValidator($c['em']);
        };

        $container[FolderCollectionType::class] = function (Container $c) {
            return new FolderCollectionType($c['em']);
        };

        $container[DocumentCollectionType::class] = function (Container $c) {
            return new DocumentCollectionType($c['em']);
        };

        $container[TranslationsType::class] = function (Container $c) {
            return new TranslationsType($c['em']);
        };

        $container[RolesType::class] = function (Container $c) {
            return new RolesType($c['em'], $c['securityAuthorizationChecker']);
        };

        $container[UrlAliasType::class] = function (Container $c) {
            return new UrlAliasType($c['em']);
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
