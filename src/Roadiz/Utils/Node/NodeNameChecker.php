<?php
/**
 * Copyright © 2016, Ambroise Maupate and Julien Blanchet
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
 *
 * @file NodeNameChecker.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils\Node;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Utils\StringHandler;

/**
 * Class NodeNameChecker
 * @package RZ\Roadiz\Utils\Node
 */
abstract class NodeNameChecker
{
    /**
     * Test if current node name is suffixed with a 13 chars Unique ID (uniqid()).
     *
     * @param string $canonicalNodeName Node name without uniqid after.
     * @param string $nodeName Node name to test
     * @return bool
     */
    public static function isNodeNameWithUniqId($canonicalNodeName, $nodeName)
    {
        $pattern = '#^' . preg_quote($canonicalNodeName) . '\-[0-9a-z]{13}$#';
        $returnState = preg_match_all($pattern, $nodeName);

        if (1 === $returnState) {
            return true;
        }

        return false;
    }

    /**
     * Test if node’s name is already used as a name or an url-alias.
     *
     * @param string $nodeName
     * @param EntityManager $entityManager
     * @return bool
     */
    public static function isNodeNameAlreadyUsed($nodeName, EntityManager $entityManager)
    {
        $nodeName = StringHandler::slugify($nodeName);

        if (false === (boolean) $entityManager
                ->getRepository('RZ\Roadiz\Core\Entities\UrlAlias')
                ->exists($nodeName) &&
            false === (boolean) $entityManager
                ->getRepository('RZ\Roadiz\Core\Entities\Node')
                ->setDisplayingNotPublishedNodes(true)
                ->exists($nodeName)) {
            return false;
        }
        return true;
    }
}
