<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cita Confirmada</title>
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #f6f6f6; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .header { background-color: #8C57FF; padding: 30px; text-align: center; color: white; }
        .header h1 { margin: 0; font-size: 24px; font-weight: normal; }
        .content { padding: 30px; color: #333333; line-height: 1.6; }
        .details-box { background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; padding: 20px; margin: 20px 0; }
        .detail-row { display: flex; margin-bottom: 10px; }
        .detail-label { font-weight: bold; width: 120px; color: #555555; }
        .detail-value { flex: 1; color: #222222; }
        .footer { background-color: #f1f1f1; text-align: center; padding: 15px; font-size: 12px; color: #777777; }
        .total-price { font-size: 18px; font-weight: bold; color: #8C57FF; margin-top: 15px; border-top: 1px solid #e9ecef; padding-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>¡Cita Confirmada, {{ $appointment->pet->customer->first_name }}!</h1>
        </div>
        
        <div class="content">
            <p>Hola <strong>{{ $appointment->pet->customer->full_name }}</strong>,</p>
            <p>Nos complace informarte que tu solicitud de cita para <strong>{{ $appointment->pet->name }}</strong> ha sido aprobada con éxito. A continuación, te detallamos la información de tu reserva:</p>
            
            <div class="details-box">
                <div class="detail-row">
                    <div class="detail-label">Día:</div>
                    <div class="detail-value">{{ \Carbon\Carbon::parse($appointment->appointment_date)->translatedFormat('l j \d\e F, Y') }}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Hora:</div>
                    <div class="detail-value">{{ \Carbon\Carbon::parse($appointment->start_time)->format('H:i') }} hrs</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Servicio:</div>
                    <div class="detail-value">{{ $appointment->service->name }}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Groomer:</div>
                    <div class="detail-value">{{ $appointment->employee->user->name ?? 'Por asignar' }}</div>
                </div>
                <div class="total-price">
                    Costo Estimado: ${{ number_format($appointment->agreed_price, 2) }}
                </div>
            </div>

            <p>Por favor, intenta llegar 10 minutos antes de la hora acordada para que podamos recibir a {{ $appointment->pet->name }} de la mejor manera.</p>
            <p>Si tienes alguna consulta adicional o necesitas modificar tu cita, no dudes en contactarnos.</p>
            
            <p>¡Gracias por confiar en Pet Spa!</p>
        </div>
        
        <div class="footer">
            &copy; {{ date('Y') }} Pet Spa. Todos los derechos reservados.<br>
            Este es un correo automático, por favor no respondas a este mensaje.
        </div>
    </div>
</body>
</html>
