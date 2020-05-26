<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use Doctrine\ORM\EntityManager;
use Solarium\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command line utils for managing nodes from terminal.
 */
class SolrCommand extends Command
{
    /**
     * @var SymfonyStyle
     */
    protected $io;

    /** @var EntityManager */
    protected $entityManager;

    /** @var Client */
    protected $solr;

    protected function configure()
    {
        $this->setName('solr:check')
            ->setDescription('Check Solr search engine server');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->entityManager = $this->getHelper('entityManager')->getEntityManager();
        $this->solr = $this->getHelper('solr')->getSolr();
        $this->io = new SymfonyStyle($input, $output);

        if (null !== $this->solr) {
            if (true === $this->getHelper('solr')->ready()) {
                $this->io->writeln('<info>Solr search engine server is running…</info>');
            } else {
                $this->io->error('Solr search engine server does not respond…');
                $this->io->note('See your config.yml file to correct your Solr connexion settings.');
                return 1;
            }
        } else {
            $this->io->note($this->displayBasicConfig());
        }
        return 0;
    }

    protected function displayBasicConfig()
    {
        $text = '<error>No Solr search engine server has been configured…</error>' . PHP_EOL;
        $text .= 'Personnalize your config.yml file to enable Solr (sample):' . PHP_EOL;
        $text .= '
solr:
    endpoint:
        localhost:
            host: "localhost"
            port: "8983"
            path: "/solr"
            core: "mycore"
            timeout: 3
            username: ""
            password: ""
            ';

        return $text;
    }

    /**
     * Empty Solr index.
     */
    protected function emptySolr(): void
    {
        $update = $this->solr->createUpdate();
        $update->addDeleteQuery('*:*');
        $update->addCommit();
        $this->solr->update($update);
    }

    /**
     * Send an optimize and commit update query to Solr.
     */
    protected function optimizeSolr(): void
    {
        $optimizeUpdate = $this->solr->createUpdate();
        $optimizeUpdate->addOptimize(true, true, 5);
        $this->solr->update($optimizeUpdate);

        $finalCommitUpdate = $this->solr->createUpdate();
        $finalCommitUpdate->addCommit(true, true, false);
        $this->solr->update($finalCommitUpdate);
    }
}
