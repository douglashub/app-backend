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
use Barryvdh\DomPDF\Facade\Pdf;
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
            $filters = $request->validate([
                'data_inicio' => 'nullable|date',
                'data_fim' => 'nullable|date',
                'rota_id' => 'nullable|integer',
                'motorista_id' => 'nullable|integer',
                'monitor_id' => 'nullable|integer',
                'onibus_id' => 'nullable|integer',
                'status' => 'nullable',
                'cargo' => 'nullable|string|in:Efetivo,ACT,Temporário'
            ]);

            // Convert status to proper boolean if present
            if (isset($filters['status'])) {
                if (is_string($filters['status'])) {
                    $statusLower = strtolower($filters['status']);
                    $filters['status'] = in_array($statusLower, ['true', '1', 'on', 'yes', 'ativo']) ? true : false;
                } else {
                    $filters['status'] = (bool)$filters['status'];
                }
            }

            // Get the report data
            $reportData = $this->reportService->getViagemReport($filters);

            // Create spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Relatório de Viagens');

            // Header style
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

            // Set headers
            $headers = [
                'A' => 'Data',
                'B' => 'Rota',
                'C' => 'Motorista',
                'D' => 'Monitor',
                'E' => 'Ônibus',
                'F' => 'Saída Prevista',
                'G' => 'Chegada Prevista',
                'H' => 'Status'
            ];

            // Add headers and apply style
            foreach ($headers as $col => $header) {
                $sheet->setCellValue("{$col}1", $header);
            }
            $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

            // Add data
            $row = 2;
            foreach ($reportData['data'] as $viagem) {
                $sheet->setCellValue("A{$row}", Carbon::parse($viagem['data_viagem'])->format('d/m/Y'));
                $sheet->setCellValue("B{$row}", $viagem['rota']['nome'] ?? 'N/A');
                $sheet->setCellValue("C{$row}", $viagem['motorista']['nome'] ?? 'N/A');
                $sheet->setCellValue("D{$row}", $viagem['monitor']['nome'] ?? 'N/A');
                $sheet->setCellValue("E{$row}", $viagem['onibus']['placa'] ?? 'N/A');
                $sheet->setCellValue("F{$row}", $viagem['hora_saida_prevista'] ?? 'N/A');
                $sheet->setCellValue("G{$row}", $viagem['hora_chegada_prevista'] ?? 'N/A');
                $sheet->setCellValue("H{$row}", $viagem['status']);
                $row++;
            }

            // Auto-size columns
            foreach (range('A', 'H') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Add filters sheet
            $filtersSheet = $spreadsheet->createSheet();
            $filtersSheet->setTitle('Filtros Aplicados');
            $filtersSheet->setCellValue('A1', 'Filtro');
            $filtersSheet->setCellValue('B1', 'Valor');
            $filtersSheet->getStyle('A1:B1')->applyFromArray($headerStyle);

            $filtersRow = 2;
            foreach ($filters as $key => $value) {
                if ($value !== null && $value !== '') {
                    $displayValue = $value;

                    // Format specific filters
                    if ($key === 'data_inicio' || $key === 'data_fim') {
                        $displayValue = Carbon::parse($value)->format('d/m/Y');
                    }

                    if ($key === 'status') {
                        $displayValue = $value ? 'Ativo' : 'Inativo';
                    }

                    $filtersSheet->setCellValue("A{$filtersRow}", ucfirst(str_replace('_', ' ', $key)));
                    $filtersSheet->setCellValue("B{$filtersRow}", $displayValue);
                    $filtersRow++;
                }
            }

            // Auto-size columns for filters sheet
            $filtersSheet->getColumnDimension('A')->setAutoSize(true);
            $filtersSheet->getColumnDimension('B')->setAutoSize(true);

            // Generate filename
            $filename = 'relatorio_viagens_' . Carbon::now()->format('YmdHis') . '.xlsx';
            $filepath = storage_path('app/public/' . $filename);

            // Save the file
            $writer = new Xlsx($spreadsheet);
            $writer->save($filepath);

            $this->loggingService->logInfo('Relatório de viagens em Excel gerado com sucesso', [
                'filename' => $filename
            ]);

            // Download the file
            return response()->download($filepath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            $this->loggingService->logError('Erro ao gerar relatório de viagens em Excel', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'filters' => $filters ?? []
            ]);

            return response()->json([
                'message' => 'Erro ao gerar relatório de viagens em Excel',
                'error_details' => $e->getMessage(),
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


    /**
     * Gera relatório de motoristas em formato PDF
     */
    public function motoristaReportPdf(Request $request)
    {
        try {
            $this->loggingService->logInfo('Gerando relatório de motoristas em PDF');

            $filters = $request->validate([
                'data_inicio' => 'nullable|date',
                'data_fim' => 'nullable|date',
                'rota_id' => 'nullable|integer',
                'cargo' => 'nullable|string|in:Efetivo,ACT,Temporário',
                'status' => 'nullable|string'
            ]);

            $report = $this->reportService->getMotoristaReport($filters);

            $pdf = Pdf::loadView('reports.motoristas', [
                'report' => $report,
                'title' => 'Relatório de Motoristas',
                'filters' => $filters
            ]);

            $filename = 'relatorio_motoristas_' . Carbon::now()->format('YmdHis') . '.pdf';

            $this->loggingService->logInfo('Relatório de motoristas em PDF gerado com sucesso');

            return $pdf->download($filename);
        } catch (\Exception $e) {
            $this->loggingService->logError('Erro ao gerar relatório de motoristas em PDF: ' . $e->getMessage());

            return response()->json([
                'message' => 'Erro ao gerar relatório de motoristas em PDF',
                'error' => $e->getMessage(),
                '_links' => $this->hateoasService->generateCollectionLinks('relatorios/motoristas')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Gera relatório de monitores em formato PDF
     */
    public function monitorReportPdf(Request $request)
    {
        try {
            $this->loggingService->logInfo('Gerando relatório de monitores em PDF');

            $filters = $request->validate([
                'data_inicio' => 'nullable|date',
                'data_fim' => 'nullable|date',
                'rota_id' => 'nullable|integer',
                'cargo' => 'nullable|string|in:Efetivo,ACT,Temporário',
                'status' => 'nullable|string'
            ]);

            $report = $this->reportService->getMonitorReport($filters);

            $pdf = Pdf::loadView('reports.monitores', [
                'report' => $report,
                'title' => 'Relatório de Monitores',
                'filters' => $filters
            ]);

            $filename = 'relatorio_monitores_' . Carbon::now()->format('YmdHis') . '.pdf';

            $this->loggingService->logInfo('Relatório de monitores em PDF gerado com sucesso');

            return $pdf->download($filename);
        } catch (\Exception $e) {
            $this->loggingService->logError('Erro ao gerar relatório de monitores em PDF: ' . $e->getMessage());

            return response()->json([
                'message' => 'Erro ao gerar relatório de monitores em PDF',
                'error' => $e->getMessage(),
                '_links' => $this->hateoasService->generateCollectionLinks('relatorios/monitores')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Gera relatório de viagens em formato PDF
     */
    public function viagemReportPdf(Request $request)
    {
        try {
            $filters = $request->validate([
                'data_inicio' => 'nullable|date',
                'data_fim' => 'nullable|date',
                'rota_id' => 'nullable|integer',
                'motorista_id' => 'nullable|integer',
                'monitor_id' => 'nullable|integer',
                'onibus_id' => 'nullable|integer',
                'status' => 'nullable',
                'cargo' => 'nullable|string|in:Efetivo,ACT,Temporário'
            ]);

            // Convert status to proper boolean if present
            if (isset($filters['status'])) {
                if (is_string($filters['status'])) {
                    $statusLower = strtolower($filters['status']);
                    $filters['status'] = in_array($statusLower, ['true', '1', 'on', 'yes', 'ativo']) ? true : false;
                } else {
                    $filters['status'] = (bool)$filters['status'];
                }
            }

            // Ensure the report data is structured correctly
            $reportData = $this->reportService->getViagemReport($filters);

            // Prepare view data with consistent structure
            $viewData = [
                'report' => [
                    'data' => $reportData['data'],
                    'total' => $reportData['total']
                ],
                'filters' => $filters,
                'title' => 'Relatório de Viagens'
            ];

            $pdf = Pdf::loadView('reports.viagens', $viewData);

            $filename = 'relatorio_viagens_' . Carbon::now()->format('YmdHis') . '.pdf';

            $this->loggingService->logInfo('Relatório de viagens em PDF gerado com sucesso');

            return $pdf->download($filename);
        } catch (\Exception $e) {
            $this->loggingService->logError('Erro ao gerar relatório de viagens em PDF: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erro ao gerar relatório de viagens em PDF',
                'error_details' => $e->getMessage(),
                '_links' => $this->hateoasService->generateCollectionLinks('relatorios/viagens')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
