<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class NodesDetailsCommand extends Command
{
    /**
     * @var EntityManager
     */
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
        $io = new SymfonyStyle($input, $output);
        $this->entityManager = $this->getHelper('doctrine')->getEntityManager();

        $translation = $this->entityManager->getRepository(Translation::class)
                                           ->findOneBy(['locale' => $input->getArgument('locale')]);

        /** @var NodesSources|null $source */
        $source = $this->entityManager->getRepository(NodesSources::class)
                                    ->setDisplayingNotPublishedNodes(true)
                                    ->findOneBy([
                                        'node.nodeName' => $input->getArgument('nodeName'),
                                        'translation' => $translation,
                                    ]);
        if (null !== $source) {
            $io->title(get_class($source));
            $io->title('Title');
            $io->text($source->getTitle());

            /** @var NodeTypeField $field */
            foreach ($source->getNode()->getNodeType()->getFields() as $field) {
                if (!$field->isVirtual()) {
                    $getter = $field->getGetterName();
                    $data = $source->$getter();

                    if (is_array($data)) {
                        $data = implode(', ', $data);
                    }
                    if ($data instanceof \DateTime) {
                        $data = $data->format('c');
                    }
                    if ($data instanceof \stdClass) {
                        $data = json_encode($data);
                    }

                    if (!empty($data)) {
                        $io->title($field->getLabel());
                        $io->text($data);
                    }
                }
            }
        } else {
            $io->error('No node found.');
            return 1;
        }
        return 0;
    }
}
