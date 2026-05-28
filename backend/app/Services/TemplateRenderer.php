<?php

namespace App\Services;

use App\Models\Template;

/**
 * Renderiza el cuerpo de una plantilla sustituyendo variables {{clave}}.
 *
 * Ejemplo:
 *   template->body = "Hola {{nombre}}, tu pedido {{pedido}} fue enviado."
 *   render($template, ['nombre' => 'Juan', 'pedido' => '#1234'])
 *   → "Hola Juan, tu pedido #1234 fue enviado."
 */
class TemplateRenderer
{
    /**
     * Renderiza la plantilla con las variables dadas.
     * Las variables desconocidas se dejan como {{clave}} sin tocar.
     */
    public function render(Template $template, array $variables = []): string
    {
        return $this->interpolate($template->body, $variables);
    }

    /**
     * Renderiza el asunto (subject) del template.
     */
    public function renderSubject(Template $template, array $variables = []): string
    {
        return $this->interpolate($template->subject ?? '', $variables);
    }

    /**
     * Devuelve las variables declaradas en el template que faltan en $variables.
     */
    public function missingVars(Template $template, array $variables): array
    {
        $declared = $template->variables ?? [];
        return array_diff($declared, array_keys($variables));
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function interpolate(string $text, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $text = str_replace('{{' . $key . '}}', (string) $value, $text);
        }
        return $text;
    }
}
