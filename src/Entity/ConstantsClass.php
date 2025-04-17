<?php

namespace App\Entity;

class ConstantsClass
{
    public const ROLE_ADMINISTRATEUR = 'ROLE_ADMINISTRATEUR';
    public const ROLE_DIRECTEUR = 'ROLE_DIRECTEUR';
    public const ROLE_PHARMACIEN = 'ROLE_PHARMACIEN';
    public const ROLE_REGIES_DES_RECETTES = 'ROLE_REGIES_DES_RECETTES';
    public const ROLE_SECRETAIRE = 'ROLE_SECRETAIRE';
    public const ROLE_CAISSIERE = 'ROLE_CAISSIERE';

    public const ADMINISTRATEUR = 'ADMINISTRATEUR';
    public const DIRECTEUR = 'DIRECTEUR';
    public const PHARMACIEN = 'PHARMACIEN';
    public const REGIES_DES_RECETTES = 'RÉGIES DES RECETTES';
    public const SECRETAIRE = 'SECRÉTAIRE';
    public const CAISSIERE = 'CAISSIÈRE';

    public const ERROR = 'ERROR';
    public const SUCCESS = 'SUCCESS';
    public const ANNULEE = 'ANNULEE';
    public const EN_COURS = 'EN_COURS';
    public const VALIDEE = 'VALIDEE';
    public const FICHIER = 'produit.png';

    public const CASH = 'CASH';
    public const CREDIT = 'CRÉDIT';
    public const PRIS_EN_CHARGE = 'PRIS EN CHARGE';
    // public const MOMO = 'MOMO';
    // public const OM = 'OM';

    public const SOLDE = 'SOLDE';
    public const NON_SOLDE = 'NON SOLDE';

    #######  nom de la photo par defaut
    public const NOM_PHOTO = 'user.png';

    public const NOM_PRODUIT = 'produit.png';

    public const NOM_PATIENT = 'Client';
    public const CONTACT_PATIENT = '+237 xxx xxx xxx';

    #####################"
    public const ACTE = 'Acte'; 
    public const EXAMEN = 'Examen'; 
    public const MEDICAMENT = 'Médicament'; 

    public const FEMININ = 'F';
    public const MASCULIN = 'M';

    public const FEMININ_IMAGE = 'f.png';
    public const MASCULIN_IMAGE = 'm.jpg';

}
