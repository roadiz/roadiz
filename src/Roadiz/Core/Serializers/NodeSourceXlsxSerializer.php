<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Serializers;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * XLSX Serialization handler for NodeSource.
 */
class NodeSourceXlsxSerializer extends AbstractXlsxSerializer
{
    protected EntityManagerInterface $em;
    protected Request $request;
    protected UrlGeneratorInterface $urlGenerator;
    protected bool $forceLocale = false;
    protected bool $addUrls = false;
    protected bool $onlyTexts = false;

    /**
     *
     * @param EntityManagerInterface $em
     * @param TranslatorInterface $translator
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        UrlGeneratorInterface $urlGenerator
    ) {
        parent::__construct($translator);
        $this->em = $em;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Create a simple associative array with a NodeSource.
     *
     * @param NodesSources|Collection|array|null $nodeSource
     * @return array
     */
    public function toArray($nodeSource)
    {
        $data = [];

        if ($nodeSource instanceof NodesSources) {
            if ($this->addUrls === true) {
                $data['_url'] = $this->urlGenerator->generate(
                    RouteObjectInterface::OBJECT_BASED_ROUTE_NAME,
                    [
                        RouteObjectInterface::ROUTE_OBJECT => $nodeSource
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
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

        return $this->em->getRepository(NodeTypeField::class)
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
     * @param bool $onlyTexts
     */
    public function setOnlyTexts(bool $onlyTexts = true)
    {
        $this->onlyTexts = (boolean) $onlyTexts;
    }

    /**
     * @param Request $request
     * @param bool $forceLocale
     */
    public function addUrls(Request $request, bool $forceLocale = false)
    {
        $this->addUrls = true;
        $this->request = $request;
        $this->forceLocale = (boolean) $forceLocale;
    }
}
