<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260407123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add invoice language and user default invoice language';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE invoice ADD language VARCHAR(5) DEFAULT 'en' NOT NULL");
        $this->addSql('ALTER TABLE app_user ADD default_invoice_language VARCHAR(5) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user DROP default_invoice_language');
        $this->addSql('ALTER TABLE invoice DROP language');
    }
}
