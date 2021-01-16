<?php
declare(strict_types=1);

namespace RZ\Roadiz\Attribute;

use Doctrine\Common\Collections\ArrayCollection;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Attribute\Event\AttributeValueIndexingSubscriber;
use RZ\Roadiz\Attribute\Event\AttributeValueLifeCycleSubscriber;
use RZ\Roadiz\Attribute\Form\AttributeDocumentType;
use RZ\Roadiz\Attribute\Form\AttributeGroupsType;
use RZ\Roadiz\Attribute\Form\AttributeGroupTranslationType;
use RZ\Roadiz\Attribute\Form\AttributeTranslationType;
use RZ\Roadiz\Attribute\Importer\AttributeImporter;
use RZ\Roadiz\Attribute\Serializer\AttributeGroupObjectConstructor;
use RZ\Roadiz\Attribute\Serializer\AttributeGroupTranslationObjectConstructor;
use RZ\Roadiz\Attribute\Serializer\AttributeObjectConstructor;
use RZ\Roadiz\Attribute\Twig\AttributesExtension;
use RZ\Roadiz\CMS\Importers\ChainImporter;
use RZ\Roadiz\Core\Serializers\ObjectConstructor\ChainDoctrineObjectConstructor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\Translator;

class AttributesServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $container)
    {
        $container[AttributeDocumentType::class] = function (Container $c) {
            return new AttributeDocumentType($c['em']);
        };
        $container[AttributeTranslationType::class] = function (Container $c) {
            return new AttributeTranslationType($c['em']);
        };
        $container[AttributeGroupTranslationType::class] = function (Container $c) {
            return new AttributeGroupTranslationType($c['em']);
        };
        $container[AttributeGroupsType::class] = function (Container $c) {
            return new AttributeGroupsType($c['em']);
        };
        $container[AttributeImporter::class] = $container->factory(function ($c) {
            return new AttributeImporter($c);
        });
        $container->extend(ChainImporter::class, function (ChainImporter $chainImporter, $c) {
            $chainImporter->addImporter($c[AttributeImporter::class]);
            return $chainImporter;
        });

        $container->extend('dispatcher', function (EventDispatcherInterface $dispatcher) {
            $dispatcher->addSubscriber(new AttributeValueIndexingSubscriber());
            return $dispatcher;
        });

        $container->extend('twig.extensions', function (ArrayCollection $extensions, $c) {
            $extensions->add(new AttributesExtension($c['em']));
            return $extensions;
        });

        $container->extend('em.eventSubscribers', function (array $subscribers, Container $c) {
            array_push($subscribers, new AttributeValueLifeCycleSubscriber());
            return $subscribers;
        });

        $container->extend('translator', function (Translator $translator) {
            $translator->addResource(
                'xlf',
                dirname(__FILE__) . '/Resources/translations/messages.en.xlf',
                'en'
            );
            $translator->addResource(
                'xlf',
                dirname(__FILE__) . '/Resources/translations/messages.fr.xlf',
                'fr'
            );
            return $translator;
        });

        $container->extend(
            ChainDoctrineObjectConstructor::class,
            function (ChainDoctrineObjectConstructor $constructor, Container $c) {
                $constructor->add(new AttributeObjectConstructor($c['em'], $c['serializer.fallback_constructor']));
                $constructor->add(new AttributeGroupObjectConstructor($c['em'], $c['serializer.fallback_constructor']));
                $constructor->add(new AttributeGroupTranslationObjectConstructor($c['em'], $c['serializer.fallback_constructor']));
                return $constructor;
            }
        );
    }
}
