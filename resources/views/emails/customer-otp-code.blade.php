<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Codigo OTP</title>
</head>
<body style="margin:0;padding:24px;background:#f6f8fb;font-family:Arial,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center">
                <table role="presentation" width="560" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="padding:28px 28px 12px;">
                            <h1 style="margin:0;font-size:22px;line-height:1.3;">Verificacion de correo</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 28px 8px;">
                            <p style="margin:0 0 10px;">Hola {{ $customerName }},</p>
                            <p style="margin:0 0 10px;">Usa este codigo OTP de 6 digitos para completar el acceso a tu cuenta en Pet Spa:</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 28px 8px;">
                            <div style="font-size:30px;font-weight:700;letter-spacing:7px;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:10px;padding:14px 16px;text-align:center;">
                                {{ $otpCode }}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 28px 28px;">
                            <p style="margin:8px 0 0;color:#6b7280;">Este codigo expira en 10 minutos.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
