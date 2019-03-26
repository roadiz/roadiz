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
use RZ\Roadiz\Utils\Node\NodeNameChecker;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Class NodesCleanNamesCommand
 * @package RZ\Roadiz\Console
 */
class NodesCleanNamesCommand extends Command
{
    /** @var  EntityManager */
    private $entityManager;

    protected function configure()
    {
        $this->setName('nodes:clean-names')
            ->setDescription('Clean every nodes names according to their default node-source title.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->entityManager = $this->getHelper('entityManager')->getEntityManager();
        $questionHelper = $this->getHelper('question');

        $translation = $this->entityManager
            ->getRepository('RZ\Roadiz\Core\Entities\Translation')
            ->findDefault();

        if (null !== $translation) {
            $nodes = $this->entityManager->getRepository('RZ\Roadiz\Core\Entities\Node')
                ->findBy([
                    'dynamicNodeName' => true,
                    'locked' => false,
                    'translation' => $translation,
                ]);

            $output->writeln('<info>This command will rename EVERY nodes (except for locked and not dynamic ones) names according to their node-source for current default translation.</info>');
            $output->writeln('<info>' . count($nodes) . '</info> nodes might be affected.');

            $question1 = new ConfirmationQuestion('<question>Are you sure to proceed? This could break many page URLs!</question> [y/N]: ', false);
            $confirmed = $questionHelper->ask(
                $input,
                $output,
                $question1
            );

            if ($confirmed) {
                $output->writeln('<info>Renaming ' . count($nodes) . ' nodes</info>…');
                $renameCount = 0;

                /** @var Node $node */
                foreach ($nodes as $node) {
                    $nodeSource = $node->getNodeSources()->first();
                    $prefixName = $nodeSource->getTitle() != "" ?
                        $nodeSource->getTitle() :
                        $node->getNodeName();

                    $prefixNameSlug = StringHandler::slugify($prefixName);

                    /*
                     * Proceed to rename only if best name is not the current
                     * node-name AND if it is not ALREADY suffixed with a unique ID.
                     */
                    if ($prefixNameSlug != $node->getNodeName() &&
                        NodeNameChecker::isNodeNameValid($prefixNameSlug) &&
                        !NodeNameChecker::isNodeNameWithUniqId($prefixNameSlug, $nodeSource->getNode()->getNodeName())) {
                        $alreadyUsed = NodeNameChecker::isNodeNameAlreadyUsed($prefixName, $this->entityManager);
                        if (!$alreadyUsed) {
                            $output->writeln($node->getNodeName(). ' ---> ' . $prefixNameSlug);
                            $node->setNodeName($prefixName);
                        } else {
                            $output->writeln($node->getNodeName(). ' ---> ' . $prefixNameSlug . '-' . uniqid());
                            $node->setNodeName($prefixName . '-' . uniqid());
                        }
                        $this->entityManager->flush($node);
                        $renameCount++;
                    }
                }

                $output->writeln('<info>Renaming done! ' . $renameCount . ' nodes have been affected.</info> Do not forget to reindex your Solr documents if you are using it.');
            } else {
                $output->writeln('<info>Renaming cancelled…</info>');
            }
        }
    }
}
