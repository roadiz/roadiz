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
 * @file NodeTypesCommand.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Command line utils for managing node-types from terminal.
 */
class NodeTypesCommand extends Command
{
    private $questionHelper;
    private $entityManager;

    protected function configure()
    {
        $this->setName('core:node-types')
             ->setDescription('Manage node-types')
             ->addArgument(
                 'name',
                 InputArgument::OPTIONAL,
                 'Node-type name'
             )
             ->addOption(
                 'create',
                 null,
                 InputOption::VALUE_NONE,
                 'Create a node-type'
             )
             ->addOption(
                 'delete',
                 null,
                 InputOption::VALUE_NONE,
                 'Delete requested node-type'
             )
             ->addOption(
                 'update',
                 null,
                 InputOption::VALUE_NONE,
                 'Update requested node-type'
             )
             ->addOption(
                 'hide',
                 null,
                 InputOption::VALUE_NONE,
                 'Hide requested node-type'
             )
             ->addOption(
                 'show',
                 null,
                 InputOption::VALUE_NONE,
                 'Show requested node-type'
             )
             ->addOption(
                 'list-fields',
                 null,
                 InputOption::VALUE_NONE,
                 'List requested node-type fields'
             );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->questionHelper = $this->getHelperSet()->get('question');
        $this->entityManager = $this->getHelperSet()->get('em')->getEntityManager();
        $text = "";
        $name = $input->getArgument('name');

        if ($name) {
            $nodetype = $this->entityManager
                             ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
                             ->findOneBy(['name' => $name]);

            if ($nodetype !== null) {
                $text = $nodetype->getOneLineSummary();

                if ($input->getOption('delete')) {
                    $question = new ConfirmationQuestion(
                        'Are you sure to delete ' . $nodetype->getName() . ' node-type?',
                        false
                    );
                    if ($this->questionHelper->ask(
                        $input,
                        $output,
                        $question
                    )) {
                        $nodetype->getHandler()->removeSourceEntityClass();
                        $this->entityManager->remove($nodetype);
                        $this->entityManager->flush();
                        $text = '<info>Node-type deleted…</info>' . PHP_EOL;
                    }
                } elseif ($input->getOption('hide')) {
                    $nodetype->setVisible(false);
                    $this->entityManager->flush();

                    $text .= '<info>' . $nodetype->getName() . " hidden…</info>" . PHP_EOL;
                } elseif ($input->getOption('show')) {
                    $nodetype->setVisible(true);
                    $this->entityManager->flush();

                    $text .= '<info>' . $nodetype->getName() . " showed…</info>" . PHP_EOL;
                } elseif ($input->getOption('list-fields')) {
                    $text .= $nodetype->getFieldsSummary() . PHP_EOL;
                }
            } else {
                if ($input->getOption('create')) {
                    $text = $this->executeCreation($input, $output);
                }
            }
        } else {
            $nodetypes = $this->entityManager
                              ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
                              ->findAll();

            if (count($nodetypes) > 0) {
                $text = '<info>Existing node-types…</info>' . PHP_EOL;
                foreach ($nodetypes as $nt) {
                    $text .= $nt->getOneLineSummary();
                }
            } else {
                $text = '<info>No available node-types…</info>' . PHP_EOL;
            }
        }

        $output->writeln($text);
    }

    private function executeCreation(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        $nt = new NodeType();
        $nt->setName($name);

        $question0 = new Question('<question>Enter your node-type display name</question>: ', 'Neutral');
        $displayName = $this->questionHelper->ask(
            $input,
            $output,
            $question0
        );
        $nt->setDisplayName($displayName);

        $question1 = new Question('<question>Enter your node-type description</question>: ', '');
        $description = $this->questionHelper->ask(
            $input,
            $output,
            $question1
        );
        $nt->setDescription($description);
        $this->entityManager->persist($nt);

        $i = 1;
        while (true) {
            // Fields
            $field = new NodeTypeField();
            $field->setPosition($i);

            $questionfName = new Question('<question>[Field ' . $i . '] Enter field name</question>: ', 'content');
            $fName = $this->questionHelper->ask(
                $input,
                $output,
                $questionfName
            );
            $field->setName($fName);

            $questionfLabel = new Question('<question>[Field ' . $i . '] Enter field label</question>: ', 'Your content');
            $fLabel = $this->questionHelper->ask(
                $input,
                $output,
                $questionfLabel
            );
            $field->setLabel($fLabel);

            $questionfType = new Question('<question>[Field ' . $i . '] Enter field type</question>: ', 'MARKDOWN_T');
            $fType = $this->questionHelper->ask(
                $input,
                $output,
                $questionfType
            );
            $fType = constant('RZ\Roadiz\Core\Entities\NodeTypeField::' . $fType);
            $field->setType($fType);

            $questionIndexed = new ConfirmationQuestion('<question>[Field ' . $i . '] Must field be indexed?</question>: ', false);
            if ($this->questionHelper->ask(
                $input,
                $output,
                $questionIndexed
            )) {
                $field->setIndexed(true);
            }

            // Need to populate each side
            $nt->getFields()->add($field);
            $this->entityManager->persist($field);
            $field->setNodeType($nt);

            $questionAdd = new ConfirmationQuestion('<question>Do you want to add another field?</question>: ', true);
            if (!$this->questionHelper->ask(
                $input,
                $output,
                $questionAdd
            )) {
                break;
            }

            $i++;
        }
        $this->entityManager->flush();
        $nt->getHandler()->regenerateEntityClass();

        $success = '<question>Node type ' . $nt->getName() . ' has been created.</question>'. PHP_EOL .
                    '<info>Do not forget to update database schema!</info>';
        return $success;
    }
}
