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
        $this->entityManager = $this->getHelper('entityManager')->getEntityManager();
        $name = $input->getArgument('name');

        /** @var NodeType $nodetype */
        $nodetype = $this->entityManager
            ->getRepository(NodeType::class)
            ->findOneBy(['name' => $name]);

        if ($nodetype !== null) {
            $latestPosition = $this->entityManager
                ->getRepository(NodeTypeField::class)
                ->findLatestPositionInNodeType($nodetype);
            $this->addNodeTypeField($nodetype, $latestPosition + 1, $io);
            $this->entityManager->flush();

            $handler = $this->getHelper('handlerFactory')->getHandler($nodetype);
            $handler->regenerateEntityClass();

            $io->success('Node type ' . $nodetype->getName() . ' has been updated.' . PHP_EOL .
                'Do not forget to update database schema!' . PHP_EOL .
                'bin/roadiz orm:schema-tool:update --dump-sql --force');
        } else {
            $io->error('Node-type "' . $name . '" does not exist.');
        }
    }
}
