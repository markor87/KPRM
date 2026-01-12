<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PodaciORadnomMestu extends Model
{
    protected $table = 'podaci_o_radnom_mestu';

    public $timestamps = false;

    protected $fillable = [
        'vrsta_organa',
        'organ',
        'naziv_radnog_mesta',
        'tip_konkursa',
        'broj_izvrsilaca',
        'zvanje',
        'mesto_rada',
        'status_konkursa_na_dan_1',
        'status_konkursa_na_dan_2',
        'datum_dobijanja_saglasnosti_vlade',
        'datum_donosenja_resenja_o_pokretanju_postupka',
        'datum_dobijanja_obavestenja_od_suka',
        'datum_odrzavanja_prvog_sastanka',
        'datum_oglasavanja',
        'datum_pregleda_prijava',
        'datum_ofk_izvestaja',
        'datum_pocetka_provere_ofk',
        'datum_pocetka_provere_pfk',
        'datum_pocetka_provere_pk',
        'datum_pk_izvestaja',
        'datum_predaje_dokumentacije',
        'datum_pocetka_sprovodjenja_intervjua',
        'datum_dostavljanja_liste_rukovodiocu_organa',
        'datum_donosenja_resenja_o_izabranom_kandidatu',
        'datum_stupanja_na_rad',
        'broj_primljenih_izvrsilaca',
        'ocena_sa_vrednovanja',
        'broj_zalbi_na_resenje_o_odbacaju_prijave',
        'broj_zalbi_na_resenje_o_prijemu_u_radni_odnos',
        'broj_usvojenih_zalbi_na_resenje_o_odbacaju_prijave',
        'broj_izvrsilaca_ponovno_oglasavanje',
        'ukupan_broj_prijava',
        'broj_prijava_iz_organa',
        'broj_prijava_iz_drugih_organa',
        'broj_prijava_van_drzavnih_organa',
        'broj_validnih_prijava',
        'broj_validnih_prijava_iz_organa',
        'broj_validnih_prijava_iz_drugog_organa',
        'broj_validnih_prijava_van_drzavnih_organa',
        'broj_kandidata_koji_su_ispunlii_merila_ofk',
        'broj_kandidata_koji_su_ispunlii_merila_pfk',
        'provera_pfk',
        'broj_kandidata_ispunili_merila_pk',
        'broj_odazvanih_kandidata_na_zavrsnom_razgovoru',
        'broj_kandidata_na_listi',
        'broj_kandidata_iz_organa_na_listi',
        'broj_kandidata_iz_drugog_drzavnog_organa_na_listi',
        'broj_kandidata_van_drzavnih_organa_na_listi',
        'izabrani_kandidat',
        'broj_bodova_izabranog_kandidata_na_ofk',
        'broj_bodova_izabranog_kandidata_na_pfk',
        'broj_bodova_izabranog_kandidata_na_pk',
        'broj_bodova_izabranog_kandidata_na_zavrsnom_razgovoru',
        'drugoplasirani_kandidat',
        'broj_bodova_drugplasiranog_kandidata_na_ofk',
        'broj_bodova_drugplasiranog_kandidata_na_pfk',
        'broj_bodova_drugplasiranog_kandidata_na_pk',
        'broj_bodova_drugoplasiranog_kandidata_na_zavrsnom_razgovoru',
    ];

    /**
     * Relacija sa sifarnik_organi tabelom
     */
    public function organRelation()
    {
        return $this->belongsTo(SifarnikOrgani::class, 'organ');
    }

    /**
     * Relacija sa sifarnik_vrsta_organa tabelom
     */
    public function vrstaOrganaRelation()
    {
        return $this->belongsTo(SifarnikVrstaOrgana::class, 'vrsta_organa');
    }

    /**
     * Relacija sa sifarnik_tip_konkursa tabelom
     */
    public function tipKonkursaRelation()
    {
        return $this->belongsTo(SifarnikTipKonkursa::class, 'tip_konkursa');
    }

    /**
     * Relacija sa sifarnik_zvanje tabelom
     */
    public function zvanjeRelation()
    {
        return $this->belongsTo(SifarnikZvanje::class, 'zvanje');
    }

    /**
     * Relacija sa sifarnik_mesta tabelom
     */
    public function mestoRadaRelation()
    {
        return $this->belongsTo(SifarnikMesta::class, 'mesto_rada');
    }

    /**
     * Relacija sa sifarnik_status_konkursa tabelom - Status na dan 1
     */
    public function statusKonkursaNaDan1Relation()
    {
        return $this->belongsTo(SifarnikStatusKonkursa::class, 'status_konkursa_na_dan_1');
    }

    /**
     * Relacija sa sifarnik_status_konkursa tabelom - Status na dan 2
     */
    public function statusKonkursaNaDan2Relation()
    {
        return $this->belongsTo(SifarnikStatusKonkursa::class, 'status_konkursa_na_dan_2');
    }

    /**
     * Relacija sa sifarnik_izabrani_kandidat tabelom - Izabrani kandidat
     */
    public function izabraniKandidatRelation()
    {
        return $this->belongsTo(SifarnikIzabraniKandidat::class, 'izabrani_kandidat');
    }

    /**
     * Relacija sa sifarnik_izabrani_kandidat tabelom - Drugoplasirani kandidat
     */
    public function drugoplasiraniKandidatRelation()
    {
        return $this->belongsTo(SifarnikIzabraniKandidat::class, 'drugoplasirani_kandidat');
    }

    /**
     * Relacija sa sifarnik_provera_pfk tabelom
     */
    public function proveraPfkRelation()
    {
        return $this->belongsTo(SifarnikProveraPfk::class, 'provera_pfk');
    }
}
