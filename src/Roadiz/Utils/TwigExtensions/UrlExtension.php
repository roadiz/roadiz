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
 * @file UrlExtension.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils\TwigExtensions;

use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Utils\UrlGenerators\NodesSourcesUrlGenerator;
use Symfony\Component\HttpFoundation\Request;

/**
 * Extension that allow render nodes, nodesSources and documents Url
 */
class UrlExtension extends \Twig_Extension
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getName()
    {
        return 'urlExtension';
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('url', [$this, 'getUrl']),
        ];
    }

    public function getUrl(AbstractEntity $mixed, array $criteria = [])
    {
        if ($mixed instanceof Document) {
            return $mixed->getViewer()->getDocumentUrlByArray($criteria);
        } elseif ($mixed instanceof NodesSources) {
            return $this->getNodesSourceUrl($mixed, $criteria);
        } elseif ($mixed instanceof Node) {
            return $this->getNodeUrl($mixed, $criteria);
        } else {
            throw new \RuntimeException("Twig “url” filter can be only used with a Document, a NodesSources or a Node", 1);
        }
    }

    public function getNodesSourceUrl(NodesSources $ns, array $criteria = [])
    {
        $urlGenerator = new NodesSourcesUrlGenerator(
            $this->request,
            $ns
        );
        if (isset($criteria['absolute'])) {
            return $urlGenerator->getUrl((boolean) $criteria['absolute']);
        }
        return $urlGenerator->getUrl(false);
    }

    public function getNodeUrl(Node $node, array $criteria = [])
    {
        return $this->getNodesSourceUrl($node->getNodeSources()->first(), $criteria);
    }
}
