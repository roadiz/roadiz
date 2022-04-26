<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectManager;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Handlers\NodeHandler;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class NodesEmptyTrashCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('nodes:empty-trash')
            ->setDescription('Remove definitely deleted nodes.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        /** @var ObjectManager $em */
        $em = $this->getHelper('doctrine')->getEntityManager();
        /** @var Kernel $kernel */
        $kernel = $this->getHelper('kernel')->getKernel();

        $countQb = $this->createNodeQueryBuilder($em);
        $countQuery = $countQb->select($countQb->expr()->count('n'))
            ->andWhere($countQb->expr()->eq('n.status', Node::DELETED))
            ->getQuery();
        $emptiedCount = $countQuery->getSingleScalarResult();
        if ($emptiedCount > 0) {
            $confirmation = new ConfirmationQuestion(
                sprintf('<question>Are you sure to empty nodes trashcan, %d nodes will be lost forever?</question> [y/N]: ', $emptiedCount),
                false
            );
            if ($io->askQuestion($confirmation) || !$input->isInteractive()) {
                $i = 0;
                $batchSize = 100;
                $io->progressStart($emptiedCount);

                $qb = $this->createNodeQueryBuilder($em);
                $q = $qb->select('n')
                    ->andWhere($countQb->expr()->eq('n.status', Node::DELETED))
                    ->getQuery();

                foreach ($q->toIterable() as $row) {
                    /** @var NodeHandler $nodeHandler */
                    $nodeHandler = $kernel->get('node.handler')->setNode($row);
                    $nodeHandler->removeWithChildrenAndAssociations();
                    $io->progressAdvance();
                    ++$i;
                    // Call flush time to times
                    if (($i % $batchSize) === 0) {
                        $em->flush();
                        $em->clear();
                    }
                }

                /*
                 * Final flush
                 */
                $em->flush();
                $io->progressFinish();
                $io->success('Nodes trashcan has been emptied.');
            }
        } else {
            $io->success('Nodes trashcan is already empty.');
        }

        return 0;
    }

    protected function createNodeQueryBuilder(ObjectManager $em): QueryBuilder
    {
        return $em
            ->getRepository(Node::class)
            ->createQueryBuilder('n');
    }
}
