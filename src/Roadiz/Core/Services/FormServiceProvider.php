<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Services;

use Doctrine\Persistence\ManagerRegistry;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Rollerworks\Component\PasswordStrength\Blacklist\ArrayProvider;
use Rollerworks\Component\PasswordStrength\Blacklist\BlacklistProviderInterface;
use Rollerworks\Component\PasswordStrength\Blacklist\LazyChainProvider;
use Rollerworks\Component\PasswordStrength\Validator\Constraints\BlacklistValidator;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueEntityValidator;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueFilenameValidator;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueNodeNameValidator;
use RZ\Roadiz\CMS\Forms\Constraints\UniqueTagNameValidator;
use RZ\Roadiz\CMS\Forms\Constraints\ValidAccountConfirmationTokenValidator;
use RZ\Roadiz\CMS\Forms\Constraints\ValidAccountEmailValidator;
use RZ\Roadiz\CMS\Forms\DocumentCollectionType;
use RZ\Roadiz\CMS\Forms\Extension\ContainerFormExtension;
use RZ\Roadiz\CMS\Forms\Extension\HelpAndGroupExtension;
use RZ\Roadiz\CMS\Forms\GroupsType;
use RZ\Roadiz\CMS\Forms\NodeSource\NodeSourceCustomFormType;
use RZ\Roadiz\CMS\Forms\NodeSource\NodeSourceDocumentType;
use RZ\Roadiz\CMS\Forms\NodeSource\NodeSourceJoinType;
use RZ\Roadiz\CMS\Forms\NodeSource\NodeSourceNodeType;
use RZ\Roadiz\CMS\Forms\NodeSource\NodeSourceProviderType;
use RZ\Roadiz\CMS\Forms\NodeSource\NodeSourceType;
use RZ\Roadiz\CMS\Forms\NodesType;
use RZ\Roadiz\CMS\Forms\NodeTypesType;
use RZ\Roadiz\CMS\Forms\RolesType;
use RZ\Roadiz\CMS\Forms\SettingDocumentType;
use RZ\Roadiz\CMS\Forms\SettingGroupType;
use RZ\Roadiz\CMS\Forms\TagTranslationDocumentType;
use RZ\Roadiz\CMS\Forms\TranslationsType;
use RZ\Roadiz\CMS\Forms\UrlAliasType;
use RZ\Roadiz\CMS\Forms\UsersType;
use RZ\Roadiz\Utils\Document\DocumentFactory;
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

        $container[UniqueEntityValidator::class] = function (Container $c) {
            return new UniqueEntityValidator($c[ManagerRegistry::class]);
        };

        $container[UniqueNodeNameValidator::class] = function (Container $c) {
            return new UniqueNodeNameValidator($c[ManagerRegistry::class]);
        };

        $container[UniqueTagNameValidator::class] = function (Container $c) {
            return new UniqueTagNameValidator($c[ManagerRegistry::class]);
        };

        $container[ValidAccountConfirmationTokenValidator::class] = function (Container $c) {
            return new ValidAccountConfirmationTokenValidator($c[ManagerRegistry::class]);
        };

        $container[ValidAccountEmailValidator::class] = function (Container $c) {
            return new ValidAccountEmailValidator($c[ManagerRegistry::class]);
        };

        $container[UniqueFilenameValidator::class] = function (Container $c) {
            return new UniqueFilenameValidator($c['assetPackages']);
        };

        $container[DocumentCollectionType::class] = function (Container $c) {
            return new DocumentCollectionType($c[ManagerRegistry::class]);
        };

        $container[TranslationsType::class] = function (Container $c) {
            return new TranslationsType($c[ManagerRegistry::class]);
        };

        $container[RolesType::class] = function (Container $c) {
            return new RolesType($c[ManagerRegistry::class], $c['securityAuthorizationChecker']);
        };

        $container[GroupsType::class] = function (Container $c) {
            return new GroupsType($c[ManagerRegistry::class], $c['securityAuthorizationChecker']);
        };

        $container[NodesType::class] = function (Container $c) {
            return new NodesType($c[ManagerRegistry::class]);
        };

        $container[NodeTypesType::class] = function (Container $c) {
            return new NodeTypesType($c[ManagerRegistry::class]);
        };

        $container[SettingDocumentType::class] = function (Container $c) {
            return new SettingDocumentType($c[ManagerRegistry::class], $c[DocumentFactory::class], $c['assetPackages']);
        };

        $container[SettingGroupType::class] = function (Container $c) {
            return new SettingGroupType($c[ManagerRegistry::class]);
        };

        $container[TagTranslationDocumentType::class] = function (Container $c) {
            return new TagTranslationDocumentType($c[ManagerRegistry::class]);
        };

        $container[UsersType::class] = function (Container $c) {
            return new UsersType($c[ManagerRegistry::class]);
        };

        $container[UrlAliasType::class] = function (Container $c) {
            return new UrlAliasType($c[ManagerRegistry::class]);
        };

        $container[NodeSourceCustomFormType::class] = function (Container $c) {
            return new NodeSourceCustomFormType($c[ManagerRegistry::class], $c['node.handler']);
        };

        $container[NodeSourceNodeType::class] = function (Container $c) {
            return new NodeSourceNodeType($c[ManagerRegistry::class], $c['node.handler']);
        };

        $container[NodeSourceDocumentType::class] = function (Container $c) {
            return new NodeSourceDocumentType($c[ManagerRegistry::class], $c['nodes_sources.handler']);
        };

        $container[NodeSourceJoinType::class] = function (Container $c) {
            return new NodeSourceJoinType($c[ManagerRegistry::class]);
        };

        $container[NodeSourceProviderType::class] = function (Container $c) {
            return new NodeSourceProviderType($c[ManagerRegistry::class], $c);
        };

        $container[NodeSourceType::class] = function (Container $c) {
            return new NodeSourceType($c[ManagerRegistry::class]);
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
