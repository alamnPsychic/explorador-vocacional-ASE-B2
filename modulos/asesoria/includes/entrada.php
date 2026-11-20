<?php
declare(strict_types=1);

const ASE_MAX_CUERPO = 16000;

function ase_entrada(array $origen, string $campo): string
{
    $valor = $origen[$campo] ?? null;
    return is_string($valor) ? trim($valor) : '';
}

function ase_verificar_token(): void
{
    if (!is_string($_POST['token'] ?? null)) {
        http_response_code(400);
        exit('Petición no válida.');
    }
    verificar_token();
}

function ase_texto_valido(string $valor, int $maximo): bool
{
    return $valor !== '' && mb_check_encoding($valor, 'UTF-8') && mb_strlen($valor, 'UTF-8') <= $maximo;
}
