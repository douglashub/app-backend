<!-- resources/views/reports/monitores.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Relatório de Monitores</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        .date {
            font-size: 10px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            font-size: 11px;
        }
        th {
            background-color: #f2f2f2;
            text-align: left;
            font-weight: bold;
        }
        .filters {
            margin-bottom: 20px;
            font-size: 10px;
        }
        .page-break {
            page-break-after: always;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Relatório de Monitores</h1>
        <div class="date">Gerado em: {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}</div>
    </div>

    @if(!empty($filters))
    <div class="filters">
        <strong>Filtros aplicados:</strong><br>
        @if(!empty($filters['data_inicio']) && !empty($filters['data_fim']))
            Período: {{ \Carbon\Carbon::parse($filters['data_inicio'])->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($filters['data_fim'])->format('d/m/Y') }}<br>
        @endif
        @if(!empty($filters['cargo']))
            Cargo: {{ $filters['cargo'] }}<br>
        @endif
        @if(!empty($filters['status']))
            Status: {{ $filters['status'] }}<br>
        @endif
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>CPF</th>
                <th>Cargo</th>
                <th>Status</th>
                <th>Total Viagens</th>
                <th>Rotas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['data'] as $monitor)
            <tr>
                <td>{{ $monitor['nome'] }}</td>
                <td>{{ $monitor['cpf'] }}</td>
                <td>{{ $monitor['cargo'] }}</td>
                <td>{{ $monitor['status'] }}</td>
                <td>{{ $monitor['total_viagens'] }}</td>
                <td>{{ implode(', ', $monitor['rotas'] ?? []) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center;">Nenhum registro encontrado.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Relatório de Monitores - Total de Registros: {{ $report['total'] ?? 0 }}
    </div>
</body>
</html>