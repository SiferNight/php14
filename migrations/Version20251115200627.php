<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251115200627 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_files DROP FOREIGN KEY FK_85AB34A48D9F6D38');
        $this->addSql('ALTER TABLE order_files DROP FOREIGN KEY FK_85AB34A493CB796C');
        $this->addSql('DROP TABLE order_files');
        $this->addSql('ALTER TABLE file ADD order_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE file ADD CONSTRAINT FK_8C9F36108D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_8C9F36108D9F6D38 ON file (order_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE order_files (order_id INT NOT NULL, file_id INT NOT NULL, INDEX IDX_85AB34A48D9F6D38 (order_id), INDEX IDX_85AB34A493CB796C (file_id), PRIMARY KEY(order_id, file_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE order_files ADD CONSTRAINT FK_85AB34A48D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_files ADD CONSTRAINT FK_85AB34A493CB796C FOREIGN KEY (file_id) REFERENCES file (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE file DROP FOREIGN KEY FK_8C9F36108D9F6D38');
        $this->addSql('DROP INDEX IDX_8C9F36108D9F6D38 ON file');
        $this->addSql('ALTER TABLE file DROP order_id');
    }
}
