<?php

declare( strict_types=1 );

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260425083900 extends
    AbstractMigration
{

    public function getDescription(): string
    {
        return '';
    }

    public function up( Schema $schema ): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql( "CREATE TABLE `sec_user` (
  `id` int(11) NOT NULL,
  `email` varchar(180) NOT NULL,
  `roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '(DC2Type:json)' CHECK (json_valid(`roles`)),
  `password` varchar(255) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;" );
    }

    public function down( Schema $schema ): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
