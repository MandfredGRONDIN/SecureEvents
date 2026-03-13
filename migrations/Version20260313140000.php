<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute l'entité Category et la relation category sur Event (filtre par catégorie).
 */
final class Version20260313140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Table category et champ category_id sur event';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE category (id SERIAL PRIMARY KEY, name VARCHAR(100) NOT NULL, slug VARCHAR(120) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_64C19C1989D9B62 ON category (slug)');
        $this->addSql('ALTER TABLE event ADD category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3Bae0AA712469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_3Bae0AA712469DE2 ON event (category_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event DROP CONSTRAINT FK_3Bae0AA712469DE2');
        $this->addSql('DROP INDEX IDX_3Bae0AA712469DE2');
        $this->addSql('ALTER TABLE event DROP category_id');
        $this->addSql('DROP TABLE category');
    }
}
