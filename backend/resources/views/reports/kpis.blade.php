<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte KPIs</title>
    <style>
        body { font-family: Arial, sans-serif; color: #222; margin: 24px; }
        h1 { font-size: 22px; margin-bottom: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 12px 14px; border: 1px solid #ddd; }
        th { background: #f4f4f5; text-align: left; }
    </style>
</head>
<body>
    <h1>Reporte de KPIs</h1>
    <table>
        <thead>
            <tr>
                <th>Métrica</th>
                <th>Valor</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total mensajes</td>
                <td>{{ $reportData['total_messages'] }}</td>
            </tr>
            <tr>
                <td>Mensajes pendientes</td>
                <td>{{ $reportData['pending_messages'] }}</td>
            </tr>
            <tr>
                <td>Plantillas activas</td>
                <td>{{ $reportData['active_templates'] }}</td>
            </tr>
            <tr>
                <td>Última actualización</td>
                <td>{{ $reportData['updated_at'] }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Conteo por estado</h2>
    <table>
        <thead>
            <tr>
                <th>Estado</th>
                <th>Cantidad</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reportData['status_counts'] as $status => $count)
                <tr>
                    <td>{{ $status }}</td>
                    <td>{{ $count }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Mensajes pendientes</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Canal</th>
                <th>Estado</th>
                <th>Destinatario</th>
                <th>Creado</th>
                <th>Programado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reportData['pending_list'] as $message)
                <tr>
                    <td>{{ $message->id }}</td>
                    <td>{{ $message->channel }}</td>
                    <td>{{ $message->status }}</td>
                    <td>{{ $message->recipient_masked }}</td>
                    <td>{{ $message->created_at }}</td>
                    <td>{{ $message->scheduled_at ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
>>>>>>> dev-frontend
