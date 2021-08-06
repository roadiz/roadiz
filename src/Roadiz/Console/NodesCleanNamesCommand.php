<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use Doctrine\Persistence\ObjectManager;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Utils\Node\NodeNamePolicyInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @package RZ\Roadiz\Console
 */
final class NodesCleanNamesCommand extends Command
{
    protected function configure()
    {
        $this->setName('nodes:clean-names')
            ->setDescription('Clean every nodes names according to their default node-source title.')
            ->addOption(
                'use-date',
                null,
                InputOption::VALUE_NONE,
                'Use date instead of uniqid.'
            )
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Do nothing, only print information.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager $entityManager */
        $entityManager = $this->getHelper('doctrine')->getEntityManager();
        $io = new SymfonyStyle($input, $output);

        $translation = $entityManager
            ->getRepository(Translation::class)
            ->findDefault();

        if (null !== $translation) {
            $nodes = $entityManager
                ->getRepository(Node::class)
                ->setDisplayingNotPublishedNodes(true)
                ->findBy([
                    'dynamicNodeName' => true,
                    'locked' => false,
                    'translation' => $translation,
                ]);

            $io->note(
                'This command will rename EVERY nodes (except for locked and not dynamic ones) names according to their node-source for current default translation.' . PHP_EOL .
                count($nodes) . ' nodes might be affected.'
            );

            $question1 = new ConfirmationQuestion('<question>Are you sure to proceed? This could break many page URLs!</question>', false);

            if ($io->askQuestion($question1)) {
                $io->note('Renaming ' . count($nodes) . ' nodes…');
                $renameCount = 0;
                $names = [];
                /** @var NodeNamePolicyInterface $nodeNameChecker */
                $nodeNameChecker = $this->getHelper('kernel')
                    ->getKernel()
                    ->get(NodeNamePolicyInterface::class);

                /** @var Node $node */
                foreach ($nodes as $node) {
                    $nodeSource = $node->getNodeSources()->first() ?: null;
                    if ($nodeSource !== null) {
                        $prefixName = $nodeSource->getTitle() != "" ?
                            $nodeSource->getTitle() :
                            $node->getNodeName();

                        $prefixNameSlug = $nodeNameChecker->getCanonicalNodeName($nodeSource);
                        /*
                         * Proceed to rename only if best name is not the current
                         * node-name AND if it is not ALREADY suffixed with a unique ID.
                         */
                        if ($prefixNameSlug != $node->getNodeName() &&
                            $nodeNameChecker->isNodeNameValid($prefixNameSlug) &&
                            !$nodeNameChecker->isNodeNameWithUniqId($prefixNameSlug, $nodeSource->getNode()->getNodeName())) {
                            $alreadyUsed = $nodeNameChecker->isNodeNameAlreadyUsed($prefixNameSlug);
                            if (!$alreadyUsed) {
                                $names[] = [
                                    $node->getNodeName(),
                                    $prefixNameSlug
                                ];
                                $node->setNodeName($prefixNameSlug);
                            } else {
                                if ($input->getOption('use-date') &&
                                    null !== $nodeSource->getPublishedAt()) {
                                    $suffixedNameSlug = $nodeNameChecker->getDatestampedNodeName($nodeSource);
                                } else {
                                    $suffixedNameSlug = $nodeNameChecker->getSafeNodeName($nodeSource);
                                }
                                if (!$nodeNameChecker->isNodeNameAlreadyUsed($suffixedNameSlug)) {
                                    $names[] = [
                                        $node->getNodeName(),
                                        $suffixedNameSlug
                                    ];
                                    $node->setNodeName($suffixedNameSlug);
                                } else {
                                    $suffixedNameSlug = $nodeNameChecker->getSafeNodeName($nodeSource);
                                    $names[] = [
                                        $node->getNodeName(),
                                        $suffixedNameSlug
                                    ];
                                    $node->setNodeName($suffixedNameSlug);
                                }
                            }
                            if (!$input->getOption('dry-run')) {
                                $entityManager->flush();
                            }
                            $renameCount++;
                        }
                    }
                }

                $io->table(['Old name', 'New name'], $names);

                if (!$input->getOption('dry-run')) {
                    $io->success('Renaming done! ' . $renameCount . ' nodes have been affected. Do not forget to reindex your Solr documents if you are using it.');
                } else {
                    $io->success($renameCount . ' nodes would have been affected. Nothing was saved to database.');
                }
            } else {
                $io->warning('Renaming cancelled…');
                return 1;
            }
        }

        return 0;
    }
}
