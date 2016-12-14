<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
 * @file DocumentExtension.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Utils\TwigExtensions;

use RZ\Roadiz\Core\Entities\Document;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;

/**
 * Extension that allow render document images
 */
class DocumentExtension extends \Twig_Extension
{

    public function getName()
    {
        return 'documentExtension';
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('display', [$this, 'display'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param Document|null $document
     * @param array $criteria
     * @return string
     * @throws \Twig_Error_Runtime
     */
    public function display(Document $document = null, array $criteria = [])
    {
        if (null === $document) {
            throw new \Twig_Error_Runtime('Document can’t be null to be displayed.');
        }
        try {
            return $document->getViewer()->getDocumentByArray($criteria);
        } catch (InvalidArgumentException $e) {
            throw new \Twig_Error_Runtime($e->getMessage(), -1, null, $e);
        }
    }
}
