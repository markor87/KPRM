<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Код за двофакторску аутентификацију</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #d97706;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #d97706;
            margin: 0;
            font-size: 28px;
        }
        .header p {
            color: #666;
            margin: 10px 0 0 0;
            font-size: 14px;
        }
        .code-box {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 3px solid #d97706;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
            box-shadow: 0 2px 8px rgba(217, 119, 6, 0.2);
        }
        .code {
            font-size: 42px;
            font-weight: bold;
            color: #92400e;
            letter-spacing: 12px;
            font-family: 'Courier New', monospace;
        }
        .content {
            margin: 20px 0;
            color: #374151;
            font-size: 15px;
        }
        .content p {
            margin: 15px 0;
        }
        .greeting {
            font-weight: 600;
            color: #1f2937;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            font-size: 13px;
            color: #6b7280;
            text-align: center;
        }
        .footer p {
            margin: 8px 0;
        }
        .warning {
            background-color: #fef3c7;
            border-left: 5px solid #f59e0b;
            padding: 20px;
            margin: 25px 0;
            border-radius: 5px;
        }
        .warning-title {
            color: #92400e;
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 10px;
        }
        .security-note {
            background-color: #f3f4f6;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            font-size: 14px;
            color: #374151;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Двофакторска аутентификација</h1>
            <p>КПРМ</p>
        </div>

        <div class="content">
            <p class="greeting">Поштовани {{ $userName }},</p>

            <p>Примили сте овај имејл јер је затражена пријава на Ваш налог у КПРМ систему. Да бисте наставили са процесом пријављивања, молимо Вас да користите следећи верификациони код:</p>
        </div>

        <div class="code-box">
            <div class="code">{{ $code }}</div>
        </div>

        <div class="warning">
            <div class="warning-title">⚠️ Важно упозорење:</div>
            <p style="margin: 5px 0;">Овај код ће истећи за <strong>10 минута</strong>.</p>
            <p style="margin: 5px 0;">Уколико нисте покренули процес пријаве, контактирајте Службу за управљање кадровима.</p>
        </div>

        <div class="security-note">
            <strong>Напомена о безбедности:</strong> Никада ни са ким немојте делити овај верификациони код.
        </div>

        <div class="footer">
            <p>Ово је аутоматска порука. Молимо Вас да не одговарате на овај имејл.</p>
            <p style="margin-top: 15px;">&copy; {{ date('Y') }} КПРМ. Сва права задржана.</p>
        </div>
    </div>
</body>
</html>
