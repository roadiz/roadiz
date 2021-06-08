<?php

declare(strict_types=1);

namespace RZ\Roadiz\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210423161606 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Additional table indexes on documents, folders, logs and tags';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE INDEX document_created_at ON documents (created_at)');
        $this->addSql('CREATE INDEX document_updated_at ON documents (updated_at)');
        $this->addSql('CREATE INDEX document_raw_created_at ON documents (raw, created_at)');
        $this->addSql('CREATE INDEX document_embed_platform ON documents (embedPlatform)');
        $this->addSql('CREATE INDEX folder_parent_position ON folders (parent_id, position)');
        $this->addSql('CREATE INDEX log_ns_datetime ON log (node_source_id, datetime)');
        $this->addSql('CREATE INDEX log_username_datetime ON log (username, datetime)');
        $this->addSql('CREATE INDEX log_user_datetime ON log (user_id, datetime)');
        $this->addSql('CREATE INDEX log_level_datetime ON log (level, datetime)');
        $this->addSql('CREATE INDEX log_channel_datetime ON log (channel, datetime)');
        $this->addSql('CREATE INDEX ns_node_translation_discr ON nodes_sources (node_id, discr, translation_id)');
        $this->addSql('CREATE INDEX tag_parent_position ON tags (parent_tag_id, position)');
    }

    public function down(Schema $schema) : void
    {
        if ($this->connection->getDatabasePlatform()->getName() === 'postgresql') {
            $this->addSql('DROP INDEX IF EXISTS document_created_at');
            $this->addSql('DROP INDEX IF EXISTS document_updated_at');
            $this->addSql('DROP INDEX IF EXISTS document_raw_created_at');
            $this->addSql('DROP INDEX IF EXISTS document_embed_platform');
            $this->addSql('DROP INDEX IF EXISTS folder_parent_position');
            $this->addSql('DROP INDEX IF EXISTS log_ns_datetime');
            $this->addSql('DROP INDEX IF EXISTS log_username_datetime');
            $this->addSql('DROP INDEX IF EXISTS log_user_datetime');
            $this->addSql('DROP INDEX IF EXISTS log_level_datetime');
            $this->addSql('DROP INDEX IF EXISTS log_channel_datetime');
            $this->addSql('DROP INDEX IF EXISTS ns_node_translation_discr');
            $this->addSql('DROP INDEX IF EXISTS tag_parent_position');
        } else {
            $this->addSql('DROP INDEX document_created_at ON documents');
            $this->addSql('DROP INDEX document_updated_at ON documents');
            $this->addSql('DROP INDEX document_raw_created_at ON documents');
            $this->addSql('DROP INDEX document_embed_platform ON documents');
            $this->addSql('DROP INDEX folder_parent_position ON folders');
            $this->addSql('DROP INDEX log_ns_datetime ON log');
            $this->addSql('DROP INDEX log_username_datetime ON log');
            $this->addSql('DROP INDEX log_user_datetime ON log');
            $this->addSql('DROP INDEX log_level_datetime ON log');
            $this->addSql('DROP INDEX log_channel_datetime ON log');
            $this->addSql('DROP INDEX ns_node_translation_discr ON nodes_sources');
            $this->addSql('DROP INDEX tag_parent_position ON tags');
        }
    }
}
