<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240623174049 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE crypto_currencies ADD wallets_crypto_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE crypto_currencies ADD CONSTRAINT FK_3494F7A7AF1299AC FOREIGN KEY (wallets_crypto_id) REFERENCES wallets (id)');
        $this->addSql('CREATE INDEX IDX_3494F7A7AF1299AC ON crypto_currencies (wallets_crypto_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE crypto_currencies DROP FOREIGN KEY FK_3494F7A7AF1299AC');
        $this->addSql('DROP INDEX IDX_3494F7A7AF1299AC ON crypto_currencies');
        $this->addSql('ALTER TABLE crypto_currencies DROP wallets_crypto_id');
    }
}
