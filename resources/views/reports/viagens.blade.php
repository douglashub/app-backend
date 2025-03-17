<!-- resources/views/reports/viagens.blade.php -->
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Relatório de Viagens</title>
    <style>
        @page {
            margin: 20mm 15mm;
        }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
        }
        .container {
            width: 100%;
            margin: 0 auto;
            padding: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 16px;
            color: #333;
        }
        .header .subtitle {
            font-size: 10px;
            color: #666;
        }
        .filters {
            background-color: #f8f8f8;
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 9px;
        }
        .filters h3 {
            margin-top: 0;
            margin-bottom: 5px;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 6px;
            text-align: left;
            font-size: 9px;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .footer {
            width: 100%;
            position: fixed;
            bottom: 15mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #666;
        }
        .page-number:after {
            content: counter(page);
        }
        .summary {
            margin-top: 15px;
            font-size: 10px;
            text-align: right;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Relatório de Viagens</h1>
            <div class="subtitle">
                Gerado em: {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}
            </div>
        </div>

        @if(!empty($filters))
        <div class="filters">
            <h3>Filtros Aplicados</h3>
            @php
                $appliedFilters = [];
                if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
                    $appliedFilters[] = "Período: " . 
                        \Carbon\Carbon::parse($filters['data_inicio'])->format('d/m/Y') . 
                        " a " . 
                        \Carbon\Carbon::parse($filters['data_fim'])->format('d/m/Y');
                }
                if (!empty($filters['rota_id'])) $appliedFilters[] = "Rota ID: {$filters['rota_id']}";
                if (!empty($filters['motorista_id'])) $appliedFilters[] = "Motorista ID: {$filters['motorista_id']}";
                if (!empty($filters['monitor_id'])) $appliedFilters[] = "Monitor ID: {$filters['monitor_id']}";
                if (!empty($filters['onibus_id'])) $appliedFilters[] = "Ônibus ID: {$filters['onibus_id']}";
                if (isset($filters['status'])) $appliedFilters[] = "Status: " . ($filters['status'] ? 'Ativo' : 'Inativo');
                if (!empty($filters['cargo'])) $appliedFilters[] = "Cargo: {$filters['cargo']}";
            @endphp
            
            @if(!empty($appliedFilters))
                <ul>
                    @foreach($appliedFilters as $filter)
                        <li>{{ $filter }}</li>
                    @endforeach
                </ul>
            @else
                <p>Nenhum filtro aplicado</p>
            @endif
        </div>
        @endif

        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Rota</th>
                    <th>Motorista</th>
                    <th>Monitor</th>
                    <th>Ônibus</th>
                    <th>Saída Prev.</th>
                    <th>Chegada Prev.</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report['data'] as $viagem)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($viagem['data_viagem'])->format('d/m/Y') }}</td>
                    <td>{{ $viagem['rota']['nome'] ?? 'N/A' }}</td>
                    <td>{{ $viagem['motorista']['nome'] ?? 'N/A' }}</td>
                    <td>{{ $viagem['monitor']['nome'] ?? 'N/A' }}</td>
                    <td>{{ $viagem['onibus']['placa'] ?? 'N/A' }}</td>
                    <td>{{ $viagem['hora_saida_prevista'] ?? 'N/A' }}</td>
                    <td>{{ $viagem['hora_chegada_prevista'] ?? 'N/A' }}</td>
                    <td>{{ $viagem['status'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center;">Nenhum registro encontrado.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="summary">
            Total de Registros: {{ $report['total'] ?? 0 }}
        </div>

        <div class="footer">
            Relatório de Viagens | 
            Página <span class="page-number"></span>
        </div>
    </div>
</body>
</html>