<?php

declare(strict_types=1);

namespace RZ\Roadiz\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210520092543 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add NodeTypeField serialization interface fields and NodeType searchable interface field.';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() === 'mysql') {
            /*
             * MYSQL
             */
            $this->addSql('ALTER TABLE node_type_fields ADD serialization_exclusion_expression LONGTEXT DEFAULT NULL, ADD serialization_groups JSON DEFAULT NULL, ADD serialization_max_depth INT DEFAULT NULL, ADD excluded_from_serialization TINYINT(1) DEFAULT \'0\' NOT NULL');
            $this->addSql('ALTER TABLE node_types ADD searchable TINYINT(1) DEFAULT \'1\' NOT NULL');
            $this->addSql('CREATE INDEX nt_searchable ON node_types (searchable)');
        } elseif ($this->connection->getDatabasePlatform()->getName() === 'postgresql') {
            /*
             * POSTGRES
             */
            $this->addSql('ALTER TABLE node_type_fields ADD serialization_exclusion_expression TEXT DEFAULT NULL');
            $this->addSql('ALTER TABLE node_type_fields ADD serialization_groups JSON DEFAULT NULL');
            $this->addSql('ALTER TABLE node_type_fields ADD serialization_max_depth INT DEFAULT NULL');
            $this->addSql('ALTER TABLE node_type_fields ADD excluded_from_serialization BOOLEAN DEFAULT \'false\' NOT NULL');

            $this->addSql('ALTER TABLE node_types ADD searchable BOOLEAN DEFAULT \'true\' NOT NULL');
            $this->addSql('CREATE INDEX nt_searchable ON node_types (searchable)');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() === 'mysql') {
            $this->addSql('ALTER TABLE node_type_fields DROP serialization_exclusion_expression, DROP serialization_groups, DROP serialization_max_depth, DROP excluded_from_serialization');
            $this->addSql('DROP INDEX nt_searchable ON node_types');
            $this->addSql('ALTER TABLE node_types DROP searchable');
        } elseif ($this->connection->getDatabasePlatform()->getName() === 'postgresql') {
            /*
             * POSTGRES
             */
            $this->addSql('DROP INDEX nt_searchable');
            $this->addSql('ALTER TABLE node_types DROP searchable');

            $this->addSql('ALTER TABLE node_type_fields DROP serialization_exclusion_expression');
            $this->addSql('ALTER TABLE node_type_fields DROP serialization_groups');
            $this->addSql('ALTER TABLE node_type_fields DROP serialization_max_depth');
            $this->addSql('ALTER TABLE node_type_fields DROP excluded_from_serialization');
        }
    }
}
