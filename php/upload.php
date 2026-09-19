<?php
declare(strict_types=1);

$uploadDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
$allowedExtensions = ['pbi', 'pbix', 'pbit'];

if (!is_dir($uploadDirectory)) {
    mkdir($uploadDirectory, 0755, true);
}

function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $files = [];
    foreach (glob($uploadDirectory . DIRECTORY_SEPARATOR . '*') ?: [] as $path) {
        if (!is_file($path)) {
            continue;
        }

        $name = basename($path);
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (in_array($extension, $allowedExtensions, true)) {
            $files[] = [
                'name' => $name,
                'size' => number_format(filesize($path) / 1024 / 1024, 2, ',', '.') . ' MB',
                'url' => 'uploads/' . rawurlencode($name),
            ];
        }
    }

    usort($files, static fn (array $left, array $right): int => strnatcasecmp($left['name'], $right['name']));
    jsonResponse($files);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['report'])) {
    jsonResponse(['error' => 'Envie um arquivo do relatorio.'], 400);
}

$file = $_FILES['report'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['error' => 'O upload falhou. Verifique o tamanho permitido no PHP.'], 400);
}

$originalName = basename((string) $file['name']);
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
if (!in_array($extension, $allowedExtensions, true)) {
    jsonResponse(['error' => 'Formato invalido. Use .pbi, .pbix ou .pbit.'], 415);
}

$safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName) ?: ('relatorio.' . $extension);
$destination = $uploadDirectory . DIRECTORY_SEPARATOR . $safeName;
if (!move_uploaded_file($file['tmp_name'], $destination)) {
    jsonResponse(['error' => 'Nao foi possivel salvar o arquivo no servidor.'], 500);
}

jsonResponse(['message' => 'Arquivo enviado e disponivel para compartilhamento.']);