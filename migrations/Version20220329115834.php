<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220329115834 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE participants_summary DROP FOREIGN KEY FK_D0E473549E92D321');
        $this->addSql('DROP INDEX IDX_D0E473549E92D321 ON participants_summary');
        $this->addSql('ALTER TABLE participants_summary DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE participants_summary CHANGE public_type_id publictype_id INT NOT NULL');
        $this->addSql('ALTER TABLE participants_summary ADD CONSTRAINT FK_D0E473543C8EC4AF FOREIGN KEY (publictype_id) REFERENCES public_type (id)');
        $this->addSql('CREATE INDEX IDX_D0E473543C8EC4AF ON participants_summary (publictype_id)');
        $this->addSql('ALTER TABLE participants_summary ADD PRIMARY KEY (session_id, publictype_id)');
        $this->addSql('ALTER TABLE trainee DROP FOREIGN KEY FK_46C68DE79E92D321');
        $this->addSql('DROP INDEX IDX_46C68DE79E92D321 ON trainee');
        $this->addSql('ALTER TABLE trainee CHANGE public_type_id publictype_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE trainee ADD CONSTRAINT FK_46C68DE73C8EC4AF FOREIGN KEY (publictype_id) REFERENCES public_type (id)');
        $this->addSql('CREATE INDEX IDX_46C68DE73C8EC4AF ON trainee (publictype_id)');
        $this->addSql('ALTER TABLE trainer DROP FOREIGN KEY FK_C51508209E92D321');
        $this->addSql('DROP INDEX IDX_C51508209E92D321 ON trainer');
        $this->addSql('ALTER TABLE trainer CHANGE public_type_id publictype_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE trainer ADD CONSTRAINT FK_C51508203C8EC4AF FOREIGN KEY (publictype_id) REFERENCES public_type (id)');
        $this->addSql('CREATE INDEX IDX_C51508203C8EC4AF ON trainer (publictype_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE participants_summary DROP FOREIGN KEY FK_D0E473543C8EC4AF');
        $this->addSql('DROP INDEX IDX_D0E473543C8EC4AF ON participants_summary');
        $this->addSql('ALTER TABLE participants_summary DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE participants_summary CHANGE publictype_id public_type_id INT NOT NULL');
        $this->addSql('ALTER TABLE participants_summary ADD CONSTRAINT FK_D0E473549E92D321 FOREIGN KEY (public_type_id) REFERENCES public_type (id)');
        $this->addSql('CREATE INDEX IDX_D0E473549E92D321 ON participants_summary (public_type_id)');
        $this->addSql('ALTER TABLE participants_summary ADD PRIMARY KEY (session_id, public_type_id)');
        $this->addSql('ALTER TABLE trainee DROP FOREIGN KEY FK_46C68DE73C8EC4AF');
        $this->addSql('DROP INDEX IDX_46C68DE73C8EC4AF ON trainee');
        $this->addSql('ALTER TABLE trainee CHANGE publictype_id public_type_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE trainee ADD CONSTRAINT FK_46C68DE79E92D321 FOREIGN KEY (public_type_id) REFERENCES public_type (id)');
        $this->addSql('CREATE INDEX IDX_46C68DE79E92D321 ON trainee (public_type_id)');
        $this->addSql('ALTER TABLE trainer DROP FOREIGN KEY FK_C51508203C8EC4AF');
        $this->addSql('DROP INDEX IDX_C51508203C8EC4AF ON trainer');
        $this->addSql('ALTER TABLE trainer CHANGE publictype_id public_type_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE trainer ADD CONSTRAINT FK_C51508209E92D321 FOREIGN KEY (public_type_id) REFERENCES public_type (id)');
        $this->addSql('CREATE INDEX IDX_C51508209E92D321 ON trainer (public_type_id)');
    }
}
