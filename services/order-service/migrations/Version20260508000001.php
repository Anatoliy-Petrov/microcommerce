<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260508000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create orders, order_items, and order_transitions tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            CREATE TABLE orders (
                id         CHAR(36)       NOT NULL,
                user_id    CHAR(36)       NOT NULL,
                status     VARCHAR(20)    NOT NULL DEFAULT 'pending',
                subtotal   DECIMAL(10,2)  NOT NULL,
                total      DECIMAL(10,2)  NOT NULL,
                created_at DATETIME       NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                updated_at DATETIME       NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                PRIMARY KEY (id),
                INDEX IDX_orders_user (user_id),
                INDEX IDX_orders_status (status)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<SQL
            CREATE TABLE order_items (
                id           CHAR(36)       NOT NULL,
                order_id     CHAR(36)       NOT NULL,
                product_id   CHAR(36)       NOT NULL,
                product_name VARCHAR(255)   NOT NULL,
                unit_price   DECIMAL(10,2)  NOT NULL,
                quantity     INT            NOT NULL,
                line_total   DECIMAL(10,2)  NOT NULL,
                PRIMARY KEY (id),
                INDEX IDX_order_items_order (order_id),
                CONSTRAINT FK_order_items_order
                    FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);

        $this->addSql(<<<SQL
            CREATE TABLE order_transitions (
                id           INT UNSIGNED   NOT NULL AUTO_INCREMENT,
                order_id     CHAR(36)       NOT NULL,
                from_state   VARCHAR(20)    NOT NULL,
                to_state     VARCHAR(20)    NOT NULL,
                triggered_by VARCHAR(100)   NOT NULL,
                created_at   DATETIME       NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                PRIMARY KEY (id),
                INDEX IDX_order_transitions_order (order_id),
                CONSTRAINT FK_order_transitions_order
                    FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE order_transitions');
        $this->addSql('DROP TABLE order_items');
        $this->addSql('DROP TABLE orders');
    }
}