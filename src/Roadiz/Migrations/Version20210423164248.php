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
        if ($this->connection->getDatabasePlatform()->getName() === 'mysql') {
            /*
             * MYSQL
             */
            $this->addSql('CREATE INDEX answer_customform_submitted_at ON custom_form_answers (custom_form_id, submitted_at)');
            $this->addSql('CREATE INDEX cffattribute_answer_field ON custom_form_field_attributes (custom_form_answer_id, custom_form_field_id)');
            $this->addSql('CREATE INDEX cfield_customform_position ON custom_form_fields (custom_form_id, position)');
            $this->addSql('CREATE INDEX ntf_type_position ON node_type_fields (node_type_id, position)');
            $this->addSql('CREATE INDEX customform_node_position ON nodes_custom_forms (node_id, position)');
            $this->addSql('CREATE INDEX customform_node_field_position ON nodes_custom_forms (node_id, node_type_field_id, position)');
            $this->addSql('DROP INDEX IDX_7C7DED6DE0D4FDE19CAA2B25 ON nodes_sources');
            $this->addSql('DROP INDEX IDX_7C7DED6DE0D4FDE14AD260649CAA2B25 ON nodes_sources');
            $this->addSql('DROP INDEX IDX_7C7DED6D2B36786BE0D4FDE19CAA2B25 ON nodes_sources');
            $this->addSql('CREATE INDEX ns_node_discr_translation_published ON nodes_sources (node_id, discr, translation_id, published_at)');
            $this->addSql('CREATE INDEX ns_translation_published ON nodes_sources (translation_id, published_at)');
            $this->addSql('CREATE INDEX ns_discr_translation_published ON nodes_sources (discr, translation_id, published_at)');
            $this->addSql('CREATE INDEX ns_title_translation_published ON nodes_sources (title, translation_id, published_at)');
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
        } elseif ($this->connection->getDatabasePlatform()->getName() === 'postgresql') {
            /*
             * POSTGRES
             */
            $this->addSql('CREATE INDEX answer_customform_submitted_at ON custom_form_answers (custom_form_id, submitted_at)');
            $this->addSql('CREATE INDEX cffattribute_answer_field ON custom_form_field_attributes (custom_form_answer_id, custom_form_field_id)');
            $this->addSql('CREATE INDEX cfield_customform_position ON custom_form_fields (custom_form_id, position)');
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
            $this->addSql('CREATE INDEX ntf_type_position ON node_type_fields (node_type_id, position)');
            $this->addSql('CREATE INDEX node_status_parent ON nodes (status, parent_node_id)');
            $this->addSql('CREATE INDEX node_nodetype_status_parent ON nodes (nodeType_id, status, parent_node_id)');
            $this->addSql('CREATE INDEX node_nodetype_status_parent_position ON nodes (nodeType_id, status, parent_node_id, position)');
            $this->addSql('CREATE INDEX node_visible_parent_position ON nodes (visible, parent_node_id, position)');
            $this->addSql('CREATE INDEX node_status_visible_parent_position ON nodes (status, visible, parent_node_id, position)');
            $this->addSql('ALTER INDEX idx_1d3d05fc7ab0e8597b00651c3445eb91 RENAME TO node_visible_status_parent');
            $this->addSql('ALTER INDEX idx_1d3d05fc7ab0e8593445eb91 RENAME TO node_visible_parent');
            $this->addSql('CREATE INDEX customform_node_position ON nodes_custom_forms (node_id, position)');
            $this->addSql('CREATE INDEX customform_node_field_position ON nodes_custom_forms (node_id, node_type_field_id, position)');
            $this->addSql('DROP INDEX idx_7c7ded6de0d4fde19caa2b25');
            $this->addSql('DROP INDEX idx_7c7ded6d2b36786be0d4fde19caa2b25');
            $this->addSql('DROP INDEX idx_7c7ded6de0d4fde14ad260649caa2b25');
            $this->addSql('CREATE INDEX ns_node_discr_translation ON nodes_sources (node_id, discr, translation_id)');
            $this->addSql('CREATE INDEX ns_node_discr_translation_published ON nodes_sources (node_id, discr, translation_id, published_at)');
            $this->addSql('CREATE INDEX ns_translation_published ON nodes_sources (translation_id, published_at)');
            $this->addSql('CREATE INDEX ns_discr_translation_published ON nodes_sources (discr, translation_id, published_at)');
            $this->addSql('CREATE INDEX ns_title_translation_published ON nodes_sources (title, translation_id, published_at)');
            $this->addSql('ALTER INDEX idx_7c7ded6d460d9fd79caa2b25e0d4fde1 RENAME TO ns_node_translation_published');
            $this->addSql('ALTER INDEX idx_7c7ded6d4ad260649caa2b25 RENAME TO ns_discr_translation');
            $this->addSql('ALTER INDEX idx_7c7ded6d2b36786be0d4fde1 RENAME TO ns_title_published');
            $this->addSql('ALTER INDEX idx_1cd104f7aa2d6147705282 RENAME TO nsdoc_field');
            $this->addSql('ALTER INDEX idx_1cd104f7aa2d6147705282462ce4f5 RENAME TO nsdoc_field_position');
            $this->addSql('ALTER INDEX idx_761f9a91fc7adece47705282 RENAME TO node_a_field');
            $this->addSql('ALTER INDEX idx_761f9a91fc7adece47705282462ce4f5 RENAME TO node_a_field_position');
            $this->addSql('ALTER INDEX idx_761f9a91eecf712047705282 RENAME TO node_b_field');
            $this->addSql('ALTER INDEX idx_761f9a91eecf712047705282462ce4f5 RENAME TO node_b_field_position');
            $this->addSql('CREATE INDEX tag_parent_position ON tags (parent_tag_id, position)');
            $this->addSql('CREATE INDEX tag_visible_position ON tags (visible, position)');
            $this->addSql('CREATE INDEX tag_parent_visible_position ON tags (parent_tag_id, visible, position)');
            $this->addSql('ALTER INDEX idx_6fbc9426f5c1a0d77ab0e859 RENAME TO tag_parent_visible');
            $this->addSql('ALTER INDEX idx_6e886f1f22010f1462ce4f5 RENAME TO tagtranslation_position');
        }
    }

    public function down(Schema $schema) : void
    {
        if ($this->connection->getDatabasePlatform()->getName() === 'mysql') {
            /*
             * MYSQL
             */
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
            $this->addSql('CREATE INDEX IDX_7C7DED6DE0D4FDE19CAA2B25 ON nodes_sources (published_at, translation_id)');
            $this->addSql('CREATE INDEX IDX_7C7DED6DE0D4FDE14AD260649CAA2B25 ON nodes_sources (published_at, discr, translation_id)');
            $this->addSql('CREATE INDEX IDX_7C7DED6D2B36786BE0D4FDE19CAA2B25 ON nodes_sources (title, published_at, translation_id)');
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
        } elseif ($this->connection->getDatabasePlatform()->getName() === 'postgresql') {
            /*
             * POSTGRES
             */
            $this->addSql('DROP INDEX cfield_customform_position');
            $this->addSql('DROP INDEX cffattribute_answer_field');
            $this->addSql('DROP INDEX folder_parent_position');
            $this->addSql('DROP INDEX ns_node_discr_translation');
            $this->addSql('DROP INDEX ns_node_discr_translation_published');
            $this->addSql('DROP INDEX ns_translation_published');
            $this->addSql('DROP INDEX ns_discr_translation_published');
            $this->addSql('DROP INDEX ns_title_translation_published');
            $this->addSql('CREATE INDEX idx_7c7ded6de0d4fde19caa2b25 ON nodes_sources (published_at, translation_id)');
            $this->addSql('CREATE INDEX idx_7c7ded6d2b36786be0d4fde19caa2b25 ON nodes_sources (title, published_at, translation_id)');
            $this->addSql('CREATE INDEX idx_7c7ded6de0d4fde14ad260649caa2b25 ON nodes_sources (published_at, discr, translation_id)');
            $this->addSql('ALTER INDEX ns_discr_translation RENAME TO idx_7c7ded6d4ad260649caa2b25');
            $this->addSql('ALTER INDEX ns_title_published RENAME TO idx_7c7ded6d2b36786be0d4fde1');
            $this->addSql('ALTER INDEX ns_node_translation_published RENAME TO idx_7c7ded6d460d9fd79caa2b25e0d4fde1');
            $this->addSql('DROP INDEX customform_node_position');
            $this->addSql('DROP INDEX customform_node_field_position');
            $this->addSql('ALTER INDEX node_a_field_position RENAME TO idx_761f9a91fc7adece47705282462ce4f5');
            $this->addSql('ALTER INDEX node_b_field RENAME TO idx_761f9a91eecf712047705282');
            $this->addSql('ALTER INDEX node_b_field_position RENAME TO idx_761f9a91eecf712047705282462ce4f5');
            $this->addSql('ALTER INDEX node_a_field RENAME TO idx_761f9a91fc7adece47705282');
            $this->addSql('ALTER INDEX tagtranslation_position RENAME TO idx_6e886f1f22010f1462ce4f5');
            $this->addSql('DROP INDEX tag_parent_position');
            $this->addSql('DROP INDEX tag_visible_position');
            $this->addSql('DROP INDEX tag_parent_visible_position');
            $this->addSql('ALTER INDEX tag_parent_visible RENAME TO idx_6fbc9426f5c1a0d77ab0e859');
            $this->addSql('DROP INDEX node_status_parent');
            $this->addSql('DROP INDEX node_nodetype_status_parent');
            $this->addSql('DROP INDEX node_nodetype_status_parent_position');
            $this->addSql('DROP INDEX node_visible_parent_position');
            $this->addSql('DROP INDEX node_status_visible_parent_position');
            $this->addSql('ALTER INDEX node_visible_parent RENAME TO idx_1d3d05fc7ab0e8593445eb91');
            $this->addSql('ALTER INDEX node_visible_status_parent RENAME TO idx_1d3d05fc7ab0e8597b00651c3445eb91');
            $this->addSql('DROP INDEX answer_customform_submitted_at');
            $this->addSql('DROP INDEX document_created_at');
            $this->addSql('DROP INDEX document_updated_at');
            $this->addSql('DROP INDEX document_raw_created_at');
            $this->addSql('DROP INDEX document_embed_platform');
            $this->addSql('DROP INDEX log_ns_datetime');
            $this->addSql('DROP INDEX log_username_datetime');
            $this->addSql('DROP INDEX log_user_datetime');
            $this->addSql('DROP INDEX log_level_datetime');
            $this->addSql('DROP INDEX log_channel_datetime');
            $this->addSql('ALTER INDEX nsdoc_field_position RENAME TO idx_1cd104f7aa2d6147705282462ce4f5');
            $this->addSql('ALTER INDEX nsdoc_field RENAME TO idx_1cd104f7aa2d6147705282');
            $this->addSql('DROP INDEX ntf_type_position');
        }
    }
}
