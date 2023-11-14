<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221020110434 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql([
            'INSERT INTO distribusion_station_mapping (station_id, station_name, internal_code) VALUES ("FRCHECML", "Chessy Marne la Vallée Bus Station", 947);',
            'INSERT INTO distribusion_station_mapping (station_id, station_name, internal_code) VALUES ("FRPARPAF", "Charles de Gaulle Airport", 948);'
        ]);

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
