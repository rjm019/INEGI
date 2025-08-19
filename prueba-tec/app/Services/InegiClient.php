<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class InegiClient
{
    private string $baseUrl;
    private const CLAVE_CACHE = 'inegi.estados.ultimo_bueno';
    private const TIEMPO_CACHE = 604800; 

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.inegi.base', 'https://gaia.inegi.org.mx/wscatgeo/v2'), '/');
    }
    public function fetchAllStates(bool $permitirObsoleto = false): array
    {
        $urlServicio = "{$this->baseUrl}/mgee/";

        $respuestaHttp = Http::acceptJson()
            ->timeout(20)
            ->retry(2, 1500)
            ->get($urlServicio);

        if ($respuestaHttp->ok()) {
            $datosNormalizados = $this->normalizarLista($respuestaHttp->json());
            Cache::put(self::CLAVE_CACHE, $datosNormalizados, self::TIEMPO_CACHE);
            return $datosNormalizados;
        }

        if ($permitirObsoleto) {
            $instantanea = Cache::get(self::CLAVE_CACHE);
            if ($instantanea) {
                return $instantanea;
            }
        }

        abort(503, "INEGI no disponible (HTTP {$respuestaHttp->status()}).");
    }

    private function normalizarLista($json): array
    {
         $lista = Arr::get($json, 'datos', Arr::get($json, 'features', $json));

        if (!is_array($lista)) {
            abort(502, 'Formato inesperado desde INEGI.');
        }

        $salida = [];
        foreach ($lista as $fila) {
            $propiedades = Arr::get($fila, 'properties', $fila);

            $claveEntidad = $this->dosDigitos($propiedades['cve_ent'] ?? $propiedades['cvegeo'] ?? null);
            if (!$claveEntidad) {
                continue;
            }

            $salida[] = [
                'cve_ent' => $claveEntidad,
                'cvegeo'  => $this->dosDigitos($propiedades['cvegeo'] ?? $claveEntidad),
                'nomgeo'  => $propiedades['nomgeo'] ?? null,
                'nom_abrev' => $propiedades['nom_abrev'] ?? null,
                'pob_total' => $this->aEntero($propiedades['pob_total'] ?? null),
                'pob_femenina' => $this->aEntero($propiedades['pob_femenina'] ?? null),
                'pob_masculina' => $this->aEntero($propiedades['pob_masculina'] ?? null),
                'total_viviendas_habitadas' => $this->aEntero($propiedades['total_viviendas_habitadas'] ?? null),
                'raw' => json_encode($propiedades, JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        return $salida;
    }

    private function aEntero($valor): ?int
    {
        if ($valor === null || $valor === '') return null;
        return (int) preg_replace('/[^0-9]/', '', (string) $valor);
    }

    private function dosDigitos($valor): ?string
    {
        if ($valor === null || $valor === '') return null;
        $texto = (string)$valor;
        return str_pad(substr($texto, 0, 2), 2, '0', STR_PAD_LEFT);
    }
}
