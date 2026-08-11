<?php
session_start();

function clean($v, $max = 3000) {
    $v = trim((string) $v);
    $v = str_replace(["\r", "\n"], ' ', $v);
    return htmlspecialchars(substr($v, 0, $max), ENT_QUOTES, 'UTF-8');
}

function valid_email($e) {
    return filter_var($e, FILTER_VALIDATE_EMAIL) && !preg_match('/[\r\n]/', $e);
}

function fail($msg) {
    $_SESSION['form_error'] = $msg;
    header('Location:/forms/error.php');
    exit;
}

function ok() {
    header('Location:/forms/success.php');
    exit;
}

function guard() {
    if (!empty($_POST['website'])) fail('Spam protection rejected this submission.');
    $t = (int) ($_POST['form_started_at'] ?? 0);
    if ($t && (time() * 1000 - $t) < 1200) fail('Please try again.');
    $last = $_SESSION['last_submit'] ?? 0;
    if (time() - $last < 20) fail('Please wait before submitting again.');
    $_SESSION['last_submit'] = time();
}

function mail_config() {
    $private = __DIR__ . '/../config/mail-config.php';
    return file_exists($private) ? require $private : require __DIR__ . '/../config/mail-config.example.php';
}

function plain_text($v, $max = 3000) {
    $v = trim((string) $v);
    $v = str_replace(["\r", "\n"], ' ', $v);
    return substr($v, 0, $max);
}

function build_message($subject, $to, $from, $reply, $body) {
    $reply = valid_email($reply) ? $reply : $from;
    $body = preg_replace("/\r\n|\r|\n/", "\r\n", $body);
    $headers = [
        'From: Trinetra Fleet Solutions <' . $from . '>',
        'To: ' . $to,
        'Reply-To: ' . $reply,
        'Subject: ' . plain_text($subject, 180),
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
    ];
    return implode("\r\n", $headers) . "\r\n\r\n" . $body;
}

function smtp_read($socket) {
    $data = '';
    while (($line = fgets($socket, 515)) !== false) {
        $data .= $line;
        if (isset($line[3]) && $line[3] === ' ') break;
    }
    return $data;
}

function smtp_command($socket, $command, array $expected) {
    if ($command !== null) fwrite($socket, $command . "\r\n");
    $response = smtp_read($socket);
    $code = (int) substr($response, 0, 3);
    return in_array($code, $expected, true);
}

function smtp_enabled($cfg) {
    return !empty($cfg['SMTP_HOST'])
        && !empty($cfg['SMTP_PORT'])
        && !empty($cfg['SMTP_USERNAME'])
        && !empty($cfg['SMTP_PASSWORD'])
        && $cfg['SMTP_PASSWORD'] !== 'REPLACE_ON_SERVER';
}

function smtp_send($cfg, $to, $subject, $body, $reply = '') {
    if (!smtp_enabled($cfg) || !valid_email($to) || !valid_email($cfg['MAIL_FROM'])) return false;

    $host = $cfg['SMTP_HOST'];
    $port = (int) $cfg['SMTP_PORT'];
    $encryption = strtolower((string) ($cfg['SMTP_ENCRYPTION'] ?? 'ssl'));
    $remote = ($encryption === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $socket = @stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
    if (!$socket) return false;
    stream_set_timeout($socket, 20);

    $ok = smtp_command($socket, null, [220])
        && smtp_command($socket, 'EHLO trinetrafleet.com', [250]);

    if ($ok && $encryption === 'tls') {
        $ok = smtp_command($socket, 'STARTTLS', [220])
            && stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)
            && smtp_command($socket, 'EHLO trinetrafleet.com', [250]);
    }

    if ($ok) {
        $ok = smtp_command($socket, 'AUTH LOGIN', [334])
            && smtp_command($socket, base64_encode($cfg['SMTP_USERNAME']), [334])
            && smtp_command($socket, base64_encode($cfg['SMTP_PASSWORD']), [235])
            && smtp_command($socket, 'MAIL FROM:<' . $cfg['MAIL_FROM'] . '>', [250])
            && smtp_command($socket, 'RCPT TO:<' . $to . '>', [250, 251])
            && smtp_command($socket, 'DATA', [354]);
    }

    if ($ok) {
        $message = build_message($subject, $to, $cfg['MAIL_FROM'], $reply, $body);
        $message = preg_replace('/^\./m', '..', $message);
        fwrite($socket, $message . "\r\n.\r\n");
        $ok = in_array((int) substr(smtp_read($socket), 0, 3), [250], true);
    }

    fwrite($socket, "QUIT\r\n");
    fclose($socket);
    return $ok;
}

function fallback_mail($to, $subject, $body, $from, $reply = '') {
    if (!valid_email($to) || !valid_email($from)) return false;
    $reply = valid_email($reply) ? $reply : $from;
    $headers = "From: {$from}\r\nReply-To: {$reply}\r\nContent-Type: text/plain; charset=UTF-8";
    return @mail($to, $subject, $body, $headers);
}

function send_email($to, $subject, $body, $reply = '') {
    $cfg = mail_config();
    $from = $cfg['MAIL_FROM'] ?? '';
    return smtp_send($cfg, $to, $subject, $body, $reply)
        || fallback_mail($to, $subject, $body, $from, $reply);
}

function send_basic_mail($subject, $fields, $reply = '') {
    $cfg = mail_config();
    $body = "Trinetra Fleet Solutions enquiry\n\n";
    foreach ($fields as $k => $v) {
        $body .= plain_text($k, 80) . ': ' . plain_text($v) . "\n";
    }
    return send_email($cfg['MAIL_TO'] ?? '', $subject, $body, $reply);
}

function ack($email, $name) {
    if (!valid_email($email)) return false;
    $safeName = plain_text($name, 120);
    $body = "Dear {$safeName},\n\nThank you for contacting Trinetra Fleet Solutions. Our team will review your enquiry and respond shortly.\n\nRegards,\nTrinetra Fleet Solutions";
    return send_email($email, 'Enquiry received - Trinetra Fleet Solutions', $body);
}

function handle_file() {
    if (empty($_FILES['cv']) || $_FILES['cv']['error'] === UPLOAD_ERR_NO_FILE) return 'No CV attached';
    if ($_FILES['cv']['error'] !== UPLOAD_ERR_OK) fail('CV upload failed.');
    if ($_FILES['cv']['size'] > 2 * 1024 * 1024) fail('CV file is too large.');
    $name = $_FILES['cv']['name'];
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf', 'doc', 'docx'], true)) fail('Only PDF, DOC and DOCX files are accepted.');
    return 'CV received: ' . basename($name);
}
