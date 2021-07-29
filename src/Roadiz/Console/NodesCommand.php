<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeType;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
        $this->entityManager = $this->getHelper('doctrine')->getEntityManager();
        $io = new SymfonyStyle($input, $output);
        $nodes = [];
        $tableContent = [];

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

        $io->table(['Id', 'Name', 'Type', 'Hidden', 'Published'], $tableContent);
        return 0;
    }
}
