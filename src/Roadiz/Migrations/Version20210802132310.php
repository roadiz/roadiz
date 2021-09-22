<?php

declare(strict_types=1);

namespace RZ\Roadiz\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210802132310 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Added webhooks description';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE webhooks ADD description LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE webhooks DROP description');
    }
}
