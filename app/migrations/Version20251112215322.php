<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Psr\Log\LoggerInterface;

final class Version20251112215322 extends AbstractMigration
{
    public function __construct(
        Connection $connection,
        LoggerInterface $logger,
    ) {
        parent::__construct($connection, $logger);
    }

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE clients (id SERIAL NOT NULL, login VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql(
            'CREATE TABLE orders (id SERIAL NOT NULL, created_by_id INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, status VARCHAR(255) NOT NULL, order_content JSON NOT NULL, PRIMARY KEY(id))'
        );
        $this->addSql('CREATE INDEX IDX_E52FFDEEB03A8386 ON orders (created_by_id)');
        $this->addSql(
            'ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEEB03A8386 FOREIGN KEY (created_by_id) REFERENCES clients (id) NOT DEFERRABLE INITIALLY IMMEDIATE'
        );

        $this->addSql('insert into clients (login) values (?)', ['client-1']);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders DROP CONSTRAINT FK_E52FFDEEB03A8386');
        $this->addSql('DROP TABLE clients');
        $this->addSql('DROP TABLE orders');
    }
}
