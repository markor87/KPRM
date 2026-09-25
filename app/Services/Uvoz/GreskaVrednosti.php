<?php

namespace App\Services\Uvoz;

use RuntimeException;

/**
 * Vrednost u celiji se ne da procitati kao trazeni tip.
 *
 * Poruka je namenjena korisniku i ide pravo u izvestaj probnog uvoza, pa mora biti
 * na srpskom i konkretna ("Није датум: 31.02.2026"), ne tehnicka.
 */
class GreskaVrednosti extends RuntimeException
{
}
