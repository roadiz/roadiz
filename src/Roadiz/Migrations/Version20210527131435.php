<?php

declare(strict_types=1);

namespace RZ\Roadiz\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210527131435 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fixes redirection redirectedUri length';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE redirections CHANGE redirectUri redirectUri TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE redirections CHANGE redirectUri redirectUri VARCHAR(255) DEFAULT NULL');
    }
}
