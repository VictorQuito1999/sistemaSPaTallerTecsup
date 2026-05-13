<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ __('Activación — Pet Spa') }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f5fa;font-family:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4f5fa;padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="560" cellspacing="0" cellpadding="0" style="max-width:560px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(46,38,61,0.08);">
          <tr>
            <td style="background:linear-gradient(135deg,#8C57FF 0%,#16B1FF 100%);padding:28px 32px;">
              <p style="margin:0;color:#ffffff;font-size:22px;font-weight:600;letter-spacing:0.5px;">Pet Spa</p>
              <p style="margin:8px 0 0;color:rgba(255,255,255,0.9);font-size:14px;">Activación de cuenta</p>
            </td>
          </tr>
          <tr>
            <td style="padding:32px;">
              <p style="margin:0 0 16px;color:#2E263D;font-size:16px;line-height:1.6;">
                Hola {{ $employeeDisplayName }}, has sido registrado en Pet Spa. Haz clic aquí para activar tu cuenta y configurar tu contraseña.
              </p>
              <table role="presentation" cellspacing="0" cellpadding="0" style="margin:28px 0;">
                <tr>
                  <td style="border-radius:8px;background-color:#8C57FF;">
                    <a href="{{ $activationUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:14px 28px;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;">
                      Activar mi cuenta
                    </a>
                  </td>
                </tr>
              </table>
              <p style="margin:24px 0 0;color:#616161;font-size:12px;line-height:1.5;">
                Este enlace caduca en 15 minutos. Si no solicitaste este correo, puedes ignorarlo.
              </p>
              <p style="margin:16px 0 0;color:#9e9e9e;font-size:11px;word-break:break-all;">
                {{ $activationUrl }}
              </p>
            </td>
          </tr>
          <tr>
            <td style="padding:16px 32px 24px;background:#fafafa;border-top:1px solid #eee;">
              <p style="margin:0;color:#9e9e9e;font-size:11px;text-align:center;">{{ config('app.name') }}</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
