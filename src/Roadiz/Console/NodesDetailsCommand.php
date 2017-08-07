<?php
/**
 * Copyright (c) 2016.
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
 * @file NodesDetailsCommand.php
 * @author ambroisemaupate
 */
namespace RZ\Roadiz\Console;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NodesDetailsCommand extends Command
{
    /** @var  EntityManager */
    private $entityManager;

    protected function configure()
    {
        $this->setName('nodes:show')
            ->setDescription('Show node details and data.')
            ->addArgument('nodeName', InputArgument::REQUIRED, 'Node name to show')
            ->addArgument('locale', InputArgument::REQUIRED, 'Translation locale to use')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->entityManager = $this->getHelper('entityManager')->getEntityManager();

        $translation = $this->entityManager->getRepository('RZ\Roadiz\Core\Entities\Translation')
                                           ->findOneBy(['locale' => $input->getArgument('locale')]);

        /** @var Node $node */
        $node = $this->entityManager->getRepository('RZ\Roadiz\Core\Entities\Node')
                                    ->setDisplayingNotPublishedNodes(true)
                                    ->findOneBy([
                                        'nodeName' => $input->getArgument('nodeName'),
                                        'translation' => $translation,
                                    ]);
        if (null !== $translation && null !== $node) {
            $source = $node->getNodeSources()->first();

            $this->entityManager->refresh($source);
            $table = new Table($output);
            $table->setHeaders(['Field', 'Data']);
            $tableContent = [
                ['class', get_class($source)],
                ['Title', $source->getTitle()],
            ];

            /** @var NodeTypeField $field */
            foreach ($node->getNodeType()->getFields() as $field) {
                if (!$field->isVirtual()) {
                    $getter = $field->getGetterName();
                    $data = $source->$getter();

                    if (is_array($data)) {
                        $data = implode(', ', $data);
                    }
                    if ($data instanceof \DateTime) {
                        $data = $data->format('Y/m/d H:i:s');
                    }

                    $tableContent[] = [
                        $field->getLabel(),
                        $data,
                    ];
                }
            }

            $table->setRows($tableContent);
            $table->render();
        } else {
            $output->writeln('<error>No node found…</error>');
        }
    }
}
