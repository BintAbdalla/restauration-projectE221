<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250419231254 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE burger (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, price DOUBLE PRECISION NOT NULL, image VARCHAR(255) DEFAULT NULL, archived TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE complement (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, price DOUBLE PRECISION NOT NULL, image VARCHAR(255) DEFAULT NULL, type VARCHAR(20) NOT NULL, archived TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE menu (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, price DOUBLE PRECISION NOT NULL, image VARCHAR(255) DEFAULT NULL, archived TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE menu_burger (menu_id INT NOT NULL, burger_id INT NOT NULL, INDEX IDX_3CA402D5CCD7E912 (menu_id), INDEX IDX_3CA402D517CE5090 (burger_id), PRIMARY KEY(menu_id, burger_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE menu_complement (menu_id INT NOT NULL, complement_id INT NOT NULL, INDEX IDX_D909AAE6CCD7E912 (menu_id), INDEX IDX_D909AAE640D9D0AA (complement_id), PRIMARY KEY(menu_id, complement_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE menu_burger ADD CONSTRAINT FK_3CA402D5CCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE menu_burger ADD CONSTRAINT FK_3CA402D517CE5090 FOREIGN KEY (burger_id) REFERENCES burger (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE menu_complement ADD CONSTRAINT FK_D909AAE6CCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE menu_complement ADD CONSTRAINT FK_D909AAE640D9D0AA FOREIGN KEY (complement_id) REFERENCES complement (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE menu_burger DROP FOREIGN KEY FK_3CA402D5CCD7E912
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE menu_burger DROP FOREIGN KEY FK_3CA402D517CE5090
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE menu_complement DROP FOREIGN KEY FK_D909AAE6CCD7E912
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE menu_complement DROP FOREIGN KEY FK_D909AAE640D9D0AA
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE burger
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE complement
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE menu
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE menu_burger
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE menu_complement
        SQL);
    }
}
