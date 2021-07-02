<?php

declare(strict_types=1);

namespace RZ\Roadiz\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210701151713 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add new table for Webhooks';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() === 'postgresql') {
            $this->addSql('CREATE TABLE webhooks (id VARCHAR(36) NOT NULL, message_type VARCHAR(255) DEFAULT NULL, uri TEXT DEFAULT NULL, payload JSON DEFAULT NULL, throttleSeconds INT NOT NULL, last_triggered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, automatic BOOLEAN DEFAULT \'false\' NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE INDEX webhook_message_type ON webhooks (message_type)');
            $this->addSql('CREATE INDEX webhook_created_at ON webhooks (created_at)');
            $this->addSql('CREATE INDEX webhook_updated_at ON webhooks (updated_at)');
            $this->addSql('CREATE INDEX webhook_last_triggered_at ON webhooks (last_triggered_at)');
            $this->addSql('CREATE INDEX IDX_998C4FDD8B8E8428 ON webhooks (created_at)');
            $this->addSql('CREATE INDEX IDX_998C4FDD43625D9F ON webhooks (updated_at)');
            $this->addSql('CREATE INDEX webhook_automatic ON webhooks (automatic)');
        } else {
            $this->addSql('CREATE TABLE webhooks (id VARCHAR(36) NOT NULL, message_type VARCHAR(255) DEFAULT NULL, uri LONGTEXT DEFAULT NULL, payload JSON DEFAULT NULL, throttleSeconds INT NOT NULL, last_triggered_at DATETIME DEFAULT NULL, automatic TINYINT(1) DEFAULT \'0\' NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX webhook_message_type (message_type), INDEX webhook_created_at (created_at), INDEX webhook_updated_at (updated_at), INDEX webhook_last_triggered_at (last_triggered_at), INDEX webhook_automatic (automatic), INDEX IDX_998C4FDD8B8E8428 (created_at), INDEX IDX_998C4FDD43625D9F (updated_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE webhooks');
    }
}
