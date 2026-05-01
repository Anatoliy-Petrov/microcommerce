<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260429000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users and refresh_tokens tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            CREATE TABLE users (
                id            BINARY(16)   NOT NULL COMMENT '(DC2Type:uuid)',
                email         VARCHAR(255) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                created_at    DATETIME     NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                updated_at    DATETIME     NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                PRIMARY KEY (id),
                UNIQUE INDEX UNIQ_users_email (email)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<SQL
            CREATE TABLE refresh_tokens (
                id         BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                user_id    BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)',
                token_hash CHAR(64)   NOT NULL,
                expires_at DATETIME   NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                revoked_at DATETIME   DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                PRIMARY KEY (id),
                UNIQUE INDEX UNIQ_refresh_tokens_hash (token_hash),
                INDEX IDX_refresh_tokens_user (user_id),
                CONSTRAINT FK_refresh_tokens_user
                    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE refresh_tokens');
        $this->addSql('DROP TABLE users');
    }
}