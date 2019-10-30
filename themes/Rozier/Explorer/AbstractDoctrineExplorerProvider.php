<?php
/**
 * Copyright (c) 2019. Ambroise Maupate and Julien Blanchet
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
 */

declare(strict_types=1);

namespace Themes\Rozier\Explorer;

use RZ\Roadiz\Core\ListManagers\EntityListManager;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @package Themes\Rozier\Explorer
 */
abstract class AbstractDoctrineExplorerProvider extends AbstractExplorerProvider
{
    /**
     * @return string
     */
    abstract protected function getProvidedClassname(): string;

    /**
     * @return array
     */
    abstract protected function getDefaultCriteria(): array;

    /**
     * @return array
     */
    abstract protected function getDefaultOrdering(): array;

    /**
     * @param array $options
     *
     * @return EntityListManager
     */
    protected function doFetchItems(array $options = []): EntityListManager
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);

        $listManager = new EntityListManager(
            $this->get('requestStack')->getCurrentRequest(),
            $this->get('em'),
            $this->getProvidedClassname(),
            $this->getDefaultCriteria(),
            $this->getDefaultOrdering()
        );
        $listManager->setDisplayingNotPublishedNodes(true);
        $listManager->setItemPerPage($this->options['itemPerPage']);
        $listManager->handle();
        $listManager->setPage($this->options['page']);

        return $listManager;
    }
    /**
     * @inheritDoc
     */
    public function getItems($options = [])
    {
        $listManager = $this->doFetchItems($options);

        $items = [];
        foreach ($listManager->getEntities() as $entity) {
            $items[] = $this->toExplorerItem($entity);
        }

        return $items;
    }

    /**
     * @inheritDoc
     */
    public function getFilters($options = [])
    {
        $listManager = $this->doFetchItems($options);

        return $listManager->getAssignation();
    }

    /**
     * @inheritDoc
     */
    public function getItemsById($ids = [])
    {
        if (is_array($ids) && count($ids) > 0) {
            $entities = $this->get('em')->getRepository($this->getProvidedClassname())->findBy([
                'id' => $ids
            ]);

            $items = [];
            foreach ($entities as $entity) {
                $items[] = $this->toExplorerItem($entity);
            }

            return $items;
        }

        return [];
    }
}
