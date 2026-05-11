<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260511000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create payments and webhook_events tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            CREATE TABLE payments (
                id                   CHAR(36)      NOT NULL,
                order_id             CHAR(36)      NOT NULL,
                user_id              CHAR(36)      NOT NULL,
                provider             VARCHAR(50)   NOT NULL,
                provider_payment_id  VARCHAR(255)  DEFAULT NULL,
                provider_session_id  VARCHAR(255)  DEFAULT NULL,
                amount               INT           NOT NULL,
                currency             VARCHAR(3)    NOT NULL DEFAULT 'usd',
                status               VARCHAR(20)   NOT NULL DEFAULT 'pending',
                created_at           DATETIME      NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                updated_at           DATETIME      NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                PRIMARY KEY (id),
                INDEX IDX_payments_order (order_id),
                INDEX IDX_payments_status (status)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<SQL
            CREATE TABLE webhook_events (
                id                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
                provider          VARCHAR(50)   NOT NULL,
                provider_event_id VARCHAR(255)  NOT NULL,
                type              VARCHAR(100)  NOT NULL,
                processed_at      DATETIME      NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                error             TEXT          DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE INDEX UNIQ_webhook_provider_event (provider, provider_event_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE webhook_events');
        $this->addSql('DROP TABLE payments');
    }
}