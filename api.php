<?php


header('Content-Type: application/json; charset=utf-8');

$DATA_FILE     = __DIR__ . '/data.json';
$MESSAGES_FILE = __DIR__ . '/messages.json';

$ADMIN_PASSWORD = 'cheickestlegoat';

function readJsonFile($path, $default) {
    if (!file_exists($path)) return $default;
    $content = file_get_contents($path);
    $data = json_decode($content, true);
    return $data === null ? $default : $data;
}

function writeJsonFile($path, $data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return file_put_contents($path, $json, LOCK_EX) !== false;
}

function respond($ok, $payload = [], $code = 200) {
    http_response_code($code);
    echo json_encode(array_merge(['success' => $ok], $payload), JSON_UNESCAPED_UNICODE);
    exit;
}

$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) $input = [];

$action = $input['action'] ?? '';

switch ($action) {

    case 'get_data':
        respond(true, ['data' => readJsonFile($DATA_FILE, null)]);
        break;

    case 'save_data':
        if (($input['password'] ?? '') !== $ADMIN_PASSWORD) {
            respond(false, ['message' => 'Mot de passe incorrect.'], 403);
        }
        if (!isset($input['data']) || !is_array($input['data'])) {
            respond(false, ['message' => 'Données manquantes ou invalides.'], 400);
        }
        if (writeJsonFile($DATA_FILE, $input['data'])) {
            respond(true, ['message' => 'Données enregistrées sur le serveur.']);
        } else {
            respond(false, ['message' => "Erreur d'écriture sur le serveur. Vérifiez les droits d'accès du fichier data.json."], 500);
        }
        break;

    case 'add_message':
        $name    = trim((string)($input['name'] ?? ''));
        $email   = trim((string)($input['email'] ?? ''));
        $message = trim((string)($input['message'] ?? ''));
        if ($name === '' || $email === '' || $message === '') {
            respond(false, ['message' => 'Champs manquants.'], 400);
        }
        $messages = readJsonFile($MESSAGES_FILE, []);
        $messages[] = [
            'name'    => $name,
            'email'   => $email,
            'message' => $message,
            'date'    => date('d/m/Y H:i:s'),
        ];
        if (writeJsonFile($MESSAGES_FILE, $messages)) {
            respond(true, ['message' => 'Message enregistré.']);
        } else {
            respond(false, ['message' => "Erreur d'écriture sur le serveur."], 500);
        }
        break;

    case 'get_messages':
        if (($input['password'] ?? '') !== $ADMIN_PASSWORD) {
            respond(false, ['message' => 'Mot de passe incorrect.'], 403);
        }
        respond(true, ['messages' => readJsonFile($MESSAGES_FILE, [])]);
        break;

    case 'clear_messages':
        if (($input['password'] ?? '') !== $ADMIN_PASSWORD) {
            respond(false, ['message' => 'Mot de passe incorrect.'], 403);
        }
        if (writeJsonFile($MESSAGES_FILE, [])) {
            respond(true, ['message' => 'Messages supprimés.']);
        } else {
            respond(false, ['message' => "Erreur d'écriture sur le serveur."], 500);
        }
        break;

    default:
        respond(false, ['message' => 'Action inconnue.'], 400);
}
