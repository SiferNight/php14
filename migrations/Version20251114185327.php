<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251114185327 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE file (id INT AUTO_INCREMENT NOT NULL, filename VARCHAR(255) NOT NULL, original_name VARCHAR(255) NOT NULL, mime_type VARCHAR(50) NOT NULL, type VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE order_files (order_id INT NOT NULL, file_id INT NOT NULL, INDEX IDX_85AB34A48D9F6D38 (order_id), INDEX IDX_85AB34A493CB796C (file_id), PRIMARY KEY(order_id, file_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE order_files ADD CONSTRAINT FK_85AB34A48D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_files ADD CONSTRAINT FK_85AB34A493CB796C FOREIGN KEY (file_id) REFERENCES file (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE client CHANGE fio name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE `order` ADD created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP files, CHANGE client_id client_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_files DROP FOREIGN KEY FK_85AB34A48D9F6D38');
        $this->addSql('ALTER TABLE order_files DROP FOREIGN KEY FK_85AB34A493CB796C');
        $this->addSql('DROP TABLE file');
        $this->addSql('DROP TABLE order_files');
        $this->addSql('ALTER TABLE client CHANGE name fio VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE `order` ADD files JSON NOT NULL, DROP created_at, CHANGE client_id client_id INT DEFAULT NULL');
    }
}
