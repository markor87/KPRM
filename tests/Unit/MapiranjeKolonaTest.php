<?php

namespace Tests\Unit;

use App\Services\Uvoz\MapiranjeKolona;
use PHPUnit\Framework\TestCase;

class MapiranjeKolonaTest extends TestCase
{
    private MapiranjeKolona $mapiranje;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mapiranje = new MapiranjeKolona();
    }

    public function test_pogadja_tacno_zaglavlje_iz_izvoza(): void
    {
        ['mapa' => $mapa] = $this->mapiranje->automatski([
            'ID', 'Орган', 'Назив радног места', 'Датум оглашавања',
        ]);

        $this->assertSame(0, $mapa['id']);
        $this->assertSame(1, $mapa['organ']);
        $this->assertSame(2, $mapa['naziv_radnog_mesta']);
        $this->assertSame(3, $mapa['datum_oglasavanja']);
    }

    public function test_pogadja_uprkos_zavrsnom_razmaku_i_tvrdom_razmaku(): void
    {
        ['mapa' => $mapa] = $this->mapiranje->automatski([
            "Орган ", "Назив\xC2\xA0радног места", 'Датум оглашавања:',
        ]);

        $this->assertSame(0, $mapa['organ']);
        $this->assertSame(1, $mapa['naziv_radnog_mesta']);
        $this->assertSame(2, $mapa['datum_oglasavanja']);
    }

    public function test_pogadja_latinicni_homoglif(): void
    {
        // "Opган" - latinicno O i p umesto cirilicnog О и р.
        ['mapa' => $mapa] = $this->mapiranje->automatski(['Opган']);

        $this->assertSame(0, $mapa['organ']);
    }

    public function test_pogadja_ime_kolone_u_bazi(): void
    {
        ['mapa' => $mapa] = $this->mapiranje->automatski(['datum_oglasavanja', 'broj_izvrsilaca']);

        $this->assertSame(0, $mapa['datum_oglasavanja']);
        $this->assertSame(1, $mapa['broj_izvrsilaca']);
    }

    public function test_pogadja_sinonim(): void
    {
        ['mapa' => $mapa] = $this->mapiranje->automatski(['Места рада', 'Претежна област рада']);

        $this->assertSame(0, $mapa['mesta_rada']);
        $this->assertSame(1, $mapa['oblasti_rada']);
    }

    public function test_nepoznato_zaglavlje_ostaje_nemapirano(): void
    {
        ['mapa' => $mapa, 'automatski' => $automatski] = $this->mapiranje->automatski([
            'Орган', 'Нека колона које нема у шеми',
        ]);

        $this->assertSame(['organ'], $automatski);
        $this->assertArrayNotHasKey(1, array_flip($mapa));
    }

    public function test_isto_polje_se_ne_mapira_dvaput(): void
    {
        ['mapa' => $mapa] = $this->mapiranje->automatski(['Орган', 'organ']);

        // Prvi pogodak pobedjuje - druga kolona ostaje neiskoriscena.
        $this->assertSame(0, $mapa['organ']);
        $this->assertCount(1, $mapa);
    }

    public function test_izvedena_polja_nisu_u_semi(): void
    {
        ['mapa' => $mapa] = $this->mapiranje->automatski([
            'Врста органа', 'Исход конкурса', 'Орган',
        ]);

        $this->assertArrayNotHasKey('vrsta_organa', $mapa);
        $this->assertArrayNotHasKey('ishod_konkursa', $mapa);
        $this->assertSame(2, $mapa['organ']);
    }

    public function test_normalizacija_je_ista_bez_obzira_na_velicinu_slova(): void
    {
        $this->assertSame(
            $this->mapiranje->normalizuj('DATUM_OGLASAVANJA'),
            $this->mapiranje->normalizuj('datum_oglasavanja'),
        );
    }
}
