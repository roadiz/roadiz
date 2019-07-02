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
use RZ\Roadiz\Core\Entities\Group;

class GroupObjectConstructor extends AbstractTypedObjectConstructor
{
    /**
     * @inheritDoc
     */
    public function supports(string $className, array $data): bool
    {
        return $className === Group::class && array_key_exists('name', $data);
    }

    /**
     * @inheritDoc
     */
    protected function findObject($data): ?object
    {
        if (null === $data['name'] || $data['name'] === '') {
            throw new ObjectConstructionException('Group name can not be empty');
        }
        return $this->entityManager
            ->getRepository(Group::class)
            ->findOneByName($data['name']);
    }

    protected function fillIdentifier(object $object, array $data): void
    {
        if ($object instanceof Group) {
            $object->setName($data['name']);
        }
    }
}
