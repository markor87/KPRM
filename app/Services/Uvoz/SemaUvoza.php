<?php

namespace App\Services\Uvoz;

use App\Models\SifarnikIzabraniKandidat;
use App\Models\SifarnikKodoviGradova;
use App\Models\SifarnikOblastRada;
use App\Models\SifarnikOrgani;
use App\Models\SifarnikProveraPfk;
use App\Models\SifarnikRazlogNeuspelihKonkursa;
use App\Models\SifarnikStatusKonkursa;
use App\Models\SifarnikTipKonkursa;
use App\Models\SifarnikZvanje;

/**
 * Jedno mesto istine za uvoz radnih mesta.
 *
 * Ova sema pokrece tri stvari odjednom: automatsko mapiranje zaglavlja iz fajla,
 * proveru tipa vrednosti u celiji i prikaz u koraku mapiranja. Zato se spisak polja
 * ne sme duplirati nigde drugde.
 *
 * Namerno izostavljeno:
 *  - vrsta_organa i ishod_konkursa - model ih uvek preračunava pri upisu
 *    (PodaciORadnomMestu::booted(), :169-171 i :194-204), pa bi uvoz samo lagao korisnika.
 *  - unos_zavrsen / unos_zavrsen_at / unos_zavrsen_by - saving kuka bi autorstvo pripisala
 *    onome ko pokrece uvoz. Nisu ni u $fillable.
 */
class SemaUvoza
{
    public const TIP_ID = 'id';
    public const TIP_TEKST = 'tekst';
    public const TIP_CEO_BROJ = 'ceo_broj';
    public const TIP_DECIMAL = 'decimal';
    public const TIP_DATUM = 'datum';
    public const TIP_LOGICKA = 'logicka';
    public const TIP_SIFARNIK = 'sifarnik';
    public const TIP_MESTA_RADA = 'mesta_rada';
    public const TIP_OBLASTI_RADA = 'oblasti_rada';

    public const TAB_OSNOVNI = 'Основни подаци о конкурсу';
    public const TAB_POKRETANJE = 'Покретање поступка и пријаве';
    public const TAB_PROVERE = 'Провере компетенција кандидата';
    public const TAB_ZAVRSETAK = 'Завршетак поступка и кандидати';
    public const TAB_ZALBE = 'Статус и жалбе';

    /**
     * Kolone koje su u bazi NOT NULL sa default false. Prazna celija tu postaje false,
     * ne null - MySQL u strogom rezimu bi na null bacio izuzetak i srusio celu transakciju.
     *
     * @see database/migrations/2026_07_15_100000_add_konkurs_bez_saglasnosti_vlade_to_podaci_o_radnom_mestu_table.php:15
     * @see database/migrations/2026_08_18_100000_add_preuzete_ocene_to_podaci_o_radnom_mestu_table.php:20-21
     */
    public const NOT_NULL_LOGICKE = [
        'konkurs_bez_saglasnosti_vlade',
        'ofk_ocene_preuzete',
        'pk_ocene_preuzete',
    ];

    /**
     * Polja koja se prikazuju u koraku mapiranja kao onemogucena, uz objasnjenje.
     * Nisu deo polja() - ne mogu se mapirati ni uvesti.
     *
     * @return array<string, string>
     */
    public static function izvedenaPolja(): array
    {
        return [
            'Врста органа' => 'Изводи се из органа при упису — занемарује се.',
            'Исход конкурса' => 'Изводи се из статуса и датума при упису — занемарује се.',
            'Завршен унос' => 'Поставља се ручно у форми, да ауторство не би припало ономе ко покреће увоз.',
        ];
    }

    /**
     * Sva polja koja se mogu uvesti, redom kojim se prikazuju u koraku mapiranja.
     *
     * Kljuc je ime kolone u bazi (ili virtuelni kljuc za veze vise-na-vise).
     * 'sinonimi' su dodatna zaglavlja koja se prihvataju uz 'labela' - labela se
     * uvek proba, ne mora se ponavljati u sinonimima.
     *
     * @return array<string, array{tip: string, labela: string, tab: string, model?: class-string, sinonimi?: array<string>, min?: float, max?: float}>
     */
    public static function polja(): array
    {
        return [
            // --- kljuc reda -------------------------------------------------
            'id' => [
                'tip' => self::TIP_ID,
                'labela' => 'ID',
                'tab' => self::TAB_OSNOVNI,
                'sinonimi' => ['ИД', 'Ид', 'id', 'ID записа', 'Шифра'],
            ],

            // --- Tab 1: osnovni podaci --------------------------------------
            'organ' => [
                'tip' => self::TIP_SIFARNIK,
                'model' => SifarnikOrgani::class,
                'labela' => 'Орган',
                'tab' => self::TAB_OSNOVNI,
                'sinonimi' => ['ID органа', 'Орган (ид)', 'Шифра органа'],
            ],
            'naziv_radnog_mesta' => [
                'tip' => self::TIP_TEKST,
                'labela' => 'Назив радног места',
                'tab' => self::TAB_OSNOVNI,
                'max_duzina' => 1000,
            ],
            'tip_konkursa' => [
                'tip' => self::TIP_SIFARNIK,
                'model' => SifarnikTipKonkursa::class,
                'labela' => 'Тип конкурса',
                'tab' => self::TAB_OSNOVNI,
            ],
            'broj_izvrsilaca' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број извршилаца',
                'tab' => self::TAB_OSNOVNI,
                'min' => 0,
            ],
            'zvanje' => [
                'tip' => self::TIP_SIFARNIK,
                'model' => SifarnikZvanje::class,
                'labela' => 'Звање',
                'tab' => self::TAB_OSNOVNI,
            ],
            'mesta_rada' => [
                'tip' => self::TIP_MESTA_RADA,
                'model' => SifarnikKodoviGradova::class,
                'labela' => 'Место рада',
                'tab' => self::TAB_OSNOVNI,
                'sinonimi' => ['Места рада', 'Места рада са бројем извршилаца'],
                'pomoc' => 'Формат: „12:3, 45:1" — ид града, двотачка, број извршилаца.',
            ],
            'oblasti_rada' => [
                'tip' => self::TIP_OBLASTI_RADA,
                'model' => SifarnikOblastRada::class,
                'labela' => 'Област рада',
                'tab' => self::TAB_OSNOVNI,
                'sinonimi' => ['Области рада', 'Претежна област рада'],
                'pomoc' => 'Формат: „3, 7" — ид-ови области раздвојени зарезом.',
            ],
            'status_konkursa_na_dan_1' => [
                'tip' => self::TIP_SIFARNIK,
                'model' => SifarnikStatusKonkursa::class,
                'labela' => 'Статус конкурса на дан 1',
                'tab' => self::TAB_OSNOVNI,
            ],
            'status_konkursa_na_dan_2' => [
                'tip' => self::TIP_SIFARNIK,
                'model' => SifarnikStatusKonkursa::class,
                'labela' => 'Статус конкурса на дан 2',
                'tab' => self::TAB_OSNOVNI,
            ],
            'datum_ishoda_konkursa' => [
                'tip' => self::TIP_DATUM,
                'labela' => 'Датум исхода конкурса',
                'tab' => self::TAB_OSNOVNI,
            ],
            'razlog_neuspelog_konkursa' => [
                'tip' => self::TIP_SIFARNIK,
                'model' => SifarnikRazlogNeuspelihKonkursa::class,
                'labela' => 'Разлог неуспелог конкурса',
                'tab' => self::TAB_OSNOVNI,
            ],

            // --- Tab 2: pokretanje postupka i prijave -----------------------
            'konkurs_bez_saglasnosti_vlade' => [
                'tip' => self::TIP_LOGICKA,
                'labela' => 'Конкурс покренут без сагласности Владе',
                'tab' => self::TAB_POKRETANJE,
            ],
            'datum_dobijanja_saglasnosti_vlade' => [
                'tip' => self::TIP_DATUM,
                'labela' => 'Датум добијања сагласности Владе',
                'tab' => self::TAB_POKRETANJE,
            ],
            'datum_donosenja_resenja_o_pokretanju_postupka' => [
                'tip' => self::TIP_DATUM,
                'labela' => 'Датум доношења решења о покретању поступка',
                'tab' => self::TAB_POKRETANJE,
            ],
            'datum_dobijanja_obavestenja_od_suka' => [
                'tip' => self::TIP_DATUM,
                'labela' => 'Датум добијања обавештења од СУКа',
                'tab' => self::TAB_POKRETANJE,
            ],
            'datum_odrzavanja_prvog_sastanka' => [
                'tip' => self::TIP_DATUM,
                'labela' => 'Датум одржавања првог састанка',
                'tab' => self::TAB_POKRETANJE,
            ],
            'datum_oglasavanja' => [
                'tip' => self::TIP_DATUM,
                'labela' => 'Датум оглашавања',
                'tab' => self::TAB_POKRETANJE,
            ],
            'datum_pregleda_prijava' => [
                'tip' => self::TIP_DATUM,
                'labela' => 'Датум прегледа пријава',
                'tab' => self::TAB_POKRETANJE,
            ],
            'ukupan_broj_prijava' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Укупан број пријава',
                'tab' => self::TAB_POKRETANJE,
                'min' => 0,
            ],
            'broj_prijava_iz_organa' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број пријава из органа',
                'tab' => self::TAB_POKRETANJE,
                'min' => 0,
            ],
            'broj_prijava_iz_drugih_organa' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број пријава из других органа',
                'tab' => self::TAB_POKRETANJE,
                'min' => 0,
            ],
            'broj_prijava_van_drzavnih_organa' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број пријава ван државних органа',
                'tab' => self::TAB_POKRETANJE,
                'min' => 0,
            ],
            'prosecna_starost_kandidata' => [
                'tip' => self::TIP_DECIMAL,
                'labela' => 'Просечна старост кандидата у изборном поступку',
                'tab' => self::TAB_POKRETANJE,
                'sinonimi' => ['Просечна старост кандидата'],
                'min' => 0,
                'max' => 100,
            ],
            'udeo_kandidata_mladjih_od_30' => [
                'tip' => self::TIP_DECIMAL,
                'labela' => 'Удео кандидата млађих од 30 година (%)',
                'tab' => self::TAB_POKRETANJE,
                'sinonimi' => ['Удео кандидата млађих од 30 година', 'Удео кандидата млађих од 30'],
                'min' => 0,
                'max' => 100,
            ],
            'broj_validnih_prijava' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број валидних пријава',
                'tab' => self::TAB_POKRETANJE,
                'min' => 0,
            ],
            'broj_validnih_prijava_iz_organa' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број валидних пријава из органа',
                'tab' => self::TAB_POKRETANJE,
                'min' => 0,
            ],
            'broj_validnih_prijava_iz_drugog_organa' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број валидних пријава из другог органа',
                'tab' => self::TAB_POKRETANJE,
                'min' => 0,
            ],
            'broj_validnih_prijava_van_drzavnih_organa' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број валидних пријава ван државних органа',
                'tab' => self::TAB_POKRETANJE,
                'min' => 0,
            ],

            // --- Tab 3: provere kompetencija --------------------------------
            'datum_slanja_zahteva_za_sprovodjenje_ofk_provera' => [
                'tip' => self::TIP_DATUM,
                'labela' => 'Датум слања захтева за спровођење ОФК провера',
                'tab' => self::TAB_PROVERE,
            ],
            'broj_kandidata_za_koje_se_zakazuju_ofk' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број кандидата за које се заказују ОФК',
                'tab' => self::TAB_PROVERE,
                'min' => 0,
            ],
            'ofk_ocene_preuzete' => [
                'tip' => self::TIP_LOGICKA,
                'labela' => 'Оцене ОФК преузете са раније спроведених провера',
                'tab' => self::TAB_PROVERE,
                'sinonimi' => ['Оцене ОФК преузете'],
            ],
            'datum_pocetka_provere_ofk' => [
                'tip' => self::TIP_DATUM,
                'labela' => 'Датум спровођења провере ОФК',
                'tab' => self::TAB_PROVERE,
                'sinonimi' => ['Датум почетка провере ОФК'],
            ],
            'datum_ofk_izvestaja' => [
                'tip' => self::TIP_DATUM,
                'labela' => 'Датум ОФК извештаја',
                'tab' => self::TAB_PROVERE,
            ],
            'broj_kandidata_koji_su_ispunlii_merila_ofk' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број кандидата који су испунили мерила ОФК',
                'tab' => self::TAB_PROVERE,
                'min' => 0,
            ],
            'broj_neodazvanih_kandidata_ofk' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број неодазваних кандидата на ОФК',
                'tab' => self::TAB_PROVERE,
                'min' => 0,
            ],
            'datum_slanja_zahteva_za_sprovodjenje_pfk_provera' => [
                'tip' => self::TIP_DATUM,
                'labela' => 'Датум слања захтева за спровођење ПФК провера',
                'tab' => self::TAB_PROVERE,
            ],
            'broj_kandidata_za_koje_se_zakazuju_pfk' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број кандидата за које се заказују ПФК',
                'tab' => self::TAB_PROVERE,
                'min' => 0,
            ],
            'datum_pocetka_provere_pfk' => [
                'tip' => self::TIP_DATUM,
                'labela' => 'Датум почетка провере ПФК',
                'tab' => self::TAB_PROVERE,
            ],
            'datum_pfk_izvestaja' => [
                'tip' => self::TIP_DATUM,
                'labela' => 'Датум ПФК извештаја',
                'tab' => self::TAB_PROVERE,
            ],
            'broj_kandidata_koji_su_ispunlii_merila_pfk' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број кандидата који су испунили мерила ПФК',
                'tab' => self::TAB_PROVERE,
                'min' => 0,
            ],
            'broj_neodazvanih_kandidata_pfk' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број неодазваних кандидата на ПФК',
                'tab' => self::TAB_PROVERE,
                'min' => 0,
            ],
            'provera_pfk' => [
                'tip' => self::TIP_SIFARNIK,
                'model' => SifarnikProveraPfk::class,
                'labela' => 'Провера ПФК',
                'tab' => self::TAB_PROVERE,
            ],
            'datum_slanja_zahteva_za_sprovodjenje_pk_provera' => [
                'tip' => self::TIP_DATUM,
                'labela' => 'Датум слања захтева за спровођење ПК провера',
                'tab' => self::TAB_PROVERE,
            ],
            'broj_kandidata_za_koje_se_zakazuju_pk' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број кандидата за које се заказују ПК',
                'tab' => self::TAB_PROVERE,
                'min' => 0,
            ],
            'pk_ocene_preuzete' => [
                'tip' => self::TIP_LOGICKA,
                'labela' => 'Оцене ПК преузете са раније спроведених провера',
                'tab' => self::TAB_PROVERE,
                'sinonimi' => ['Оцене ПК преузете'],
            ],
            'datum_pocetka_provere_pk' => [
                'tip' => self::TIP_DATUM,
                'labela' => 'Датум почетка провере ПК',
                'tab' => self::TAB_PROVERE,
            ],
            'datum_pk_izvestaja' => [
                'tip' => self::TIP_DATUM,
                'labela' => 'Датум ПК извештаја',
                'tab' => self::TAB_PROVERE,
            ],
            'broj_kandidata_ispunili_merila_pk' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број кандидата који су испунили мерила ПК',
                'tab' => self::TAB_PROVERE,
                'min' => 0,
            ],
            'broj_neodazvanih_kandidata_pk' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број неодазваних кандидата на ПК',
                'tab' => self::TAB_PROVERE,
                'min' => 0,
            ],
            'broj_dana_sprovodjenja_pk_provera' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број дана спровођења ПК провера',
                'tab' => self::TAB_PROVERE,
                'min' => 0,
            ],

            // --- Tab 4: zavrsetak postupka i kandidati ----------------------
            'datum_predaje_dokumentacije' => [
                'tip' => self::TIP_DATUM,
                'labela' => 'Датум предаје документације',
                'tab' => self::TAB_ZAVRSETAK,
            ],
            'broj_neodazvanih_kandidata_dokumentacija' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број неодазваних кандидата на доставу документације',
                'tab' => self::TAB_ZAVRSETAK,
                'min' => 0,
            ],
            'datum_pocetka_sprovodjenja_intervjua' => [
                'tip' => self::TIP_DATUM,
                'labela' => 'Датум почетка спровођења интервјуа',
                'tab' => self::TAB_ZAVRSETAK,
                'sinonimi' => ['Датум спровођења завршног интервјуа'],
            ],
            'datum_izvestaja_sa_zavrsnog_intervjua' => [
                'tip' => self::TIP_DATUM,
                'labela' => 'Датум извештаја са завршног интервјуа',
                'tab' => self::TAB_ZAVRSETAK,
            ],
            'broj_odazvanih_kandidata_na_zavrsnom_razgovoru' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број одазваних кандидата на завршном разговору',
                'tab' => self::TAB_ZAVRSETAK,
                'min' => 0,
            ],
            'broj_neodazvanih_kandidata_zavrsni_razgovor' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број неодазваних кандидата на завршном разговору',
                'tab' => self::TAB_ZAVRSETAK,
                'min' => 0,
            ],
            'datum_formiranja_liste_kandidata' => [
                'tip' => self::TIP_DATUM,
                'labela' => 'Дан објављивања листе кандидата који су испунили мерила у изборном поступку',
                'tab' => self::TAB_ZAVRSETAK,
                // Стари наслов из извоза, да се ранији Excel фајлови и даље препознају.
                'sinonimi' => ['Дан формирања листе кандидата који учествују у изборном поступку', 'Датум формирања листе кандидата'],
            ],
            'broj_kandidata_na_listi' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број кандидата на листи',
                'tab' => self::TAB_ZAVRSETAK,
                'min' => 0,
            ],
            'broj_kandidata_iz_organa_na_listi' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број кандидата из органа на листи',
                'tab' => self::TAB_ZAVRSETAK,
                'min' => 0,
            ],
            'broj_kandidata_iz_drugog_drzavnog_organa_na_listi' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број кандидата из другог државног органа на листи',
                'tab' => self::TAB_ZAVRSETAK,
                'min' => 0,
            ],
            'broj_kandidata_van_drzavnih_organa_na_listi' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број кандидата ван државних органа на листи',
                'tab' => self::TAB_ZAVRSETAK,
                'min' => 0,
            ],
            'izabrani_kandidat' => [
                'tip' => self::TIP_SIFARNIK,
                'model' => SifarnikIzabraniKandidat::class,
                'labela' => 'Изабрани кандидат је из',
                'tab' => self::TAB_ZAVRSETAK,
                'sinonimi' => ['Изабрани кандидат'],
            ],
            'broj_bodova_izabranog_kandidata_na_ofk' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број бодова изабраног кандидата на ОФК',
                'tab' => self::TAB_ZAVRSETAK,
                'min' => 0,
            ],
            'broj_bodova_izabranog_kandidata_na_pfk' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број бодова изабраног кандидата на ПФК',
                'tab' => self::TAB_ZAVRSETAK,
                'min' => 0,
            ],
            'broj_bodova_izabranog_kandidata_na_pk' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број бодова изабраног кандидата на ПК',
                'tab' => self::TAB_ZAVRSETAK,
                'min' => 0,
            ],
            'broj_bodova_izabranog_kandidata_na_zavrsnom_razgovoru' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број бодова изабраног кандидата на завршном разговору',
                'tab' => self::TAB_ZAVRSETAK,
                'min' => 0,
            ],
            'drugoplasirani_kandidat' => [
                'tip' => self::TIP_SIFARNIK,
                'model' => SifarnikIzabraniKandidat::class,
                'labela' => 'Следеће рангирани кандидат је из',
                'tab' => self::TAB_ZAVRSETAK,
                'sinonimi' => ['Следеће рангирани кандидат', 'Другопласирани кандидат'],
            ],
            'broj_bodova_drugplasiranog_kandidata_na_ofk' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број бодова следеће рангираног кандидата на ОФК',
                'tab' => self::TAB_ZAVRSETAK,
                'min' => 0,
            ],
            'broj_bodova_drugplasiranog_kandidata_na_pfk' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број бодова следеће рангираног кандидата на ПФК',
                'tab' => self::TAB_ZAVRSETAK,
                'min' => 0,
            ],
            'broj_bodova_drugplasiranog_kandidata_na_pk' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број бодова следеће рангираног кандидата на ПК',
                'tab' => self::TAB_ZAVRSETAK,
                'min' => 0,
            ],
            'broj_bodova_drugoplasiranog_kandidata_na_zavrsnom_razgovoru' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број бодова следеће рангираног кандидата на завршном разговору',
                'tab' => self::TAB_ZAVRSETAK,
                'min' => 0,
            ],
            'datum_dostavljanja_liste_rukovodiocu_organa' => [
                'tip' => self::TIP_DATUM,
                'labela' => 'Датум достављања листе руководиоцу органа',
                'tab' => self::TAB_ZAVRSETAK,
            ],
            'datum_donosenja_resenja_o_izabranom_kandidatu' => [
                'tip' => self::TIP_DATUM,
                'labela' => 'Датум доношења решења о изабраном кандидату',
                'tab' => self::TAB_ZAVRSETAK,
            ],
            'datum_stupanja_na_rad' => [
                'tip' => self::TIP_DATUM,
                'labela' => 'Датум ступања на рад',
                'tab' => self::TAB_ZAVRSETAK,
            ],

            // --- Tab 5: status i zalbe --------------------------------------
            'broj_primljenih_izvrsilaca' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број примљених извршилаца',
                'tab' => self::TAB_ZALBE,
                'min' => 0,
            ],
            'ocena_sa_vrednovanja' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Оцена са вредновања',
                'tab' => self::TAB_ZALBE,
                'min' => 0,
            ],
            'broj_zalbi_na_resenje_o_odbacaju_prijave' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број жалби на решење о одбацивању пријаве',
                'tab' => self::TAB_ZALBE,
                'min' => 0,
            ],
            'broj_zalbi_na_resenje_o_prijemu_u_radni_odnos' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број жалби на решење о пријему у радни однос',
                'tab' => self::TAB_ZALBE,
                'min' => 0,
            ],
            'broj_usvojenih_zalbi_na_resenje_o_odbacaju_prijave' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број усвојених жалби на решење о одбацивању пријаве',
                'tab' => self::TAB_ZALBE,
                'min' => 0,
            ],
            'broj_usvojenih_zalbi_na_resenje_o_prijemu_u_radni_odnos' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број усвојених жалби на решење о пријему у радни однос',
                'tab' => self::TAB_ZALBE,
                'min' => 0,
            ],
            'broj_izvrsilaca_ponovno_oglasavanje' => [
                'tip' => self::TIP_CEO_BROJ,
                'labela' => 'Број извршилаца - поновно оглашавање',
                'tab' => self::TAB_ZALBE,
                'min' => 0,
            ],
        ];
    }

    /** @return array<string, string> kljuc => labela */
    public static function labele(): array
    {
        return array_map(fn (array $def): string => $def['labela'], self::polja());
    }

    public static function definicija(string $kljuc): ?array
    {
        return self::polja()[$kljuc] ?? null;
    }

    /** Kljucevi grupisani po tabu, redom kojim se prikazuju. */
    public static function poTabovima(): array
    {
        $grupe = [];

        foreach (self::polja() as $kljuc => $def) {
            $grupe[$def['tab']][$kljuc] = $def;
        }

        return $grupe;
    }

    /**
     * Sifarnicka polja i njihovi modeli - koristi se da se ucita po jedan pluck('id')
     * po sifarniku za ceo uvoz, umesto po jednog upita po redu.
     *
     * @return array<string, class-string>
     */
    public static function sifarnickaPolja(): array
    {
        $tipovi = [self::TIP_SIFARNIK, self::TIP_MESTA_RADA, self::TIP_OBLASTI_RADA];
        $izlaz = [];

        foreach (self::polja() as $kljuc => $def) {
            if (in_array($def['tip'], $tipovi, true) && isset($def['model'])) {
                $izlaz[$kljuc] = $def['model'];
            }
        }

        return $izlaz;
    }

    /** Kljucevi koji se upisuju kroz fill() - bez id-a i bez veza vise-na-vise. */
    public static function kolonaBaze(): array
    {
        $veze = [self::TIP_ID, self::TIP_MESTA_RADA, self::TIP_OBLASTI_RADA];

        return array_keys(array_filter(
            self::polja(),
            fn (array $def): bool => ! in_array($def['tip'], $veze, true),
        ));
    }
}
