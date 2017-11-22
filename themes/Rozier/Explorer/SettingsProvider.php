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
 * @file SettingsProvider.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace Themes\Rozier\Explorer;

use RZ\Roadiz\Core\Entities\Setting;
use RZ\Roadiz\Core\ListManagers\EntityListManager;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingsProvider extends AbstractExplorerProvider
{
    /**
     * @inheritDoc
     */
    public function supports($item)
    {
        if ($item instanceof Setting) {
            return true;
        }

        return false;
    }


    /**
     * @inheritDoc
     */
    public function toExplorerItem($item)
    {
        if ($item instanceof Setting) {
            return new SettingExplorerItem($item);
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function getItems($options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);

        $listManager = new EntityListManager(
            $this->get('request'),
            $this->get('em'),
            'RZ\Roadiz\Core\Entities\Setting',
            [],
            ['name' =>'ASC']
        );
        $listManager->setDisplayingNotPublishedNodes(true);
        $listManager->setItemPerPage($this->options['itemPerPage']);
        $listManager->handle();
        $listManager->setPage($this->options['page']);

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
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);

        $listManager = new EntityListManager(
            $this->get('request'),
            $this->get('em'),
            'RZ\Roadiz\Core\Entities\Setting',
            [],
            ['name' =>'ASC']
        );
        $listManager->setDisplayingNotPublishedNodes(true);
        $listManager->setItemPerPage($this->options['itemPerPage']);
        $listManager->handle();
        $listManager->setPage($this->options['page']);

        return $listManager->getAssignation();
    }

    /**
     * @inheritDoc
     */
    public function getItemsById($ids = [])
    {
        if (count($ids) > 0) {
            $entities = $this->get('em')->getRepository('RZ\Roadiz\Core\Entities\Setting')->findBy([
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
