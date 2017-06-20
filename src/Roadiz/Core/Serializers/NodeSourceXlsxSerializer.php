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
 * @file NodeSourceXlsxSerializer.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Core\Serializers;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Utils\UrlGenerators\NodesSourcesUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\Translator;

/**
 * XLSX Serialization handler for NodeSource.
 */
class NodeSourceXlsxSerializer extends AbstractXlsxSerializer
{
    /** @var EntityManager */
    protected $em;

    /** @var Request */
    protected $request;

    protected $forceLocale = false;
    protected $addUrls = false;
    protected $onlyTexts = false;

    /**
     *
     * @param EntityManager $em
     * @param Translator $translator
     */
    public function __construct(EntityManager $em, Translator $translator)
    {
        parent::__construct($translator);
        $this->em = $em;
    }

    /**
     * Create a simple associative array with a NodeSource.
     *
     * @param NodesSources|Collection|array $nodeSource
     * @return array
     */
    public function toArray($nodeSource)
    {
        $data = [];

        if ($nodeSource instanceof NodesSources) {
            if ($this->addUrls === true) {
                $generator = new NodesSourcesUrlGenerator($this->request, $nodeSource, $this->forceLocale);
                $data['_url'] = $generator->getUrl(true);
            }

            $data['translation'] = $nodeSource->getTranslation()->getLocale();
            $data['title'] = $nodeSource->getTitle();
            $data['published_at'] = $nodeSource->getPublishedAt();
            $data['meta_title'] = $nodeSource->getMetaTitle();
            $data['meta_keywords'] = $nodeSource->getMetaKeywords();
            $data['meta_description'] = $nodeSource->getMetaDescription();

            $data = array_merge($data, $this->getSourceFields($nodeSource));
        } elseif ($nodeSource instanceof Collection || is_array($nodeSource)) {
            /*
             * If asked to serialize a nodeSource collection
             */
            foreach ($nodeSource as $singleSource) {
                $data[] = $this->toArray($singleSource);
            }
        }

        return $data;
    }

    /**
     * @param NodesSources $nodeSource
     * @return array
     */
    protected function getSourceFields(NodesSources $nodeSource)
    {
        $fields = $this->getFields($nodeSource->getNode()->getNodeType());

        /*
         * Create nodeSource default values
         */
        $sourceDefaults = [];
        foreach ($fields as $field) {
            $getter = $field->getGetterName();
            $sourceDefaults[$field->getName()] = $nodeSource->$getter();
        }

        return $sourceDefaults;
    }

    /**
     * @param NodeType $nodeType
     * @return array
     */
    protected function getFields(NodeType $nodeType)
    {
        $criteria = [
            'nodeType' => $nodeType,
        ];

        if (true === $this->onlyTexts) {
            $criteria['type'] = [
                NodeTypeField::STRING_T,
                NodeTypeField::TEXT_T,
                NodeTypeField::MARKDOWN_T,
                NodeTypeField::RICHTEXT_T,
            ];
        } else {
            $criteria['type'] = [
                NodeTypeField::STRING_T,
                NodeTypeField::DATETIME_T,
                NodeTypeField::DATE_T,
                NodeTypeField::RICHTEXT_T,
                NodeTypeField::TEXT_T,
                NodeTypeField::MARKDOWN_T,
                NodeTypeField::BOOLEAN_T,
                NodeTypeField::INTEGER_T,
                NodeTypeField::DECIMAL_T,
                NodeTypeField::EMAIL_T,
                NodeTypeField::ENUM_T,
                NodeTypeField::MULTIPLE_T,
                NodeTypeField::COLOUR_T,
                NodeTypeField::GEOTAG_T,
                NodeTypeField::MULTI_GEOTAG_T,
            ];
        }

        return $this->em->getRepository('RZ\Roadiz\Core\Entities\NodeTypeField')
            ->findBy($criteria, ['position' => 'ASC']);
    }

    /**
     * {@inheritDoc}
     */
    public function deserialize($string)
    {
        return null;
    }

    /**
     * Serialize only texts.
     *
     * @param boolean $onlyTexts
     */
    public function setOnlyTexts($onlyTexts = true)
    {
        $this->onlyTexts = (boolean) $onlyTexts;
    }

    /**
     * @param Request $request
     * @param bool $forceLocale
     */
    public function addUrls(Request $request, $forceLocale = false)
    {
        $this->addUrls = true;
        $this->request = $request;
        $this->forceLocale = (boolean) $forceLocale;
    }
}
