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
 * @file NodeTypesAddFieldCommand.php
 * @author Ambroise Maupate
 */
namespace RZ\Roadiz\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        $this->questionHelper = $this->getHelper('question');
        $this->entityManager = $this->getHelper('entityManager')->getEntityManager();
        $text = "";
        $name = $input->getArgument('name');

        $nodetype = $this->entityManager
            ->getRepository('RZ\Roadiz\Core\Entities\NodeType')
            ->findOneBy(['name' => $name]);

        if ($nodetype !== null) {
            $latestPosition = $this->entityManager
                ->getRepository('RZ\Roadiz\Core\Entities\NodeTypeField')
                ->findLatestPositionInNodeType($nodetype);
            $this->addNodeTypeField($nodetype, $latestPosition + 1, $input, $output);
            $this->entityManager->flush();
            $nodetype->getHandler()->regenerateEntityClass();

            $output->writeln('Do not forget to update database schema! <info>bin/roadiz orm:schema-tool:update --dump-sql --force</info>');
        } else {
            $text .= '<error>Node-type "' . $name . '" does not exist.</error>';
        }

        $output->writeln($text);
    }
}
