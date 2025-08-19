<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\InegiState;
use App\Services\InegiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;


class StateApiController extends Controller
{

    /**
     * @OA\Get(
     *   path="/api/states",
     *   tags={"Estados"},
     *   summary="Listar estados (compatible con DataTables)",
     *   description="Retorna un listado paginado de estados. Acepta paginación por **bloques permitidos** (10,25,50,100) y parámetros de DataTables (`draw`, `start`, `length`, `search[value]`).",
     *   @OA\Parameter(name="q", in="query", description="Búsqueda por nombre/abreviatura/clave", @OA\Schema(type="string")),
     *   @OA\Parameter(name="page", in="query", description="Página (modo API simple)", @OA\Schema(type="integer", minimum=1)),
     *   @OA\Parameter(name="bloque", in="query", description="Tamaño de página (10,25,50,100). Gana prioridad sobre size/length", @OA\Schema(type="integer", enum={10,25,50,100})),
     *   @OA\Parameter(name="size", in="query", description="Tamaño de página (si no se usa 'bloque')", @OA\Schema(type="integer", enum={10,25,50,100})),
     *   @OA\Parameter(name="draw", in="query", description="Flag DataTables", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="start", in="query", description="Offset DataTables", @OA\Schema(type="integer", minimum=0)),
     *   @OA\Parameter(name="length", in="query", description="Tamaño DataTables (10,25,50,100)", @OA\Schema(type="integer", enum={10,25,50,100})),
     *   @OA\Parameter(name="search[value]", in="query", description="Texto de búsqueda DataTables", @OA\Schema(type="string")),
     *   @OA\Response(
     *     response=200,
     *     description="Listado de estados",
     *     @OA\JsonContent(
     *       oneOf={
     *         @OA\Schema(ref="#/components/schemas/RespuestaListado"),
     *         @OA\Schema(ref="#/components/schemas/RespuestaDataTables")
     *       }
     *     )
     *   )
     * )
     */
    public function index(Request $solicitud)
    {
        $terminoBusqueda = trim((string)($solicitud->query('q', $solicitud->input('search.value', ''))));

        $bloquesPermitidos = [10, 25, 50, 100];
        $bloqueSolicitado = $solicitud->query('bloque', 0);
        $tamanoSolicitado = $solicitud->query('size', $solicitud->input('length', 10));

        if (in_array($bloqueSolicitado, $bloquesPermitidos)) {
            $tamanoPagina = $bloqueSolicitado;
        } elseif (in_array($tamanoSolicitado, $bloquesPermitidos)) {
            $tamanoPagina = $tamanoSolicitado;
        } else {
            $tamanoPagina = 10;
        }

        $paginaActual = $solicitud->query('page', 1);
        $inicio = $solicitud->input('start', 0);
        $esDataTables = $solicitud->has('draw');
        if ($esDataTables) {
            $paginaActual = intdiv($inicio, max($tamanoPagina, 1)) + 1;
        }

        $constructor = InegiState::query();

        if ($terminoBusqueda !== '') {
            $constructor->where(function ($subconsulta) use ($terminoBusqueda) {
                $subconsulta->where('nomgeo', 'like', "%{$terminoBusqueda}%")
                            ->orWhere('nom_abrev', 'like', "%{$terminoBusqueda}%")
                            ->orWhere('cve_ent', 'like', "%{$terminoBusqueda}%");
            });
        }

        $paginacion = $constructor->orderBy('cve_ent')->paginate($tamanoPagina, ['*'], 'page', $paginaActual);

        if ($esDataTables) {
            $filas = $paginacion->map(function ($estado) {
                return [
                    'cve_ent' => $estado->cve_ent,
                    'nomgeo' => $estado->nomgeo,
                    'nom_abrev' => $estado->nom_abrev,
                    'pob_total' => $estado->pob_total,
                    'acciones' => route('api.states.show', $estado->cve_ent),
                ];
            })->all();

            return response()->json([
                'draw' => $solicitud->input('draw', 1),
                'recordsTotal' => InegiState::count(),
                'recordsFiltered' => $paginacion->total(),
                'data' => $filas,
                'meta' => [
                    'page' => $paginacion->currentPage(),
                    'bloque_aplicado' => $tamanoPagina,
                    'bloques_permitidos' => $bloquesPermitidos,
                    'total' => $paginacion->total(),
                    'pages' => $paginacion->lastPage(),
                ],
            ]);
        }

        $datos = $paginacion->map(function ($estado) {
            return [
                'cvegeo' => $estado->cvegeo,
                'cve_ent' => $estado->cve_ent,
                'nomgeo' => $estado->nomgeo,
                'nom_abrev' => $estado->nom_abrev,
                'pob_total' => $estado->pob_total,
            ];
        })->all();

        return response()->json([
            'data' => $datos,
            'meta' => [
                'page' => $paginacion->currentPage(),
                'bloque_aplicado' => $tamanoPagina,
                'bloques_permitidos' => $bloquesPermitidos,
                'total' => $paginacion->total(),
                'pages' => $paginacion->lastPage(),
            ],
        ]);
    }
/**
     * @OA\Get(
     *   path="/api/states/{cve_ent}",
     *   tags={"Estados"},
     *   summary="Detalle de un estado (5 campos)",
     *   @OA\Parameter(
     *     name="cve_ent", in="path", required=true,
     *     description="Clave de entidad (01..32)",
     *     @OA\Schema(type="string", pattern="^[0-9]{2}$", example="19")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Detalle del estado",
     *     @OA\JsonContent(ref="#/components/schemas/EstadoDetalle")
     *   ),
     *   @OA\Response(response=404, description="No encontrado")
     * )
     */

    public function show(string $cve_ent)
    {
        $claveEntidad = str_pad($cve_ent, 2, '0', STR_PAD_LEFT);
        $estado = InegiState::where('cve_ent', $claveEntidad)->firstOrFail();

        return response()->json([
            'cvegeo' => $estado->cvegeo,
            'cve_ent' => $estado->cve_ent,
            'nomgeo' => $estado->nomgeo,
            'nom_abrev' => $estado->nom_abrev,
            'pob_total' => $estado->pob_total,
        ]);
    }
/**
     * @OA\Post(
     *   path="/api/states/sync",
     *   tags={"Estados"},
     *   summary="Sincronizar estados desde INEGI",
     *   description="Descarga y sincroniza los estados (upsert). Si `resynchronize=1` y el INEGI no responde, usa el último snapshot bueno del cache (stale-on-error).",
     *   @OA\Parameter(
     *     name="resynchronize", in="query", required=false,
     *     description="Usar snapshot si INEGI cae (1=true, 0=false)",
     *     @OA\Schema(type="boolean")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Métricas de sincronización",
     *     @OA\JsonContent(ref="#/components/schemas/SyncRespuesta")
     *   ),
     *   @OA\Response(
     *     response=503,
     *     description="INEGI no disponible y no hay snapshot"
     *   )
     * )
     */
    public function sync(Request $solicitud)
    {
        $resincronizar = $solicitud->boolean('resynchronize', false);
        $clienteInegi = new InegiClient();

        $filasNormalizadas = $clienteInegi->fetchAllStates($resincronizar);

        $filasNormalizadas = array_map(function ($fila) {
            if (isset($fila['raw']) && is_array($fila['raw'])) {
                $fila['raw'] = json_encode($fila['raw'], JSON_UNESCAPED_UNICODE);
            }
            return $fila;
        }, $filasNormalizadas);

        $clavesEntrantes = array_column($filasNormalizadas, 'cve_ent');
        $existentesPorClave = InegiState::whereIn('cve_ent', $clavesEntrantes)->get()->keyBy('cve_ent');

        $insertados = 0; $actualizados = 0; $sinCambios = 0;

        foreach ($filasNormalizadas as $fila) {
            $clave = $fila['cve_ent'];
            $existente = $existentesPorClave->get($clave);

            if (!$existente) { $insertados++; continue; }

            $cambio = (
                $existente->cvegeo != ($fila['cvegeo'] ?? null) ||
                $existente->nomgeo != ($fila['nomgeo'] ?? null) ||
                $existente->nom_abrev != ($fila['nom_abrev'] ?? null) ||
                $existente->pob_total != ($fila['pob_total'] ?? null) ||
                $existente->pob_femenina != ($fila['pob_femenina'] ?? null) ||
                $existente->pob_masculina != ($fila['pob_masculina'] ?? null) ||
                $existente->total_viviendas_habitadas != ($fila['total_viviendas_habitadas'] ?? null)
            );

            if ($cambio) { $actualizados++; } else { $sinCambios++; }
        }

        DB::transaction(function () use ($filasNormalizadas) {
            InegiState::upsert(
                $filasNormalizadas,
                uniqueBy: ['cve_ent'],
                update: [
                    'cvegeo','nomgeo','nom_abrev',
                    'pob_total','pob_femenina','pob_masculina',
                    'total_viviendas_habitadas','raw','updated_at'
                ]
            );
        });

        return response()->json([
            'insertados' => $insertados,
            'actualizados' => $actualizados,
            'sin_cambios' => $sinCambios,
            'total_recibidos' => count($filasNormalizadas),
            'resynchronize_param' => $resincronizar,
        ], 200);
    }
/**
     * @OA\Post(
     *   path="/api/states/deduplicate",
     *   tags={"Estados"},
     *   summary="Analizar y eliminar duplicados por cve_ent",
     *   description="Elimina duplicados dejando 1 registro por cve_ent (el de menor id).",
     *   @OA\Response(
     *     response=200,
     *     description="Resultado de la deduplicación",
     *     @OA\JsonContent(ref="#/components/schemas/DeduplicateRespuesta")
     *   )
     * )
     */

    public function deduplicate()
    {
        $duplicados = DB::select("
            SELECT cve_ent, COUNT(*) AS conteo
            FROM inegi_states
            GROUP BY cve_ent
            HAVING conteo > 1
        ");

        $filasDuplicadas = 0;
        foreach ($duplicados as $filaDuplicada) {
            $filasDuplicadas += ($filaDuplicada->conteo - 1);
        }

        $filasEliminadas = 0;
        if ($filasDuplicadas > 0) {
            $filasEliminadas = DB::affectingStatement("
                DELETE s1
                FROM inegi_states s1
                JOIN inegi_states s2
                  ON s1.cve_ent = s2.cve_ent
                 AND s1.id > s2.id
            ");
        }

        $totalFinal = InegiState::count();

        return response()->json([
            'claves_duplicadas' => count($duplicados),
            'filas_duplicadas' => $filasDuplicadas,
            'filas_eliminadas' => $filasEliminadas,
            'total_final' => $totalFinal,
        ], 200);
    }

    /**
 * @OA\Post(
 *   path="/api/states/clear",
 *   tags={"Estados"},
 *   summary="Vaciar tabla de estados",
 *   description="Trunca la tabla inegi_states (resetea AUTO_INCREMENT).",
 *   @OA\Response(response=200, description="OK")
 * )
 */
    public function clear()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('inegi_states')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        return response()->json([
            'ok' => true,
            'mensaje' => 'Tabla inegi_states vaciada'
        ], 200);
    }

}
