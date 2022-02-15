<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220215153152 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE email (id INT AUTO_INCREMENT NOT NULL, user_from_id INT DEFAULT NULL, trainee_id INT DEFAULT NULL, trainer_id INT DEFAULT NULL, session_id INT DEFAULT NULL, emailFrom VARCHAR(128) DEFAULT NULL, send_at DATETIME DEFAULT NULL, subject VARCHAR(512) DEFAULT NULL, cc LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', body LONGTEXT DEFAULT NULL, INDEX IDX_E7927C7420C3C701 (user_from_id), INDEX IDX_E7927C7436C682D0 (trainee_id), INDEX IDX_E7927C74FB08EDF6 (trainer_id), INDEX IDX_E7927C74613FECDF (session_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE inscription (id INT AUTO_INCREMENT NOT NULL, trainee_id INT DEFAULT NULL, session_id INT DEFAULT NULL, inscription_status_id INT DEFAULT NULL, presence_status_id INT DEFAULT NULL, type VARCHAR(255) NOT NULL, INDEX IDX_5E90F6D636C682D0 (trainee_id), INDEX IDX_5E90F6D6613FECDF (session_id), INDEX IDX_5E90F6D6CDE2C2A5 (inscription_status_id), INDEX IDX_5E90F6D6D079F0B (presence_status_id), UNIQUE INDEX traineesession_idx (trainee_id, session_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE inscription_status (id INT AUTO_INCREMENT NOT NULL, organization_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, private TINYINT(1) NOT NULL, position INT NOT NULL, status INT NOT NULL, notify TINYINT(1) NOT NULL, machine_name VARCHAR(255) DEFAULT NULL, INDEX IDX_733F116532C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE institution (id INT AUTO_INCREMENT NOT NULL, organization_id INT DEFAULT NULL, name VARCHAR(512) NOT NULL, address_type INT DEFAULT NULL, address VARCHAR(512) DEFAULT NULL, zip VARCHAR(32) DEFAULT NULL, city VARCHAR(128) DEFAULT NULL, email VARCHAR(128) DEFAULT NULL, phone_number VARCHAR(255) DEFAULT NULL, fax_number VARCHAR(255) DEFAULT NULL, website VARCHAR(512) DEFAULT NULL, type VARCHAR(255) NOT NULL, INDEX IDX_3A9F98E532C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE material (id INT AUTO_INCREMENT NOT NULL, training_id INT DEFAULT NULL, session_id INT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, is_public TINYINT(1) NOT NULL, type VARCHAR(255) NOT NULL, INDEX IDX_7CBE7595BEFD98D1 (training_id), INDEX IDX_7CBE7595613FECDF (session_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE organization (id INT AUTO_INCREMENT NOT NULL, institution_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(32) NOT NULL, trainee_registrable TINYINT(1) NOT NULL, address_type INT DEFAULT NULL, address VARCHAR(512) DEFAULT NULL, zip VARCHAR(32) DEFAULT NULL, city VARCHAR(128) DEFAULT NULL, email VARCHAR(128) DEFAULT NULL, phone_number VARCHAR(255) DEFAULT NULL, fax_number VARCHAR(255) DEFAULT NULL, website VARCHAR(512) DEFAULT NULL, type VARCHAR(255) NOT NULL, INDEX IDX_C1EE637C10405986 (institution_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE participants_summary (session_id INT NOT NULL, public_type_id INT NOT NULL, count INT DEFAULT NULL, INDEX IDX_D0E47354613FECDF (session_id), INDEX IDX_D0E473549E92D321 (public_type_id), PRIMARY KEY(session_id, public_type_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE participation (id INT AUTO_INCREMENT NOT NULL, trainer_id INT DEFAULT NULL, session_id INT DEFAULT NULL, organization_id INT DEFAULT NULL, is_organization TINYINT(1) DEFAULT NULL, type VARCHAR(255) NOT NULL, INDEX IDX_AB55E24FFB08EDF6 (trainer_id), INDEX IDX_AB55E24F613FECDF (session_id), INDEX IDX_AB55E24F32C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE presence_status (id INT AUTO_INCREMENT NOT NULL, organization_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, private TINYINT(1) NOT NULL, position INT NOT NULL, status INT NOT NULL, machine_name VARCHAR(255) DEFAULT NULL, INDEX IDX_9691D40C32C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE public_type (id INT AUTO_INCREMENT NOT NULL, organization_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, private TINYINT(1) NOT NULL, position INT NOT NULL, machine_name VARCHAR(255) DEFAULT NULL, INDEX IDX_F78315F832C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE publipost_template (id INT AUTO_INCREMENT NOT NULL, organization_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, private TINYINT(1) NOT NULL, position INT NOT NULL, entity LONGTEXT NOT NULL, machine_name VARCHAR(255) DEFAULT NULL, file_path VARCHAR(255) DEFAULT NULL, file_name VARCHAR(255) DEFAULT NULL, uploaded DATETIME DEFAULT NULL, INDEX IDX_82319DD032C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE session (id INT AUTO_INCREMENT NOT NULL, training_id INT DEFAULT NULL, promote TINYINT(1) NOT NULL, dateBegin DATETIME NOT NULL, dateEnd DATETIME DEFAULT NULL, registration INT NOT NULL, status INT NOT NULL, displayOnline TINYINT(1) NOT NULL, numberOfRegistrations INT DEFAULT NULL, maximumNumberOfRegistrations INT NOT NULL, limitRegistrationDate DATETIME NOT NULL, comments LONGTEXT DEFAULT NULL, hourNumber DOUBLE PRECISION NOT NULL, dayNumber DOUBLE PRECISION NOT NULL, schedule VARCHAR(512) DEFAULT NULL, place LONGTEXT DEFAULT NULL, sessionType_id INT DEFAULT NULL, type VARCHAR(255) NOT NULL, INDEX IDX_D044D5D4BEFD98D1 (training_id), INDEX IDX_D044D5D49CA958D5 (sessionType_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE session_type (id INT AUTO_INCREMENT NOT NULL, organization_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, private TINYINT(1) NOT NULL, position INT NOT NULL, machine_name VARCHAR(255) DEFAULT NULL, INDEX IDX_4AAF570332C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE supervisor (id INT AUTO_INCREMENT NOT NULL, organization_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, private TINYINT(1) NOT NULL, position INT NOT NULL, first_name VARCHAR(50) DEFAULT NULL, email VARCHAR(128) DEFAULT NULL, phone_number VARCHAR(255) DEFAULT NULL, machine_name VARCHAR(255) DEFAULT NULL, INDEX IDX_4D9192F832C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tag (id INT AUTO_INCREMENT NOT NULL, organization_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, private TINYINT(1) NOT NULL, position INT NOT NULL, machine_name VARCHAR(255) DEFAULT NULL, INDEX IDX_389B78332C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE theme (id INT AUTO_INCREMENT NOT NULL, organization_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, private TINYINT(1) NOT NULL, position INT NOT NULL, machine_name VARCHAR(255) DEFAULT NULL, INDEX IDX_9775E70832C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE title (id INT AUTO_INCREMENT NOT NULL, organization_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, private TINYINT(1) NOT NULL, position INT NOT NULL, machine_name VARCHAR(255) DEFAULT NULL, INDEX IDX_2B36786B32C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE trainee (id INT AUTO_INCREMENT NOT NULL, organization_id INT DEFAULT NULL, title_id INT DEFAULT NULL, institution_id INT DEFAULT NULL, public_type_id INT DEFAULT NULL, salt VARCHAR(32) NOT NULL, password VARCHAR(255) NOT NULL, is_active TINYINT(1) NOT NULL, shibboleth_persistent_id VARCHAR(255) DEFAULT NULL, eppn VARCHAR(255) DEFAULT NULL, data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', first_name VARCHAR(50) DEFAULT NULL, last_name VARCHAR(50) NOT NULL, address_type INT DEFAULT NULL, address VARCHAR(512) DEFAULT NULL, zip VARCHAR(32) DEFAULT NULL, city VARCHAR(128) DEFAULT NULL, email VARCHAR(128) DEFAULT NULL, phone_number VARCHAR(255) DEFAULT NULL, fax_number VARCHAR(255) DEFAULT NULL, website VARCHAR(512) DEFAULT NULL, service VARCHAR(255) DEFAULT NULL, is_paying TINYINT(1) NOT NULL, status VARCHAR(512) DEFAULT NULL, type VARCHAR(255) NOT NULL, INDEX IDX_46C68DE732C8A3DE (organization_id), INDEX IDX_46C68DE7A9F87BD (title_id), INDEX IDX_46C68DE710405986 (institution_id), INDEX IDX_46C68DE79E92D321 (public_type_id), UNIQUE INDEX emailUnique (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE trainee_email_template (id INT AUTO_INCREMENT NOT NULL, inscription_status_id INT DEFAULT NULL, presence_status_id INT DEFAULT NULL, organization_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, private TINYINT(1) NOT NULL, position INT NOT NULL, subject VARCHAR(255) NOT NULL, cc LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', body LONGTEXT NOT NULL, machine_name VARCHAR(255) DEFAULT NULL, INDEX IDX_811201CFCDE2C2A5 (inscription_status_id), INDEX IDX_811201CFD079F0B (presence_status_id), INDEX IDX_811201CF32C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE email_templates__publipost_templates (email_template_id INT NOT NULL, publipost_template_id INT NOT NULL, INDEX IDX_98984A89131A730F (email_template_id), INDEX IDX_98984A897EF904EF (publipost_template_id), PRIMARY KEY(email_template_id, publipost_template_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE trainer (id INT AUTO_INCREMENT NOT NULL, organization_id INT DEFAULT NULL, trainer_type_id INT DEFAULT NULL, title_id INT DEFAULT NULL, institution_id INT DEFAULT NULL, public_type_id INT DEFAULT NULL, is_archived TINYINT(1) DEFAULT NULL, is_allow_send_mail TINYINT(1) DEFAULT NULL, is_organization TINYINT(1) DEFAULT NULL, is_public TINYINT(1) NOT NULL, comments LONGTEXT DEFAULT NULL, first_name VARCHAR(50) DEFAULT NULL, last_name VARCHAR(50) NOT NULL, address_type INT DEFAULT NULL, address VARCHAR(512) DEFAULT NULL, zip VARCHAR(32) DEFAULT NULL, city VARCHAR(128) DEFAULT NULL, email VARCHAR(128) DEFAULT NULL, phone_number VARCHAR(255) DEFAULT NULL, fax_number VARCHAR(255) DEFAULT NULL, website VARCHAR(512) DEFAULT NULL, service VARCHAR(255) DEFAULT NULL, is_paying TINYINT(1) NOT NULL, status VARCHAR(512) DEFAULT NULL, type VARCHAR(255) NOT NULL, INDEX IDX_C515082032C8A3DE (organization_id), INDEX IDX_C5150820973B55C (trainer_type_id), INDEX IDX_C5150820A9F87BD (title_id), INDEX IDX_C515082010405986 (institution_id), INDEX IDX_C51508209E92D321 (public_type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE trainer_type (id INT AUTO_INCREMENT NOT NULL, organization_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, private TINYINT(1) NOT NULL, position INT NOT NULL, machine_name VARCHAR(255) DEFAULT NULL, INDEX IDX_79EE624932C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE training (id INT AUTO_INCREMENT NOT NULL, organization_id INT NOT NULL, theme_id INT NOT NULL, institution_id INT DEFAULT NULL, supervisor_id INT DEFAULT NULL, category_id INT DEFAULT NULL, number INT NOT NULL, name VARCHAR(255) NOT NULL, program LONGTEXT DEFAULT NULL, description LONGTEXT DEFAULT NULL, teaching_methods LONGTEXT DEFAULT NULL, interventionType VARCHAR(255) DEFAULT NULL, externalInitiative TINYINT(1) DEFAULT NULL, firstSessionPeriodSemester INT NOT NULL, firstSessionPeriodYear INT NOT NULL, comments LONGTEXT DEFAULT NULL, type VARCHAR(255) NOT NULL, INDEX IDX_D5128A8F32C8A3DE (organization_id), INDEX IDX_D5128A8F59027487 (theme_id), INDEX IDX_D5128A8F10405986 (institution_id), INDEX IDX_D5128A8F19E9AC5F (supervisor_id), INDEX IDX_D5128A8F12469DE2 (category_id), UNIQUE INDEX organization_number (number, organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE training__training_tag (training_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_BDC5CDF2BEFD98D1 (training_id), INDEX IDX_BDC5CDF2BAD26311 (tag_id), PRIMARY KEY(training_id, tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE training_category (id INT AUTO_INCREMENT NOT NULL, organization_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, private TINYINT(1) NOT NULL, position INT NOT NULL, trainingType VARCHAR(256) DEFAULT NULL, machine_name VARCHAR(255) DEFAULT NULL, INDEX IDX_E1290A5632C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, organization_id INT DEFAULT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, access_rights LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), INDEX IDX_8D93D64932C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE email ADD CONSTRAINT FK_E7927C7420C3C701 FOREIGN KEY (user_from_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE email ADD CONSTRAINT FK_E7927C7436C682D0 FOREIGN KEY (trainee_id) REFERENCES trainee (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE email ADD CONSTRAINT FK_E7927C74FB08EDF6 FOREIGN KEY (trainer_id) REFERENCES trainer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE email ADD CONSTRAINT FK_E7927C74613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE inscription ADD CONSTRAINT FK_5E90F6D636C682D0 FOREIGN KEY (trainee_id) REFERENCES trainee (id)');
        $this->addSql('ALTER TABLE inscription ADD CONSTRAINT FK_5E90F6D6613FECDF FOREIGN KEY (session_id) REFERENCES session (id)');
        $this->addSql('ALTER TABLE inscription ADD CONSTRAINT FK_5E90F6D6CDE2C2A5 FOREIGN KEY (inscription_status_id) REFERENCES inscription_status (id)');
        $this->addSql('ALTER TABLE inscription ADD CONSTRAINT FK_5E90F6D6D079F0B FOREIGN KEY (presence_status_id) REFERENCES presence_status (id)');
        $this->addSql('ALTER TABLE inscription_status ADD CONSTRAINT FK_733F116532C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE institution ADD CONSTRAINT FK_3A9F98E532C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE material ADD CONSTRAINT FK_7CBE7595BEFD98D1 FOREIGN KEY (training_id) REFERENCES training (id)');
        $this->addSql('ALTER TABLE material ADD CONSTRAINT FK_7CBE7595613FECDF FOREIGN KEY (session_id) REFERENCES session (id)');
        $this->addSql('ALTER TABLE organization ADD CONSTRAINT FK_C1EE637C10405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE participants_summary ADD CONSTRAINT FK_D0E47354613FECDF FOREIGN KEY (session_id) REFERENCES session (id)');
        $this->addSql('ALTER TABLE participants_summary ADD CONSTRAINT FK_D0E473549E92D321 FOREIGN KEY (public_type_id) REFERENCES public_type (id)');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24FFB08EDF6 FOREIGN KEY (trainer_id) REFERENCES trainer (id)');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24F613FECDF FOREIGN KEY (session_id) REFERENCES session (id)');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24F32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE presence_status ADD CONSTRAINT FK_9691D40C32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE public_type ADD CONSTRAINT FK_F78315F832C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE publipost_template ADD CONSTRAINT FK_82319DD032C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_D044D5D4BEFD98D1 FOREIGN KEY (training_id) REFERENCES training (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_D044D5D49CA958D5 FOREIGN KEY (sessionType_id) REFERENCES session_type (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE session_type ADD CONSTRAINT FK_4AAF570332C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE supervisor ADD CONSTRAINT FK_4D9192F832C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tag ADD CONSTRAINT FK_389B78332C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE theme ADD CONSTRAINT FK_9775E70832C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE title ADD CONSTRAINT FK_2B36786B32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trainee ADD CONSTRAINT FK_46C68DE732C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE trainee ADD CONSTRAINT FK_46C68DE7A9F87BD FOREIGN KEY (title_id) REFERENCES title (id)');
        $this->addSql('ALTER TABLE trainee ADD CONSTRAINT FK_46C68DE710405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE trainee ADD CONSTRAINT FK_46C68DE79E92D321 FOREIGN KEY (public_type_id) REFERENCES public_type (id)');
        $this->addSql('ALTER TABLE trainee_email_template ADD CONSTRAINT FK_811201CFCDE2C2A5 FOREIGN KEY (inscription_status_id) REFERENCES inscription_status (id)');
        $this->addSql('ALTER TABLE trainee_email_template ADD CONSTRAINT FK_811201CFD079F0B FOREIGN KEY (presence_status_id) REFERENCES presence_status (id)');
        $this->addSql('ALTER TABLE trainee_email_template ADD CONSTRAINT FK_811201CF32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE email_templates__publipost_templates ADD CONSTRAINT FK_98984A89131A730F FOREIGN KEY (email_template_id) REFERENCES trainee_email_template (id)');
        $this->addSql('ALTER TABLE email_templates__publipost_templates ADD CONSTRAINT FK_98984A897EF904EF FOREIGN KEY (publipost_template_id) REFERENCES publipost_template (id)');
        $this->addSql('ALTER TABLE trainer ADD CONSTRAINT FK_C515082032C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE trainer ADD CONSTRAINT FK_C5150820973B55C FOREIGN KEY (trainer_type_id) REFERENCES trainer_type (id)');
        $this->addSql('ALTER TABLE trainer ADD CONSTRAINT FK_C5150820A9F87BD FOREIGN KEY (title_id) REFERENCES title (id)');
        $this->addSql('ALTER TABLE trainer ADD CONSTRAINT FK_C515082010405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE trainer ADD CONSTRAINT FK_C51508209E92D321 FOREIGN KEY (public_type_id) REFERENCES public_type (id)');
        $this->addSql('ALTER TABLE trainer_type ADD CONSTRAINT FK_79EE624932C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE training ADD CONSTRAINT FK_D5128A8F32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE training ADD CONSTRAINT FK_D5128A8F59027487 FOREIGN KEY (theme_id) REFERENCES theme (id)');
        $this->addSql('ALTER TABLE training ADD CONSTRAINT FK_D5128A8F10405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE training ADD CONSTRAINT FK_D5128A8F19E9AC5F FOREIGN KEY (supervisor_id) REFERENCES supervisor (id)');
        $this->addSql('ALTER TABLE training ADD CONSTRAINT FK_D5128A8F12469DE2 FOREIGN KEY (category_id) REFERENCES training_category (id)');
        $this->addSql('ALTER TABLE training__training_tag ADD CONSTRAINT FK_BDC5CDF2BEFD98D1 FOREIGN KEY (training_id) REFERENCES training (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE training__training_tag ADD CONSTRAINT FK_BDC5CDF2BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE training_category ADD CONSTRAINT FK_E1290A5632C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64932C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE inscription DROP FOREIGN KEY FK_5E90F6D6CDE2C2A5');
        $this->addSql('ALTER TABLE trainee_email_template DROP FOREIGN KEY FK_811201CFCDE2C2A5');
        $this->addSql('ALTER TABLE organization DROP FOREIGN KEY FK_C1EE637C10405986');
        $this->addSql('ALTER TABLE trainee DROP FOREIGN KEY FK_46C68DE710405986');
        $this->addSql('ALTER TABLE trainer DROP FOREIGN KEY FK_C515082010405986');
        $this->addSql('ALTER TABLE training DROP FOREIGN KEY FK_D5128A8F10405986');
        $this->addSql('ALTER TABLE inscription_status DROP FOREIGN KEY FK_733F116532C8A3DE');
        $this->addSql('ALTER TABLE institution DROP FOREIGN KEY FK_3A9F98E532C8A3DE');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24F32C8A3DE');
        $this->addSql('ALTER TABLE presence_status DROP FOREIGN KEY FK_9691D40C32C8A3DE');
        $this->addSql('ALTER TABLE public_type DROP FOREIGN KEY FK_F78315F832C8A3DE');
        $this->addSql('ALTER TABLE publipost_template DROP FOREIGN KEY FK_82319DD032C8A3DE');
        $this->addSql('ALTER TABLE session_type DROP FOREIGN KEY FK_4AAF570332C8A3DE');
        $this->addSql('ALTER TABLE supervisor DROP FOREIGN KEY FK_4D9192F832C8A3DE');
        $this->addSql('ALTER TABLE tag DROP FOREIGN KEY FK_389B78332C8A3DE');
        $this->addSql('ALTER TABLE theme DROP FOREIGN KEY FK_9775E70832C8A3DE');
        $this->addSql('ALTER TABLE title DROP FOREIGN KEY FK_2B36786B32C8A3DE');
        $this->addSql('ALTER TABLE trainee DROP FOREIGN KEY FK_46C68DE732C8A3DE');
        $this->addSql('ALTER TABLE trainee_email_template DROP FOREIGN KEY FK_811201CF32C8A3DE');
        $this->addSql('ALTER TABLE trainer DROP FOREIGN KEY FK_C515082032C8A3DE');
        $this->addSql('ALTER TABLE trainer_type DROP FOREIGN KEY FK_79EE624932C8A3DE');
        $this->addSql('ALTER TABLE training DROP FOREIGN KEY FK_D5128A8F32C8A3DE');
        $this->addSql('ALTER TABLE training_category DROP FOREIGN KEY FK_E1290A5632C8A3DE');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64932C8A3DE');
        $this->addSql('ALTER TABLE inscription DROP FOREIGN KEY FK_5E90F6D6D079F0B');
        $this->addSql('ALTER TABLE trainee_email_template DROP FOREIGN KEY FK_811201CFD079F0B');
        $this->addSql('ALTER TABLE participants_summary DROP FOREIGN KEY FK_D0E473549E92D321');
        $this->addSql('ALTER TABLE trainee DROP FOREIGN KEY FK_46C68DE79E92D321');
        $this->addSql('ALTER TABLE trainer DROP FOREIGN KEY FK_C51508209E92D321');
        $this->addSql('ALTER TABLE email_templates__publipost_templates DROP FOREIGN KEY FK_98984A897EF904EF');
        $this->addSql('ALTER TABLE email DROP FOREIGN KEY FK_E7927C74613FECDF');
        $this->addSql('ALTER TABLE inscription DROP FOREIGN KEY FK_5E90F6D6613FECDF');
        $this->addSql('ALTER TABLE material DROP FOREIGN KEY FK_7CBE7595613FECDF');
        $this->addSql('ALTER TABLE participants_summary DROP FOREIGN KEY FK_D0E47354613FECDF');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24F613FECDF');
        $this->addSql('ALTER TABLE session DROP FOREIGN KEY FK_D044D5D49CA958D5');
        $this->addSql('ALTER TABLE training DROP FOREIGN KEY FK_D5128A8F19E9AC5F');
        $this->addSql('ALTER TABLE training__training_tag DROP FOREIGN KEY FK_BDC5CDF2BAD26311');
        $this->addSql('ALTER TABLE training DROP FOREIGN KEY FK_D5128A8F59027487');
        $this->addSql('ALTER TABLE trainee DROP FOREIGN KEY FK_46C68DE7A9F87BD');
        $this->addSql('ALTER TABLE trainer DROP FOREIGN KEY FK_C5150820A9F87BD');
        $this->addSql('ALTER TABLE email DROP FOREIGN KEY FK_E7927C7436C682D0');
        $this->addSql('ALTER TABLE inscription DROP FOREIGN KEY FK_5E90F6D636C682D0');
        $this->addSql('ALTER TABLE email_templates__publipost_templates DROP FOREIGN KEY FK_98984A89131A730F');
        $this->addSql('ALTER TABLE email DROP FOREIGN KEY FK_E7927C74FB08EDF6');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24FFB08EDF6');
        $this->addSql('ALTER TABLE trainer DROP FOREIGN KEY FK_C5150820973B55C');
        $this->addSql('ALTER TABLE material DROP FOREIGN KEY FK_7CBE7595BEFD98D1');
        $this->addSql('ALTER TABLE session DROP FOREIGN KEY FK_D044D5D4BEFD98D1');
        $this->addSql('ALTER TABLE training__training_tag DROP FOREIGN KEY FK_BDC5CDF2BEFD98D1');
        $this->addSql('ALTER TABLE training DROP FOREIGN KEY FK_D5128A8F12469DE2');
        $this->addSql('ALTER TABLE email DROP FOREIGN KEY FK_E7927C7420C3C701');
        $this->addSql('DROP TABLE email');
        $this->addSql('DROP TABLE inscription');
        $this->addSql('DROP TABLE inscription_status');
        $this->addSql('DROP TABLE institution');
        $this->addSql('DROP TABLE material');
        $this->addSql('DROP TABLE organization');
        $this->addSql('DROP TABLE participants_summary');
        $this->addSql('DROP TABLE participation');
        $this->addSql('DROP TABLE presence_status');
        $this->addSql('DROP TABLE public_type');
        $this->addSql('DROP TABLE publipost_template');
        $this->addSql('DROP TABLE session');
        $this->addSql('DROP TABLE session_type');
        $this->addSql('DROP TABLE supervisor');
        $this->addSql('DROP TABLE tag');
        $this->addSql('DROP TABLE theme');
        $this->addSql('DROP TABLE title');
        $this->addSql('DROP TABLE trainee');
        $this->addSql('DROP TABLE trainee_email_template');
        $this->addSql('DROP TABLE email_templates__publipost_templates');
        $this->addSql('DROP TABLE trainer');
        $this->addSql('DROP TABLE trainer_type');
        $this->addSql('DROP TABLE training');
        $this->addSql('DROP TABLE training__training_tag');
        $this->addSql('DROP TABLE training_category');
        $this->addSql('DROP TABLE user');
    }
}
