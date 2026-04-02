<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260402130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add default hourly hours per business day to user profile';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user ADD default_hourly_hours_per_business_day NUMERIC(6, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user DROP default_hourly_hours_per_business_day');
    }
}
