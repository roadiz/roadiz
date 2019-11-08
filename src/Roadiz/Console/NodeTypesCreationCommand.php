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
 * @file NodeTypesCreationCommand.php
 * @author Ambroise Maupate
 */
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

        /** @var NodeType $nodetype */
        $nodetype = $this->entityManager
            ->getRepository(NodeType::class)
            ->findOneBy(['name' => $name]);

        if ($nodetype !== null) {
            $io->error('Node-type "' . $name . '" already exists.');
        } else {
            $this->executeCreation($input, $output);
        }
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
        $field->setPosition($position);

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
