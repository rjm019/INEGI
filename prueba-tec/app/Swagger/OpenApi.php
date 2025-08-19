<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 *   @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Railway"
 *   ),
 *   @OA\Server(
 *     url="http://localhost:8000",
 *     description="Local"
 *   ),
 *   @OA\Info(
 *     title="API Estados INEGI",
 *     version="1.0.0",
 *     description="API para sincronizar y consultar estados del INEGI (demo técnica)."
 *   )
 * )
 */

/**
 * @OA\Tag(
 *   name="Estados",
 *   description="Consulta y sincronización de estados"
 * )
 *
 * @OA\Schema(
 *   schema="EstadoItem",
 *   type="object",
 *   required={"cvegeo","cve_ent","nomgeo","nom_abrev","pob_total"},
 *   @OA\Property(property="cvegeo", type="string", example="01"),
 *   @OA\Property(property="cve_ent", type="string", example="01"),
 *   @OA\Property(property="nomgeo", type="string", example="Aguascalientes"),
 *   @OA\Property(property="nom_abrev", type="string", example="Ags."),
 *   @OA\Property(property="pob_total", type="integer", example=1425607)
 * )
 *
 * @OA\Schema(
 *   schema="MetaListado",
 *   type="object",
 *   @OA\Property(property="page", type="integer", example=1),
 *   @OA\Property(property="bloque_aplicado", type="integer", example=25),
 *   @OA\Property(property="bloques_permitidos", type="array", @OA\Items(type="integer"), example={10,25,50,100}),
 *   @OA\Property(property="total", type="integer", example=32),
 *   @OA\Property(property="pages", type="integer", example=4)
 * )
 *
 * @OA\Schema(
 *   schema="RespuestaListado",
 *   type="object",
 *   @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/EstadoItem")),
 *   @OA\Property(property="meta", ref="#/components/schemas/MetaListado")
 * )
 *
 * @OA\Schema(
 *   schema="RespuestaDataTables",
 *   type="object",
 *   @OA\Property(property="draw", type="integer", example=1),
 *   @OA\Property(property="recordsTotal", type="integer", example=32),
 *   @OA\Property(property="recordsFiltered", type="integer", example=32),
 *   @OA\Property(property="data", type="array", @OA\Items(
 *     type="object",
 *     @OA\Property(property="cve_ent", type="string", example="19"),
 *     @OA\Property(property="nomgeo", type="string", example="Nuevo León"),
 *     @OA\Property(property="nom_abrev", type="string", example="NL"),
 *     @OA\Property(property="pob_total", type="integer", example=5784442),
 *     @OA\Property(property="acciones", type="string", example="/api/states/19")
 *   )),
 *   @OA\Property(property="meta", ref="#/components/schemas/MetaListado")
 * )
 *
 * @OA\Schema(
 *   schema="EstadoDetalle",
 *   type="object",
 *   allOf={@OA\Schema(ref="#/components/schemas/EstadoItem")}
 * )
 *
 * @OA\Schema(
 *   schema="SyncRespuesta",
 *   type="object",
 *   @OA\Property(property="insertados", type="integer", example=32),
 *   @OA\Property(property="actualizados", type="integer", example=0),
 *   @OA\Property(property="sin_cambios", type="integer", example=0),
 *   @OA\Property(property="total_recibidos", type="integer", example=32),
 *   @OA\Property(property="resynchronize_param", type="boolean", example=true)
 * )
 *
 * @OA\Schema(
 *   schema="DeduplicateRespuesta",
 *   type="object",
 *   @OA\Property(property="claves_duplicadas", type="integer", example=0),
 *   @OA\Property(property="filas_duplicadas", type="integer", example=0),
 *   @OA\Property(property="filas_eliminadas", type="integer", example=0),
 *   @OA\Property(property="total_final", type="integer", example=32)
 * )
 */
class OpenApi {}
