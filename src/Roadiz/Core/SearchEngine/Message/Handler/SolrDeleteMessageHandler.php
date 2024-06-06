<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\SearchEngine\Message\Handler;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RZ\Roadiz\Core\SearchEngine\Indexer\IndexerFactory;
use RZ\Roadiz\Core\Exceptions\SolrServerNotAvailableException;
use RZ\Roadiz\Core\SearchEngine\Message\SolrReindexMessage;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class SolrDeleteMessageHandler implements MessageHandlerInterface
{
    private LoggerInterface $logger;
    private IndexerFactory $indexerFactory;

    /**
     * @param IndexerFactory $indexerFactory
     * @param LoggerInterface|null $logger
     */
    public function __construct(IndexerFactory $indexerFactory, ?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
        $this->indexerFactory = $indexerFactory;
    }

    public function __invoke(SolrReindexMessage $message)
    {
        try {
            $this->indexerFactory->getIndexerFor($message->getClassname())->delete($message->getIdentifier());
        } catch (SolrServerNotAvailableException $exception) {
            $this->logger->info($exception);
        } catch (\LogicException $exception) {
            $this->logger->error($exception);
        }
    }
}
