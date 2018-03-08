<?php
/**
 * Copyright (c) 2018. Ambroise Maupate and Julien Blanchet
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
 * @file ExplorerProviderItemTransformer.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\CMS\Forms\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Themes\Rozier\Explorer\ExplorerItemInterface;
use Themes\Rozier\Explorer\ExplorerProviderInterface;

class ExplorerProviderItemTransformer implements DataTransformerInterface
{
    /**
     * @var ExplorerProviderInterface
     */
    protected $explorerProvider;

    /**
     * @param ExplorerProviderInterface $explorerProvider
     */
    public function __construct(ExplorerProviderInterface $explorerProvider)
    {
        $this->explorerProvider = $explorerProvider;
    }

    /**
     * @inheritDoc
     */
    public function transform($value)
    {
        if (!empty($value) && $this->explorerProvider->supports($value)) {
            $item = $this->explorerProvider->toExplorerItem($value);
            if (null === $item) {
                throw new TransformationFailedException();
            }
            return [$item];
        } elseif (!empty($value) && is_array($value)) {
            $idArray = [];
            foreach ($value as $entity) {
                if ($this->explorerProvider->supports($entity)) {
                    $item = $this->explorerProvider->toExplorerItem($entity);
                    if (null === $item) {
                        throw new TransformationFailedException();
                    }
                    $idArray[] = $item;
                } else {
                    throw new TransformationFailedException();
                }
            }

            return array_filter($idArray);
        }
        return '';
    }

    /**
     * @inheritDoc
     */
    public function reverseTransform($value)
    {
        /** @var ExplorerItemInterface[] $items */
        $items = $this->explorerProvider->getItemsById($value);
        $originals = [];
        /** @var ExplorerItemInterface $item */
        foreach ($items as $item) {
            $originals[] = $item->getOriginal();
        }

        return array_filter($originals);
    }
}
