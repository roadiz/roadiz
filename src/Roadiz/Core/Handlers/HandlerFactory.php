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
 * @file HandlerFactory.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Core\Handlers;

use Pimple\Container;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\Entities\NodesSources;

class HandlerFactory
{
    /**
     * @var Container
     */
    private $container;

    /**
     * HandlerFactory constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param AbstractEntity $entity
     * @return AbstractHandler
     */
    public function getHandler(AbstractEntity $entity)
    {
        if ($entity instanceof NodesSources) {
            return $this->container['nodes_sources.handler']->setNodeSource($entity);
        }

        switch (get_class($entity)) {
            case 'RZ\Roadiz\Core\Entities\Node':
                return $this->container['node.handler']->setNode($entity);
            case 'RZ\Roadiz\Core\Entities\NodeType':
                return $this->container['node_type.handler']->setNodeType($entity);
            case 'RZ\Roadiz\Core\Entities\NodeTypeField':
                return $this->container['node_type_field.handler']->setNodeTypeField($entity);
            case 'RZ\Roadiz\Core\Entities\Document':
                return $this->container['document.handler']->setDocument($entity);
            case 'RZ\Roadiz\Core\Entities\CustomForm':
                return $this->container['custom_form.handler']->setCustomForm($entity);
            case 'RZ\Roadiz\Core\Entities\CustomFormField':
                return $this->container['custom_form_field.handler']->setCustomFormField($entity);
            case 'RZ\Roadiz\Core\Entities\Folder':
                return $this->container['folder.handler']->setFolder($entity);
            case 'RZ\Roadiz\Core\Entities\Font':
                return $this->container['font.handler']->setFont($entity);
            case 'RZ\Roadiz\Core\Entities\Group':
                return $this->container['group.handler']->setGroup($entity);
            case 'RZ\Roadiz\Core\Entities\Newsletter':
                return $this->container['newsletter.handler']->setNewsletter($entity);
            case 'RZ\Roadiz\Core\Entities\Tag':
                return $this->container['tag.handler']->setTag($entity);
            case 'RZ\Roadiz\Core\Entities\Translation':
                return $this->container['translation.handler']->setTranslation($entity);
        }

        throw new \InvalidArgumentException('HandlerFactory does not support ' . get_class($entity));
    }
}
