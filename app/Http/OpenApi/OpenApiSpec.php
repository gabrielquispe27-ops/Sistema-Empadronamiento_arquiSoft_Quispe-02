<?php

declare(strict_types=1);

namespace App\Http\OpenApi;

/**
 * Especificación Base OpenAPI 3.0 (L5-Swagger) para el Sistema de Empadronamiento en Zonas de Emergencia.
 *
 * @OA\Info(
 *     version="1.0.0",
 *     title="API REST - Sistema Transaccional de Respuesta Rápida para el Empadronamiento en Zonas de Emergencia",
 *     description="Especificación OpenAPI 3.0 de los endpoints transaccionales protegidos con Laravel Sanctum (ADR-004). Soporta captura masiva en campo con deduplicación atómica O(1) en Redis SETNX (RF03, AC03), encolamiento asíncrono en Laravel Queues (RF04, AC01, ADR-003), consulta paginada del padrón consolidado (RF05) y reportes estadísticos por zona (RF06).",
 *     @OA\Contact(
 *         name="Gabriel Quispe Andia - Arquitectura de Software (UNSCH)",
 *         email="coordinador@defensacivil.gob.pe"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Servidor Principal / Balanceador Nginx (Cluster Local Stateless)"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Sanctum",
 *     description="Autenticación basada en tokens de Laravel Sanctum (ADR-004). Obtenga su token en POST /api/auth/token e ingréselo con el formato: Bearer {token}"
 * )
 *
 * @OA\Tag(
 *     name="Autenticación",
 *     description="Emisión y revocación de tokens Bearer con Laravel Sanctum para Brigadistas y Coordinadores (ADR-004, AC05)"
 * )
 * @OA\Tag(
 *     name="Captura / Empadronamiento",
 *     description="Recepción de registros en campo, deduplicación O(1) con Redis SETNX y despacho asíncrono a cola (RF01-RF04, AC01, AC03)"
 * )
 * @OA\Tag(
 *     name="Padrón Consolidado",
 *     description="Consulta paginada y filtrada por zona del padrón único sin duplicados, exclusiva para Coordinadores (RF05, HU03)"
 * )
 * @OA\Tag(
 *     name="Reportes Estadísticos",
 *     description="Métricas de cobertura y conteo de damnificados por zona geográfica con caché Redis (RF06, HU04)"
 * )
 *
 * @OA\Schema(
 *     schema="LoginRequestSchema",
 *     type="object",
 *     required={"email", "password"},
 *     @OA\Property(property="email", type="string", format="email", example="brigadista@defensacivil.gob.pe", description="Correo institucional del Brigadista o Coordinador"),
 *     @OA\Property(property="password", type="string", format="password", example="Brigadista2026!", description="Contraseña del actor (mínimo 8 caracteres)")
 * )
 *
 * @OA\Schema(
 *     schema="AuthTokenResponseSchema",
 *     type="object",
 *     @OA\Property(property="status", type="string", example="ok"),
 *     @OA\Property(property="message", type="string", example="Autenticación exitosa."),
 *     @OA\Property(property="token", type="string", example="1|aBcDeFgHiJkLmNoPqRsTuVwXyZ1234567890"),
 *     @OA\Property(property="token_type", type="string", example="Bearer")
 * )
 *
 * @OA\Schema(
 *     schema="StoreDamnificadoRequestSchema",
 *     type="object",
 *     required={"dni", "nombres", "apellidos", "zona_id", "danos_materiales"},
 *     @OA\Property(property="dni", type="string", pattern="^\d{8}$", example="71234567", description="Documento Nacional de Identidad de exactamente 8 dígitos numéricos (RF02)"),
 *     @OA\Property(property="nombres", type="string", maxLength=120, example="Carlos Alberto", description="Nombres del damnificado"),
 *     @OA\Property(property="apellidos", type="string", maxLength=120, example="Huamán Quispe", description="Apellidos del damnificado"),
 *     @OA\Property(property="zona_id", type="integer", example=1, description="ID de la zona geográfica de emergencia registrada"),
 *     @OA\Property(property="danos_materiales", type="string", maxLength=1000, example="Colapso parcial de techo de adobe e inundación de vivienda.", description="Descripción de los daños materiales verificados en campo")
 * )
 *
 * @OA\Schema(
 *     schema="CapturaAcceptedResponseSchema",
 *     type="object",
 *     @OA\Property(property="status", type="string", example="queued"),
 *     @OA\Property(property="message", type="string", example="Registro recibido y encolado para procesamiento asíncrono."),
 *     @OA\Property(property="dni", type="string", example="71234567"),
 *     @OA\Property(property="queue", type="string", example="empadronamiento")
 * )
 *
 * @OA\Schema(
 *     schema="ConflictDuplicateDniResponseSchema",
 *     type="object",
 *     @OA\Property(property="status", type="string", example="conflict"),
 *     @OA\Property(property="error_code", type="string", example="DUPLICATE_DNI"),
 *     @OA\Property(property="message", type="string", example="El DNI ingresado ya se encuentra registrado o en proceso de empadronamiento (RF03).")
 * )
 *
 * @OA\Schema(
 *     schema="ValidationErrorResponseSchema",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="El DNI debe contener exactamente 8 dígitos numéricos (RF02)."),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         @OA\Property(
 *             property="dni",
 *             type="array",
 *             @OA\Items(type="string", example="El DNI debe contener exactamente 8 dígitos numéricos (RF02).")
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="UnauthorizedErrorResponseSchema",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="No autenticado. Se requiere un token válido de Laravel Sanctum.")
 * )
 *
 * @OA\Schema(
 *     schema="ForbiddenErrorResponseSchema",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="No tiene permisos suficientes para acceder a este módulo.")
 * )
 *
 * @OA\Schema(
 *     schema="DamnificadoItemSchema",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=15),
 *     @OA\Property(property="dni", type="string", example="71234567"),
 *     @OA\Property(property="nombres", type="string", example="Carlos Alberto"),
 *     @OA\Property(property="apellidos", type="string", example="Huamán Quispe"),
 *     @OA\Property(property="zona_id", type="integer", example=1),
 *     @OA\Property(property="danos_materiales", type="string", example="Colapso parcial de techo de adobe."),
 *     @OA\Property(property="registrado_por", type="integer", nullable=true, example=1),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-11-19T12:00:00Z")
 * )
 *
 * @OA\Schema(
 *     schema="PadronPaginadoResponseSchema",
 *     type="object",
 *     @OA\Property(property="status", type="string", example="ok"),
 *     @OA\Property(property="zona_seleccionada", type="integer", nullable=true, example=1),
 *     @OA\Property(
 *         property="padron",
 *         type="object",
 *         @OA\Property(property="current_page", type="integer", example=1),
 *         @OA\Property(property="per_page", type="integer", example=25),
 *         @OA\Property(property="total", type="integer", example=142),
 *         @OA\Property(
 *             property="data",
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/DamnificadoItemSchema")
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ZonaMetricaItemSchema",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nombre", type="string", example="Sector Huamanga Centro"),
 *     @OA\Property(property="nivel_afectacion", type="string", enum={"bajo", "medio", "alto", "critico"}, example="critico"),
 *     @OA\Property(property="empadronados", type="integer", example=450),
 *     @OA\Property(property="poblacion_estimada", type="integer", example=1500),
 *     @OA\Property(property="porcentaje_cobertura", type="number", format="float", example=30.0)
 * )
 *
 * @OA\Schema(
 *     schema="ReporteMetricasResponseSchema",
 *     type="object",
 *     @OA\Property(property="total_empadronados", type="integer", example=840),
 *     @OA\Property(property="total_zonas", type="integer", example=5),
 *     @OA\Property(
 *         property="zonas",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/ZonaMetricaItemSchema")
 *     )
 * )
 */
class OpenApiSpec
{
}
