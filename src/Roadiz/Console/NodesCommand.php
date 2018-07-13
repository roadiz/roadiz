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
 * @file NodesCommand.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeType;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command line utils for managing nodes from terminal.
 */
class NodesCommand extends Command
{
    private $entityManager;

    protected function configure()
    {
        $this->setName('nodes:list')
            ->setDescription('List available nodes')
            ->addOption(
                'type',
                't',
                InputOption::VALUE_REQUIRED,
                'Filter by node-type name'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->entityManager = $this->getHelper('entityManager')->getEntityManager();
        $text = "";
        $table = new Table($output);
        $table->setHeaders(['Id', 'Name', 'Type', 'Hidden', 'Published']);
        $tableContent = [];
        $nodes = [];

        if ($input->getOption('type')) {
            $nodeType = $this->entityManager
                ->getRepository(NodeType::class)
                ->findByName($input->getOption('type'));
            if (null !== $nodeType) {
                $nodes = $this->entityManager
                    ->getRepository(Node::class)
                    ->setDisplayingNotPublishedNodes(true)
                    ->findBy(['nodeType' => $nodeType], ['nodeName' => 'ASC']);
            }
        } else {
            $nodes = $this->entityManager
                ->getRepository(Node::class)
                ->setDisplayingNotPublishedNodes(true)
                ->findBy([], ['nodeName' => 'ASC']);
        }

        /** @var Node $node */
        foreach ($nodes as $node) {
            $tableContent[] = [
                $node->getId(),
                $node->getNodeName(),
                $node->getNodeType()->getName(),
                (!$node->isVisible() ? 'X' : ''),
                ($node->isPublished() ? 'X' : ''),
            ];
        }
        $table->setRows($tableContent);
        $table->render();

        $output->writeln($text);
    }
}
