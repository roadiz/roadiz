<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file RedirectionMatcher.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
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
     * RedirectionMatcher constructor.
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
            return [
                '_controller' => RedirectionController::class . '::redirectAction',
                'redirection' => $redirection,
                '_route' => null,
            ];
        }

        throw new ResourceNotFoundException();
    }

    /**
     * @param $decodedUrl
     * @return Redirection
     */
    protected function matchRedirection($decodedUrl): ?Redirection
    {
        /** @var Redirection|null $redirection */
        return $this->repository->findOneByQuery($decodedUrl);
    }
}
