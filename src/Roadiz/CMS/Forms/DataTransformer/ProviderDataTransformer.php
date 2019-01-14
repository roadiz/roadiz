<?php
/**
 * Copyright © 2018, Ambroise Maupate and Julien Blanchet
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
 * @file NodeTypeTransformer.php
 * @author Ambroise Maupate
 */
declare(strict_types=1);

namespace RZ\Roadiz\CMS\Forms\DataTransformer;

use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Themes\Rozier\Explorer\ExplorerProviderInterface;

class ProviderDataTransformer implements DataTransformerInterface
{
    /**
     * @var NodeTypeField
     */
    protected $nodeTypeField;

    /**
     * @var ExplorerProviderInterface
     */
    protected $provider;

    /**
     * ProviderDataTransformer constructor.
     *
     * @param NodeTypeField             $nodeTypeField
     * @param ExplorerProviderInterface $provider
     */
    public function __construct(NodeTypeField $nodeTypeField, ExplorerProviderInterface $provider)
    {
        $this->nodeTypeField = $nodeTypeField;
        $this->provider = $provider;
    }

    /**
     * @param mixed $entitiesToForm
     * @return mixed
     */
    public function transform($entitiesToForm)
    {
        if ($this->nodeTypeField->isMultiProvider() && is_array($entitiesToForm)) {
            if (count($entitiesToForm) > 0) {
                return $this->provider->getItemsById($entitiesToForm);
            }
            return [];
        } elseif ($this->nodeTypeField->isSingleProvider()) {
            if (isset($entitiesToForm)) {
                return $this->provider->getItemsById($entitiesToForm);
            }
            return null;
        }
        throw new TransformationFailedException('Provider entities cannot be transformed to form model.');
    }

    /**
     * @param mixed $formToEntities
     * @return mixed
     */
    public function reverseTransform($formToEntities)
    {
        if (is_array($formToEntities) &&
            $this->nodeTypeField->isSingleProvider() &&
            isset($formToEntities[0])) {
            return $formToEntities[0];
        }
        return array_values($formToEntities);
    }
}
