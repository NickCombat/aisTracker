<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260423083842 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE flaggenstaaten (id INT AUTO_INCREMENT NOT NULL, amtliche_kurzform VARCHAR(50) NOT NULL, amtliche_vollform VARCHAR(150) NOT NULL, kuerzel VARCHAR(2) NOT NULL, flagge VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE net_port (id INT AUTO_INCREMENT NOT NULL, kuerzel VARCHAR(6) NOT NULL, bezeichnung VARCHAR(100) NOT NULL, land VARCHAR(2) NOT NULL, flag_id INT DEFAULT NULL, INDEX IDX_31E89BBF919FE4E5 (flag_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE net_projekt_anlagen (id INT AUTO_INCREMENT NOT NULL, filetype VARCHAR(150) NOT NULL, filename VARCHAR(255) NOT NULL, filesize VARCHAR(10) NOT NULL, original_name VARCHAR(150) NOT NULL, basename VARCHAR(150) NOT NULL, revision VARCHAR(10) DEFAULT NULL, projekt_id INT DEFAULT NULL, INDEX IDX_87CE17D1261D545D (projekt_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE net_projekt_galerie (id INT AUTO_INCREMENT NOT NULL, filename VARCHAR(255) NOT NULL, original_name VARCHAR(255) NOT NULL, basename VARCHAR(150) NOT NULL, filetype VARCHAR(150) NOT NULL, filesize VARCHAR(10) NOT NULL, bermerkung VARCHAR(255) DEFAULT NULL, projekt_id INT NOT NULL, INDEX IDX_BBA0F076261D545D (projekt_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE net_projekt_status (id INT AUTO_INCREMENT NOT NULL, bezeichnung VARCHAR(20) NOT NULL, style VARCHAR(100) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE net_seiten_parameter (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, wert VARCHAR(255) NOT NULL, beschreibung VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE net_ship_nav_status (id INT AUTO_INCREMENT NOT NULL, status INT NOT NULL, beschreibung VARCHAR(100) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE net_ship_position_history (id INT AUTO_INCREMENT NOT NULL, latitude DOUBLE PRECISION NOT NULL, longitude DOUBLE PRECISION NOT NULL, timestamp DATETIME NOT NULL, course VARCHAR(5) DEFAULT NULL, speed DOUBLE PRECISION DEFAULT NULL, draught DOUBLE PRECISION DEFAULT NULL, destination VARCHAR(150) DEFAULT NULL, locode VARCHAR(6) DEFAULT NULL, eta DATETIME DEFAULT NULL, etaais VARCHAR(12) DEFAULT NULL, zone VARCHAR(100) DEFAULT NULL, navstat_id INT DEFAULT NULL, net_shipdata_id INT NOT NULL, INDEX IDX_65D9302C99BFA130 (navstat_id), INDEX IDX_65D9302C17BBC401 (net_shipdata_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE net_ship_typ (id INT AUTO_INCREMENT NOT NULL, bezeichnung VARCHAR(50) NOT NULL, beschreibung VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE net_shipdata (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, imo INT NOT NULL, mmsi INT DEFAULT NULL, rufzeichen VARCHAR(8) DEFAULT NULL, laenge VARCHAR(10) DEFAULT NULL, breite VARCHAR(10) DEFAULT NULL, pic VARCHAR(255) DEFAULT NULL, is_in_lte_range TINYINT DEFAULT 0 NOT NULL, orderno SMALLINT DEFAULT NULL, ais_update TINYINT NOT NULL, type_id INT DEFAULT NULL, flag_id INT DEFAULT NULL, status_id INT DEFAULT NULL, INDEX IDX_964EE95C54C8C93 (type_id), INDEX IDX_964EE95919FE4E5 (flag_id), INDEX IDX_964EE956BF700BD (status_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE net_shipdata_port (id INT AUTO_INCREMENT NOT NULL, arrival DATETIME DEFAULT NULL, departure DATETIME DEFAULT NULL, shipdata_id INT NOT NULL, port_id INT NOT NULL, status_id INT DEFAULT NULL, INDEX IDX_AFEE6B09387E19B7 (shipdata_id), INDEX IDX_AFEE6B0976E92A9C (port_id), INDEX IDX_AFEE6B096BF700BD (status_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE net_shipdata_port_log (id INT AUTO_INCREMENT NOT NULL, event_timestamp DATETIME NOT NULL, event_type VARCHAR(50) NOT NULL, shipdata_id INT NOT NULL, port_id INT NOT NULL, INDEX IDX_7D4A7A6F387E19B7 (shipdata_id), INDEX IDX_7D4A7A6F76E92A9C (port_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE net_shipdata_port_status (id INT AUTO_INCREMENT NOT NULL, bezeichnung VARCHAR(20) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE net_port ADD CONSTRAINT FK_31E89BBF919FE4E5 FOREIGN KEY (flag_id) REFERENCES flaggenstaaten (id)');
        $this->addSql('ALTER TABLE net_projekt_anlagen ADD CONSTRAINT FK_87CE17D1261D545D FOREIGN KEY (projekt_id) REFERENCES net_shipdata (id)');
        $this->addSql('ALTER TABLE net_projekt_galerie ADD CONSTRAINT FK_BBA0F076261D545D FOREIGN KEY (projekt_id) REFERENCES net_shipdata (id)');
        $this->addSql('ALTER TABLE net_ship_position_history ADD CONSTRAINT FK_65D9302C99BFA130 FOREIGN KEY (navstat_id) REFERENCES net_ship_nav_status (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE net_ship_position_history ADD CONSTRAINT FK_65D9302C17BBC401 FOREIGN KEY (net_shipdata_id) REFERENCES net_shipdata (id)');
        $this->addSql('ALTER TABLE net_shipdata ADD CONSTRAINT FK_964EE95C54C8C93 FOREIGN KEY (type_id) REFERENCES net_ship_typ (id)');
        $this->addSql('ALTER TABLE net_shipdata ADD CONSTRAINT FK_964EE95919FE4E5 FOREIGN KEY (flag_id) REFERENCES flaggenstaaten (id)');
        $this->addSql('ALTER TABLE net_shipdata ADD CONSTRAINT FK_964EE956BF700BD FOREIGN KEY (status_id) REFERENCES net_projekt_status (id)');
        $this->addSql('ALTER TABLE net_shipdata_port ADD CONSTRAINT FK_AFEE6B09387E19B7 FOREIGN KEY (shipdata_id) REFERENCES net_shipdata (id)');
        $this->addSql('ALTER TABLE net_shipdata_port ADD CONSTRAINT FK_AFEE6B0976E92A9C FOREIGN KEY (port_id) REFERENCES net_port (id)');
        $this->addSql('ALTER TABLE net_shipdata_port ADD CONSTRAINT FK_AFEE6B096BF700BD FOREIGN KEY (status_id) REFERENCES net_shipdata_port_status (id)');
        $this->addSql('ALTER TABLE net_shipdata_port_log ADD CONSTRAINT FK_7D4A7A6F387E19B7 FOREIGN KEY (shipdata_id) REFERENCES net_shipdata (id)');
        $this->addSql('ALTER TABLE net_shipdata_port_log ADD CONSTRAINT FK_7D4A7A6F76E92A9C FOREIGN KEY (port_id) REFERENCES net_port (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE net_port DROP FOREIGN KEY FK_31E89BBF919FE4E5');
        $this->addSql('ALTER TABLE net_projekt_anlagen DROP FOREIGN KEY FK_87CE17D1261D545D');
        $this->addSql('ALTER TABLE net_projekt_galerie DROP FOREIGN KEY FK_BBA0F076261D545D');
        $this->addSql('ALTER TABLE net_ship_position_history DROP FOREIGN KEY FK_65D9302C99BFA130');
        $this->addSql('ALTER TABLE net_ship_position_history DROP FOREIGN KEY FK_65D9302C17BBC401');
        $this->addSql('ALTER TABLE net_shipdata DROP FOREIGN KEY FK_964EE95C54C8C93');
        $this->addSql('ALTER TABLE net_shipdata DROP FOREIGN KEY FK_964EE95919FE4E5');
        $this->addSql('ALTER TABLE net_shipdata DROP FOREIGN KEY FK_964EE956BF700BD');
        $this->addSql('ALTER TABLE net_shipdata_port DROP FOREIGN KEY FK_AFEE6B09387E19B7');
        $this->addSql('ALTER TABLE net_shipdata_port DROP FOREIGN KEY FK_AFEE6B0976E92A9C');
        $this->addSql('ALTER TABLE net_shipdata_port DROP FOREIGN KEY FK_AFEE6B096BF700BD');
        $this->addSql('ALTER TABLE net_shipdata_port_log DROP FOREIGN KEY FK_7D4A7A6F387E19B7');
        $this->addSql('ALTER TABLE net_shipdata_port_log DROP FOREIGN KEY FK_7D4A7A6F76E92A9C');
        $this->addSql('DROP TABLE flaggenstaaten');
        $this->addSql('DROP TABLE net_port');
        $this->addSql('DROP TABLE net_projekt_anlagen');
        $this->addSql('DROP TABLE net_projekt_galerie');
        $this->addSql('DROP TABLE net_projekt_status');
        $this->addSql('DROP TABLE net_seiten_parameter');
        $this->addSql('DROP TABLE net_ship_nav_status');
        $this->addSql('DROP TABLE net_ship_position_history');
        $this->addSql('DROP TABLE net_ship_typ');
        $this->addSql('DROP TABLE net_shipdata');
        $this->addSql('DROP TABLE net_shipdata_port');
        $this->addSql('DROP TABLE net_shipdata_port_log');
        $this->addSql('DROP TABLE net_shipdata_port_status');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
