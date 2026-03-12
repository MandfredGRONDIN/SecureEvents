<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260312110659 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Idempotent : n'ajoute la colonne et les contraintes que si elles n'existent pas
        $this->addSql('ALTER TABLE event ADD COLUMN IF NOT EXISTS created_by_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_3BAE0AA7B03A8386 ON event (created_by_id)');
        // PostgreSQL stocke les noms de contraintes en minuscules
        $this->addSql('DO $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = \'fk_3bae0aa7b03a8386\') THEN
                    ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7B03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE;
                END IF;
            END $$');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_IDENTIFIER_EMAIL ON "user" (email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event DROP CONSTRAINT IF EXISTS FK_3BAE0AA7B03A8386');
        $this->addSql('DROP INDEX IF EXISTS IDX_3BAE0AA7B03A8386');
        $this->addSql('ALTER TABLE event DROP COLUMN IF EXISTS created_by_id');
        $this->addSql('DROP INDEX IF EXISTS UNIQ_IDENTIFIER_EMAIL');
    }
}
