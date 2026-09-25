<?php

namespace Tests\Unit;

use App\Models\PodaciORadnomMestu;
use App\Services\Uvoz\SemaUvoza;
use PHPUnit\Framework\TestCase;

/**
 * Sema je jedino mesto istine za uvoz. Ovi testovi hvataju njeno tiho razilazenje
 * sa modelom - npr. kad se polje preimenuje u $fillable a sema ostane po starom.
 */
class SemaUvozaTest extends TestCase
{
    public function test_nema_duplih_labela(): void
    {
        $labele = array_map(fn (array $def): string => $def['labela'], SemaUvoza::polja());
        $duple = array_keys(array_filter(array_count_values($labele), fn (int $n): bool => $n > 1));

        // Dve iste labele bi se sudarile u recniku automatskog mapiranja i jedna kolona
        // bi tiho ostala nemapirana.
        $this->assertSame([], $duple, 'Дупле лабеле: ' . implode(', ', $duple));
    }

    public function test_svako_polje_seme_je_u_fillable(): void
    {
        $fillable = (new PodaciORadnomMestu())->getFillable();
        $veze = ['id', 'mesta_rada', 'oblasti_rada'];

        foreach (SemaUvoza::polja() as $kljuc => $def) {
            if (in_array($kljuc, $veze, true)) {
                continue;
            }

            $this->assertContains($kljuc, $fillable, "Поље „{$kljuc}" . '" није у $fillable модела.');
        }
    }

    public function test_izostavljena_su_tacno_tri_polja(): void
    {
        $fillable = (new PodaciORadnomMestu())->getFillable();
        $viska = array_values(array_diff($fillable, array_keys(SemaUvoza::polja())));

        sort($viska);

        // vrsta_organa i ishod_konkursa model preracunava pri upisu; unos_zavrsen bi
        // autorstvo pripisao onome ko pokrece uvoz. Ako se ovaj spisak promeni, neko je
        // dodao polje u model a zaboravio semu.
        $this->assertSame(['ishod_konkursa', 'unos_zavrsen', 'vrsta_organa'], $viska);
    }

    public function test_svako_sifarnicko_polje_ima_model(): void
    {
        $sifarnicka = SemaUvoza::sifarnickaPolja();

        foreach (SemaUvoza::polja() as $kljuc => $def) {
            if (! in_array($def['tip'], [SemaUvoza::TIP_SIFARNIK, SemaUvoza::TIP_MESTA_RADA, SemaUvoza::TIP_OBLASTI_RADA], true)) {
                continue;
            }

            $this->assertArrayHasKey($kljuc, $sifarnicka, "Шифарничко поље „{$kljuc}\" нема модел.");
            $this->assertTrue(class_exists($sifarnicka[$kljuc]), "Модел за „{$kljuc}\" не постоји.");
        }
    }

    public function test_not_null_logicke_kolone_su_logickog_tipa(): void
    {
        $polja = SemaUvoza::polja();

        foreach (SemaUvoza::NOT_NULL_LOGICKE as $kljuc) {
            $this->assertArrayHasKey($kljuc, $polja);
            $this->assertSame(SemaUvoza::TIP_LOGICKA, $polja[$kljuc]['tip']);
        }
    }

    public function test_kolone_baze_izostavljaju_id_i_veze(): void
    {
        $kolone = SemaUvoza::kolonaBaze();

        $this->assertNotContains('id', $kolone);
        $this->assertNotContains('mesta_rada', $kolone);
        $this->assertNotContains('oblasti_rada', $kolone);
        $this->assertContains('datum_oglasavanja', $kolone);
    }
}
