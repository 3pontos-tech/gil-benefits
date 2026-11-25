<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationClau\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentica a reserva de desconto vinda da Clau por três portões: segredo configurado,
 * timestamp dentro da tolerância e assinatura HMAC-SHA256 do corpo cru.
 *
 * A mensagem assinada é o timestamp, um ponto e o corpo exatamente como chegou. Assim a
 * assinatura cobre o payload inteiro e prova que ninguém o alterou em trânsito. Qualquer
 * falha responde 401 sem dizer qual portão recusou, para não ensinar quem sondar a API.
 */
final class ValidateClauSignature
{
    public const TIMESTAMP_HEADER = 'X-Clau-Timestamp';

    public const SIGNATURE_HEADER = 'X-Clau-Signature';

    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('clau.signing_secret');
        $timestamp = (string) $request->header(self::TIMESTAMP_HEADER);
        $signature = (string) $request->header(self::SIGNATURE_HEADER);

        abort_if(blank($secret), 503);
        abort_if(blank($signature) || preg_match('/^\d{1,12}$/', $timestamp) !== 1, 401);
        abort_if($this->isStale($timestamp), 401);
        abort_if(! $this->isSigned($request, $timestamp, $signature, $secret), 401);

        return $next($request);
    }

    private function isStale(string $timestamp): bool
    {
        return abs(now()->getTimestamp() - (int) $timestamp) > (int) config('clau.timestamp_tolerance', 300);
    }

    private function isSigned(Request $request, string $timestamp, string $signature, string $secret): bool
    {
        $expected = hash_hmac('sha256', $timestamp . '.' . $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }
}
