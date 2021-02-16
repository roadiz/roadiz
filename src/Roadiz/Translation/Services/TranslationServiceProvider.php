<?php
declare(strict_types=1);

namespace RZ\Roadiz\Translation\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Preview\PreviewResolverInterface;
use RZ\Roadiz\Translation\TranslatorFactory;
use RZ\Roadiz\Translation\TranslatorFactoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Register Embed documents services for dependency injection container.
 */
final class TranslationServiceProvider implements ServiceProviderInterface
{
    /**
     * Initialize translator services.
     *
     * @param Container $container
     */
    public function register(Container $container)
    {
        /**
         * @param Container $c
         * @return Translation
         */
        $container['defaultTranslation'] = function (Container $c) {
            return $c['em']->getRepository(Translation::class)->findDefault();
        };

        $container[TranslatorFactoryInterface::class] = function (Container $c) {
            return new TranslatorFactory(
                $c['kernel'],
                $c['requestStack'],
                $c['em'],
                $c['stopwatch'],
                $c['themeResolver'],
                $c[PreviewResolverInterface::class],
            );
        };

        /**
         * @param Container $c
         * @return TranslatorInterface
         */
        $container['translator'] = function (Container $c) {
            /** @var TranslatorFactory $factory */
            $factory = $c[TranslatorFactoryInterface::class];
            return $factory->create();
        };
    }
}
