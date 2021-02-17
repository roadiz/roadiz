<?php
declare(strict_types=1);

namespace RZ\Roadiz\Console;

use Doctrine\ORM\EntityManager;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command line utils for managing node-types from terminal.
 */
class NodeTypesCreationCommand extends Command
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    protected function configure()
    {
        $this->setName('nodetypes:create')
            ->setDescription('Manage node-types')
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

        if (empty($name)) {
            throw new \InvalidArgumentException('Name must not be empty.');
        }

        /** @var NodeType|null $nodeType */
        $nodeType = $this->entityManager
            ->getRepository(NodeType::class)
            ->findOneBy(['name' => $name]);

        if ($nodeType !== null) {
            $io->error('Node-type "' . $name . '" already exists.');
            return 1;
        } else {
            $this->executeCreation($input, $output);
        }
        return 0;
    }

    private function executeCreation(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');
        $nt = new NodeType();
        $nt->setName($name);

        $io->note('OK! Let’s create that "' . $nt->getName() . '" node-type together!');

        $question0 = new Question('<question>Enter your node-type display name</question>', ucwords($name));
        $displayName = $io->askQuestion($question0);
        $nt->setDisplayName($displayName);

        $question1 = new Question('<question>Enter your node-type description</question>', ucwords($name));
        $description = $io->askQuestion($question1);
        $nt->setDescription($description);
        $this->entityManager->persist($nt);

        // Begin nt-field creation loop
        $this->addNodeTypeField($nt, 1, $io);

        $this->entityManager->flush();
        $handler = $this->getHelper('handlerFactory')->getHandler($nt);
        $handler->regenerateEntityClass();

        $io->success('Node type ' . $nt->getName() . ' has been created.' . PHP_EOL .
            'Do not forget to update database schema!' . PHP_EOL .
            'bin/roadiz orm:schema-tool:update --dump-sql --force');
    }

    protected function addNodeTypeField(NodeType $nodeType, $position, SymfonyStyle $io)
    {
        $field = new NodeTypeField();
        $field->setPosition((float) $position);

        $questionfName = new Question('[Field ' . $position . '] <question>Enter field name</question>', 'content');
        $fName = $io->askQuestion($questionfName);
        $field->setName($fName);

        $questionfLabel = new Question('[Field ' . $position . '] <question>Enter field label</question>', 'Your content');
        $fLabel = $io->askQuestion($questionfLabel);
        $field->setLabel($fLabel);

        $questionfType = new Question('[Field ' . $position . '] <question>Enter field type</question>', 'STRING_T');
        $questionfType->setAutocompleterValues([
            'STRING_T',
            'DATETIME_T',
            'DATE_T',
            'TEXT_T',
            'MARKDOWN_T',
            'BOOLEAN_T',
            'INTEGER_T',
            'DECIMAL_T',
            'EMAIL_T',
            'ENUM_T',
            'MULTIPLE_T',
            'DOCUMENTS_T',
            'NODES_T',
            'CHILDREN_T',
            'COLOUR_T',
            'GEOTAG_T',
            'CUSTOM_FORMS_T',
            'MULTI_GEOTAG_T',
            'JSON_T',
            'CSS_T',
        ]);

        $fType = $io->askQuestion($questionfType);
        $fType = constant(NodeTypeField::class . '::' . $fType);
        $field->setType($fType);

        $questionIndexed = new ConfirmationQuestion('[Field ' . $position . '] <question>Must this field be indexed?</question>', false);
        if ($io->askQuestion($questionIndexed)) {
            $field->setIndexed(true);
        }

        // Need to populate each side
        $nodeType->getFields()->add($field);
        $this->entityManager->persist($field);
        $field->setNodeType($nodeType);

        $questionAdd = new ConfirmationQuestion('<question>Do you want to add another field?</question>', true);
        if ($io->askQuestion($questionAdd)) {
            $this->addNodeTypeField($nodeType, $position + 1, $io);
        }
    }
}
