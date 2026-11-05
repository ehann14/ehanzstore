{{--
    Kolom jebakan bot (honeypot). Pasang di dalam <form>:  <x-honeypot />
    Disembunyikan dengan CSS inline di luar layar (bukan display:none, karena bot pintar
    melewati kolom yang display:none). Manusia tidak melihatnya dan pembaca layar melewatinya.
--}}
<div aria-hidden="true" style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden;">
    <label>Jangan diisi
        <input type="text" name="{{ \App\Support\SpamGuard::HONEYPOT }}" value="" tabindex="-1" autocomplete="off">
    </label>
</div>