<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260407120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add default annual fixed rate field to user profile';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user ADD default_annual_fixed_rate NUMERIC(12, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user DROP default_annual_fixed_rate');
    }
}
