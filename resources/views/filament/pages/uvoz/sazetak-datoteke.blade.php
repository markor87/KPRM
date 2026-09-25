{{--
    Tema админ панела се компајлира са source(none) — Tailwind класе овде немају ефекта,
    зато иде прави CSS.
--}}
<div style="border:1px solid rgb(228 228 231);border-radius:.5rem;padding:.75rem 1rem;background:rgb(250 250 250);">
    <div style="font-weight:600;font-size:.875rem;margin-bottom:.25rem;">
        {{ $this->imeDatoteke }}
    </div>
    <div style="font-size:.8125rem;color:rgb(82 82 91);">
        Пронађено {{ count($this->zaglavlje) }} колона у заглављу.
        Аутоматски мапирано {{ count($this->automatskiMapirani) }} поља.
    </div>
</div>
