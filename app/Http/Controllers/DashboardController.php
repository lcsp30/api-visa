<?php

namespace App\Http\Controllers;

use App\Models\Controle_licencas;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Retorna métricas consolidadas do dashboard (total cadastrados, licenças ativas/vencidas e notificados).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $data = Carbon::now()->format('Y-m-d');
            $totalCadastrados = DB::table('estabelecimentos_cnpj')->count() + DB::table('estabelecimentos_cpf')->count();
            $licencasAtivas   = Controle_licencas::where('validade','>',$data)->count();
            $licencasVencidas = Controle_licencas::where('validade', '<', $data)->count();
            $notificados      = DB::table('estabelecimentos_notificados')->count();

            return response()->json([
                'totalCadastrados' => $totalCadastrados,
                'licencasAtivas'   => $licencasAtivas,
                'licencasVencidas' => $licencasVencidas,
                'notificados'      => $notificados,
            ]);
        } catch (\Throwable $e) {
            Log::error('DashboardController::index: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['Error' => 'Erro ao carregar métricas.'], 500);
        }
    }
}