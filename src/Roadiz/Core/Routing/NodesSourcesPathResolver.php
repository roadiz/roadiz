<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Routing;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\AbstractEntities\TranslationInterface;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Repositories\TranslationRepository;
use RZ\Roadiz\Preview\PreviewResolverInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Stopwatch\Stopwatch;

final class NodesSourcesPathResolver implements PathResolverInterface
{
    private EntityManagerInterface $entityManager;
    private ?Stopwatch $stopwatch;
    private static string $nodeNamePattern = '[a-zA-Z0-9\-\_\.]+';
    private PreviewResolverInterface $previewResolver;

    /**
     * @param EntityManagerInterface $entityManager
     * @param PreviewResolverInterface $previewResolver
     * @param Stopwatch|null $stopwatch
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        PreviewResolverInterface $previewResolver,
        ?Stopwatch $stopwatch
    ) {
        $this->entityManager = $entityManager;
        $this->stopwatch = $stopwatch;
        $this->previewResolver = $previewResolver;
    }

    /**
     * @param string $path
     * @param array $supportedFormatExtensions
     * @param bool $allowRootPaths Allow resolving / and /en, /fr paths to home pages
     * @return ResourceInfo
     */
    public function resolvePath(
        string $path,
        array $supportedFormatExtensions = ['html'],
        bool $allowRootPaths = false
    ): ResourceInfo {
        $resourceInfo = new ResourceInfo();
        $tokens = $this->tokenizePath($path);

        if (count($tokens) === 0 && !$allowRootPaths) {
            throw new ResourceNotFoundException();
        }

        $_format = 'html';
        $identifier = '';
        if (count($tokens) > 0) {
            $identifier = strip_tags($tokens[(int) (count($tokens) - 1)]);
        }

        if ($identifier !== '') {
            /*
             * Prevent searching nodes with special characters.
             */
            if (0 === preg_match('#'.static::$nodeNamePattern.'#', $identifier)) {
                throw new ResourceNotFoundException();
            }

            /*
             * Look for any supported format extension after last token.
             */
            if (0 !== preg_match(
                '#^('.static::$nodeNamePattern.')\.('.implode('|', $supportedFormatExtensions).')$#',
                $identifier,
                $matches
            )) {
                $realIdentifier = $matches[1];
                $_format = $matches[2];
                // replace last token with real node-name without extension.
                $tokens[(int) (count($tokens) - 1)] = $realIdentifier;
            }
        }

        if (null !== $this->stopwatch) {
            $this->stopwatch->start('parseTranslation');
        }
        $translation = $this->parseTranslation($tokens);
        if (null !== $this->stopwatch) {
            $this->stopwatch->stop('parseTranslation');
        }
        /*
         * Try with URL Aliases OR nodeName
         */
        if (null !== $this->stopwatch) {
            $this->stopwatch->start('parseFromIdentifier');
        }
        $nodeSource = $this->parseFromIdentifier($tokens, $translation);
        if (null !== $this->stopwatch) {
            $this->stopwatch->stop('parseFromIdentifier');
        }

        if (null === $nodeSource) {
            throw new ResourceNotFoundException();
        }

        $resourceInfo->setResource($nodeSource);
        $resourceInfo->setTranslation($nodeSource->getTranslation());
        $resourceInfo->setFormat($_format);
        $resourceInfo->setLocale($nodeSource->getTranslation()->getPreferredLocale());
        return $resourceInfo;
    }

    /**
     * Split path into meaningful tokens.
     *
     * @param string $path
     * @return array
     */
    private function tokenizePath(string $path): array
    {
        $tokens = explode('/', $path);
        $tokens = array_values(array_filter($tokens));

        return $tokens;
    }

    /**
     * Parse translation from URL tokens even if it is not available yet.
     *
     * @param array<string> $tokens
     *
     * @return TranslationInterface|null
     */
    private function parseTranslation(array &$tokens): ?TranslationInterface
    {
        /** @var TranslationRepository $repository */
        $repository = $this->entityManager->getRepository(Translation::class);

        if (!empty($tokens[0])) {
            $firstToken = $tokens[0];
            $locale = mb_strtolower(strip_tags((string) $firstToken));
            // First token is for language
            if ($locale !== null && $locale != '') {
                $translation = $repository->findOneByLocaleOrOverrideLocale($locale);
                if (null !== $translation) {
                    return $translation;
                } elseif (in_array($tokens[0], Translation::getAvailableLocales())) {
                    throw new ResourceNotFoundException(sprintf('"%s" translation was not found.', $tokens[0]));
                }
            }
        }

        return $repository->findDefault();
    }

    /**
     * @param array<string> $tokens
     * @param TranslationInterface|null $translation
     *
     * @return NodesSources|null
     */
    private function parseFromIdentifier(array &$tokens, ?TranslationInterface $translation = null): ?NodesSources
    {
        if (!empty($tokens[0])) {
            /*
             * If the only url token is not for language
             */
            if (count($tokens) > 1 || !in_array($tokens[0], Translation::getAvailableLocales())) {
                $identifier = mb_strtolower(strip_tags($tokens[(int) (count($tokens) - 1)]));
                if ($identifier !== null && $identifier != '') {
                    $array = $this->entityManager
                        ->getRepository(Node::class)
                        ->findNodeTypeNameAndSourceIdByIdentifier(
                            $identifier,
                            $translation,
                            !$this->previewResolver->isPreview()
                        );
                    if (null !== $array) {
                        /** @var NodesSources|null $nodeSource */
                        $nodeSource = $this->entityManager
                            ->getRepository($this->getNodeTypeClassname($array['name']))
                            ->findOneBy([
                                'id' => $array['id']
                            ]);
                        return $nodeSource;
                    } else {
                        throw new ResourceNotFoundException(sprintf('"%s" was not found.', $identifier));
                    }
                } else {
                    throw new ResourceNotFoundException();
                }
            }
        }
        /*
         * Resolve home page
         */
        return $this->entityManager
            ->getRepository(NodesSources::class)
            ->findOneBy([
                'node.home' => true,
                'translation' => $translation
            ]);
    }

    /**
     * @param string $name
     * @return class-string
     */
    private function getNodeTypeClassname(string $name): string
    {
        $fqcn = NodeType::getGeneratedEntitiesNamespace() . '\\NS' . ucwords($name);
        if (!class_exists($fqcn)) {
            throw new ResourceNotFoundException($fqcn . ' entity does not exist.');
        }
        return $fqcn;
    }
}
