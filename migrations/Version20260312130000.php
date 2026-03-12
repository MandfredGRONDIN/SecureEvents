<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Le créateur d'un événement est obligatoire : created_by_id en NOT NULL.
 * Les événements existants sans créateur sont assignés au premier utilisateur.
 */
final class Version20260312130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rend le créateur obligatoire pour tout événement (created_by_id NOT NULL)';
    }

    public function up(Schema $schema): void
    {
        // Assigner le premier utilisateur aux événements sans créateur
        $this->addSql('UPDATE event SET created_by_id = (SELECT id FROM "user" ORDER BY id ASC LIMIT 1) WHERE created_by_id IS NULL');
        // Rendre la colonne obligatoire
        $this->addSql('ALTER TABLE event ALTER COLUMN created_by_id SET NOT NULL');
        // Changer ON DELETE en RESTRICT (ne plus autoriser la suppression du créateur sans réaffectation)
        $this->addSql('ALTER TABLE event DROP CONSTRAINT IF EXISTS fk_3bae0aa7b03a8386');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT fk_3bae0aa7b03a8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event ALTER COLUMN created_by_id DROP NOT NULL');
        $this->addSql('ALTER TABLE event DROP CONSTRAINT IF EXISTS fk_3bae0aa7b03a8386');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT fk_3bae0aa7b03a8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
