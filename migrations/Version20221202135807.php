<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221202135807 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE organization DROP FOREIGN KEY FK_C1EE637C10405986');
        $this->addSql('ALTER TABLE organization CHANGE institution_id institution_id INT NOT NULL');
        $this->addSql('ALTER TABLE organization ADD CONSTRAINT FK_C1EE637C10405986 FOREIGN KEY (institution_id) REFERENCES institution (id)');
        $this->addSql('ALTER TABLE publictype CHANGE machine_name machine_name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE trainee DROP eppn');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE organization DROP FOREIGN KEY FK_C1EE637C10405986');
        $this->addSql('ALTER TABLE organization CHANGE institution_id institution_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE organization ADD CONSTRAINT FK_C1EE637C10405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE publictype CHANGE machine_name machine_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE trainee ADD eppn VARCHAR(255) DEFAULT NULL');
    }
}
