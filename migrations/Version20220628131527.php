<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220628131527 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE file_material CHANGE file_path file_path VARCHAR(255) NOT NULL, CHANGE file_name file_name VARCHAR(255) NOT NULL, CHANGE uploaded uploaded DATETIME NOT NULL');
        $this->addSql('ALTER TABLE material DROP is_public');
        $this->addSql('ALTER TABLE publipost_template CHANGE file_path file_path VARCHAR(255) NOT NULL, CHANGE file_name file_name VARCHAR(255) NOT NULL, CHANGE uploaded uploaded DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE file_material CHANGE file_path file_path VARCHAR(255) DEFAULT NULL, CHANGE file_name file_name VARCHAR(255) DEFAULT NULL, CHANGE uploaded uploaded DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE material ADD is_public TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE publipost_template CHANGE file_path file_path VARCHAR(255) DEFAULT NULL, CHANGE file_name file_name VARCHAR(255) DEFAULT NULL, CHANGE uploaded uploaded DATETIME DEFAULT NULL');
    }
}
