<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Une seule réservation par (événement, participant).
 */
final class Version20260312120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Contrainte d\'unicité sur reservation (event_id, participant_id) : un utilisateur ne peut réserver qu\'une fois par événement.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX UNIQ_RESERVATION_EVENT_PARTICIPANT ON reservation (event_id, participant_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_RESERVATION_EVENT_PARTICIPANT');
    }
}
