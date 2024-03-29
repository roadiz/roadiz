<?php
declare(strict_types=1);

namespace RZ\Roadiz\Translation\Services;

use Doctrine\Persistence\ManagerRegistry;
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
     * @param Container $pimple
     */
    public function register(Container $pimple)
    {
        /**
         * @param Container $c
         * @return Translation
         */
        $pimple['defaultTranslation'] = function (Container $c) {
            return $c[ManagerRegistry::class]->getRepository(Translation::class)->findDefault();
        };

        $pimple[TranslatorFactoryInterface::class] = function (Container $c) {
            return new TranslatorFactory(
                $c['kernel'],
                $c['requestStack'],
                $c[ManagerRegistry::class],
                $c['stopwatch'],
                $c['themeResolver'],
                $c[PreviewResolverInterface::class],
            );
        };

        /**
         * @param Container $c
         * @return TranslatorInterface
         */
        $pimple['translator'] = function (Container $c) {
            /** @var TranslatorFactory $factory */
            $factory = $c[TranslatorFactoryInterface::class];
            return $factory->create();
        };
    }
}
