<?php

namespace Tests\Unit;

use App\Services\Uvoz\GreskaVrednosti;
use App\Services\Uvoz\NormalizatorVrednosti as N;
use PHPUnit\Framework\TestCase;

class NormalizatorVrednostiTest extends TestCase
{
    public function test_prazno_prepoznaje_razmake_ali_ne_nulu(): void
    {
        $this->assertTrue(N::jePrazno(null));
        $this->assertTrue(N::jePrazno(''));
        $this->assertTrue(N::jePrazno('   '));
        $this->assertTrue(N::jePrazno("\xC2\xA0"));

        $this->assertFalse(N::jePrazno(0));
        $this->assertFalse(N::jePrazno('0'));
    }

    public function test_datum_prihvata_srpske_i_iso_oblike(): void
    {
        $this->assertSame('2026-12-31', N::datum('31.12.2026'));
        $this->assertSame('2026-12-31', N::datum('31.12.2026.'));
        $this->assertSame('2026-12-31', N::datum('2026-12-31'));
        $this->assertSame('2026-12-31', N::datum('31/12/2026'));
        $this->assertNull(N::datum(''));
    }

    public function test_datum_prihvata_excel_serijski_broj(): void
    {
        // 45000 = 2023-03-15 po Excel-ovom racunanju.
        $this->assertSame('2023-03-15', N::datum(45000));
    }

    public function test_datum_odbija_nepostojeci_dan(): void
    {
        $this->expectException(GreskaVrednosti::class);

        N::datum('31.02.2026');
    }

    public function test_datum_odbija_broj_van_razumnog_opsega(): void
    {
        $this->expectException(GreskaVrednosti::class);

        N::datum(12);
    }

    public function test_ceo_broj_uklanja_razmake_i_grupisanje_hiljada(): void
    {
        $this->assertSame(1234, N::ceoBroj('1 234'));
        $this->assertSame(1234, N::ceoBroj("1\xC2\xA0234"));
        $this->assertSame(1234, N::ceoBroj('1.234'));
        $this->assertSame(12, N::ceoBroj(12));
        $this->assertNull(N::ceoBroj(''));
    }

    public function test_ceo_broj_odbija_tekst_i_decimale(): void
    {
        $this->expectException(GreskaVrednosti::class);

        N::ceoBroj('пет');
    }

    public function test_ceo_broj_postuje_opseg(): void
    {
        $this->expectException(GreskaVrednosti::class);

        N::ceoBroj('-3', 0);
    }

    public function test_decimal_prihvata_zarez_i_tacku(): void
    {
        $this->assertSame(12.5, N::decimal('12,5'));
        $this->assertSame(12.5, N::decimal('12.5'));
        $this->assertSame(12.35, N::decimal('12,345'));
        $this->assertNull(N::decimal(''));
    }

    public function test_decimal_odbija_vrednost_van_opsega(): void
    {
        $this->expectException(GreskaVrednosti::class);

        N::decimal('150', 0, 100);
    }

    public function test_logicka_prihvata_srpske_oblike(): void
    {
        $this->assertTrue(N::logicka('да'));
        $this->assertTrue(N::logicka('1'));
        $this->assertFalse(N::logicka('не'));
        $this->assertFalse(N::logicka('0'));
        $this->assertNull(N::logicka(''));
    }

    public function test_logicka_odbija_nepoznatu_vrednost(): void
    {
        $this->expectException(GreskaVrednosti::class);

        N::logicka('можда');
    }

    public function test_mesta_rada_razlaze_spakovanu_celiju(): void
    {
        $this->assertSame([12 => 3, 45 => 1], N::mestaRada('12:3, 45:1'));
        $this->assertSame([12 => 3], N::mestaRada('12-3'));
        $this->assertNull(N::mestaRada(''));
    }

    public function test_mesta_rada_odbija_grad_bez_broja_izvrsilaca(): void
    {
        $this->expectException(GreskaVrednosti::class);

        N::mestaRada('12, 45');
    }

    public function test_mesta_rada_odbija_ponovljen_grad(): void
    {
        $this->expectException(GreskaVrednosti::class);

        N::mestaRada('12:3, 12:1');
    }

    public function test_oblasti_rada_razlaze_listu_idova(): void
    {
        $this->assertSame([3, 7], N::oblastiRada('3, 7'));
        $this->assertSame([3], N::oblastiRada('3, 3'));
        $this->assertNull(N::oblastiRada(''));
    }

    public function test_oblasti_rada_odbija_naziv_umesto_ida(): void
    {
        $this->expectException(GreskaVrednosti::class);

        N::oblastiRada('Правосуђе');
    }

    public function test_tekst_odbija_predugu_vrednost(): void
    {
        $this->expectException(GreskaVrednosti::class);

        N::tekst(str_repeat('а', 1001), 1000);
    }

    public function test_tekst_cuva_unutrasnje_razmake(): void
    {
        // Naziv radnog mesta u bazi cesto ima dvostruki razmak. Ako ga uvoz sazme,
        // datoteka izvezena pa vracena nepromenjena prijavi stotine laznih izmena
        // i zaista ih upise.
        $this->assertSame('насиља  и дискриминације', N::tekst('насиља  и дискриминације'));
        $this->assertSame('насиља  и дискриминације', N::tekst('  насиља  и дискриминације  '));
    }

    public function test_tekst_tvrdi_razmak_postaje_obican(): void
    {
        $tvrdiRazmak = "\xC2\xA0";

        $this->assertSame('а б', N::tekst('а' . $tvrdiRazmak . 'б'));
    }

    public function test_zaglavlja_se_i_dalje_sazimaju(): void
    {
        // Za zaglavlja je sazimanje pozeljno - „Датум  оглашавања" mora pogoditi polje.
        $this->assertSame('Датум оглашавања', N::ocisti('  Датум   оглашавања '));
    }
}
