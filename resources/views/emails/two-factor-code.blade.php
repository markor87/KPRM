<!DOCTYPE html>
<html lang="sr" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!--[if mso]>
    <noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
    <![endif]-->
    <title>Верификациони код — КПРМ</title>
    <style>
        body, table, td { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; }
        body { margin: 0; padding: 0; background-color: #f0f2f5; }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#f0f2f5; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">

<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" bgcolor="#f0f2f5">
<tr><td align="center" style="padding:40px 20px;">

    <!-- Container 560px -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="560" style="max-width:560px; width:100%;">

        <!-- HEADER -->
        <tr>
            <td bgcolor="#1e3a5f" align="center" style="padding:28px 40px; background-color:#1e3a5f;">
                <p style="margin:0 0 8px 0; font-size:11px; font-weight:600; letter-spacing:3px; text-transform:uppercase; color:#93c5fd; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">КПРМ</p>
                <h1 style="margin:0; font-size:22px; font-weight:600; color:#ffffff; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; mso-line-height-rule:exactly; line-height:30px;">Верификациони код</h1>
            </td>
        </tr>

        <!-- BODY -->
        <tr>
            <td bgcolor="#ffffff" style="padding:36px 40px 32px 40px; background-color:#ffffff;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">

                    <!-- Greeting -->
                    <tr>
                        <td style="padding-bottom:12px; font-size:16px; font-weight:600; color:#111827; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
                            Поштовани/а {{ $userName }},
                        </td>
                    </tr>

                    <!-- Intro -->
                    <tr>
                        <td style="padding-bottom:28px; font-size:14px; color:#4b5563; line-height:22px; mso-line-height-rule:exactly; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
                            Затражена је пријава на Ваш налог. Употребите код испод да бисте завршили верификацију.
                        </td>
                    </tr>

                    <!-- Code label -->
                    <tr>
                        <td align="center" style="padding-bottom:12px; font-size:11px; font-weight:600; letter-spacing:2px; text-transform:uppercase; color:#9ca3af; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
                            ВАШ КОД
                        </td>
                    </tr>

                    <!-- Code box -->
                    <tr>
                        <td bgcolor="#f8fafc" align="center" style="background-color:#f8fafc; border:2px solid #dde3ea; padding:22px 40px;">
                            <p style="margin:0; font-size:46px; font-weight:700; color:#1e3a5f; letter-spacing:14px; font-family:'Courier New',monospace; mso-line-height-rule:exactly; line-height:56px;">{{ $code }}</p>
                            <p style="margin:10px 0 0 0; font-size:13px; color:#6b7280; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">Истиче за <strong style="color:#dc2626;">10 минута</strong></p>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding:26px 0 22px 0;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr><td style="border-top:1px solid #e5e7eb; font-size:0; line-height:0;">&nbsp;</td></tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Warning -->
                    <tr>
                        <td bgcolor="#fff7ed" style="background-color:#fff7ed; border-left:4px solid #f97316; padding:14px 16px; font-size:13px; color:#7c2d12; line-height:20px; mso-line-height-rule:exactly; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
                            ⚠️&nbsp; Уколико нисте покренули пријаву, занемарите овај имејл и контактирајте администратора система.
                        </td>
                    </tr>

                    <!-- Security note -->
                    <tr>
                        <td style="padding-top:18px; font-size:13px; color:#6b7280; line-height:20px; mso-line-height-rule:exactly; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
                            🔒&nbsp; <strong style="color:#374151;">Никада не делите овај код</strong> са другим особама.
                        </td>
                    </tr>

                </table>
            </td>
        </tr>

        <!-- FOOTER -->
        <tr>
            <td bgcolor="#f8fafc" align="center" style="background-color:#f8fafc; border-top:1px solid #e5e7eb; padding:18px 40px; font-size:12px; color:#9ca3af; line-height:20px; mso-line-height-rule:exactly; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
                <p style="margin:0;">Ово је аутоматска порука — молимо не одговарајте.</p>
                <p style="margin:4px 0 0 0;">&copy; {{ date('Y') }} КПРМ &mdash; Сва права задржана</p>
            </td>
        </tr>

    </table>

</td></tr>
</table>

</body>
</html>
