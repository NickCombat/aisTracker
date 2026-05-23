<?php

declare( strict_types=1 );

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260426083900 extends
    AbstractMigration
{

    public function getDescription(): string
    {
        return '';
    }

    public function up( Schema $schema ): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql( "CREATE TABLE `login_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `login_time` datetime NOT NULL,
  `ip_adresse` varchar(100) NOT NULL,
  `user_agent` varchar(150) NOT NULL,
  `status` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;" );

        $this->addSql( "INSERT INTO `net_projekt_status` (`id`, `bezeichnung`, `style`) VALUES
(1, 'aktiv', 'badge text-bg-success'),
(2, 'archiv', 'badge text-bg-muted'),
(3, 'in Vorbereitung', 'badge text-bg-info'),
(4, 'in Ausrüstung', 'badge text-bg-warning'),
(5, 'TEST', 'badge text-bg-primary'),
(6, 'Betrieb', 'badge text-bg-success'),
(7, 'check', 'badge text-bg-secondary'),
(8, 'monitoring', 'badge text-bg-info'),
(9, 'planung', 'badge text-bg-secondary'),
(10, 'wartung', 'badge text-bg-warning');" );

        $this->addSql( "INSERT INTO `net_ship_typ` (`id`, `bezeichnung`, `beschreibung`) VALUES
(1, 'Containerschiff', 'Transportieren standardisierte Container. Beispiele sind Feeder-Schiffe, Panamax, und Ultra Large Container Vessels (ULCV)'),
(2, 'Vehicles Carrier', 'Transportieren Fahrzeuge und rollende Ladung. Beispiele sind Auto- und Lkw-Fähren.'),
(3, 'Massengutfrachter', '(Bulk Carrier) Transportieren lose Ladung wie Kohle, Erz oder Getreide. Unterteilt in Handysize, Supramax, Panamax und Capesize.'),
(4, 'Tanker', 'Transportieren Flüssigkeiten wie Rohöl, Chemikalien oder Flüssiggas. Beispiele sind VLCC (Very Large Crude Carrier) und LNG-Tanker (Liquefied Natural Gas).'),
(5, 'Kühlschiff', '(Reefer) Transportieren verderbliche Waren wie Obst, Gemüse und Fleisch unter kontrollierten Temperaturbedingungen.'),
(6, 'Mehrzweckfrachter', '(Multipurpose Vessel) Können verschiedene Arten von Ladung transportieren, einschließlich Container, Stückgut und Projektladung.'),
(7, 'Feeder-Schiff ', 'Kleinere Containerschiffe, die Container zwischen kleineren Häfen und größeren Umschlaghäfen transportieren.'),
(20, '(WIG)', 'all ships of this type'),
(21, '(WIG)', 'Hazardous category A'),
(22, '(WIG)', 'Hazardous category B'),
(23, '(WIG)', 'Hazardous category C'),
(24, '(WIG)', 'Hazardous category D'),
(25, '(WIG)', 'Reserved for future use'),
(26, '(WIG)', 'Reserved for future use'),
(27, '(WIG)', 'Reserved for future use'),
(28, '(WIG)', 'Reserved for future use'),
(29, '(WIG)', 'Reserved for future use'),
(30, 'Fishing', ''),
(31, 'Towing', ''),
(32, 'Towing', 'length exceeds 200m or breadth exceeds 25m'),
(33, 'underwater ops', ''),
(34, 'Diving ops', ''),
(35, 'Military ops', ''),
(36, 'Sailing', ''),
(37, 'Pleasure Craft', ''),
(40, 'High speed craft', 'all ships of this type'),
(41, 'High speed craft', 'Hazardous category A'),
(42, 'High speed craft', 'Hazardous category B'),
(43, 'High speed craft', 'Hazardous category C'),
(44, 'High speed craft', 'Hazardous category D'),
(45, 'High speed craft', 'Reserved for future use'),
(46, 'High speed craft', 'Reserved for future use'),
(47, 'High speed craft', 'Reserved for future use'),
(48, 'High speed craft', 'Reserved for future use'),
(49, 'High speed craft', 'No additional information'),
(50, 'Pilot Vessel', ''),
(51, 'SaR vessel', ''),
(52, 'Tug', ''),
(53, 'Port Tender', ''),
(54, 'Anti-pollution equipment', ''),
(55, 'Law Enforcement', ''),
(56, 'Spare - Local Vessel', ''),
(57, 'Spare - Local Vessel', ''),
(58, 'Medical Transport', ''),
(59, 'Noncombatant ship', 'Noncombatant ship according to RR Resolution No. 18'),
(60, 'Passenger', 'all ships of this type'),
(61, 'Passenger', 'Hazardous category A'),
(62, 'Passenger', 'Hazardous category B'),
(63, 'Passenger', 'Hazardous category C'),
(64, 'Passenger', 'Hazardous category D'),
(65, 'Passenger', 'Reserved for future use'),
(66, 'Passenger', 'Reserved for future use'),
(67, 'Passenger', 'Reserved for future use'),
(68, 'Passenger', 'Reserved for future use'),
(69, 'Passenger', 'No additional information'),
(70, 'Cargo', 'all ships of this type'),
(71, 'Cargo', 'Hazardous category A'),
(72, 'Cargo', 'Hazardous category B'),
(73, 'Cargo', 'Hazardous category C'),
(74, 'Cargo', 'Hazardous category D'),
(75, 'Cargo', 'Reserved for future use'),
(76, 'Cargo', 'Reserved for future use'),
(77, 'Cargo', 'Reserved for future use'),
(78, 'Cargo', 'Reserved for future use'),
(79, 'Cargo', 'No additional information'),
(80, 'Tanker', 'all ships of this type'),
(81, 'Tanker', 'Hazardous category A'),
(82, 'Tanker', 'Hazardous category B'),
(83, 'Tanker', 'Hazardous category C'),
(84, 'Tanker', 'Hazardous category D'),
(85, 'Tanker', 'Reserved for future use'),
(86, 'Tanker', 'Reserved for future use'),
(87, 'Tanker', 'Reserved for future use'),
(88, 'Tanker', 'Reserved for future use'),
(89, 'Tanker', 'No additional information'),
(90, 'Other Type', 'all ships of this type'),
(91, 'Other Type', 'Hazardous category A'),
(92, 'Other Type', 'Hazardous category B'),
(93, 'Other Type', 'Hazardous category C'),
(94, 'Other Type', 'Hazardous category D'),
(95, 'Other Type', 'Reserved for future use'),
(96, 'Other Type', 'Reserved for future use'),
(97, 'Other Type', 'Reserved for future use'),
(98, 'Other Type', 'Reserved for future use'),
(99, 'Other Type', 'no additional information'),
(171, 'Not available', 'Not available (default)'),
(252, 'Planung', 'Planung'),
(253, 'Werkstatt', 'Werkstatt'),
(254, 'Lager', 'Lager'),
(255, 'BetonKreuzer', 'BetonKreuzer');" );

        //$this->addSql( "" );
    }

    public function down( Schema $schema ): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
