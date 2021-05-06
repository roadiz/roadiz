<?php

declare(strict_types=1);

namespace RZ\Roadiz\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210506085247 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Additional table indexes';
    }

    public function up(Schema $schema): void
    {
        $this->skipIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );
        $this->addSql('CREATE INDEX node_status_parent ON nodes (status, parent_node_id)');
        $this->addSql('CREATE INDEX node_nodetype_status_parent ON nodes (nodeType_id, status, parent_node_id)');
        $this->addSql('CREATE INDEX node_nodetype_status_parent_position ON nodes (nodeType_id, status, parent_node_id, position)');
        $this->addSql('CREATE INDEX node_visible_parent_position ON nodes (visible, parent_node_id, position)');
        $this->addSql('CREATE INDEX node_status_visible_parent_position ON nodes (status, visible, parent_node_id, position)');
        $this->addSql('ALTER TABLE nodes RENAME INDEX idx_1d3d05fc7ab0e8597b00651c3445eb91 TO node_visible_status_parent');
        $this->addSql('ALTER TABLE nodes RENAME INDEX idx_1d3d05fc7ab0e8593445eb91 TO node_visible_parent');
        $this->addSql('CREATE INDEX tag_visible_position ON tags (visible, position)');
        $this->addSql('CREATE INDEX tag_parent_visible_position ON tags (parent_tag_id, visible, position)');
        $this->addSql('ALTER TABLE tags RENAME INDEX idx_6fbc9426f5c1a0d77ab0e859 TO tag_parent_visible');
    }

    public function down(Schema $schema): void
    {
        $this->skipIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );
        $this->addSql('DROP INDEX node_status_parent ON nodes');
        $this->addSql('DROP INDEX node_nodetype_status_parent ON nodes');
        $this->addSql('DROP INDEX node_nodetype_status_parent_position ON nodes');
        $this->addSql('DROP INDEX node_visible_parent_position ON nodes');
        $this->addSql('DROP INDEX node_status_visible_parent_position ON nodes');
        $this->addSql('ALTER TABLE nodes RENAME INDEX node_visible_status_parent TO IDX_1D3D05FC7AB0E8597B00651C3445EB91');
        $this->addSql('ALTER TABLE nodes RENAME INDEX node_visible_parent TO IDX_1D3D05FC7AB0E8593445EB91');
        $this->addSql('DROP INDEX tag_visible_position ON tags');
        $this->addSql('DROP INDEX tag_parent_visible_position ON tags');
        $this->addSql('ALTER TABLE tags RENAME INDEX tag_parent_visible TO IDX_6FBC9426F5C1A0D77AB0E859');
    }
}
