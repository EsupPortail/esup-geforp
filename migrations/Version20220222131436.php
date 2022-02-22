<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220222131436 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE accesstoken');
        $this->addSql('DROP TABLE disciplinary');
        $this->addSql('ALTER TABLE date_session ADD hourNumberMorn NUMERIC(10, 2) DEFAULT NULL, ADD hourNumberAfter NUMERIC(10, 2) DEFAULT NULL, DROP hour_number_morn, DROP hour_number_after');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE accesstoken (id INT NOT NULL, client_id INT NOT NULL, user_id INT DEFAULT NULL, token VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, expires_at INT DEFAULT NULL, scope VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE disciplinary (id INT NOT NULL, organization_id INT DEFAULT NULL, name VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, private TINYINT(1) NOT NULL, position INT NOT NULL, machine_name VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE date_session ADD hour_number_morn NUMERIC(10, 2) DEFAULT NULL, ADD hour_number_after NUMERIC(10, 2) DEFAULT NULL, DROP hourNumberMorn, DROP hourNumberAfter');
    }
}
