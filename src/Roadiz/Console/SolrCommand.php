<?php
/**
 * Copyright (c) 2016. Ambroise Maupate and Julien Blanchet
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
 * @author ambroisemaupate
 */
namespace RZ\Roadiz\Console;

use Doctrine\ORM\EntityManager;
use Solarium\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command line utils for managing nodes from terminal.
 */
class SolrCommand extends Command
{
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
        $this->entityManager = $this->getHelperSet()->get('em')->getEntityManager();
        $this->solr = $this->getHelperSet()->get('solr')->getSolr();

        $text = "";

        if (null !== $this->solr) {
            if (true === $this->getHelperSet()->get('solr')->ready()) {
                $text .= '<info>Solr search engine server is running…</info>' . PHP_EOL;
            } else {
                $text .= '<error>Solr search engine server does not respond…</error>' . PHP_EOL;
                $text .= 'See your config.yml file to correct your Solr connexion settings.' . PHP_EOL;
            }
        } else {
            $text .= $this->displayBasicConfig();
        }

        $output->writeln($text);
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
     *
     * @param  OutputInterface $output
     */
    protected function emptySolr(OutputInterface $output)
    {
        $update = $this->solr->createUpdate();
        $update->addDeleteQuery('*:*');
        $update->addCommit();
        $this->solr->update($update);
    }

    /**
     * Send an optimize and commit update query to Solr.
     *
     * @param  OutputInterface $output
     */
    protected function optimizeSolr(OutputInterface $output)
    {
        $optimizeUpdate = $this->solr->createUpdate();
        $optimizeUpdate->addOptimize(true, true, 5);
        $this->solr->update($optimizeUpdate);

        $finalCommitUpdate = $this->solr->createUpdate();
        $finalCommitUpdate->addCommit(true, true, false);
        $this->solr->update($finalCommitUpdate);
    }
}
