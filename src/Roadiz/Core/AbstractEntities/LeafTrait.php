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
 * @file LeafTrait.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace RZ\Roadiz\Core\AbstractEntities;

use Doctrine\Common\Collections\Collection;

trait LeafTrait
{
    use PositionedTrait;

    /**
     * @var LeafInterface
     */
    protected $parent;

    /**
     * @var Collection
     */
    protected $children;

    /**
     * @return Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param LeafInterface $child
     * @return $this
     */
    public function addChild(LeafInterface $child)
    {
        if (!$this->getChildren()->contains($child)) {
            $this->getChildren()->add($child);
            $child->setParent($this);
        }

        return $this;
    }
    /**
     * @param LeafInterface $child
     * @return $this
     */
    public function removeChild(LeafInterface $child)
    {
        if ($this->getChildren()->contains($child)) {
            $this->getChildren()->removeElement($child);
            $child->setParent(null);
        }

        return $this;
    }

    /**
     * @return LeafInterface parent
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param LeafInterface $parent
     * @return $this
     */
    public function setParent(LeafInterface $parent = null)
    {
        if ($parent === $this) {
            throw new \InvalidArgumentException('An entity cannot have itself as a parent.');
        }

        $this->parent = $parent;
        if (null !== $this->parent) {
            $this->parent->addChild($this);
        }

        return $this;
    }

    /**
     * Return every tag’s parents.
     *
     * @return LeafInterface[]
     */
    public function getParents()
    {
        $parentsArray = [];
        $parent = $this;

        do {
            $parent = $parent->getParent();
            if ($parent !== null) {
                $parentsArray[] = $parent;
            } else {
                break;
            }
        } while ($parent !== null);

        return array_reverse($parentsArray);
    }

    /**
     * Gets the nodes depth.
     *
     * @return int
     */
    public function getDepth()
    {
        if ($this->getParent() === null) {
            return 0;
        }
        return $this->getParent()->getDepth() + 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return $this->getChildren()->getIterator();
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->getChildren()->count();
    }
}
