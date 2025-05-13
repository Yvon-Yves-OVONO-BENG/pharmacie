<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250509091756 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        //$this->addSql('ALTER TABLE facture ADD reste INT NOT NULL');
        //$this->addSql('ALTER TABLE ligne_de_facture ADD CONSTRAINT FK_2132BC8E7F2DEE08 FOREIGN KEY (facture_id) REFERENCES facture (id)');
        //$this->addSql('ALTER TABLE ligne_de_facture ADD CONSTRAINT FK_2132BC8EF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('ALTER TABLE lot CHANGE vendu vendu INT DEFAULT NULL');
        $this->addSql('ALTER TABLE lot ADD CONSTRAINT FK_B81291BCB5FDB3E FOREIGN KEY (enregistre_par_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE lot ADD CONSTRAINT FK_B81291B1237A8DE FOREIGN KEY (type_produit_id) REFERENCES type_produit (id)');
        $this->addSql('ALTER TABLE patient CHANGE termine termine TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE patient ADD CONSTRAINT FK_1ADAD7EBFCF77503 FOREIGN KEY (societe_id) REFERENCES societe (id)');
        $this->addSql('ALTER TABLE patient ADD CONSTRAINT FK_1ADAD7EBA6E44244 FOREIGN KEY (pays_id) REFERENCES pays (id)');
        $this->addSql('ALTER TABLE produit ADD examen TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC27A8CBA5F7 FOREIGN KEY (lot_id) REFERENCES lot (id)');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC27CB5FDB3E FOREIGN KEY (enregistre_par_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE profil ADD CONSTRAINT FK_E6D6B297A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE profil ADD CONSTRAINT FK_E6D6B2974296D31F FOREIGN KEY (genre_id) REFERENCES genre (id)');
        $this->addSql('ALTER TABLE reponse_question ADD CONSTRAINT FK_E97BC6396BD4A821 FOREIGN KEY (question_secrete_id) REFERENCES question_secrete (id)');
        $this->addSql('ALTER TABLE reponse_question ADD CONSTRAINT FK_E97BC639A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE sous_categorie ADD CONSTRAINT FK_52743D7BBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6494296D31F FOREIGN KEY (genre_id) REFERENCES genre (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facture DROP reste');
        $this->addSql('ALTER TABLE ligne_de_facture DROP FOREIGN KEY FK_2132BC8E7F2DEE08');
        $this->addSql('ALTER TABLE ligne_de_facture DROP FOREIGN KEY FK_2132BC8EF347EFB');
        $this->addSql('ALTER TABLE lot DROP FOREIGN KEY FK_B81291BCB5FDB3E');
        $this->addSql('ALTER TABLE lot DROP FOREIGN KEY FK_B81291B1237A8DE');
        $this->addSql('ALTER TABLE lot CHANGE vendu vendu INT DEFAULT 0');
        $this->addSql('ALTER TABLE patient DROP FOREIGN KEY FK_1ADAD7EBFCF77503');
        $this->addSql('ALTER TABLE patient DROP FOREIGN KEY FK_1ADAD7EBA6E44244');
        $this->addSql('ALTER TABLE patient CHANGE termine termine TINYINT(1) DEFAULT 0');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC27A8CBA5F7');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC27CB5FDB3E');
        $this->addSql('ALTER TABLE produit DROP examen');
        $this->addSql('ALTER TABLE profil DROP FOREIGN KEY FK_E6D6B297A76ED395');
        $this->addSql('ALTER TABLE profil DROP FOREIGN KEY FK_E6D6B2974296D31F');
        $this->addSql('ALTER TABLE reponse_question DROP FOREIGN KEY FK_E97BC6396BD4A821');
        $this->addSql('ALTER TABLE reponse_question DROP FOREIGN KEY FK_E97BC639A76ED395');
        $this->addSql('ALTER TABLE sous_categorie DROP FOREIGN KEY FK_52743D7BBCF5E72D');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D6494296D31F');
    }
}
