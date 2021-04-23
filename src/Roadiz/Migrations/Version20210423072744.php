<?php

declare(strict_types=1);

namespace RZ\Roadiz\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * @package RZ\Roadiz\Migrations
 */
final class Version20210423072744 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Added Documents duration field.';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE documents ADD duration INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE documents DROP duration');
    }
}
