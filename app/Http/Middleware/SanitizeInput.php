<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SanitizeInput
{
    /**
     * Remove tags HTML de todos os campos de texto da requisição.
     * Protege contra XSS persistente.
     */
    public function handle(Request $request, Closure $next)
    {
        $sanitized = $this->sanitizeArray($request->all());
        $request->replace($sanitized);

        return $next($request);
    }

    /**
     * Recursivamente sanitiza um array, removendo tags HTML de strings.
     */
    private function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Não sanitizar senhas (podem conter caracteres especiais)
                if (in_array($key, ['password', 'password_confirmation', 'senha'], true)) {
                    continue;
                }

                $data[$key] = strip_tags($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value);
            }
        }

        return $data;
    }
}