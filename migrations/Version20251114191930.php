<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251114191930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client CHANGE name fio VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE dish ADD image_id INT DEFAULT NULL, DROP image');
        $this->addSql('ALTER TABLE dish ADD CONSTRAINT FK_957D8CB83DA5256D FOREIGN KEY (image_id) REFERENCES file (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_957D8CB83DA5256D ON dish (image_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client CHANGE fio name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE dish DROP FOREIGN KEY FK_957D8CB83DA5256D');
        $this->addSql('DROP INDEX UNIQ_957D8CB83DA5256D ON dish');
        $this->addSql('ALTER TABLE dish ADD image VARCHAR(255) DEFAULT NULL, DROP image_id');
    }
}
