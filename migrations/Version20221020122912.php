<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221020122912 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE institution__institution_domain (institution_id INT NOT NULL, domain_id INT NOT NULL, INDEX IDX_7CA9D6F210405986 (institution_id), INDEX IDX_7CA9D6F2115F0EE5 (domain_id), PRIMARY KEY(institution_id, domain_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE institution__institution_domain ADD CONSTRAINT FK_7CA9D6F210405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE institution__institution_domain ADD CONSTRAINT FK_7CA9D6F2115F0EE5 FOREIGN KEY (domain_id) REFERENCES domain (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE institution DROP FOREIGN KEY FK_3A9F98E532C8A3DE');
        $this->addSql('DROP INDEX IDX_3A9F98E532C8A3DE ON institution');
        $this->addSql('ALTER TABLE institution DROP organization_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE institution__institution_domain DROP FOREIGN KEY FK_7CA9D6F210405986');
        $this->addSql('ALTER TABLE institution__institution_domain DROP FOREIGN KEY FK_7CA9D6F2115F0EE5');
        $this->addSql('DROP TABLE institution__institution_domain');
        $this->addSql('ALTER TABLE institution ADD organization_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE institution ADD CONSTRAINT FK_3A9F98E532C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('CREATE INDEX IDX_3A9F98E532C8A3DE ON institution (organization_id)');
    }
}
