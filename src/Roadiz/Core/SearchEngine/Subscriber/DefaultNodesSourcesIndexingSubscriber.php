<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\SearchEngine\Subscriber;

use Doctrine\Common\Collections\Criteria;
use RZ\Roadiz\Core\AbstractEntities\AbstractField;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Events\NodesSources\NodesSourcesIndexingEvent;
use RZ\Roadiz\Core\Handlers\HandlerFactory;
use RZ\Roadiz\Core\Handlers\NodesSourcesHandler;
use RZ\Roadiz\Core\SearchEngine\SolariumNodeSource;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class DefaultNodesSourcesIndexingSubscriber implements EventSubscriberInterface
{
    /**
     * @var HandlerFactory
     */
    private $handlerFactory;

    /**
     * DefaultNodesSourcesIndexingSubscriber constructor.
     *
     * @param HandlerFactory $handlerFactory
     */
    public function __construct(HandlerFactory $handlerFactory)
    {
        $this->handlerFactory = $handlerFactory;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            NodesSourcesIndexingEvent::class => ['onIndexing', -1000],
        ];
    }

    public function onIndexing(NodesSourcesIndexingEvent $event)
    {
        $nodeSource = $event->getNodeSource();
        $subResource = $event->isSubResource();
        $assoc = $event->getAssociations();
        $collection = [];
        $node = $nodeSource->getNode();

        if (null === $node) {
            throw new \RuntimeException("No node relation found for source: " . $nodeSource->getTitle(), 1);
        }

        // Need a documentType field
        $assoc[SolariumNodeSource::TYPE_DISCRIMINATOR] = SolariumNodeSource::DOCUMENT_TYPE;
        // Need a nodeSourceId field
        $assoc[SolariumNodeSource::IDENTIFIER_KEY] = $nodeSource->getId();
        $assoc['node_type_s'] = $node->getNodeType()->getName();
        $assoc['node_name_s'] = $node->getNodeName();
        $assoc['node_status_i'] = $node->getStatus();
        $assoc['node_visible_b'] = $node->isVisible();

        // Need a locale field
        $locale = $nodeSource->getTranslation()->getLocale();
        $lang = \Locale::getPrimaryLanguage($locale);
        $assoc['locale_s'] = $locale;

        /*
         * Index resource title
         */
        $assoc['title'] = $nodeSource->getTitle();
        $assoc['title_txt_' . $lang] = $nodeSource->getTitle();

        $assoc['created_at_dt'] = $node->getCreatedAt()
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s\Z');
        $assoc['updated_at_dt'] = $node->getUpdatedAt()
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s\Z');

        if (null !== $nodeSource->getPublishedAt()) {
            $assoc['published_at_dt'] = $nodeSource->getPublishedAt()
                ->setTimezone(new \DateTimeZone('UTC'))
                ->format('Y-m-d\TH:i:s\Z');
        }

        /*
         * Do not index locale and tags if this is a sub-resource
         */
        if (!$subResource) {
            $collection[] = $nodeSource->getTitle();
            /*
             * Index parent node ID and name to filter on it
             */
            $parent = $node->getParent();
            if (null !== $parent) {
                $assoc['node_parent_i'] = $parent->getId();
                $assoc['node_parent_s'] = $parent->getNodeName();
            }

            /** @var NodesSourcesHandler $handler */
            $handler = $this->handlerFactory->getHandler($nodeSource);
            $out = array_map(
                function (Tag $x) {
                    return $x->getTranslatedTags()->first() ?
                        $x->getTranslatedTags()->first()->getName() :
                        $x->getTagName();
                },
                $handler->getTags()
            );
            // Use tags_txt to be compatible with other data types
            $assoc['tags_txt'] = $out;
        }

        $criteria = new Criteria();
        $criteria->andWhere(Criteria::expr()->eq("type", AbstractField::BOOLEAN_T));
        $booleanFields = $node->getNodeType()->getFields()->matching($criteria);

        /** @var NodeTypeField $booleanField */
        foreach ($booleanFields as $booleanField) {
            $name = $booleanField->getName();
            $name .= '_b';
            $getter = $booleanField->getGetterName();
            $assoc[$name] = $nodeSource->$getter();
        }

        $searchableFields = $node->getNodeType()->getSearchableFields();
        /** @var NodeTypeField $field */
        foreach ($searchableFields as $field) {
            $name = $field->getName();
            $getter = $field->getGetterName();
            $content = $nodeSource->$getter();
            /*
             * Strip markdown syntax
             */
            $content = $event->getSolariumDocument()->cleanTextContent($content);
            /*
             * Use locale to create field name
             * with right language
             */
            if (in_array($lang, SolariumNodeSource::$availableLocalizedTextFields)) {
                $name .= '_txt_' . $lang;
            } else {
                $name .= '_t';
            }

            $assoc[$name] = $content;
            $collection[] = $content;
        }

        /*
         * Collect data in a single field
         * for global search
         */
        $assoc['collection_txt'] = $collection;
        $event->setAssociations($assoc);
    }
}
