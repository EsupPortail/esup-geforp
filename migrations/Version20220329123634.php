<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220329123634 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE participants_summary ADD CONSTRAINT FK_D0E473543C8EC4AF FOREIGN KEY (publictype_id) REFERENCES publictype (id)');
        $this->addSql('CREATE INDEX IDX_D0E473543C8EC4AF ON participants_summary (publictype_id)');
        $this->addSql('ALTER TABLE trainee ADD CONSTRAINT FK_46C68DE73C8EC4AF FOREIGN KEY (publictype_id) REFERENCES publictype (id)');
        $this->addSql('ALTER TABLE trainer ADD CONSTRAINT FK_C51508203C8EC4AF FOREIGN KEY (publictype_id) REFERENCES publictype (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE participants_summary DROP FOREIGN KEY FK_D0E473543C8EC4AF');
        $this->addSql('DROP INDEX IDX_D0E473543C8EC4AF ON participants_summary');
        $this->addSql('ALTER TABLE trainee DROP FOREIGN KEY FK_46C68DE73C8EC4AF');
        $this->addSql('ALTER TABLE trainer DROP FOREIGN KEY FK_C51508203C8EC4AF');
    }
}
