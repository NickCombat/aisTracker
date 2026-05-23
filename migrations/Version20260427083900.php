<?php

declare( strict_types=1 );

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260427083900 extends
    AbstractMigration
{

    public function getDescription(): string
    {
        return '';
    }

    public function up( Schema $schema ): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql( "INSERT INTO `net_seiten_parameter` ( `name`, `wert`, `beschreibung`) VALUES
( 'lte.distance', '20', 'Maximale Distanz für LTE-Berechnungen in Kilometern'),
( 'system.locale', 'de', 'Standardsprache für E-Mails und Benachrichtigungen (z.B. de, en, fr, cn)'),
( 'url.back', 'https://df399c15-32cb-48ae-b084-895d05aec85e.mock.pstmn.io', 'Backup'),
( 'aisstream.api.key', 'bc41072d5de480f977db6501493b7bd9cf103548', 'API Key für den aisstream.io Dienst');" );

        //$this->addSql( "" );
    }

    public function down( Schema $schema ): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
