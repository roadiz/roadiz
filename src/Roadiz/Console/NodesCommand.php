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
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command line utils for managing nodes from terminal.
 */
class NodesCommand extends Command
{
    private $dialog;
    private $entityManager;

    protected function configure()
    {
        $this->setName('core:nodes')
             ->setDescription('Manage nodes')
             ->addArgument(
                 'node-name',
                 InputArgument::OPTIONAL,
                 'Node name'
             )
             ->addArgument(
                 'node-type',
                 InputArgument::OPTIONAL,
                 'Node-type name'
             )
             ->addArgument(
                 'locale',
                 InputArgument::OPTIONAL,
                 'Translation locale'
             )
             ->addOption(
                 'create',
                 null,
                 InputOption::VALUE_NONE,
                 'Create a node'
             )
             ->addOption(
                 'delete',
                 null,
                 InputOption::VALUE_NONE,
                 'Delete requested node'
             )
             ->addOption(
                 'update',
                 null,
                 InputOption::VALUE_NONE,
                 'Update requested node'
             )
             ->addOption(
                 'hide',
                 null,
                 InputOption::VALUE_NONE,
                 'Hide requested node'
             )
             ->addOption(
                 'show',
                 null,
                 InputOption::VALUE_NONE,
                 'Show requested node'
             );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dialog = $this->getHelperSet()->get('dialog');
        $this->entityManager = $this->getHelperSet()->get('em')->getEntityManager();
        $text = "";
        $nodeName = $input->getArgument('node-name');
        $typeName = $input->getArgument('node-type');
        $locale = $input->getArgument('locale');

        if ($nodeName &&
            $typeName &&
            $input->getOption('create')
        ) {
            $type = $this->entityManager
                         ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
                         ->findOneBy(['name' => $typeName]);
            $translation = null;

            if ($locale) {
                $translation = $this->entityManager
                                    ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                                    ->findOneBy(['locale' => $locale]);
            }

            if ($translation === null) {
                $translation = $this->entityManager
                                    ->getRepository('RZ\Roadiz\Core\Entities\Translation')
                                    ->findOneBy([], ['id' => 'ASC']);
            }

            if ($type !== null &&
                $translation !== null) {
                // Node
                $text = $this->executeNodeCreation($input, $output, $type, $translation);
            } else {
            }

        } elseif ($nodeName) {
            $node = $this->entityManager
                         ->getRepository('RZ\Roadiz\Core\Entities\Node')
                         ->findOneBy(['nodeName' => $nodeName]);

            if ($node !== null) {
                $text .= $node->getOneLineSummary() . $node->getOneLineSourceSummary();
            } else {
                $text = '<info>Node “' . $nodeName . '” does not exists…</info>' . PHP_EOL;
            }
        } else {
            $nodes = $this->entityManager
                          ->getRepository('RZ\Roadiz\Core\Entities\Node')
                          ->findAll();

            foreach ($nodes as $key => $node) {
                $text .= $node->getOneLineSummary();
            }
        }

        $output->writeln($text);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param NodeType        $type
     * @param Translation     $translation
     *
     * @return string
     */
    private function executeNodeCreation(
        InputInterface $input,
        OutputInterface $output,
        NodeType $type,
        Translation $translation
    ) {
        $nodeName = $input->getArgument('node-name');
        $node = new Node($type);
        $node->setNodeName($nodeName);
        $this->entityManager->persist($node);

        // Source
        $sourceClass = "GeneratedNodeSources\\" . $type->getSourceEntityClassName();
        $source = new $sourceClass($node, $translation);

        $fields = $type->getFields();

        foreach ($fields as $field) {
            $fValue = $this->dialog->ask(
                $output,
                '<question>[Field ' . $field->getLabel() . ']</question> : ',
                ''
            );
            $setterName = 'set' . ucwords($field->getName());
            $source->$setterName($fValue);
        }

        $this->entityManager->persist($source);
        $this->entityManager->flush();
        $text = '<info>Node “' . $nodeName . '” created…</info>' . PHP_EOL;

        return $text;
    }
}
