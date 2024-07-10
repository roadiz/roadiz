<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\SearchEngine\Message\Handler;

use Psr\Container\ContainerExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RZ\Roadiz\Core\Exceptions\SolrServerNotAvailableException;
use RZ\Roadiz\Core\Exceptions\SolrServerNotConfiguredException;
use RZ\Roadiz\Core\SearchEngine\Indexer\IndexerFactory;
use RZ\Roadiz\Core\SearchEngine\Message\AbstractSolrMessage;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class SolrReindexMessageHandler implements MessageHandlerInterface
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

    public function __invoke(AbstractSolrMessage $message)
    {
        if (null === $message->getIdentifier()) {
            return;
        }
        try {
            $this->indexerFactory->getIndexerFor($message->getClassname())->index($message->getIdentifier());
        } catch (SolrServerNotConfiguredException $exception) {
            // do nothing
        } catch (SolrServerNotAvailableException $exception) {
            $this->logger->info($exception);
        } catch (\LogicException $exception) {
            $this->logger->error($exception);
        } catch (ContainerExceptionInterface $e) {
            $this->logger->error($e);
        }
    }
}
