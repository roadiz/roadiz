<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command line utils for managing node-types from terminal.
 */
class NodeTypesCommand extends Command
{
    private $entityManager;

    protected function configure()
    {
        $this->setName('nodetypes:list')
            ->setDescription('List available node-types or fields for a given node-type name')
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'Node-type name'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->entityManager = $this->getHelper('entityManager')->getEntityManager();
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');

        if ($name) {
            $nodetype = $this->entityManager
                ->getRepository(NodeType::class)
                ->findOneByName($name);

            if ($nodetype !== null) {
                /** @var array<NodeTypeField> $fields */
                $fields = $this->entityManager->getRepository(NodeTypeField::class)
                    ->findBy([
                        'nodeType' => $nodetype,
                    ], ['position' => 'ASC']);

                $tableContent = [];
                foreach ($fields as $field) {
                    $tableContent[] = [
                        $field->getId(),
                        $field->getLabel(),
                        $field->getName(),
                        str_replace('.type', '', $field->getTypeName()),
                        ($field->isVisible() ? 'X' : ''),
                        ($field->isIndexed() ? 'X' : ''),
                    ];
                }
                $io->table(['Id', 'Label', 'Name', 'Type', 'Visible', 'Index'], $tableContent);
            } else {
                $io->error($name . ' node type does not exist.');
                return 1;
            }
        } else {
            /** @var array<NodeType> $nodetypes */
            $nodetypes = $this->entityManager
                ->getRepository(NodeType::class)
                ->findBy([], ['name' => 'ASC']);

            if (count($nodetypes) > 0) {
                $tableContent = [];

                foreach ($nodetypes as $nt) {
                    $tableContent[] = [
                        $nt->getId(),
                        $nt->getName(),
                        ($nt->isVisible() ? 'X' : ''),
                    ];
                }

                $io->table(['Id', 'Title', 'Visible'], $tableContent);
            } else {
                $io->error('No available node-types…');
                return 1;
            }
        }
        return 0;
    }
}
