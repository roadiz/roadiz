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
 * @file NodeTypesDeleteCommand.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Command line utils for managing node-types from terminal.
 */
class NodeTypesDeleteCommand extends Command
{
    private $questionHelper;
    private $entityManager;

    protected function configure()
    {
        $this->setName('nodetypes:delete')
            ->setDescription('Delete a node-type')
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'Node-type name'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->questionHelper = $this->getHelper('question');
        $this->entityManager = $this->getHelper('entityManager')->getEntityManager();
        $text = "";
        $name = $input->getArgument('name');

        $nodetype = $this->entityManager
            ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
            ->findOneByName($name);

        if ($nodetype !== null) {
            $question = new ConfirmationQuestion(
                '///////////////////////////////' . PHP_EOL .
                '/////////// WARNING ///////////' . PHP_EOL .
                '///////////////////////////////' . PHP_EOL .
                'This operation cannot be undone.' . PHP_EOL .
                'Deleting a node-type, you will automatically delete every <info>nodes</info> of this type.' . PHP_EOL .
                '<question>Are you sure to delete ' . $nodetype->getName() . ' node-type?</question> [y/N]:',
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
                $text .= '<info>Node-type deleted.</info>' . PHP_EOL .
                    'Do not forget to update database schema! <info>bin/roadiz orm:schema-tool:update --dump-sql --force</info>' . PHP_EOL;
            }
        } else {
            $text .= '<error>"' . $name . '" node type does not exist.</error>' . PHP_EOL;
        }

        $output->writeln($text);
    }
}
