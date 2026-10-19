<!DOCTYPE html>
<html lang="en">
<body style="margin:0;padding:24px;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellspacing="0" cellpadding="0" style="max-width:480px;background:#ffffff;border-radius:12px;padding:32px;">
                    <tr>
                        <td style="font-size:20px;font-weight:bold;color:#172554;">SPCC AI Learning Assistant</td>
                    </tr>
                    <tr>
                        <td style="padding-top:20px;font-size:15px;line-height:1.5;">
                            Hi {{ $name }},<br><br>
                            Use this code to verify your SPCC email and finish creating your account:
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:24px 0;">
                            <span style="display:inline-block;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:14px 24px;font-size:32px;font-weight:bold;letter-spacing:8px;color:#1d4ed8;">{{ $code }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:13px;line-height:1.5;color:#64748b;">
                            This code expires in {{ $minutes }} minutes. If you did not try to register, you can ignore this email.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
