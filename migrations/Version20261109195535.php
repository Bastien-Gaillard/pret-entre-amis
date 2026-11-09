<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261109195535 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création des tables amis, objets et prêts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE friends (id INT UNSIGNED AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, email VARCHAR(255) DEFAULT NULL, phone VARCHAR(30) DEFAULT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX uq_friends_name (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE items (id INT UNSIGNED AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, category VARCHAR(20) NOT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, INDEX idx_items_category (category), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        // active_item_id : colonne générée qui vaut item_id tant que le prêt n'est pas rendu.
        // Son index unique interdit, au niveau de la base, deux prêts en cours pour un même objet.
        $this->addSql('CREATE TABLE loans (id INT UNSIGNED AUTO_INCREMENT NOT NULL, loaned_at DATE NOT NULL, due_at DATE DEFAULT NULL, returned_at DATE DEFAULT NULL, notes VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, active_item_id INT UNSIGNED AS (IF(returned_at IS NULL, item_id, NULL)) STORED, item_id INT UNSIGNED NOT NULL, friend_id INT UNSIGNED NOT NULL, INDEX IDX_82C24DBC126F525E (item_id), INDEX IDX_82C24DBC6A5458E8 (friend_id), INDEX idx_loans_status (returned_at, due_at), UNIQUE INDEX uq_loans_one_active_per_item (active_item_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBC126F525E FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT FK_82C24DBC6A5458E8 FOREIGN KEY (friend_id) REFERENCES friends (id) ON DELETE RESTRICT');

        // Contraintes de cohérence des dates (non gérées par Doctrine, ajoutées à la main).
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT chk_loans_due CHECK (due_at IS NULL OR due_at >= loaned_at)');
        $this->addSql('ALTER TABLE loans ADD CONSTRAINT chk_loans_returned CHECK (returned_at IS NULL OR returned_at >= loaned_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE loans DROP FOREIGN KEY FK_82C24DBC126F525E');
        $this->addSql('ALTER TABLE loans DROP FOREIGN KEY FK_82C24DBC6A5458E8');
        $this->addSql('DROP TABLE loans');
        $this->addSql('DROP TABLE items');
        $this->addSql('DROP TABLE friends');
    }
}
