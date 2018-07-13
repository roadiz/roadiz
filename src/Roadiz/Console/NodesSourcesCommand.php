<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
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
 * @file NodesSourcesCommand.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Handlers\NodeTypeHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command line utils for managing node-types from terminal.
 */
class NodesSourcesCommand extends Command
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    protected function configure()
    {
        $this->setName('generate:nsentities')
            ->setDescription('Generate node-sources entities classes.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager entityManager */
        $this->entityManager = $this->getHelper('entityManager')->getEntityManager();
        $text = "";

        $nodetypes = $this->entityManager
            ->getRepository(NodeType::class)
            ->findAll();

        if (count($nodetypes) > 0) {
            /** @var NodeType $nt */
            foreach ($nodetypes as $nt) {
                /** @var NodeTypeHandler $handler */
                $handler = $this->getHelper('handlerFactory')->getHandler($nt);
                $handler->removeSourceEntityClass();
                $text .= '<info>' . $handler->generateSourceEntityClass() . '</info>' . PHP_EOL;
            }
        } else {
            $text = '<info>No available node-types…</info>' . PHP_EOL;
        }

        $output->writeln($text);
    }
}
