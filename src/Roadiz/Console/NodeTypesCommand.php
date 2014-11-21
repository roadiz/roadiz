<?php
/**
 * Copyright © 2014, REZO ZERO
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
 * Except as contained in this notice, the name of the REZO ZERO shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from the REZO ZERO SARL.
 *
 * @file NodeTypesCommand.php
 * @copyright REZO ZERO 2014
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console;

use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command line utils for managing node-types from terminal.
 */
class NodeTypesCommand extends Command
{
    private $dialog;

    protected function configure()
    {
        $this->setName('core:node:types')
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
                'listFields',
                null,
                InputOption::VALUE_NONE,
                'List requested node-type fields'
            )
            ->addOption(
                'generateEntity',
                null,
                InputOption::VALUE_NONE,
                'Generate requested node-type source entity class'
            )
            ->addOption(
                'generateAllEntities',
                null,
                InputOption::VALUE_NONE,
                'Generate every node-types source entity classes'
            )
            ->addOption(
                'regenerateAllEntities',
                null,
                InputOption::VALUE_NONE,
                'Delete and re-generate every node-types source entity classes'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dialog = $this->getHelperSet()->get('dialog');
        $text="";
        $name = $input->getArgument('name');

        if ($name) {

            $nodetype = Kernel::getService('em')
                ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
                ->findOneBy(array('name'=>$name));

            if ($nodetype !== null) {
                $text = $nodetype->getOneLineSummary();
                $text .= $nodetype->getFieldsSummary();

                if ($input->getOption('delete')) {
                    if ($this->dialog->askConfirmation(
                        $output,
                        '<question>Are you sure to delete '.$nodetype->getName().' node-type?</question> : ',
                        false
                    )) {
                        Kernel::getService('em')->remove($nodetype);
                        Kernel::getService('em')->flush();
                        $text = '<info>Node-type deleted…</info>'.PHP_EOL;
                    }
                } elseif ($input->getOption('hide')) {
                    $nodetype->setVisible(false);
                    Kernel::getService('em')->flush();

                    $text .= '<info>'.$nodetype->getName()." hidden…</info>".PHP_EOL;
                } elseif ($input->getOption('show')) {
                    $nodetype->setVisible(true);
                    Kernel::getService('em')->flush();

                    $text .= '<info>'.$nodetype->getName()." showed…</info>".PHP_EOL;
                } elseif ($input->getOption('listFields')) {
                    $text .= $nodetype->getFieldsSummary().PHP_EOL;
                } elseif ($input->getOption('generateEntity')) {
                    $text .= '<info>'.$nodetype->getHandler()->generateSourceEntityClass().'</info>'.PHP_EOL;
                }
            } else {
                if ($input->getOption('create')) {
                    $text = $this->executeCreation($input, $output);
                }
            }
        } else {

            $nodetypes = Kernel::getService('em')
                ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
                ->findAll();

            if (count($nodetypes) > 0) {

                if ($input->getOption('generateAllEntities')) {
                    foreach ($nodetypes as $nt) {
                        $text .= '<info>'.$nt->getHandler()->generateSourceEntityClass().'</info>'.PHP_EOL;
                    }
                } elseif ($input->getOption('regenerateAllEntities')) {
                    foreach ($nodetypes as $nt) {
                        $nt->getHandler()->removeSourceEntityClass();
                        $text .= '<info>'.$nt->getHandler()->generateSourceEntityClass().'</info>'.PHP_EOL;
                    }
                } else {
                    $text = '<info>Existing node-types…</info>'.PHP_EOL;
                    foreach ($nodetypes as $nt) {
                        $text .= $nt->getOneLineSummary();
                    }
                }
            } else {
                $text = '<info>No available node-types…</info>'.PHP_EOL;
            }
        }

        $output->writeln($text);
    }

    private function executeCreation(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        $nt = new NodeType();
        $nt->setName($name);

        $displayName = $this->dialog->ask(
            $output,
            '<question>Enter your node-type display name</question> : ',
            'Neutral'
        );
        $nt->setDisplayName($displayName);

        $description = $this->dialog->ask(
            $output,
            '<question>Enter your node-type description</question> : ',
            ''
        );
        $nt->setDescription($description);
        Kernel::getService('em')->persist($nt);

        $i = 1;
        while (true) {
            // FIelds
            $field = new NodeTypeField();
            $field->setPosition($i);
            $fName = $this->dialog->ask(
                $output,
                '<question>[Field '.$i.'] Enter field name</question> (default:title): ',
                'title'
            );
            $field->setName($fName);
            $fLabel = $this->dialog->ask(
                $output,
                '<question>[Field '.$i.'] Enter field label</question> (default:Your title): ',
                'Your title'
            );
            $field->setLabel($fLabel);
            $fType = $this->dialog->ask(
                $output,
                '<question>[Field '.$i.'] Enter field type</question> (default:STRING_T): ',
                'STRING_T'
            );
            $fType = constant('RZ\Roadiz\Core\Entities\NodeTypeField::' . $fType);
            $field->setType($fType);

            if ($this->dialog->askConfirmation(
                $output,
                '<question>[Field '.$i.'] Must field be indexed?</question> (yes|No): ',
                false
            )) {
                $field->setIndexed(true);
            }
            // Need to populate each side
            $nt->getFields()->add($field);
            $field->setNodeType($nt);

            Kernel::getService('em')->persist($field);

            if (!$this->dialog->askConfirmation(
                $output,
                '<question>Do you want to add another field?</question> (Yes|no): ',
                true
            )) {
                break;
            }

            $i++;
        }
        Kernel::getService('em')->flush();
        $nt->getHandler()->updateSchema();

        return '<question>Node type '.$nt->getName().' has been created.</question>';
    }
}
