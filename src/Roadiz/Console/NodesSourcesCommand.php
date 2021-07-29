<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Handlers\NodeTypeHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
            ->setDescription('Generate node-sources entities PHP classes.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->entityManager = $this->getHelper('doctrine')->getEntityManager();
        $io = new SymfonyStyle($input, $output);

        $nodetypes = $this->entityManager
            ->getRepository(NodeType::class)
            ->findAll();

        if (count($nodetypes) > 0) {
            /** @var NodeType $nt */
            foreach ($nodetypes as $nt) {
                /** @var NodeTypeHandler $handler */
                $handler = $this->getHelper('handlerFactory')->getHandler($nt);

                $handler->removeSourceEntityClass();
                $handler->generateSourceEntityClass();
                $io->writeln("* Source class <info>".$nt->getSourceEntityClassName()."</info> has been generated.");

                if ($output->isVeryVerbose()) {
                    $io->writeln("\t<info>".$handler->getSourceClassPath()."</info>");
                }
            }
            return 0;
        } else {
            $io->error('No available node-types…');
            return 1;
        }
    }
}
