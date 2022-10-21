<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221021075759 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE trainee DROP FOREIGN KEY FK_46C68DE732C8A3DE');
        $this->addSql('ALTER TABLE trainee DROP FOREIGN KEY FK_46C68DE710405986');
        $this->addSql('DROP INDEX IDX_46C68DE732C8A3DE ON trainee');
        $this->addSql('ALTER TABLE trainee DROP organization_id');
        $this->addSql('ALTER TABLE trainee ADD CONSTRAINT FK_46C68DE710405986 FOREIGN KEY (institution_id) REFERENCES institution (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE trainee DROP FOREIGN KEY FK_46C68DE710405986');
        $this->addSql('ALTER TABLE trainee ADD organization_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE trainee ADD CONSTRAINT FK_46C68DE732C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE trainee ADD CONSTRAINT FK_46C68DE710405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_46C68DE732C8A3DE ON trainee (organization_id)');
    }
}
