<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use RZ\Roadiz\Core\Entities\Node;
use Symfony\Component\Console\Command\Command;
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
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->entityManager = $this->getHelper('doctrine')->getEntityManager();
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
                $io->note('Use --delete option to actually remove these nodes.');
            }
        } else {
            $io->success('That’s OK, you don’t have any orphan node.');
        }
        return 0;
    }
}
