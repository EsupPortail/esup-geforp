<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221121103810 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE image_file (id INT AUTO_INCREMENT NOT NULL, organization_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, private TINYINT(1) NOT NULL, position INT NOT NULL, machine_name VARCHAR(255) DEFAULT NULL, file_path VARCHAR(255) NOT NULL, file_name VARCHAR(255) NOT NULL, uploaded DATETIME NOT NULL, INDEX IDX_7EA5DC8E32C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE image_file ADD CONSTRAINT FK_7EA5DC8E32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE organization CHANGE institution_id institution_id INT NOT NULL');
        $this->addSql('ALTER TABLE publictype CHANGE machine_name machine_name VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE image_file DROP FOREIGN KEY FK_7EA5DC8E32C8A3DE');
        $this->addSql('DROP TABLE image_file');
        $this->addSql('ALTER TABLE organization CHANGE institution_id institution_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE publictype CHANGE machine_name machine_name VARCHAR(255) DEFAULT NULL');
    }
}
