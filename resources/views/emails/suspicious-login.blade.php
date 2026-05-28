<!DOCTYPE html>
<html lang="sr" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!--[if mso]>
    <noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
    <![endif]-->
    <title>Пријава са стране локације — КПРМ</title>
    <style>
        body, table, td { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; }
        body { margin: 0; padding: 0; background-color: #f0f2f5; }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#f0f2f5; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">

<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" bgcolor="#f0f2f5">
<tr><td align="center" style="padding:40px 20px;">

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="560" style="max-width:560px; width:100%;">

        <!-- HEADER -->
        <tr>
            <td bgcolor="#7f1d1d" align="center" style="padding:28px 40px; background-color:#7f1d1d;">
                <p style="margin:0 0 8px 0; font-size:11px; font-weight:600; letter-spacing:3px; text-transform:uppercase; color:#fca5a5; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">КПРМ</p>
                <h1 style="margin:0; font-size:21px; font-weight:600; color:#ffffff; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; mso-line-height-rule:exactly; line-height:30px;">⚠️ Пријава са стране локације</h1>
            </td>
        </tr>

        <!-- BODY -->
        <tr>
            <td bgcolor="#ffffff" style="padding:36px 40px 32px 40px; background-color:#ffffff;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">

                    <tr>
                        <td style="padding-bottom:20px; font-size:14px; color:#374151; line-height:22px; mso-line-height-rule:exactly; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
                            Детектована је успешна пријава на КПРМ систем са IP адресе која се не налази у Србији.
                        </td>
                    </tr>

                    <!-- Details table -->
                    <tr>
                        <td style="padding-bottom:28px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border:1px solid #e5e7eb;">

                                <tr bgcolor="#f8fafc">
                                    <td style="padding:12px 16px; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#6b7280; background-color:#f8fafc; border-bottom:1px solid #e5e7eb; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;" colspan="2">
                                        Детаљи пријаве
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:11px 16px; font-size:13px; color:#6b7280; border-bottom:1px solid #f3f4f6; width:40%; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">Корисник</td>
                                    <td style="padding:11px 16px; font-size:13px; font-weight:600; color:#111827; border-bottom:1px solid #f3f4f6; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">{{ $user->name }}</td>
                                </tr>
                                <tr bgcolor="#fafafa">
                                    <td style="padding:11px 16px; font-size:13px; color:#6b7280; border-bottom:1px solid #f3f4f6; background-color:#fafafa; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">Имејл</td>
                                    <td style="padding:11px 16px; font-size:13px; color:#374151; border-bottom:1px solid #f3f4f6; background-color:#fafafa; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">{{ $user->email }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:11px 16px; font-size:13px; color:#6b7280; border-bottom:1px solid #f3f4f6; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">IP адреса</td>
                                    <td style="padding:11px 16px; font-size:13px; font-weight:600; color:#1e3a5f; border-bottom:1px solid #f3f4f6; font-family:'Courier New',monospace;">{{ $ip }}</td>
                                </tr>
                                <tr bgcolor="#fafafa">
                                    <td style="padding:11px 16px; font-size:13px; color:#6b7280; border-bottom:1px solid #f3f4f6; background-color:#fafafa; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">Земља</td>
                                    <td style="padding:11px 16px; font-size:13px; font-weight:600; color:#dc2626; border-bottom:1px solid #f3f4f6; background-color:#fafafa; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">{{ $country }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:11px 16px; font-size:13px; color:#6b7280; border-bottom:1px solid #f3f4f6; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">Град</td>
                                    <td style="padding:11px 16px; font-size:13px; color:#374151; border-bottom:1px solid #f3f4f6; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">{{ $city }}</td>
                                </tr>
                                <tr bgcolor="#fafafa">
                                    <td style="padding:11px 16px; font-size:13px; color:#6b7280; background-color:#fafafa; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">Датум и време</td>
                                    <td style="padding:11px 16px; font-size:13px; color:#374151; background-color:#fafafa; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">{{ now()->format('d.m.Y. H:i:s') }}</td>
                                </tr>

                            </table>
                        </td>
                    </tr>

                    <!-- Action note -->
                    <tr>
                        <td bgcolor="#fef2f2" style="background-color:#fef2f2; border-left:4px solid #ef4444; padding:14px 16px; font-size:13px; color:#7f1d1d; line-height:20px; mso-line-height-rule:exactly; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
                            Уколико овај корисник није очекиван на тој локацији, размотрите привремено онемогућавање налога и контактирање корисника.
                        </td>
                    </tr>

                </table>
            </td>
        </tr>

        <!-- FOOTER -->
        <tr>
            <td bgcolor="#f8fafc" align="center" style="background-color:#f8fafc; border-top:1px solid #e5e7eb; padding:18px 40px; font-size:12px; color:#9ca3af; line-height:20px; mso-line-height-rule:exactly; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
                <p style="margin:0;">Ово је аутоматска порука безбедносног система — молимо не одговарајте.</p>
                <p style="margin:4px 0 0 0;">&copy; {{ date('Y') }} КПРМ &mdash; Сва права задржана</p>
            </td>
        </tr>

    </table>

</td></tr>
</table>

</body>
</html>
