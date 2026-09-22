<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Noure' }}</title>
</head>

<body style="margin:0;background:#f8f5ef;color:#211c1a;font-family:Arial,Helvetica,sans-serif;line-height:1.6;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8f5ef;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                    style="max-width:620px;background:#fffdf9;">
                    <tr>
                        <td style="padding:34px 36px 24px;text-align:center;border-bottom:1px solid #e7dfd7;">
                            <div style="font-family:Georgia,'Times New Roman',serif;font-size:28px;letter-spacing:8px;">
                                NOURE</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:36px;">@yield('content')</td>
                    </tr>
                    <tr>
                        <td
                            style="padding:24px 36px 34px;border-top:1px solid #e7dfd7;color:#766d68;font-size:12px;text-align:center;">
                            Need help? Contact {{ config('mail.from.address') }}<br>
                            Noure, considered womenswear.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
