<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
 * @file NodeRouter.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Routing;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Stopwatch\Stopwatch;

class NodeRouter extends Router
{
    protected $em;
    protected $stopwatch;
    /**
     * @var AuthorizationChecker
     */
    protected $authorizationChecker;
    /**
     * @var bool
     */
    protected $preview;

    /**
     * NodeRouter constructor.
     *
     * @param EntityManager $em
     * @param array $options
     * @param RequestContext|null $context
     * @param LoggerInterface|null $logger
     * @param Stopwatch|null $stopwatch
     * @param AuthorizationChecker|null $authorizationChecker
     * @param bool $preview
     */
    public function __construct(
        EntityManager $em,
        array $options = [],
        RequestContext $context = null,
        LoggerInterface $logger = null,
        Stopwatch $stopwatch = null,
        AuthorizationChecker $authorizationChecker = null,
        $preview = false
    ) {
        $this->em = $em;
        $this->stopwatch = $stopwatch;
        $this->logger = $logger;
        $this->context = $context ?: new RequestContext();
        $this->setOptions($options);
        $this->authorizationChecker = $authorizationChecker;
        $this->preview = $preview;
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
    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        return "";
    }

    /**
     * Gets the UrlMatcher instance associated with this Router.
     *
     * @return UrlMatcherInterface A UrlMatcherInterface instance
     */
    public function getMatcher()
    {
        if (null !== $this->matcher) {
            return $this->matcher;
        }
        return $this->matcher = new NodeUrlMatcher(
            $this->context,
            $this->em,
            $this->stopwatch,
            $this->logger,
            $this->authorizationChecker,
            $this->preview
        );
    }

    /**
     * No generator for a node router.
     *
     * @return null
     */
    public function getGenerator()
    {
        return null;
    }
}
