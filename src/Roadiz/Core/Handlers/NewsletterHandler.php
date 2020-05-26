<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Handlers;

use RZ\Roadiz\Core\Entities\Newsletter;
use RZ\Roadiz\Utils\Node\NodeDuplicator;

/**
 * Handle operations with newsletters entities.
 */
class NewsletterHandler extends AbstractHandler
{
    /**
     * @var Newsletter
     */
    private $newsletter;

    /**
     * @return Newsletter
     */
    public function getNewsletter()
    {
        return $this->newsletter;
    }

    /**
     * @param Newsletter|null $newsletter
     *
     * @return $this
     */
    public function setNewsletter(Newsletter $newsletter = null)
    {
        $this->newsletter = $newsletter;
        return $this;
    }

    /**
     * Duplicate newsletter
     */
    public function duplicate()
    {
        $duplicator = new NodeDuplicator($this->newsletter->getNode(), $this->objectManager);
        $newNode = $duplicator->duplicate();
        $this->objectManager->persist($newNode);

        $this->objectManager->refresh($this->newsletter);
        $newsletter = clone $this->newsletter;
        $this->objectManager->persist($newsletter);

        $newsletter->setNode($newNode);

        $this->objectManager->flush();

        return $newsletter;
    }
}
