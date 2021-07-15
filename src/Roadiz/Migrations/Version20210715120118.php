<?php

declare(strict_types=1);

namespace RZ\Roadiz\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210715120118 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add root node to scope automatic webhooks';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() === 'postgresql') {
            $this->addSql('ALTER TABLE webhooks ADD root_node INT DEFAULT NULL');
            $this->addSql('ALTER TABLE webhooks ADD CONSTRAINT FK_998C4FDDC2A25172 FOREIGN KEY (root_node) REFERENCES nodes (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        } else {
            $this->addSql('ALTER TABLE webhooks ADD root_node INT DEFAULT NULL');
            $this->addSql('ALTER TABLE webhooks ADD CONSTRAINT FK_998C4FDDC2A25172 FOREIGN KEY (root_node) REFERENCES nodes (id) ON DELETE SET NULL');
        }
        $this->addSql('CREATE INDEX webhook_root_node ON webhooks (root_node)');
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() === 'postgresql') {
            $this->addSql('ALTER TABLE webhooks DROP CONSTRAINT FK_998C4FDDC2A25172');
            $this->addSql('DROP INDEX webhook_root_node');
        } else {
            $this->addSql('ALTER TABLE webhooks DROP FOREIGN KEY FK_998C4FDDC2A25172');
            $this->addSql('DROP INDEX webhook_root_node ON webhooks');
        }
        $this->addSql('ALTER TABLE webhooks DROP root_node');
    }
}
