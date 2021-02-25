<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Utils\Node\NodeFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command line utils for managing nodes from terminal.
 */
class NodesCreationCommand extends Command
{
    protected SymfonyStyle $io;
    private EntityManagerInterface $entityManager;
    private NodeFactory $nodeFactory;

    protected function configure()
    {
        $this->setName('nodes:create')
            ->setDescription('Create a new node')
            ->addArgument(
                'node-name',
                InputArgument::REQUIRED,
                'Node name'
            )
            ->addArgument(
                'node-type',
                InputArgument::REQUIRED,
                'Node-type name'
            )
            ->addArgument(
                'locale',
                InputArgument::OPTIONAL,
                'Translation locale'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->entityManager = $this->getHelper('entityManager')->getEntityManager();
        $this->nodeFactory = $this->getHelper('kernel')->getKernel()->get(NodeFactory::class);
        $nodeName = $input->getArgument('node-name');
        $typeName = $input->getArgument('node-type');
        $locale = $input->getArgument('locale');
        $this->io = new SymfonyStyle($input, $output);

        $existingNode = $this->entityManager
            ->getRepository(Node::class)
            ->setDisplayingNotPublishedNodes(true)
            ->findOneByNodeName($nodeName);

        if (null === $existingNode) {
            $type = $this->entityManager
                ->getRepository(NodeType::class)
                ->findOneByName($typeName);

            if (null !== $type) {
                $translation = null;

                if ($locale) {
                    $translation = $this->entityManager
                        ->getRepository(Translation::class)
                        ->findOneBy(['locale' => $locale]);
                }

                if ($translation === null) {
                    $translation = $this->entityManager
                        ->getRepository(Translation::class)
                        ->findDefault();
                }

                $this->executeNodeCreation($input->getArgument('node-name'), $type, $translation);
            } else {
                $this->io->error('"' . $typeName . '" node type does not exist.');
                return 1;
            }
            return 0;
        } else {
            $this->io->error($existingNode->getNodeName() . ' node already exists.');
            return 1;
        }
    }

    /**
     * @param NodeType        $type
     * @param Translation     $translation
     */
    private function executeNodeCreation(
        string $nodeName,
        NodeType $type,
        Translation $translation
    ) {
        $node = $this->nodeFactory->create($nodeName, $type, $translation);
        $source = $node->getNodeSources()->first() ?: null;
        if (null === $source) {
            throw new \InvalidArgumentException('Node source is null');
        }
        $fields = $type->getFields();

        foreach ($fields as $field) {
            if (!$field->isVirtual()) {
                $question = new Question('<question>[Field ' . $field->getLabel() . ']</question> : ', null);
                $fValue = $this->io->askQuestion($question);
                $setterName = $field->getSetterName();
                $source->$setterName($fValue);
            }
        }

        $this->entityManager->flush();
        $this->io->success('Node “' . $nodeName . '” created at root level.');
    }
}
