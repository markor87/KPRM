{{--
    Страница за режим одржавања (php artisan down --render="errors::503").

    Мора да буде потпуно самостална: Laravel је израдује У ТРЕНУТКУ покретања
    команде и снима готов HTML у storage/framework/maintenance.php, па се приказује
    без покретања апликације — и док composer брише vendor фолдер. Зато овде нема
    ни Vite асета, ни база, ни сесије, ни спољних фонтова — само инлајн CSS.
--}}
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Систем је у режиму одржавања — КПРМ</title>

    <style>
        :root {
            --pozadina: #f4f6f9;
            --karta: #ffffff;
            --naslov: #2e3440;
            --tekst: #5b6472;
            --ivica: rgb(9 9 11 / 0.08);
            --akcenat: #d97706;
            --akcenat-blaga: rgb(217 119 6 / 0.12);
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --pozadina: #1b2027;
                --karta: #252b35;
                --naslov: #e5e9f0;
                --tekst: #b4bcca;
                --ivica: rgb(255 255 255 / 0.08);
                --akcenat: #f0b429;
                --akcenat-blaga: rgb(240 180 41 / 0.14);
            }
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            margin: 0;
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background-color: var(--pozadina);
            color: var(--tekst);
            font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        .karta {
            width: 100%;
            max-width: 30rem;
            padding: 2.75rem 2.5rem;
            border-radius: 1rem;
            background-color: var(--karta);
            box-shadow: 0 1px 2px rgb(0 0 0 / 0.04), 0 12px 32px -12px rgb(0 0 0 / 0.18), 0 0 0 1px var(--ivica);
            text-align: center;
        }

        .znak {
            display: inline-block;
            padding: 0.375rem 0.875rem;
            border-radius: 0.5rem;
            background-color: var(--akcenat-blaga);
            color: var(--akcenat);
            font-size: 0.8125rem;
            font-weight: 700;
            letter-spacing: 0.12em;
        }

        .ikona {
            display: block;
            width: 3.25rem;
            height: 3.25rem;
            margin: 1.75rem auto 1.25rem;
            color: var(--akcenat);
        }

        h1 {
            margin: 0 0 0.75rem;
            font-size: 1.5rem;
            line-height: 1.3;
            font-weight: 700;
            color: var(--naslov);
        }

        p {
            margin: 0 auto;
            max-width: 24rem;
            font-size: 0.9375rem;
        }

        @media (max-width: 30rem) {
            .karta { padding: 2rem 1.5rem; }
            h1 { font-size: 1.3125rem; }
        }
    </style>
</head>
<body>
    <main class="karta">
        <span class="znak">КПРМ</span>

        <svg class="ikona" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="9"></circle>
            <path d="M12 7.5V12l3 2"></path>
        </svg>

        <h1>Систем је у режиму одржавања</h1>

        <p>Апликација је привремено недоступна.</p>
    </main>
</body>
</html>
