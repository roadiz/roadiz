<?php
/**
 * Copyright © 2014, Ambroise Maupate and Julien Blanchet
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
 * @file SolrCommand.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\SearchEngine\SolariumNodeSource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
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
        $text = "";

        $solr = Kernel::getService('solr');

        if (null !== $solr) {
            if (true === Kernel::getService('solr.ready')) {
                if ($input->getOption('reset')) {
                    if ($this->dialog->askConfirmation(
                        $output,
                        '<question>Are you sure to reset Solr index?</question> : ',
                        false
                    )) {
                        $update = $solr->createUpdate();
                        $update->addDeleteQuery('*:*');
                        $update->addCommit();
                        $solr->update($update);

                        $text = '<info>Solr index resetted…</info>' . PHP_EOL;
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

                        $text = PHP_EOL . sprintf('<info>Node database has been re-indexed in %.2d ms.</info>', $duration) . PHP_EOL;
                    }
                } else {
                    $text .= '<info>Solr search engine server is running…</info>' . PHP_EOL;
                }
            } else {
                $text .= '<error>Solr search engine server does not respond…</error>' . PHP_EOL;
                $text .= 'See your config.yml file to correct your Solr connexion settings.' . PHP_EOL;
            }
        } else {
            $text .= '<error>No Solr search engine server has been configured…</error>' . PHP_EOL;
            $text .= 'Personnalize your config.yml file to enable Solr (sample):' . PHP_EOL;
            $text .= '
solr:
    endpoint:
        localhost:
            host:"localhost"
            port:"8983"
            path:"/solr"
            core:"mycore"
            timeout:3
            username:""
            password:""
            ';
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
        $solr->update($update);
        $update->addCommit();

        /*
         * Use buffered insertion
         */
        $buffer = $solr->getPlugin('bufferedadd');
        $buffer->setBufferSize(100);

        // Then index
        $nSources = Kernel::getService('em')
            ->getRepository('RZ\Roadiz\Core\Entities\NodesSources')
            ->findAll();

        $progress = new ProgressBar($output, count($nSources));
        $progress->setFormat('verbose');
        $progress->start();

        foreach ($nSources as $ns) {
            $solariumNS = new SolariumNodeSource($ns, $solr);
            $solariumNS->setDocument($update->createDocument());
            $solariumNS->index();
            $buffer->addDocument($solariumNS->getDocument());
            $progress->advance();
        }

        $buffer->flush();

        // optimize the index
        $update->addOptimize(true, false, 5);

        $progress->finish();
    }
}
