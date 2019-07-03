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

namespace RZ\Roadiz\Core\Serializers\ObjectConstructor;

use JMS\Serializer\Exception\ObjectConstructionException;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;

class NodeTypeFieldObjectConstructor extends AbstractTypedObjectConstructor
{
    /**
     * @inheritDoc
     */
    public function supports(string $className, array $data): bool
    {
        return $className === NodeTypeField::class && array_key_exists('name', $data);
    }

    /**
     * @inheritDoc
     */
    protected function findObject($data): ?object
    {
        if (null === $data['name'] || $data['name'] === '') {
            throw new ObjectConstructionException('NodeTypeField name can not be empty');
        }
        if (empty($data['nodeTypeName']) || null === $data['nodeTypeName'] || $data['nodeTypeName'] === '') {
            throw new ObjectConstructionException('nodeTypeName is missing to check duplication.');
        }

        $nodeType = $this->entityManager
            ->getRepository(NodeType::class)
            ->findOneByName($data['nodeTypeName']);

        if (null === $nodeType) {
            /*
             * Do not look for existing fields if node-type does not exist either.
             */
            return null;
        }
        return $this->entityManager
            ->getRepository(NodeTypeField::class)
            ->findOneBy([
                'name' => $data['name'],
                'nodeType' => $nodeType,
            ]);
    }

    protected function fillIdentifier(object $object, array $data): void
    {
        trigger_error('Cannot call fillIdentifier on NodeTypeField', E_USER_WARNING);
    }

    /**
     * @return bool
     */
    protected function canBeFlushed(): bool
    {
        return false;
    }
}
