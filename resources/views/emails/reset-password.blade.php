<!DOCTYPE html>
<html lang="sr" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!--[if mso]>
    <noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
    <![endif]-->
    <title>Ресетовање лозинке — КПРМ</title>
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
                <h1 style="margin:0; font-size:22px; font-weight:600; color:#ffffff; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; mso-line-height-rule:exactly; line-height:30px;">Ресетовање лозинке</h1>
            </td>
        </tr>

        <!-- BODY -->
        <tr>
            <td bgcolor="#ffffff" style="padding:36px 40px 32px 40px; background-color:#ffffff;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">

                    <!-- Greeting -->
                    <tr>
                        <td style="padding-bottom:12px; font-size:16px; font-weight:600; color:#111827; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
                            Поштовани{{ $userName ? '/а ' . $userName : '' }},
                        </td>
                    </tr>

                    <!-- Intro -->
                    <tr>
                        <td style="padding-bottom:28px; font-size:14px; color:#4b5563; line-height:22px; mso-line-height-rule:exactly; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
                            Примили смо захтев за ресетовање лозинке за Ваш налог. Кликом на дугме испод можете поставити нову лозинку.
                        </td>
                    </tr>

                    <!-- Action button (Outlook-compatible) -->
                    <tr>
                        <td align="center" style="padding-bottom:28px;">
                            <!--[if mso]>
                            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ $url }}" style="height:46px;v-text-anchor:middle;width:240px;" arcsize="13%" stroke="f" fillcolor="#1e3a5f">
                            <w:anchorlock/>
                            <center style="color:#ffffff;font-family:'Segoe UI',sans-serif;font-size:15px;font-weight:600;">Ресетуј лозинку</center>
                            </v:roundrect>
                            <![endif]-->
                            <!--[if !mso]><!-- -->
                            <a href="{{ $url }}" style="display:inline-block; background-color:#1e3a5f; color:#ffffff; font-size:15px; font-weight:600; text-decoration:none; padding:13px 36px; border-radius:6px; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">Ресетуј лозинку</a>
                            <!--<![endif]-->
                        </td>
                    </tr>

                    <!-- Expiry note -->
                    <tr>
                        <td style="padding-bottom:8px; font-size:13px; color:#6b7280; line-height:20px; mso-line-height-rule:exactly; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
                            Овај линк истиче за <strong style="color:#dc2626;">{{ $count }} минута</strong>.
                        </td>
                    </tr>

                    <!-- Fallback URL -->
                    <tr>
                        <td style="padding-bottom:4px; font-size:12px; color:#9ca3af; line-height:18px; mso-line-height-rule:exactly; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
                            Уколико дугме не ради, копирајте и налепите следећи линк у Ваш веб прегледач:
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:12px; color:#1e3a5f; line-height:18px; word-break:break-all; font-family:'Courier New',monospace;">
                            {{ $url }}
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
                            ⚠️&nbsp; Уколико нисте затражили ресетовање лозинке, занемарите овај имејл — Ваша лозинка остаје непромењена.
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
