<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Rend created_by_id nullable pour permettre la suppression de compte :
 * l'utilisateur peut supprimer son profil ; les événements qu'il a créés restent avec created_by = null.
 */
final class Version20260313120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rend created_by_id nullable (suppression de compte utilisateur)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event ALTER COLUMN created_by_id DROP NOT NULL');
        $this->addSql('ALTER TABLE event DROP CONSTRAINT IF EXISTS fk_3bae0aa7b03a8386');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT fk_3bae0aa7b03a8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE event SET created_by_id = (SELECT id FROM "user" ORDER BY id ASC LIMIT 1) WHERE created_by_id IS NULL');
        $this->addSql('ALTER TABLE event ALTER COLUMN created_by_id SET NOT NULL');
        $this->addSql('ALTER TABLE event DROP CONSTRAINT IF EXISTS fk_3bae0aa7b03a8386');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT fk_3bae0aa7b03a8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
