<?php
/*
 * Copyright REZO ZERO 2014
 *
 *
 * @file NodesCommand.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Renzo\Console;

use RZ\Renzo\Core\Kernel;
use RZ\Renzo\Core\Entities\Node;
use RZ\Renzo\Core\Entities\NodesSources;
use RZ\Renzo\Core\SearchEngine\SolariumNodeSource;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Command line utils for managing nodes from terminal.
 */
class SolrCommand extends Command
{
    private $dialog;

    protected function configure()
    {
        $this->setName('solr')
            ->setDescription('Manage Solr search engine index')
            ->addOption(
                'reset',
                null,
                InputOption::VALUE_NONE,
                'Reset Solr search engine index'
            )
            ->addOption(
                'reindex',
                null,
                InputOption::VALUE_NONE,
                'Reindex every NodesSources into Solr'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dialog = $this->getHelperSet()->get('dialog');
        $text="";

        $solr = Kernel::getInstance()->getSolrService();
        if (null !== $solr) {

            if (true === Kernel::getInstance()->pingSolrServer()) {

                if ($input->getOption('reset')) {

                    if ($this->dialog->askConfirmation(
                        $output,
                        '<question>Are you sure to reset Solr index?</question> : ',
                        false
                    )) {

                        $update = $solr->createUpdate();
                        $update->addDeleteQuery('*:*');
                        $update->addCommit();
                        $result = $solr->update($update);

                        $text = '<info>Solr index resetted…</info>'.PHP_EOL;
                    }

                } elseif ($input->getOption('reindex')) {
                    if ($this->dialog->askConfirmation(
                        $output,
                        '<question>Are you sure to reindex your Node database?</question> : ',
                        false
                    )) {
                        $stopwatch = new Stopwatch();
                        $stopwatch->start('global');
                        $this->reindexNodeSources($solr, $output);
                        $stopwatch->stop('global');

                        $duration = $stopwatch->getEvent('global')->getDuration();

                        $text = PHP_EOL.sprintf('<info>Node database has been re-indexed in %.2d ms.</info>', $duration).PHP_EOL;
                    }
                } else {
                    $text .= '<info>Solr search engine server is running…</info>'.PHP_EOL;
                }
            } else {
                $text .= '<error>Solr search engine server does not respond…</error>'.PHP_EOL;
                $text .= 'See your config.json file to correct your Solr connexion settings.'.PHP_EOL;
            }
        } else {
            $text .= '<error>No Solr search engine server has been configured…</error>'.PHP_EOL;
            $text .= 'Personnalize your config.json file to enable Solr (sample):'.PHP_EOL;
            $text .=
            '"solr": {
                "endpoint": {
                    "localhost": {
                        "host":"localhost",
                        "port":"8983",
                        "path":"/solr",
                        "core":"mycore",
                        "timeout":3,
                        "username":"",
                        "password":""
                    }
                }
            }';
        }

        $output->writeln($text);
    }

    /**
     * Delete Solr index and loop over every NodesSources to index them again.
     *
     * @param \Solarium\Client $solr
     * @param OutputInterface  $output
     */
    private function reindexNodeSources(\Solarium\Client $solr, OutputInterface $output)
    {
        $update = $solr->createUpdate();

        // Empty first
        $update->addDeleteQuery('*:*');

        // Then index
        $nSources = Kernel::getService('em')
            ->getRepository('RZ\Renzo\Core\Entities\NodesSources')
            ->findAll();

        $progress = new ProgressBar($output, count($nSources));
        $progress->setFormat('verbose');
        $progress->start();

        foreach ($nSources as $ns) {
            $solariumNS = new SolariumNodeSource($ns, $solr);
            $solariumNS->setDocument($update->createDocument());
            $solariumNS->index();
            $update->addDocument($solariumNS->getDocument());

            $progress->advance();
        }

        $update->addCommit();

        // optimize the index
        $update->addOptimize(true, false, 5);

        $solr->update($update);
        $progress->finish();
    }
}
