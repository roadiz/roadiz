<?php
/**
 * Copyright © 2016, Ambroise Maupate and Julien Blanchet
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
 * @file NodesCreationCommand.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use RZ\Roadiz\Core\Entities\Node;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class NodesOrphansCommand
 * @package RZ\Roadiz\Console
 */
class NodesOrphansCommand extends Command
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    protected function configure()
    {
        $this->setName('nodes:orphans')
            ->setDescription('Find nodes without any source attached, and delete them.')
            ->addOption(
                'delete',
                'd',
                InputOption::VALUE_NONE,
                'Delete orphans nodes.'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->entityManager = $this->getHelper('entityManager')->getEntityManager();
        $io = new SymfonyStyle($input, $output);

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('n')
            ->from(Node::class, 'n')
            ->leftJoin('n.nodeSources', 'ns')
            ->having('COUNT(ns.id) = 0')
            ->groupBy('n');

        $orphans = [];
        try {
            $orphans = $qb->getQuery()->getResult();
        } catch (NoResultException $e) {
        }

        if (count($orphans) > 0) {
            $io->note(sprintf('You have %s orphan node(s)!', count($orphans)));
            $tableContent = [];

            /** @var Node $node */
            foreach ($orphans as $node) {
                $tableContent[] = [
                    $node->getId(),
                    $node->getNodeName(),
                    $node->getNodeType()->getName(),
                    (!$node->isVisible() ? 'X' : ''),
                    ($node->isPublished() ? 'X' : ''),
                ];
            }

            $io->table(['Id', 'Name', 'Type', 'Hidden', 'Published'], $tableContent);

            if ($input->getOption('delete')) {
                /** @var Node $orphan */
                foreach ($orphans as $orphan) {
                    $this->entityManager->remove($orphan);
                }
                $this->entityManager->flush();

                $io->success('Orphan nodes have been removed from your database.');
            } else {
                $io->note('Use <info>--delete</info> option to actually remove these nodes.');
            }
        } else {
            $io->success('That’s OK, you don’t have any orphan node.');
        }
    }
}
