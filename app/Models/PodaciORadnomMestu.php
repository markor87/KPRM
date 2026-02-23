<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PodaciORadnomMestu extends Model
{
    use LogsActivity;

    protected $table = 'podaci_o_radnom_mestu';

    public $timestamps = false;

    protected $fillable = [
        'vrsta_organa',
        'organ',
        'naziv_radnog_mesta',
        'tip_konkursa',
        'broj_izvrsilaca',
        'zvanje',
        // 'mesto_rada', // Uklonjeno - sada je many-to-many relacija
        'status_konkursa_na_dan_1',
        'razlog_neuspelog_konkursa',
        'status_konkursa_na_dan_2',
        'datum_dobijanja_saglasnosti_vlade',
        'datum_donosenja_resenja_o_pokretanju_postupka',
        'datum_dobijanja_obavestenja_od_suka',
        'datum_odrzavanja_prvog_sastanka',
        'datum_oglasavanja',
        'datum_pregleda_prijava',
        'datum_slanja_zahteva_za_sprovodjenje_ofk_provera',
        'broj_kandidata_za_koje_se_zakazuju_ofk',
        'datum_pocetka_provere_ofk',
        'datum_ofk_izvestaja',
        'datum_slanja_zahteva_za_sprovodjenje_pfk_provera',
        'broj_kandidata_za_koje_se_zakazuju_pfk',
        'datum_pocetka_provere_pfk',
        'datum_pfk_izvestaja',
        'datum_slanja_zahteva_za_sprovodjenje_pk_provera',
        'broj_kandidata_za_koje_se_zakazuju_pk',
        'datum_pocetka_provere_pk',
        'broj_dana_sprovodjenja_pk_provera',
        'datum_pk_izvestaja',
        'datum_predaje_dokumentacije',
        'datum_pocetka_sprovodjenja_intervjua',
        'datum_izvestaja_sa_zavrsnog_intervjua',
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
        'datum_formiranja_liste_kandidata',
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
        'broj_neodazvanih_kandidata_ofk',
        'broj_neodazvanih_kandidata_pfk',
        'broj_neodazvanih_kandidata_pk',
        'broj_neodazvanih_kandidata_dokumentacija',
        'broj_neodazvanih_kandidata_zavrsni_razgovor',
        // 'oblast_rada', // Uklonjeno - sada je many-to-many relacija
        'velicina_organa',
        'broj_uspelih_postupaka',
        'broj_neuspelih_postupaka',
        'broj_obustavljenih_postupaka',
        'broj_ponistenih_postupaka',
        'prosecna_starost_kandidata',
        'udeo_kandidata_mladjih_od_30',
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
     * Relacija sa sifarnik_mesta tabelom (many-to-many)
     */
    public function mestaRada()
    {
        return $this->belongsToMany(
            SifarnikMesta::class,
            'mesto_rada_podaci_o_radnom_mestu',
            'podaci_o_radnom_mestu_id',
            'sifarnik_mesta_id'
        )
        ->withPivot('broj_izvrsilaca')
        ->withTimestamps();
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

    /**
     * Relacija sa sifarnik_oblast_rada tabelom (many-to-many)
     */
    public function oblastiRada()
    {
        return $this->belongsToMany(
            SifarnikOblastRada::class,
            'oblast_rada_podaci_o_radnom_mestu',
            'podaci_o_radnom_mestu_id',
            'sifarnik_oblast_rada_id'
        );
    }

    /**
     * Relacija sa sifarnik_velicina_organa tabelom
     */
    public function velicinaOrganaRelation()
    {
        return $this->belongsTo(SifarnikVelicinaOrgana::class, 'velicina_organa');
    }

    /**
     * Relacija sa sifarnik_razlog_neuspelih_konkursa tabelom
     */
    public function razlogNeuspelogKonkursaRelation()
    {
        return $this->belongsTo(SifarnikRazlogNeuspelihKonkursa::class, 'razlog_neuspelog_konkursa');
    }

    /**
     * Activity log konfiguracija
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'vrsta_organa',
                'organ',
                'naziv_radnog_mesta',
                'tip_konkursa',
                'broj_izvrsilaca',
                'zvanje',
                // 'mesto_rada', // Uklonjeno - many-to-many se ne loguje ovako
                'status_konkursa_na_dan_1',
                'razlog_neuspelog_konkursa',
                'status_konkursa_na_dan_2',
                // Datumi poступka
                'datum_dobijanja_saglasnosti_vlade',
                'datum_donosenja_resenja_o_pokretanju_postupka',
                'datum_dobijanja_obavestenja_od_suka',
                'datum_odrzavanja_prvog_sastanka',
                'datum_oglasavanja',
                'datum_pregleda_prijava',
                'datum_slanja_zahteva_za_sprovodjenje_ofk_provera',
                'broj_kandidata_za_koje_se_zakazuju_ofk',
                'datum_pocetka_provere_ofk',
                'datum_ofk_izvestaja',
                'datum_slanja_zahteva_za_sprovodjenje_pfk_provera',
                'broj_kandidata_za_koje_se_zakazuju_pfk',
                'datum_pocetka_provere_pfk',
                'datum_pfk_izvestaja',
                'datum_slanja_zahteva_za_sprovodjenje_pk_provera',
                'broj_kandidata_za_koje_se_zakazuju_pk',
                'datum_pocetka_provere_pk',
                'broj_dana_sprovodjenja_pk_provera',
                'datum_pk_izvestaja',
                'datum_predaje_dokumentacije',
                'datum_pocetka_sprovodjenja_intervjua',
                'datum_izvestaja_sa_zavrsnog_intervjua',
                'datum_dostavljanja_liste_rukovodiocu_organa',
                'datum_donosenja_resenja_o_izabranom_kandidatu',
                'datum_stupanja_na_rad',
                // Остала поља
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
                'datum_formiranja_liste_kandidata',
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
                'broj_neodazvanih_kandidata_ofk',
                'broj_neodazvanih_kandidata_pfk',
                'broj_neodazvanih_kandidata_pk',
                'broj_neodazvanih_kandidata_dokumentacija',
                'broj_neodazvanih_kandidata_zavrsni_razgovor',
                'oblast_rada',
                'velicina_organa',
                'broj_uspelih_postupaka',
                'broj_neuspelih_postupaka',
                'broj_obustavljenih_postupaka',
                'broj_ponistenih_postupaka',
                'prosecna_starost_kandidata',
                'udeo_kandidata_mladjih_od_30',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'Kreirano novo radno mesto',
                'updated' => 'Ažurirano radno mesto',
                'deleted' => 'Obrisano radno mesto',
                default => "Radno mesto {$eventName}",
            })
            ->useLogName('podaci_o_radnom_mestu');
    }

    /**
     * Tap into activity before logging to add IP address
     */
    public function tapActivity(\Spatie\Activitylog\Contracts\Activity $activity, string $eventName)
    {
        $activity->ip_address = request()->ip();
    }
}
