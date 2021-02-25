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
        $this->skipIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );
        $this->skipIf($schema->hasTable('usergroups'), 'Table `usergroups` already exists.');

        $this->addSql('RENAME TABLE `groups` TO `usergroups`');
        $this->addSql('ALTER TABLE groups_roles DROP FOREIGN KEY FK_E79D4963FE54D947');
        $this->addSql('ALTER TABLE groups_roles ADD CONSTRAINT FK_E79D4963FE54D947 FOREIGN KEY (group_id) REFERENCES usergroups (id)');
        $this->addSql('ALTER TABLE users_groups DROP FOREIGN KEY FK_FF8AB7E0FE54D947');
        $this->addSql('ALTER TABLE users_groups ADD CONSTRAINT FK_FF8AB7E0FE54D947 FOREIGN KEY (group_id) REFERENCES usergroups (id)');
        // BC with MariaDB 10.2
        $this->addSql('DROP INDEX uniq_f06d39705e237e06 ON `usergroups`');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_98972EB45E237E06 ON `usergroups` (name)');
        // Only available on MariaDB 10.5
        // $this->addSql('ALTER TABLE `usergroups` RENAME INDEX uniq_f06d39705e237e06 TO UNIQ_98972EB45E237E06');
    }

    public function down(Schema $schema) : void
    {
        $this->skipIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );
        $this->skipIf($schema->hasTable('groups'), 'Table `groups` already exists.');

        $this->addSql('RENAME TABLE `usergroups` TO `groups`');
        $this->addSql('ALTER TABLE groups_roles DROP FOREIGN KEY FK_E79D4963FE54D947');
        $this->addSql('ALTER TABLE groups_roles ADD CONSTRAINT FK_E79D4963FE54D947 FOREIGN KEY (group_id) REFERENCES `groups` (id)');
        $this->addSql('ALTER TABLE users_groups DROP FOREIGN KEY FK_FF8AB7E0FE54D947');
        $this->addSql('ALTER TABLE users_groups ADD CONSTRAINT FK_FF8AB7E0FE54D947 FOREIGN KEY (group_id) REFERENCES `groups` (id)');
        // BC with MariaDB 10.2
        $this->addSql('DROP INDEX UNIQ_98972EB45E237E06 ON `groups`');
        $this->addSql('CREATE UNIQUE INDEX uniq_f06d39705e237e06 ON `groups` (name)');
        // Only available on MariaDB 10.5
        // $this->addSql('ALTER TABLE `groups` RENAME INDEX UNIQ_98972EB45E237E06 TO uniq_f06d39705e237e06');
    }

    /**
     * Temporary workaround
     *
     * @return bool
     * @see https://github.com/doctrine/migrations/issues/1104
     */
    public function isTransactional(): bool
    {
        return false;
    }
}
