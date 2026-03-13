<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Table des tokens de réinitialisation de mot de passe (lien magique par email).
 */
final class Version20260313160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Table reset_password_token pour la réinitialisation du mot de passe';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE reset_password_token (
            id SERIAL PRIMARY KEY,
            token VARCHAR(64) NOT NULL,
            user_id INT NOT NULL,
            expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_RESET_TOKEN ON reset_password_token (token)');
        $this->addSql('CREATE INDEX IDX_RESET_USER ON reset_password_token (user_id)');
        $this->addSql('ALTER TABLE reset_password_token ADD CONSTRAINT FK_RESET_USER FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reset_password_token DROP CONSTRAINT FK_RESET_USER');
        $this->addSql('DROP INDEX IDX_RESET_USER');
        $this->addSql('DROP INDEX UNIQ_RESET_TOKEN');
        $this->addSql('DROP TABLE reset_password_token');
    }
}
