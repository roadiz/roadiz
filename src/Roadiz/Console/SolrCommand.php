<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use Doctrine\ORM\EntityManagerInterface;
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
    protected ?SymfonyStyle $io = null;
    protected ?EntityManagerInterface $entityManager = null;
    protected ?Client $solr = null;

    protected function configure()
    {
        $this->setName('solr:check')
            ->setDescription('Check Solr search engine server');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->entityManager = $this->getHelper('doctrine')->getEntityManager();
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
            $this->displayBasicConfig();
        }
        return 0;
    }

    protected function displayBasicConfig(): void
    {
        if (null !== $this->io) {
            $this->io->error('No Solr search engine server has been configured…');
            $this->io->note(<<<EOD
Edit your app/config.yml file to enable Solr (example):

solr:
    endpoint:
        localhost:
            host: "localhost"
            port: "8983"
            path: "/"
            core: "roadiz"
            timeout: 3
            username: ""
            password: ""
EOD);
        }
    }
}
