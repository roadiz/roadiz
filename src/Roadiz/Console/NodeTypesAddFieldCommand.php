<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command line utils for managing node-types from terminal.
 */
class NodeTypesAddFieldCommand extends NodeTypesCreationCommand
{
    protected function configure()
    {
        $this->setName('nodetypes:add-fields')
            ->setDescription('Add fields to a node-type')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Node-type name'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $this->entityManager = $this->getHelper('doctrine')->getEntityManager();
        $name = $input->getArgument('name');

        /** @var NodeType|null $nodeType */
        $nodeType = $this->entityManager
            ->getRepository(NodeType::class)
            ->findOneBy(['name' => $name]);

        if ($nodeType !== null) {
            $latestPosition = $this->entityManager
                ->getRepository(NodeTypeField::class)
                ->findLatestPositionInNodeType($nodeType);
            $this->addNodeTypeField($nodeType, $latestPosition + 1, $io);
            $this->entityManager->flush();

            $handler = $this->getHelper('handlerFactory')->getHandler($nodeType);
            $handler->regenerateEntityClass();

            $io->success('Node type ' . $nodeType->getName() . ' has been updated.' . PHP_EOL .
                'Do not forget to update database schema!' . PHP_EOL .
                'bin/roadiz orm:schema-tool:update --dump-sql --force');
            return 0;
        } else {
            $io->error('Node-type "' . $name . '" does not exist.');
            return 1;
        }
    }
}
