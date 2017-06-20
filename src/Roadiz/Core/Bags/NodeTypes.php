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
 * @file NodeTypes.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Core\Bags;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Repositories\NodeTypeRepository;
use Symfony\Component\HttpFoundation\ParameterBag;

class NodeTypes extends ParameterBag
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var NodeTypeRepository
     */
    private $repository;

    /**
     * SettingsBag constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return NodeTypeRepository
     */
    public function getRepository()
    {
        if (null === $this->repository) {
            $this->repository = $this->entityManager->getRepository('RZ\Roadiz\Core\Entities\NodeType');
        }
        return $this->repository;
    }

    protected function populateParameters()
    {
        $nodeTypes = $this->getRepository()->findAll();
        $this->parameters = [];
        /** @var NodeType $nodeType */
        foreach ($nodeTypes as $nodeType) {
            $this->parameters[$nodeType->getName()] = $nodeType;
        }
    }

    /**
     * @param string $key
     * @param null $default
     * @param bool $deep
     * @return bool|mixed
     */
    public function get($key, $default = null, $deep = false)
    {
        if (!is_array($this->parameters)) {
            $this->populateParameters();
        }

        return parent::get($key, null, false);
    }

    /**
     * @return array
     */
    public function all()
    {
        if (!is_array($this->parameters)) {
            $this->populateParameters();
        }

        return parent::all();
    }
}
