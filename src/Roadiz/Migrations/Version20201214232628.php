<?php

declare(strict_types=1);

namespace RZ\Roadiz\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201214232628 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Rename groups table to usergroups to comply with MySQL 8 reserved words.';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('RENAME TABLE `groups` TO `usergroups`');
        $this->addSql('ALTER TABLE groups_roles DROP FOREIGN KEY FK_E79D4963FE54D947');
        $this->addSql('ALTER TABLE groups_roles ADD CONSTRAINT FK_E79D4963FE54D947 FOREIGN KEY (group_id) REFERENCES usergroups (id)');
        $this->addSql('ALTER TABLE users_groups DROP FOREIGN KEY FK_FF8AB7E0FE54D947');
        $this->addSql('ALTER TABLE users_groups ADD CONSTRAINT FK_FF8AB7E0FE54D947 FOREIGN KEY (group_id) REFERENCES usergroups (id)');
        $this->addSql('ALTER TABLE usergroups RENAME INDEX uniq_f06d39705e237e06 TO UNIQ_98972EB45E237E06');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('RENAME TABLE `usergroups` TO `groups`');
        $this->addSql('ALTER TABLE groups_roles DROP FOREIGN KEY FK_E79D4963FE54D947');
        $this->addSql('ALTER TABLE groups_roles ADD CONSTRAINT FK_E79D4963FE54D947 FOREIGN KEY (group_id) REFERENCES `groups` (id)');
        $this->addSql('ALTER TABLE users_groups DROP FOREIGN KEY FK_FF8AB7E0FE54D947');
        $this->addSql('ALTER TABLE users_groups ADD CONSTRAINT FK_FF8AB7E0FE54D947 FOREIGN KEY (group_id) REFERENCES `groups` (id)');
        $this->addSql('ALTER TABLE usergroups RENAME INDEX UNIQ_98972EB45E237E06 TO uniq_f06d39705e237e06');
    }
}
