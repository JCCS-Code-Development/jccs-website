<?php
// Quote request endpoint for the public website.
//
// Receives the Contact page form (multipart POST from js/main.js), checks it,
// and emails it to the office inbox as noreply@jccs-services.com through the
// Microsoft Graph API — the same shared "JCCS Apps — noreply sender" app
// registration the other JCCS apps use. Reply-To is set to the visitor, so
// hitting Reply in Outlook answers the client directly.
//
// Secrets never live in this repo. Config is loaded from outside the web
// root; see api/config.sample.php for the constants it must define.

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function respond(int $status, array $body): void {
    http_response_code($status);
    echo json_encode($body);
    exit;
}

function fail(int $status, string $en, string $es): void {
    respond($status, ['ok' => false, 'error' => ['en' => $en, 'es' => $es]]);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    fail(405, 'Method not allowed.', 'Método no permitido.');
}

// ---- Config (outside the web root) ---------------------------------------
$configCandidates = array_filter([
    getenv('JCCS_SITE_CONFIG') ?: null,
    dirname(__DIR__, 2) . '/secure_backups/website-config.php', // /home/<user>/secure_backups when the site is in public_html
    __DIR__ . '/config.php',                                     // fallback; gitignored and blocked by api/.htaccess
]);
foreach ($configCandidates as $path) {
    if (is_file($path)) {
        require $path;
        break;
    }
}
foreach (['GRAPH_TENANT_ID', 'GRAPH_CLIENT_ID', 'GRAPH_CLIENT_SECRET', 'MAIL_SENDER', 'QUOTE_TO'] as $const) {
    if (!defined($const) || !constant($const)) {
        error_log("jccs-website quote.php: missing config constant $const");
        fail(503, 'The quote form is temporarily unavailable. Please email estimates@jccs-services.com.',
                  'El formulario no está disponible por ahora. Escriba a estimates@jccs-services.com.');
    }
}

// ---- Spam checks ----------------------------------------------------------
// Honeypot field is hidden from people; bots fill it. Also reject forms
// submitted under 3 seconds after the page loaded. Both answer "ok" so bots
// get no signal.
$startedAt = (int) ($_POST['started_at'] ?? 0);
if (trim((string) ($_POST['website'] ?? '')) !== '' ||
    ($startedAt > 0 && (int) (microtime(true) * 1000) - $startedAt < 3000)) {
    respond(200, ['ok' => true]);
}

// Simple per-IP limit: 5 requests per hour, tracked in the system temp dir.
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateFile = sys_get_temp_dir() . '/jccs-quote-' . hash('sha256', $ip . 'jccs-quote');
$now = time();
$recent = [];
if (is_file($rateFile)) {
    $recent = array_filter(
        array_map('intval', explode(',', (string) file_get_contents($rateFile))),
        fn ($t) => $t > $now - 3600
    );
}
if (count($recent) >= 5) {
    fail(429, 'Too many requests. Please try again later or email estimates@jccs-services.com.',
              'Demasiadas solicitudes. Intente más tarde o escriba a estimates@jccs-services.com.');
}

// ---- Validate -------------------------------------------------------------
function field(string $key, int $max): string {
    $value = trim((string) ($_POST[$key] ?? ''));
    $value = preg_replace('/[^\P{C}\n\t]/u', '', $value) ?? ''; // strip control chars, keep newlines/tabs
    return mb_substr($value, 0, $max);
}

$name    = field('name', 120);
$email   = field('email', 200);
$phone   = field('phone', 40);
$company = field('company', 160);
$message = field('message', 5000);

if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail(422, 'Please enter your name, a valid email, and a message.',
              'Ingrese su nombre, un correo válido y un mensaje.');
}

// ---- Build the email ------------------------------------------------------
$singleLine = fn (string $s) => preg_replace('/\s+/', ' ', $s);
$subject = 'Website quote request — ' . $singleLine($name) . ($company !== '' ? ' (' . $singleLine($company) . ')' : '');

$rows = [
    'Name'    => $name,
    'Email'   => $email,
    'Phone'   => $phone !== '' ? $phone : '—',
    'Company' => $company !== '' ? $company : '—',
];
$e = fn (string $s) => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$html = '<div style="font-family:Arial,sans-serif;font-size:14px;color:#1f2a36">'
      . '<h2 style="color:#13263b;margin:0 0 12px">New quote request from the website</h2>'
      . '<table cellpadding="6" style="border-collapse:collapse">';
foreach ($rows as $label => $value) {
    $html .= '<tr><td style="color:#56626f;padding-right:16px">' . $label . '</td><td><strong>' . $e($value) . '</strong></td></tr>';
}
$html .= '</table><h3 style="color:#13263b;margin:18px 0 6px">Message</h3>'
       . '<p style="white-space:pre-wrap;margin:0">' . $e($message) . '</p>'
       . '<p style="color:#56626f;margin-top:18px;font-size:12px">Reply to this email to answer the client directly.</p></div>';

// ---- Send through Microsoft Graph ----------------------------------------
function graphToken(): ?string {
    $ch = curl_init('https://login.microsoftonline.com/' . GRAPH_TENANT_ID . '/oauth2/v2.0/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'client_id'     => GRAPH_CLIENT_ID,
            'client_secret' => GRAPH_CLIENT_SECRET,
            'scope'         => 'https://graph.microsoft.com/.default',
            'grant_type'    => 'client_credentials',
        ]),
        CURLOPT_TIMEOUT        => 10,
    ]);
    $res = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($res === false || $status !== 200) {
        error_log('jccs-website Graph token failed (' . $status . '): ' . substr((string) $res, 0, 300));
        return null;
    }
    $data = json_decode((string) $res, true);
    return is_array($data) && isset($data['access_token']) ? (string) $data['access_token'] : null;
}

$token = graphToken();
$sent = false;
if ($token !== null) {
    $payload = [
        'message' => [
            'subject'      => $subject,
            'body'         => ['contentType' => 'HTML', 'content' => $html],
            'toRecipients' => [['emailAddress' => ['address' => QUOTE_TO]]],
            'replyTo'      => [['emailAddress' => ['address' => $email, 'name' => $name]]],
            'from'         => ['emailAddress' => [
                'address' => MAIL_SENDER,
                'name'    => defined('FROM_NAME') && FROM_NAME ? FROM_NAME : 'JCCS Services Website',
            ]],
        ],
        'saveToSentItems' => false,
    ];
    $ch = curl_init('https://graph.microsoft.com/v1.0/users/' . rawurlencode(MAIL_SENDER) . '/sendMail');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 15,
    ]);
    $res = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $sent = $status === 202;
    if (!$sent) {
        error_log('jccs-website Graph sendMail failed (' . $status . '): ' . substr((string) $res, 0, 300));
    }
}

if (!$sent) {
    fail(502, 'Your request could not be sent. Please try again or email estimates@jccs-services.com.',
              'No se pudo enviar su solicitud. Intente de nuevo o escriba a estimates@jccs-services.com.');
}

$recent[] = $now;
@file_put_contents($rateFile, implode(',', $recent), LOCK_EX);

respond(200, ['ok' => true]);
