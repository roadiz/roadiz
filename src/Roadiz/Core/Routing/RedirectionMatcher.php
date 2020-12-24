<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Routing;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\CMS\Controllers\RedirectionController;
use RZ\Roadiz\Core\Entities\Redirection;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * UrlMatcher which tries to grab Node and Translation
 * information for a route.
 */
class RedirectionMatcher extends UrlMatcher
{
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var Stopwatch
     */
    private $stopwatch;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var EntityRepository
     */
    private $repository;

    /**
     * @param RequestContext $context
     * @param EntityManager $entityManager
     * @param Stopwatch $stopwatch
     * @param LoggerInterface $logger
     */
    public function __construct(
        RequestContext $context,
        EntityManager $entityManager,
        Stopwatch $stopwatch,
        LoggerInterface $logger
    ) {
        parent::__construct(new RouteCollection(), $context);
        $this->entityManager = $entityManager;
        $this->stopwatch = $stopwatch;
        $this->logger = $logger;
    }
    /**
     * {@inheritdoc}
     */
    public function match($pathinfo)
    {
        if (null !== $this->stopwatch) {
            $this->stopwatch->start('findRedirection');
        }

        $this->repository = $this->entityManager->getRepository(Redirection::class);
        $decodedUrl = rawurldecode($pathinfo);

        /*
         * Try nodes routes
         */
        if (null !== $redirection = $this->matchRedirection($decodedUrl)) {
            $this->logger->debug('Matched redirection.', ['query' => $redirection->getQuery()]);
            if (null !== $this->stopwatch) {
                $this->stopwatch->stop('findRedirection');
            }
            return [
                '_controller' => RedirectionController::class . '::redirectAction',
                'redirection' => $redirection,
                '_route' => null,
            ];
        }
        if (null !== $this->stopwatch) {
            $this->stopwatch->stop('findRedirection');
        }

        throw new ResourceNotFoundException(sprintf('%s did not match any Doctrine Redirection', $pathinfo));
    }

    /**
     * @param string $decodedUrl
     * @return Redirection|null
     */
    protected function matchRedirection(string $decodedUrl): ?Redirection
    {
        return $this->repository->findOneByQuery($decodedUrl);
    }
}
