<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220401154003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE trainee DROP first_name_aut, DROP last_name_aut, DROP email_aut');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE trainee ADD first_name_aut VARCHAR(255) DEFAULT NULL, ADD last_name_aut VARCHAR(255) DEFAULT NULL, ADD email_aut VARCHAR(255) DEFAULT NULL');
    }
}
