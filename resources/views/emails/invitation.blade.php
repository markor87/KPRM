<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Позивница за регистрацију</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #d97706;
            margin: 0;
        }
        .content {
            margin-bottom: 30px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #d97706;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }
        .button:hover {
            background-color: #b45309;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
        .expiry {
            background-color: #fef3c7;
            padding: 10px;
            border-left: 4px solid #d97706;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>КПРМ</h1>
            <p>Конкурси и попуњеност радних места</p>
        </div>

        <div class="content">
            <h2>Позвани сте да се региструјете</h2>

            <p>Поштовани/а,</p>

            <p>Позвани сте да се региструјете у систем КПРМ (Конкурси и попуњеност радних места).</p>

            <p>Кликните на дугме испод да бисте завршили процес регистрације:</p>

            <div style="text-align: center;">
                <a href="{{ url('/register-invite/' . $invitation->token) }}" class="button">
                    Завршите регистрацију
                </a>
            </div>

            <p>Или копирајте и налепите следећи линк у ваш претраживач:</p>
            <p style="word-break: break-all; background-color: #f3f4f6; padding: 10px; border-radius: 3px;">
                {{ url('/register-invite/' . $invitation->token) }}
            </p>

            <div class="expiry">
                <strong>Важно:</strong> Ова позивница истиче {{ $invitation->expires_at->translatedFormat('d. F Y. у H:i') }}.
            </div>
        </div>

        <div class="footer">
            <p>Ако нисте очекивали ову е-пошту, молимо вас да је игноришете.</p>
            <p>Овај е-mail је аутоматски генерисан. Молимо вас да не одговарате на њега.</p>
        </div>
    </div>
</body>
</html>
