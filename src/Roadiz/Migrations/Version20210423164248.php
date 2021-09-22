<?php

declare(strict_types=1);

namespace RZ\Roadiz\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210423164248 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Additional table indexes and renaming important performance indexes';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE INDEX answer_customform_submitted_at ON custom_form_answers (custom_form_id, submitted_at)');
        $this->addSql('CREATE INDEX cffattribute_answer_field ON custom_form_field_attributes (custom_form_answer_id, custom_form_field_id)');
        $this->addSql('CREATE INDEX cfield_customform_position ON custom_form_fields (custom_form_id, position)');
        $this->addSql('CREATE INDEX ntf_type_position ON node_type_fields (node_type_id, position)');
        $this->addSql('CREATE INDEX customform_node_position ON nodes_custom_forms (node_id, position)');
        $this->addSql('CREATE INDEX customform_node_field_position ON nodes_custom_forms (node_id, node_type_field_id, position)');

        if ($this->connection->getDatabasePlatform()->getName() === 'postgresql') {
            $this->addSql('DROP INDEX IF EXISTS IDX_7C7DED6DE0D4FDE19CAA2B25');
            $this->addSql('DROP INDEX IF EXISTS IDX_7C7DED6DE0D4FDE14AD260649CAA2B25');
            $this->addSql('DROP INDEX IF EXISTS IDX_7C7DED6D2B36786BE0D4FDE19CAA2B25');
        } else {
            $this->addSql('DROP INDEX IDX_7C7DED6DE0D4FDE19CAA2B25 ON nodes_sources');
            $this->addSql('DROP INDEX IDX_7C7DED6DE0D4FDE14AD260649CAA2B25 ON nodes_sources');
            $this->addSql('DROP INDEX IDX_7C7DED6D2B36786BE0D4FDE19CAA2B25 ON nodes_sources');
        }

        $this->addSql('CREATE INDEX ns_node_discr_translation_published ON nodes_sources (node_id, discr, translation_id, published_at)');
        $this->addSql('CREATE INDEX ns_translation_published ON nodes_sources (translation_id, published_at)');
        $this->addSql('CREATE INDEX ns_discr_translation_published ON nodes_sources (discr, translation_id, published_at)');
        $this->addSql('CREATE INDEX ns_title_translation_published ON nodes_sources (title, translation_id, published_at)');

        if ($this->connection->getDatabasePlatform()->getName() === 'postgresql') {
            $this->addSql('ALTER INDEX IF EXISTS ns_node_translation_discr RENAME TO ns_node_discr_translation');
            $this->addSql('ALTER INDEX IF EXISTS idx_7c7ded6d460d9fd79caa2b25e0d4fde1 RENAME TO ns_node_translation_published');
            $this->addSql('ALTER INDEX IF EXISTS idx_7c7ded6d4ad260649caa2b25 RENAME TO ns_discr_translation');
            $this->addSql('ALTER INDEX IF EXISTS idx_7c7ded6d2b36786be0d4fde1 RENAME TO ns_title_published');
            $this->addSql('ALTER INDEX IF EXISTS idx_1cd104f7aa2d6147705282 RENAME TO nsdoc_field');
            $this->addSql('ALTER INDEX IF EXISTS idx_1cd104f7aa2d6147705282462ce4f5 RENAME TO nsdoc_field_position');
            $this->addSql('ALTER INDEX IF EXISTS idx_761f9a91fc7adece47705282 RENAME TO node_a_field');
            $this->addSql('ALTER INDEX IF EXISTS idx_761f9a91fc7adece47705282462ce4f5 RENAME TO node_a_field_position');
            $this->addSql('ALTER INDEX IF EXISTS idx_761f9a91eecf712047705282 RENAME TO node_b_field');
            $this->addSql('ALTER INDEX IF EXISTS idx_761f9a91eecf712047705282462ce4f5 RENAME TO node_b_field_position');
            $this->addSql('ALTER INDEX IF EXISTS idx_6e886f1f22010f1462ce4f5 RENAME TO tagtranslation_position');
        } else {
            $this->addSql('ALTER TABLE nodes_sources RENAME INDEX ns_node_translation_discr TO ns_node_discr_translation');
            $this->addSql('ALTER TABLE nodes_sources RENAME INDEX idx_7c7ded6d460d9fd79caa2b25e0d4fde1 TO ns_node_translation_published');
            $this->addSql('ALTER TABLE nodes_sources RENAME INDEX idx_7c7ded6d4ad260649caa2b25 TO ns_discr_translation');
            $this->addSql('ALTER TABLE nodes_sources RENAME INDEX idx_7c7ded6d2b36786be0d4fde1 TO ns_title_published');
            $this->addSql('ALTER TABLE nodes_sources_documents RENAME INDEX idx_1cd104f7aa2d6147705282 TO nsdoc_field');
            $this->addSql('ALTER TABLE nodes_sources_documents RENAME INDEX idx_1cd104f7aa2d6147705282462ce4f5 TO nsdoc_field_position');
            $this->addSql('ALTER TABLE nodes_to_nodes RENAME INDEX idx_761f9a91fc7adece47705282 TO node_a_field');
            $this->addSql('ALTER TABLE nodes_to_nodes RENAME INDEX idx_761f9a91fc7adece47705282462ce4f5 TO node_a_field_position');
            $this->addSql('ALTER TABLE nodes_to_nodes RENAME INDEX idx_761f9a91eecf712047705282 TO node_b_field');
            $this->addSql('ALTER TABLE nodes_to_nodes RENAME INDEX idx_761f9a91eecf712047705282462ce4f5 TO node_b_field_position');
            $this->addSql('ALTER TABLE tags_translations_documents RENAME INDEX idx_6e886f1f22010f1462ce4f5 TO tagtranslation_position');
        }
    }

    public function down(Schema $schema) : void
    {
        if ($this->connection->getDatabasePlatform()->getName() === 'postgresql') {
            $this->addSql('DROP INDEX IF EXISTS answer_customform_submitted_at');
            $this->addSql('DROP INDEX IF EXISTS cffattribute_answer_field');
            $this->addSql('DROP INDEX IF EXISTS cfield_customform_positio');
            $this->addSql('DROP INDEX IF EXISTS ntf_type_position');
            $this->addSql('DROP INDEX IF EXISTS customform_node_position');
            $this->addSql('DROP INDEX IF EXISTS customform_node_field_position');
            $this->addSql('DROP INDEX IF EXISTS ns_node_discr_translation_published');
            $this->addSql('DROP INDEX IF EXISTS ns_translation_published');
            $this->addSql('DROP INDEX IF EXISTS ns_discr_translation_published');
            $this->addSql('DROP INDEX IF EXISTS ns_title_translation_published');
        } else {
            $this->addSql('DROP INDEX answer_customform_submitted_at ON custom_form_answers');
            $this->addSql('DROP INDEX cffattribute_answer_field ON custom_form_field_attributes');
            $this->addSql('DROP INDEX cfield_customform_position ON custom_form_fields');
            $this->addSql('DROP INDEX ntf_type_position ON node_type_fields');
            $this->addSql('DROP INDEX customform_node_position ON nodes_custom_forms');
            $this->addSql('DROP INDEX customform_node_field_position ON nodes_custom_forms');
            $this->addSql('DROP INDEX ns_node_discr_translation_published ON nodes_sources');
            $this->addSql('DROP INDEX ns_translation_published ON nodes_sources');
            $this->addSql('DROP INDEX ns_discr_translation_published ON nodes_sources');
            $this->addSql('DROP INDEX ns_title_translation_published ON nodes_sources');
        }

        $this->addSql('CREATE INDEX IDX_7C7DED6DE0D4FDE19CAA2B25 ON nodes_sources (published_at, translation_id)');
        $this->addSql('CREATE INDEX IDX_7C7DED6DE0D4FDE14AD260649CAA2B25 ON nodes_sources (published_at, discr, translation_id)');
        $this->addSql('CREATE INDEX IDX_7C7DED6D2B36786BE0D4FDE19CAA2B25 ON nodes_sources (title, published_at, translation_id)');

        if ($this->connection->getDatabasePlatform()->getName() === 'postgresql') {
            $this->addSql('ALTER INDEX IF EXISTS ns_title_published RENAME TO IDX_7C7DED6D2B36786BE0D4FDE1');
            $this->addSql('ALTER INDEX IF EXISTS ns_node_discr_translation RENAME TO ns_node_translation_discr');
            $this->addSql('ALTER INDEX IF EXISTS ns_discr_translation RENAME TO IDX_7C7DED6D4AD260649CAA2B25');
            $this->addSql('ALTER INDEX IF EXISTS ns_node_translation_published RENAME TO IDX_7C7DED6D460D9FD79CAA2B25E0D4FDE1');
            $this->addSql('ALTER INDEX IF EXISTS nsdoc_field RENAME TO IDX_1CD104F7AA2D6147705282');
            $this->addSql('ALTER INDEX IF EXISTS nsdoc_field_position RENAME TO IDX_1CD104F7AA2D6147705282462CE4F5');
            $this->addSql('ALTER INDEX IF EXISTS node_b_field RENAME TO IDX_761F9A91EECF712047705282');
            $this->addSql('ALTER INDEX IF EXISTS node_b_field_position RENAME TO IDX_761F9A91EECF712047705282462CE4F5');
            $this->addSql('ALTER INDEX IF EXISTS node_a_field RENAME TO IDX_761F9A91FC7ADECE47705282');
            $this->addSql('ALTER INDEX IF EXISTS node_a_field_position RENAME TO IDX_761F9A91FC7ADECE47705282462CE4F5');
            $this->addSql('ALTER INDEX IF EXISTS tagtranslation_position RENAME TO IDX_6E886F1F22010F1462CE4F5');
        } else {
            $this->addSql('ALTER TABLE nodes_sources RENAME INDEX ns_title_published TO IDX_7C7DED6D2B36786BE0D4FDE1');
            $this->addSql('ALTER TABLE nodes_sources RENAME INDEX ns_node_discr_translation TO ns_node_translation_discr');
            $this->addSql('ALTER TABLE nodes_sources RENAME INDEX ns_discr_translation TO IDX_7C7DED6D4AD260649CAA2B25');
            $this->addSql('ALTER TABLE nodes_sources RENAME INDEX ns_node_translation_published TO IDX_7C7DED6D460D9FD79CAA2B25E0D4FDE1');
            $this->addSql('ALTER TABLE nodes_sources_documents RENAME INDEX nsdoc_field TO IDX_1CD104F7AA2D6147705282');
            $this->addSql('ALTER TABLE nodes_sources_documents RENAME INDEX nsdoc_field_position TO IDX_1CD104F7AA2D6147705282462CE4F5');
            $this->addSql('ALTER TABLE nodes_to_nodes RENAME INDEX node_b_field TO IDX_761F9A91EECF712047705282');
            $this->addSql('ALTER TABLE nodes_to_nodes RENAME INDEX node_b_field_position TO IDX_761F9A91EECF712047705282462CE4F5');
            $this->addSql('ALTER TABLE nodes_to_nodes RENAME INDEX node_a_field TO IDX_761F9A91FC7ADECE47705282');
            $this->addSql('ALTER TABLE nodes_to_nodes RENAME INDEX node_a_field_position TO IDX_761F9A91FC7ADECE47705282462CE4F5');
            $this->addSql('ALTER TABLE tags_translations_documents RENAME INDEX tagtranslation_position TO IDX_6E886F1F22010F1462CE4F5');
        }
    }
}
