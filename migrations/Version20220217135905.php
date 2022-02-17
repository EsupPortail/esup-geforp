<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220217135905 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE internship (id INT NOT NULL, prerequisites LONGTEXT DEFAULT NULL, designated_public TINYINT(1) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE internship__internship_public_type (intership_id INT NOT NULL, public_type_id INT NOT NULL, INDEX IDX_EFCEC7479495B42F (intership_id), INDEX IDX_EFCEC7479E92D321 (public_type_id), PRIMARY KEY(intership_id, public_type_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE internship__internship_public_type_restrict (intership_id INT NOT NULL, public_type_restrict_id INT NOT NULL, INDEX IDX_D3D52E5E9495B42F (intership_id), INDEX IDX_D3D52E5ED0C812B8 (public_type_restrict_id), PRIMARY KEY(intership_id, public_type_restrict_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE internship ADD CONSTRAINT FK_10D1B00CBF396750 FOREIGN KEY (id) REFERENCES training (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE internship__internship_public_type ADD CONSTRAINT FK_EFCEC7479495B42F FOREIGN KEY (intership_id) REFERENCES internship (id)');
        $this->addSql('ALTER TABLE internship__internship_public_type ADD CONSTRAINT FK_EFCEC7479E92D321 FOREIGN KEY (public_type_id) REFERENCES public_type (id)');
        $this->addSql('ALTER TABLE internship__internship_public_type_restrict ADD CONSTRAINT FK_D3D52E5E9495B42F FOREIGN KEY (intership_id) REFERENCES internship (id)');
        $this->addSql('ALTER TABLE internship__internship_public_type_restrict ADD CONSTRAINT FK_D3D52E5ED0C812B8 FOREIGN KEY (public_type_restrict_id) REFERENCES public_type (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE internship__internship_public_type DROP FOREIGN KEY FK_EFCEC7479495B42F');
        $this->addSql('ALTER TABLE internship__internship_public_type_restrict DROP FOREIGN KEY FK_D3D52E5E9495B42F');
        $this->addSql('DROP TABLE internship');
        $this->addSql('DROP TABLE internship__internship_public_type');
        $this->addSql('DROP TABLE internship__internship_public_type_restrict');
    }
}
