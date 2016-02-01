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
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        $table = new Table($output);
        $this->entityManager = $this->getHelperSet()->get('em')->getEntityManager();
        $text = "";
        $name = $input->getArgument('name');

        if ($name) {
            $nodetype = $this->entityManager
                ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
                ->findOneByName($name);

            if ($nodetype !== null) {
                $fields = $this->entityManager->getRepository('RZ\Roadiz\Core\Entities\NodeTypeField')
                    ->findBy([
                        'nodeType' => $nodetype,
                    ], ['position' => 'ASC']);

                $table->setHeaders(['Id', 'Label', 'Name', 'Type', 'Visible', 'Index']);
                $tableContent = [];
                foreach ($fields as $field) {
                    $tableContent[] = [
                        $field->getId(),
                        $field->getLabel(),
                        $field->getName(),
                        str_replace('.type', '', NodeTypeField::$typeToHuman[$field->getType()]),
                        ($field->isVisible() ? 'X' : ''),
                        ($field->isIndexed() ? 'X' : ''),
                    ];
                }
                $table->setRows($tableContent);
                $table->render($output);
            } else {
                $text .= '<error>"' . $name . '" node type does not exist.</error>' . PHP_EOL;
            }
        } else {
            $nodetypes = $this->entityManager
                ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
                ->findBy([], ['name' => 'ASC']);

            if (count($nodetypes) > 0) {
                $table->setHeaders(['Id', 'Title', 'Visible']);
                $tableContent = [];

                foreach ($nodetypes as $nt) {
                    $tableContent[] = [
                        $nt->getId(),
                        $nt->getName(),
                        ($nt->isVisible() ? 'X' : ''),
                    ];
                }

                $table->setRows($tableContent);
                $table->render($output);
            } else {
                $text .= '<info>No available node-types…</info>' . PHP_EOL;
            }
        }

        $output->writeln($text);
    }
}
