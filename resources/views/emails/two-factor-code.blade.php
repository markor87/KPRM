<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Верификациони код — КПРМ</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            padding: 40px 20px;
            color: #1f2937;
        }
        .wrapper {
            max-width: 560px;
            margin: 0 auto;
        }
        .header {
            background-color: #1e3a5f;
            border-radius: 10px 10px 0 0;
            padding: 32px 40px;
            text-align: center;
        }
        .header .logo {
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #93c5fd;
            margin-bottom: 10px;
        }
        .header h1 {
            color: #ffffff;
            font-size: 22px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        .body {
            background-color: #ffffff;
            padding: 40px;
        }
        .greeting {
            font-size: 16px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 14px;
        }
        .intro {
            font-size: 14.5px;
            color: #4b5563;
            line-height: 1.65;
        }
        .code-section {
            margin: 32px 0;
            text-align: center;
        }
        .code-label {
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #9ca3af;
            margin-bottom: 16px;
        }
        .code-box {
            display: inline-block;
            background-color: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px 40px;
            width: 100%;
        }
        .code {
            font-size: 46px;
            font-weight: 700;
            color: #1e3a5f;
            letter-spacing: 14px;
            font-family: 'Courier New', monospace;
        }
        .expiry {
            margin-top: 10px;
            font-size: 13px;
            color: #6b7280;
        }
        .expiry strong {
            color: #dc2626;
        }
        .divider {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 28px 0;
        }
        .warning {
            background-color: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 8px;
            padding: 16px 18px;
            font-size: 13.5px;
            color: #7c2d12;
            line-height: 1.6;
        }
        .warning .warning-icon {
            font-size: 15px;
            margin-right: 4px;
        }
        .security {
            margin-top: 20px;
            font-size: 13px;
            color: #6b7280;
            line-height: 1.6;
        }
        .footer {
            background-color: #f8fafc;
            border-top: 1px solid #e5e7eb;
            border-radius: 0 0 10px 10px;
            padding: 20px 40px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
            line-height: 1.8;
        }
        .footer a {
            color: #9ca3af;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div class="logo">КПРМ</div>
            <h1>Верификациони код</h1>
        </div>

        <div class="body">
            <p class="greeting">Поштовани/а {{ $userName }},</p>
            <p class="intro">Затражена је пријава на Ваш налог. Употребите код испод да бисте завршили верификацију.</p>

            <div class="code-section">
                <div class="code-label">Ваш код</div>
                <div class="code-box">
                    <div class="code">{{ $code }}</div>
                    <div class="expiry">Истиче за <strong>10 минута</strong></div>
                </div>
            </div>

            <hr class="divider">

            <div class="warning">
                <span class="warning-icon">⚠️</span>
                Уколико нисте покренули пријаву, занемарите овај имејл и контактирајте администратора система.
            </div>

            <p class="security">
                🔒 <strong>Никада не делите овај код</strong> са другим особама.
            </p>
        </div>

        <div class="footer">
            <p>Ово је аутоматска порука — молимо не одговарајте.</p>
            <p>&copy; {{ date('Y') }} КПРМ &mdash; Сва права задржана</p>
        </div>
    </div>
</body>
</html>
