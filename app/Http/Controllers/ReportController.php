<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use App\Services\HateoasService;
use App\Services\LoggingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use Carbon\Carbon;

class ReportController extends Controller
{
    protected $reportService;
    protected $hateoasService;
    protected $loggingService;

    public function __construct(
        ReportService $reportService,
        HateoasService $hateoasService,
        LoggingService $loggingService
    ) {
        $this->reportService = $reportService;
        $this->hateoasService = $hateoasService;
        $this->loggingService = $loggingService;
    }

    /**
     * Gera relatório de motoristas em formato JSON
     */
    public function motoristaReport(Request $request): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Gerando relatório de motoristas');

            $filters = $request->validate([
                'data_inicio' => 'nullable|date',
                'data_fim' => 'nullable|date',
                'rota_id' => 'nullable|integer',
                'cargo' => 'nullable|string|in:Efetivo,ACT,Temporário',
                'status' => 'nullable|string'
            ]);

            $report = $this->reportService->getMotoristaReport($filters);

            $this->loggingService->logInfo('Relatório de motoristas gerado com sucesso', ['total' => $report['total']]);

            return response()->json([
                'data' => $report,
                '_links' => $this->hateoasService->generateCollectionLinks('relatorios/motoristas')
            ]);
        } catch (\Exception $e) {
            $this->loggingService->logError('Erro ao gerar relatório de motoristas: ' . $e->getMessage());

            return response()->json([
                'message' => 'Erro ao gerar relatório de motoristas',
                'error' => $e->getMessage(),
                '_links' => $this->hateoasService->generateCollectionLinks('relatorios/motoristas')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Gera relatório de motoristas em formato Excel
     */
    public function motoristaReportExcel(Request $request)
    {
        try {
            $this->loggingService->logInfo('Gerando relatório de motoristas em Excel');

            $filters = $request->validate([
                'data_inicio' => 'nullable|date',
                'data_fim' => 'nullable|date',
                'rota_id' => 'nullable|integer',
                'cargo' => 'nullable|string|in:Efetivo,ACT,Temporário',
                'status' => 'nullable|string'
            ]);

            $report = $this->reportService->getMotoristaReport($filters);

            // Criar planilha
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Relatório de Motoristas');

            // Estilo para o cabeçalho
            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ];

            // Definir cabeçalhos
            $sheet->setCellValue('A1', 'Nome');
            $sheet->setCellValue('B1', 'CPF');
            $sheet->setCellValue('C1', 'Cargo');
            $sheet->setCellValue('D1', 'Status');
            $sheet->setCellValue('E1', 'Total Viagens');
            $sheet->setCellValue('F1', 'Rotas');
            $sheet->setCellValue('G1', 'Horários');

            // Aplicar estilo ao cabeçalho
            $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

            // Preencher dados
            $row = 2;
            foreach ($report['data'] as $motorista) {
                $sheet->setCellValue('A' . $row, $motorista['nome']);
                $sheet->setCellValue('B' . $row, $motorista['cpf']);
                $sheet->setCellValue('C' . $row, $motorista['cargo']);
                $sheet->setCellValue('D' . $row, $motorista['status']);
                $sheet->setCellValue('E' . $row, $motorista['total_viagens']);
                $sheet->setCellValue('F' . $row, implode(', ', $motorista['rotas']));
                $sheet->setCellValue('G' . $row, implode(', ', $motorista['horarios']));
                $row++;
            }

            // Auto-dimensionar colunas
            foreach (range('A', 'G') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Adicionar informações de filtro
            $row += 2;
            $sheet->setCellValue('A' . $row, 'Filtros Aplicados:');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;

            if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
                $sheet->setCellValue('A' . $row, 'Período:');
                $sheet->setCellValue('B' . $row, Carbon::parse($filters['data_inicio'])->format('d/m/Y') . ' a ' . Carbon::parse($filters['data_fim'])->format('d/m/Y'));
                $row++;
            }

            if (!empty($filters['cargo'])) {
                $sheet->setCellValue('A' . $row, 'Cargo:');
                $sheet->setCellValue('B' . $row, $filters['cargo']);
                $row++;
            }

            if (!empty($filters['status'])) {
                $sheet->setCellValue('A' . $row, 'Status:');
                $sheet->setCellValue('B' . $row, $filters['status']);
                $row++;
            }

            $sheet->setCellValue('A' . $row, 'Data de Geração:');
            $sheet->setCellValue('B' . $row, Carbon::now()->format('d/m/Y H:i:s'));

            // Criar o arquivo Excel
            $writer = new Xlsx($spreadsheet);
            $filename = 'relatorio_motoristas_' . Carbon::now()->format('YmdHis') . '.xlsx';
            $path = storage_path('app/public/' . $filename);
            $writer->save($path);

            $this->loggingService->logInfo('Relatório de motoristas em Excel gerado com sucesso', ['filename' => $filename]);

            return response()->download($path, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            $this->loggingService->logError('Erro ao gerar relatório de motoristas em Excel: ' . $e->getMessage());

            return response()->json([
                'message' => 'Erro ao gerar relatório de motoristas em Excel',
                'error' => $e->getMessage(),
                '_links' => $this->hateoasService->generateCollectionLinks('relatorios/motoristas')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Gera relatório de monitores em formato JSON
     */
    public function monitorReport(Request $request): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Gerando relatório de monitores');

            $filters = $request->validate([
                'data_inicio' => 'nullable|date',
                'data_fim' => 'nullable|date',
                'rota_id' => 'nullable|integer',
                'cargo' => 'nullable|string|in:Efetivo,ACT,Temporário',
                'status' => 'nullable|string'
            ]);

            $report = $this->reportService->getMonitorReport($filters);

            $this->loggingService->logInfo('Relatório de monitores gerado com sucesso', ['total' => $report['total']]);

            return response()->json([
                'data' => $report,
                '_links' => $this->hateoasService->generateCollectionLinks('relatorios/monitores')
            ]);
        } catch (\Exception $e) {
            $this->loggingService->logError('Erro ao gerar relatório de monitores: ' . $e->getMessage());

            return response()->json([
                'message' => 'Erro ao gerar relatório de monitores',
                'error' => $e->getMessage(),
                '_links' => $this->hateoasService->generateCollectionLinks('relatorios/monitores')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Gera relatório de monitores em formato Excel
     */
    public function monitorReportExcel(Request $request)
    {
        try {
            $this->loggingService->logInfo('Gerando relatório de monitores em Excel');

            $filters = $request->validate([
                'data_inicio' => 'nullable|date',
                'data_fim' => 'nullable|date',
                'rota_id' => 'nullable|integer',
                'cargo' => 'nullable|string|in:Efetivo,ACT,Temporário',
                'status' => 'nullable|string'
            ]);

            $report = $this->reportService->getMonitorReport($filters);

            // Criar planilha
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Relatório de Monitores');

            // Estilo para o cabeçalho
            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ];

            // Definir cabeçalhos
            $sheet->setCellValue('A1', 'Nome');
            $sheet->setCellValue('B1', 'CPF');
            $sheet->setCellValue('C1', 'Cargo');
            $sheet->setCellValue('D1', 'Status');
            $sheet->setCellValue('E1', 'Total Viagens');
            $sheet->setCellValue('F1', 'Rotas');
            $sheet->setCellValue('G1', 'Horários');

            // Aplicar estilo ao cabeçalho
            $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

            // Preencher dados
            $row = 2;
            foreach ($report['data'] as $monitor) {
                $sheet->setCellValue('A' . $row, $monitor['nome']);
                $sheet->setCellValue('B' . $row, $monitor['cpf']);
                $sheet->setCellValue('C' . $row, $monitor['cargo']);
                $sheet->setCellValue('D' . $row, $monitor['status']);
                $sheet->setCellValue('E' . $row, $monitor['total_viagens']);
                $sheet->setCellValue('F' . $row, implode(', ', $monitor['rotas']));
                $sheet->setCellValue('G' . $row, implode(', ', $monitor['horarios']));
                $row++;
            }

            // Auto-dimensionar colunas
            foreach (range('A', 'G') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Adicionar informações de filtro
            $row += 2;
            $sheet->setCellValue('A' . $row, 'Filtros Aplicados:');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;

            if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
                $sheet->setCellValue('A' . $row, 'Período:');
                // Continuação do método monitorReportExcel
                $sheet->setCellValue('B' . $row, Carbon::parse($filters['data_inicio'])->format('d/m/Y') . ' a ' . Carbon::parse($filters['data_fim'])->format('d/m/Y'));
                $row++;
            }

            if (!empty($filters['cargo'])) {
                $sheet->setCellValue('A' . $row, 'Cargo:');
                $sheet->setCellValue('B' . $row, $filters['cargo']);
                $row++;
            }

            if (!empty($filters['status'])) {
                $sheet->setCellValue('A' . $row, 'Status:');
                $sheet->setCellValue('B' . $row, $filters['status']);
                $row++;
            }

            $sheet->setCellValue('A' . $row, 'Data de Geração:');
            $sheet->setCellValue('B' . $row, Carbon::now()->format('d/m/Y H:i:s'));

            // Criar o arquivo Excel
            $writer = new Xlsx($spreadsheet);
            $filename = 'relatorio_monitores_' . Carbon::now()->format('YmdHis') . '.xlsx';
            $path = storage_path('app/public/' . $filename);
            $writer->save($path);

            $this->loggingService->logInfo('Relatório de monitores em Excel gerado com sucesso', ['filename' => $filename]);

            return response()->download($path, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            $this->loggingService->logError('Erro ao gerar relatório de monitores em Excel: ' . $e->getMessage());

            return response()->json([
                'message' => 'Erro ao gerar relatório de monitores em Excel',
                'error' => $e->getMessage(),
                '_links' => $this->hateoasService->generateCollectionLinks('relatorios/monitores')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Gera relatório de viagens em formato JSON
     */
    public function viagemReport(Request $request): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Gerando relatório de viagens');

            $filters = $request->validate([
                'data_inicio' => 'nullable|date',
                'data_fim' => 'nullable|date',
                'rota_id' => 'nullable|integer',
                'motorista_id' => 'nullable|integer',
                'monitor_id' => 'nullable|integer',
                'onibus_id' => 'nullable|integer',
                'status' => 'nullable|boolean'
            ]);

            $report = $this->reportService->getViagemReport($filters);

            $this->loggingService->logInfo('Relatório de viagens gerado com sucesso', ['total' => $report['total']]);

            return response()->json([
                'data' => $report,
                '_links' => $this->hateoasService->generateCollectionLinks('relatorios/viagens')
            ]);
        } catch (\Exception $e) {
            $this->loggingService->logError('Erro ao gerar relatório de viagens: ' . $e->getMessage());

            return response()->json([
                'message' => 'Erro ao gerar relatório de viagens',
                'error' => $e->getMessage(),
                '_links' => $this->hateoasService->generateCollectionLinks('relatorios/viagens')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Gera relatório de viagens em formato Excel
     */
    public function viagemReportExcel(Request $request)
    {
        try {
            $this->loggingService->logInfo('Gerando relatório de viagens em Excel');

            $filters = $request->validate([
                'data_inicio' => 'nullable|date',
                'data_fim' => 'nullable|date',
                'rota_id' => 'nullable|integer',
                'motorista_id' => 'nullable|integer',
                'monitor_id' => 'nullable|integer',
                'onibus_id' => 'nullable|integer',
                'status' => 'nullable|boolean'
            ]);

            $report = $this->reportService->getViagemReport($filters);

            // Criar planilha
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Relatório de Viagens');

            // Estilo para o cabeçalho
            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ];

            // Definir cabeçalhos
            $sheet->setCellValue('A1', 'Data');
            $sheet->setCellValue('B1', 'Rota');
            $sheet->setCellValue('C1', 'Horário');
            $sheet->setCellValue('D1', 'Motorista');
            $sheet->setCellValue('E1', 'Cargo Motorista');
            $sheet->setCellValue('F1', 'Monitor');
            $sheet->setCellValue('G1', 'Cargo Monitor');
            $sheet->setCellValue('H1', 'Ônibus');
            $sheet->setCellValue('I1', 'Saída Prevista');
            $sheet->setCellValue('J1', 'Chegada Prevista');
            $sheet->setCellValue('K1', 'Saída Real');
            $sheet->setCellValue('L1', 'Chegada Real');
            $sheet->setCellValue('M1', 'Status');

            // Aplicar estilo ao cabeçalho
            $sheet->getStyle('A1:M1')->applyFromArray($headerStyle);

            // Preencher dados
            $row = 2;
            foreach ($report['data'] as $viagem) {
                $sheet->setCellValue('A' . $row, $viagem['data']);
                $sheet->setCellValue('B' . $row, $viagem['rota']);
                $sheet->setCellValue('C' . $row, $viagem['horario']);
                $sheet->setCellValue('D' . $row, $viagem['motorista']['nome']);
                $sheet->setCellValue('E' . $row, $viagem['motorista']['cargo']);
                $sheet->setCellValue('F' . $row, $viagem['monitor']['nome']);
                $sheet->setCellValue('G' . $row, $viagem['monitor']['cargo']);
                $sheet->setCellValue('H' . $row, $viagem['onibus']);
                $sheet->setCellValue('I' . $row, $viagem['hora_saida_prevista']);
                $sheet->setCellValue('J' . $row, $viagem['hora_chegada_prevista']);
                $sheet->setCellValue('K' . $row, $viagem['hora_saida_real']);
                $sheet->setCellValue('L' . $row, $viagem['hora_chegada_real']);
                $sheet->setCellValue('M' . $row, $viagem['status']);
                $row++;
            }

            // Auto-dimensionar colunas
            foreach (range('A', 'M') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Adicionar informações de filtro
            $row += 2;
            $sheet->setCellValue('A' . $row, 'Filtros Aplicados:');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;

            if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
                $sheet->setCellValue('A' . $row, 'Período:');
                $sheet->setCellValue('B' . $row, Carbon::parse($filters['data_inicio'])->format('d/m/Y') . ' a ' . Carbon::parse($filters['data_fim'])->format('d/m/Y'));
                $row++;
            }

            $sheet->setCellValue('A' . $row, 'Data de Geração:');
            $sheet->setCellValue('B' . $row, Carbon::now()->format('d/m/Y H:i:s'));

            // Criar o arquivo Excel
            $writer = new Xlsx($spreadsheet);
            $filename = 'relatorio_viagens_' . Carbon::now()->format('YmdHis') . '.xlsx';
            $path = storage_path('app/public/' . $filename);
            $writer->save($path);

            $this->loggingService->logInfo('Relatório de viagens em Excel gerado com sucesso', ['filename' => $filename]);

            return response()->download($path, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            $this->loggingService->logError('Erro ao gerar relatório de viagens em Excel: ' . $e->getMessage());

            return response()->json([
                'message' => 'Erro ao gerar relatório de viagens em Excel',
                'error' => $e->getMessage(),
                '_links' => $this->hateoasService->generateCollectionLinks('relatorios/viagens')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obter dados para configuração de relatórios
     */
    public function getReportOptions(): JsonResponse
    {
        try {
            $this->loggingService->logInfo('Obtendo opções para configuração de relatórios');

            $rotas = \App\Models\Rota::select('id', 'nome')->where('status', true)->get();
            $motoristas = \App\Models\Motorista::select('id', 'nome', 'cargo')->get();
            $monitores = \App\Models\Monitor::select('id', 'nome', 'cargo')->get();
            $onibus = \App\Models\Onibus::select('id', 'placa', 'modelo')->get();
            $horarios = \App\Models\Horario::select('id', 'hora_inicio', 'hora_fim')->get();

            $cargos = ['Efetivo', 'ACT', 'Temporário'];
            $status = ['Ativo', 'Inativo', 'Ferias', 'Licenca'];

            $response = [
                'rotas' => $rotas,
                'motoristas' => $motoristas,
                'monitores' => $monitores,
                'onibus' => $onibus,
                'horarios' => $horarios,
                'cargos' => $cargos,
                'status' => $status
            ];

            return response()->json([
                'data' => $response,
                '_links' => $this->hateoasService->generateCollectionLinks('relatorios/opcoes')
            ]);
        } catch (\Exception $e) {
            $this->loggingService->logError('Erro ao obter opções para relatórios: ' . $e->getMessage());

            return response()->json([
                'message' => 'Erro ao obter opções para relatórios',
                'error' => $e->getMessage(),
                '_links' => $this->hateoasService->generateCollectionLinks('relatorios')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
