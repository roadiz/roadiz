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

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Translation;
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
    /** @var SymfonyStyle */
    protected $io;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

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
            }
        } else {
            $this->io->error($existingNode->getNodeName() . ' node already exists.');
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
        $node = new Node($type);
        $node->setTtl($node->getNodeType()->getDefaultTtl());
        $node->setNodeName($nodeName);
        $this->entityManager->persist($node);

        // Source
        $sourceClass = NodeType::getGeneratedEntitiesNamespace() . "\\" . $type->getSourceEntityClassName();
        /** @var NodesSources $source */
        $source = new $sourceClass($node, $translation);
        $source->setTitle($nodeName);
        $fields = $type->getFields();

        foreach ($fields as $field) {
            if (!$field->isVirtual()) {
                $question = new Question('<question>[Field ' . $field->getLabel() . ']</question> : ', null);
                $fValue = $this->io->askQuestion($question);
                $setterName = $field->getSetterName();
                $source->$setterName($fValue);
            }
        }

        $this->entityManager->persist($source);
        $this->entityManager->flush();
        $this->io->success('Node “' . $nodeName . '” created at root level.');
    }
}
