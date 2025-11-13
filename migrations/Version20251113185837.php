<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251113185837 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client CHANGE phone phone VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE dish ADD image VARCHAR(255) DEFAULT NULL, CHANGE price price NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE `order` ADD files JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client CHANGE phone phone VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE dish DROP image, CHANGE price price INT NOT NULL');
        $this->addSql('ALTER TABLE `order` DROP files');
    }
}
