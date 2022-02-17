<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220217161017 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE action_type (id INT AUTO_INCREMENT NOT NULL, organization_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, private TINYINT(1) NOT NULL, position INT NOT NULL, machine_name VARCHAR(255) DEFAULT NULL, INDEX IDX_FA3FEC2732C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE alert (id INT AUTO_INCREMENT NOT NULL, trainee_id INT DEFAULT NULL, session_id INT DEFAULT NULL, created_at DATETIME DEFAULT NULL, INDEX IDX_17FD46C136C682D0 (trainee_id), INDEX IDX_17FD46C1613FECDF (session_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE date_session (id INT AUTO_INCREMENT NOT NULL, session_id INT DEFAULT NULL, dateBegin DATETIME NOT NULL, dateEnd DATETIME DEFAULT NULL, scheduleMorn VARCHAR(512) DEFAULT NULL, scheduleAfter VARCHAR(512) DEFAULT NULL, hour_number_morn NUMERIC(10, 2) DEFAULT NULL, hour_number_after NUMERIC(10, 2) DEFAULT NULL, place VARCHAR(512) DEFAULT NULL, INDEX IDX_E9A3D229613FECDF (session_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE evaluation_criterion (id INT AUTO_INCREMENT NOT NULL, organization_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, private TINYINT(1) NOT NULL, position INT NOT NULL, machine_name VARCHAR(255) DEFAULT NULL, INDEX IDX_AE82E9F532C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE evaluation_noted_criterion (id INT AUTO_INCREMENT NOT NULL, inscription_id INT DEFAULT NULL, criterion_id INT DEFAULT NULL, note INT NOT NULL, INDEX IDX_CF1B085E5DAC5993 (inscription_id), INDEX IDX_CF1B085E97766307 (criterion_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE presence (id INT AUTO_INCREMENT NOT NULL, inscription_id INT DEFAULT NULL, dateBegin DATETIME NOT NULL, morning VARCHAR(512) DEFAULT NULL, afternoon VARCHAR(512) DEFAULT NULL, INDEX IDX_6977C7A55DAC5993 (inscription_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE action_type ADD CONSTRAINT FK_FA3FEC2732C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE alert ADD CONSTRAINT FK_17FD46C136C682D0 FOREIGN KEY (trainee_id) REFERENCES trainee (id)');
        $this->addSql('ALTER TABLE alert ADD CONSTRAINT FK_17FD46C1613FECDF FOREIGN KEY (session_id) REFERENCES session (id)');
        $this->addSql('ALTER TABLE date_session ADD CONSTRAINT FK_E9A3D229613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE evaluation_criterion ADD CONSTRAINT FK_AE82E9F532C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE evaluation_noted_criterion ADD CONSTRAINT FK_CF1B085E5DAC5993 FOREIGN KEY (inscription_id) REFERENCES inscription (id)');
        $this->addSql('ALTER TABLE evaluation_noted_criterion ADD CONSTRAINT FK_CF1B085E97766307 FOREIGN KEY (criterion_id) REFERENCES evaluation_criterion (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE presence ADD CONSTRAINT FK_6977C7A55DAC5993 FOREIGN KEY (inscription_id) REFERENCES inscription (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE inscription ADD actiontype_id INT DEFAULT NULL, ADD motivation LONGTEXT DEFAULT NULL, ADD message LONGTEXT DEFAULT NULL, ADD refuse LONGTEXT DEFAULT NULL, ADD dif TINYINT(1) DEFAULT 0');
        $this->addSql('ALTER TABLE inscription ADD CONSTRAINT FK_5E90F6D6FE4F5A7B FOREIGN KEY (actiontype_id) REFERENCES action_type (id)');
        $this->addSql('CREATE INDEX IDX_5E90F6D6FE4F5A7B ON inscription (actiontype_id)');
        $this->addSql('ALTER TABLE session ADD name VARCHAR(255) DEFAULT NULL, ADD price DOUBLE PRECISION DEFAULT NULL, ADD teaching_cost DOUBLE PRECISION DEFAULT NULL, ADD vacation_cost DOUBLE PRECISION DEFAULT NULL, ADD accommodation_cost DOUBLE PRECISION DEFAULT NULL, ADD meal_cost DOUBLE PRECISION DEFAULT NULL, ADD transport_cost DOUBLE PRECISION DEFAULT NULL, ADD material_cost DOUBLE PRECISION DEFAULT NULL, ADD taking DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE trainee ADD birth_date VARCHAR(255) DEFAULT NULL, ADD amu_statut VARCHAR(255) DEFAULT NULL, ADD bap VARCHAR(255) DEFAULT NULL, ADD corps VARCHAR(255) DEFAULT NULL, ADD category VARCHAR(255) DEFAULT NULL, ADD campus VARCHAR(20) DEFAULT NULL, ADD first_name_sup VARCHAR(255) DEFAULT NULL, ADD last_name_sup VARCHAR(255) DEFAULT NULL, ADD email_sup VARCHAR(255) DEFAULT NULL, ADD first_name_corr VARCHAR(255) DEFAULT NULL, ADD last_name_corr VARCHAR(255) DEFAULT NULL, ADD email_corr VARCHAR(255) DEFAULT NULL, ADD first_name_aut VARCHAR(255) DEFAULT NULL, ADD last_name_aut VARCHAR(255) DEFAULT NULL, ADD email_aut VARCHAR(255) DEFAULT NULL, ADD fonction VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE inscription DROP FOREIGN KEY FK_5E90F6D6FE4F5A7B');
        $this->addSql('ALTER TABLE evaluation_noted_criterion DROP FOREIGN KEY FK_CF1B085E97766307');
        $this->addSql('DROP TABLE action_type');
        $this->addSql('DROP TABLE alert');
        $this->addSql('DROP TABLE date_session');
        $this->addSql('DROP TABLE evaluation_criterion');
        $this->addSql('DROP TABLE evaluation_noted_criterion');
        $this->addSql('DROP TABLE presence');
        $this->addSql('DROP INDEX IDX_5E90F6D6FE4F5A7B ON inscription');
        $this->addSql('ALTER TABLE inscription DROP actiontype_id, DROP motivation, DROP message, DROP refuse, DROP dif');
        $this->addSql('ALTER TABLE session DROP name, DROP price, DROP teaching_cost, DROP vacation_cost, DROP accommodation_cost, DROP meal_cost, DROP transport_cost, DROP material_cost, DROP taking');
        $this->addSql('ALTER TABLE trainee DROP birth_date, DROP amu_statut, DROP bap, DROP corps, DROP category, DROP campus, DROP first_name_sup, DROP last_name_sup, DROP email_sup, DROP first_name_corr, DROP last_name_corr, DROP email_corr, DROP first_name_aut, DROP last_name_aut, DROP email_aut, DROP fonction');
    }
}
