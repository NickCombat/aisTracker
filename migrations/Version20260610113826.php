<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260610113826 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySQL80Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MySQL80Platform'."
        );

        $this->addSql(<<<'SQL'
            ALTER TABLE
              flaggenstaaten
            CHANGE
              amtliche_kurzform amtliche_kurzform VARCHAR(50) NOT NULL,
            CHANGE
              amtliche_vollform amtliche_vollform VARCHAR(150) NOT NULL,
            CHANGE
              kuerzel kuerzel VARCHAR(2) NOT NULL,
            CHANGE
              flagge flagge VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              login_log
            CHANGE
              ip_adresse ip_adresse VARCHAR(100) NOT NULL,
            CHANGE
              user_agent user_agent VARCHAR(150) NOT NULL,
            CHANGE
              status status VARCHAR(10) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              net_eigner
            CHANGE
              bezeichnung bezeichnung VARCHAR(150) NOT NULL,
            CHANGE
              sitz sitz VARCHAR(100) NOT NULL,
            CHANGE
              leitung leitung VARCHAR(150) DEFAULT NULL,
            CHANGE
              webseite webseite VARCHAR(255) DEFAULT NULL,
            CHANGE
              gruendung gruendung VARCHAR(12) DEFAULT NULL,
            CHANGE
              geschaeftsfeld geschaeftsfeld VARCHAR(150) DEFAULT NULL,
            CHANGE
              wappen wappen VARCHAR(255) DEFAULT NULL,
            CHANGE
              kuerzel kuerzel VARCHAR(50) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              net_port
            CHANGE
              kuerzel kuerzel VARCHAR(6) NOT NULL,
            CHANGE
              bezeichnung bezeichnung VARCHAR(100) NOT NULL,
            CHANGE
              land land VARCHAR(2) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              net_projekt_anlagen
            CHANGE
              filetype filetype VARCHAR(150) NOT NULL,
            CHANGE
              filename filename VARCHAR(255) NOT NULL,
            CHANGE
              filesize filesize VARCHAR(10) NOT NULL,
            CHANGE
              original_name original_name VARCHAR(150) NOT NULL,
            CHANGE
              basename basename VARCHAR(150) NOT NULL,
            CHANGE
              revision revision VARCHAR(10) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              net_projekt_galerie
            CHANGE
              filename filename VARCHAR(255) NOT NULL,
            CHANGE
              original_name original_name VARCHAR(255) NOT NULL,
            CHANGE
              basename basename VARCHAR(150) NOT NULL,
            CHANGE
              filetype filetype VARCHAR(150) NOT NULL,
            CHANGE
              filesize filesize VARCHAR(10) NOT NULL,
            CHANGE
              bermerkung bermerkung VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              net_projekt_status
            CHANGE
              bezeichnung bezeichnung VARCHAR(20) NOT NULL,
            CHANGE
              style style VARCHAR(100) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              net_seiten_parameter
            CHANGE
              name name VARCHAR(150) NOT NULL,
            CHANGE
              wert wert VARCHAR(255) NOT NULL,
            CHANGE
              beschreibung beschreibung VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql('ALTER TABLE net_ship_nav_status CHANGE beschreibung beschreibung VARCHAR(100) NOT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              net_ship_position_history
            CHANGE
              course course VARCHAR(5) DEFAULT NULL,
            CHANGE
              speed speed DOUBLE PRECISION DEFAULT NULL,
            CHANGE
              draught draught DOUBLE PRECISION DEFAULT NULL,
            CHANGE
              destination destination VARCHAR(150) DEFAULT NULL,
            CHANGE
              locode locode VARCHAR(6) DEFAULT NULL,
            CHANGE
              eta eta DATETIME DEFAULT NULL,
            CHANGE
              etaais etaais VARCHAR(12) DEFAULT NULL,
            CHANGE
              zone zone VARCHAR(100) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              net_ship_typ
            CHANGE
              bezeichnung bezeichnung VARCHAR(50) NOT NULL,
            CHANGE
              beschreibung beschreibung VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              net_shipdata
            ADD
              secid INT DEFAULT NULL,
            ADD
              secrev INT DEFAULT NULL,
            ADD
              brt INT DEFAULT NULL,
            ADD
              bauwerft VARCHAR(150) DEFAULT NULL,
            ADD
              baujahr VARCHAR(10) DEFAULT NULL,
            ADD
              eigner_id INT DEFAULT NULL,
            CHANGE
              name name VARCHAR(150) NOT NULL,
            CHANGE
              rufzeichen rufzeichen VARCHAR(8) DEFAULT NULL,
            CHANGE
              laenge laenge VARCHAR(10) DEFAULT NULL,
            CHANGE
              breite breite VARCHAR(10) DEFAULT NULL,
            CHANGE
              pic pic VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              net_shipdata
            ADD
              CONSTRAINT FK_964EE954A52FB9C FOREIGN KEY (eigner_id) REFERENCES net_eigner (id)
        SQL);
        $this->addSql('CREATE INDEX IDX_964EE954A52FB9C ON net_shipdata (eigner_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              net_shipdata_port
            CHANGE
              arrival arrival DATETIME DEFAULT NULL,
            CHANGE
              departure departure DATETIME DEFAULT NULL
        SQL);
        $this->addSql('ALTER TABLE net_shipdata_port_log CHANGE event_type event_type VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE net_shipdata_port_status CHANGE bezeichnung bezeichnung VARCHAR(20) NOT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              sec_user
            CHANGE
              email email VARCHAR(180) NOT NULL,
            CHANGE
              roles roles JSON NOT NULL,
            CHANGE
              password password VARCHAR(255) NOT NULL,
            CHANGE
              firstname firstname VARCHAR(255) NOT NULL,
            CHANGE
              lastname lastname VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              messenger_messages
            CHANGE
              body body LONGTEXT NOT NULL,
            CHANGE
              headers headers LONGTEXT NOT NULL,
            CHANGE
              queue_name queue_name VARCHAR(190) NOT NULL,
            CHANGE
              delivered_at delivered_at DATETIME DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySQL80Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MySQL80Platform'."
        );

        $this->addSql(<<<'SQL'
            ALTER TABLE
              flaggenstaaten
            CHANGE
              amtliche_kurzform amtliche_kurzform VARCHAR(50) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              amtliche_vollform amtliche_vollform VARCHAR(150) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              kuerzel kuerzel VARCHAR(2) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              flagge flagge VARCHAR(255) DEFAULT 'NULL' COLLATE `utf8mb4_uca1400_ai_ci`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              login_log
            CHANGE
              ip_adresse ip_adresse VARCHAR(100) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              user_agent user_agent VARCHAR(150) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              status status VARCHAR(10) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              messenger_messages
            CHANGE
              body body LONGTEXT NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              headers headers LONGTEXT NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              queue_name queue_name VARCHAR(190) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              delivered_at delivered_at DATETIME DEFAULT 'NULL'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              net_eigner
            CHANGE
              bezeichnung bezeichnung VARCHAR(150) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              sitz sitz VARCHAR(100) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              leitung leitung VARCHAR(150) DEFAULT 'NULL' COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              webseite webseite VARCHAR(255) DEFAULT 'NULL' COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              gruendung gruendung VARCHAR(12) DEFAULT 'NULL' COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              geschaeftsfeld geschaeftsfeld VARCHAR(150) DEFAULT 'NULL' COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              wappen wappen VARCHAR(255) DEFAULT 'NULL' COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              kuerzel kuerzel VARCHAR(50) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              net_port
            CHANGE
              kuerzel kuerzel VARCHAR(6) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              bezeichnung bezeichnung VARCHAR(100) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              land land VARCHAR(2) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              net_projekt_anlagen
            CHANGE
              filetype filetype VARCHAR(150) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              filename filename VARCHAR(255) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              filesize filesize VARCHAR(10) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              original_name original_name VARCHAR(150) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              basename basename VARCHAR(150) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              revision revision VARCHAR(10) DEFAULT 'NULL' COLLATE `utf8mb4_uca1400_ai_ci`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              net_projekt_galerie
            CHANGE
              filename filename VARCHAR(255) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              original_name original_name VARCHAR(255) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              basename basename VARCHAR(150) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              filetype filetype VARCHAR(150) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              filesize filesize VARCHAR(10) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              bermerkung bermerkung VARCHAR(255) DEFAULT 'NULL' COLLATE `utf8mb4_uca1400_ai_ci`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              net_projekt_status
            CHANGE
              bezeichnung bezeichnung VARCHAR(20) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              style style VARCHAR(100) DEFAULT 'NULL' COLLATE `utf8mb4_uca1400_ai_ci`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              net_seiten_parameter
            CHANGE
              name name VARCHAR(150) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              wert wert VARCHAR(255) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              beschreibung beschreibung VARCHAR(255) DEFAULT 'NULL' COLLATE `utf8mb4_uca1400_ai_ci`
        SQL);
        $this->addSql('ALTER TABLE net_shipdata DROP FOREIGN KEY FK_964EE954A52FB9C');
        $this->addSql('DROP INDEX IDX_964EE954A52FB9C ON net_shipdata');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              net_shipdata
            DROP
              secid,
            DROP
              secrev,
            DROP
              brt,
            DROP
              bauwerft,
            DROP
              baujahr,
            DROP
              eigner_id,
            CHANGE
              name name VARCHAR(150) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              rufzeichen rufzeichen VARCHAR(8) DEFAULT 'NULL' COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              laenge laenge VARCHAR(10) DEFAULT 'NULL' COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              breite breite VARCHAR(10) DEFAULT 'NULL' COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              pic pic VARCHAR(255) DEFAULT 'NULL' COLLATE `utf8mb4_uca1400_ai_ci`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              net_shipdata_port
            CHANGE
              arrival arrival DATETIME DEFAULT 'NULL',
            CHANGE
              departure departure DATETIME DEFAULT 'NULL'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              net_shipdata_port_log
            CHANGE
              event_type event_type VARCHAR(50) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              net_shipdata_port_status
            CHANGE
              bezeichnung bezeichnung VARCHAR(20) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              net_ship_nav_status
            CHANGE
              beschreibung beschreibung VARCHAR(100) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              net_ship_position_history
            CHANGE
              course course VARCHAR(5) DEFAULT 'NULL' COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              speed speed DOUBLE PRECISION DEFAULT 'NULL',
            CHANGE
              draught draught DOUBLE PRECISION DEFAULT 'NULL',
            CHANGE
              destination destination VARCHAR(150) DEFAULT 'NULL' COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              locode locode VARCHAR(6) DEFAULT 'NULL' COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              eta eta DATETIME DEFAULT 'NULL',
            CHANGE
              etaais etaais VARCHAR(12) DEFAULT 'NULL' COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              zone zone VARCHAR(100) DEFAULT 'NULL' COLLATE `utf8mb4_uca1400_ai_ci`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              net_ship_typ
            CHANGE
              bezeichnung bezeichnung VARCHAR(50) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              beschreibung beschreibung VARCHAR(255) DEFAULT 'NULL' COLLATE `utf8mb4_uca1400_ai_ci`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              sec_user
            CHANGE
              email email VARCHAR(180) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`,
            CHANGE
              password password VARCHAR(255) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              firstname firstname VARCHAR(255) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`,
            CHANGE
              lastname lastname VARCHAR(255) NOT NULL COLLATE `utf8mb4_uca1400_ai_ci`
        SQL);
    }
}
