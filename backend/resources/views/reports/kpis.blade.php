<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte KPIs</title>
</head>
<body>

    <h1>Reporte Mensajería</h1>

    <h2>Volumen</h2>

    <table border="1" cellpadding="5">

        <tr>
            <th>Canal</th>
            <th>Estado</th>
            <th>Total</th>
        </tr>

        @foreach($data['volumen'] as $item)

        <tr>
            <td>{{ $item['channel'] }}</td>
            <td>{{ $item['status'] }}</td>
            <td>{{ $item['total'] }}</td>
        </tr>

        @endforeach

    </table>

</body>
</html>