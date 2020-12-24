<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Routing;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\Config\NullLoader;
use Symfony\Cmf\Component\Routing\VersatileGeneratorInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Router;
use Symfony\Component\Stopwatch\Stopwatch;

class RedirectionRouter extends Router implements VersatileGeneratorInterface
{
    /**
     * @var EntityManager
     */
    protected $em;
    /**
     * @var Stopwatch|null
     */
    protected $stopwatch;

    /**
     * @param EntityManager $em
     * @param array $options
     * @param RequestContext|null $context
     * @param LoggerInterface|null $logger
     * @param Stopwatch|null $stopwatch
     */
    public function __construct(
        EntityManager $em,
        array $options = [],
        RequestContext $context = null,
        LoggerInterface $logger = null,
        Stopwatch $stopwatch = null
    ) {
        parent::__construct(
            new NullLoader(),
            null,
            $options,
            $context,
            $logger
        );
        $this->em = $em;
        $this->stopwatch = $stopwatch;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection()
    {
        return new RouteCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH): string
    {
        return '';
    }

    /**
     * Gets the UrlMatcher instance associated with this Router.
     *
     * @return RedirectionMatcher|UrlMatcherInterface A UrlMatcherInterface instance
     */
    public function getMatcher(): UrlMatcherInterface
    {
        if (null !== $this->matcher) {
            return $this->matcher;
        }

        return $this->matcher = new RedirectionMatcher(
            $this->context,
            $this->em,
            $this->stopwatch,
            $this->logger
        );
    }

    /**
     * No generator for a node router.
     */
    public function getGenerator()
    {
        throw new \BadMethodCallException(get_class($this) . ' does not support path generation.');
    }

    public function supports($name): bool
    {
        return false;
    }

    public function getRouteDebugMessage($name, array $parameters = [])
    {
        return 'RedirectionRouter does not support path generation.';
    }
}
