<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute la relation createdBy (créateur) sur l'entité Event.
 */
final class Version20260312000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la colonne created_by_id sur event pour lier l\'événement à son créateur';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7B03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7B03A8386 ON event (created_by_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event DROP CONSTRAINT FK_3BAE0AA7B03A8386');
        $this->addSql('DROP INDEX IDX_3BAE0AA7B03A8386');
        $this->addSql('ALTER TABLE event DROP created_by_id');
    }
}
