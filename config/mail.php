<?php

declare(strict_types=1);

if (is_file(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

const FLOWFORM_MAIL_CONFIG = [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'avinashmunavalli522@gmail.com',
    'password' => 'qmdhwzjptcnenkyd',
    'from_email' => 'avinashmunavalli522@gmail.com',
    'from_name' => 'Flowform',
];

function sendMail(string $toEmail, string $toName, string $subject, string $body): bool
{
    $GLOBALS['FLOWFORM_LAST_MAIL_ERROR'] = '';

    if (!class_exists(PHPMailer::class)) {
        $GLOBALS['FLOWFORM_LAST_MAIL_ERROR'] = 'PHPMailer is not installed. Run composer require phpmailer/phpmailer.';
        error_log($GLOBALS['FLOWFORM_LAST_MAIL_ERROR']);
        return false;
    }

    try {
        $mailer = new PHPMailer(true);
        $mailer->isSMTP();
        $mailer->Host = FLOWFORM_MAIL_CONFIG['host'];
        $mailer->Port = FLOWFORM_MAIL_CONFIG['port'];
        $mailer->SMTPAuth = true;
        $mailer->SMTPSecure = FLOWFORM_MAIL_CONFIG['encryption'];
        $mailer->Username = FLOWFORM_MAIL_CONFIG['username'];
        $mailer->Password = FLOWFORM_MAIL_CONFIG['password'];
        $mailer->CharSet = 'UTF-8';

        $mailer->setFrom(FLOWFORM_MAIL_CONFIG['from_email'], FLOWFORM_MAIL_CONFIG['from_name']);
        $mailer->addAddress($toEmail, $toName);
        $mailer->isHTML(true);
        $mailer->Subject = $subject;
        $mailer->Body = wrapMailBody($subject, $body);
        $mailer->AltBody = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], PHP_EOL, $body)));

        $sent = $mailer->send();
        if (!$sent) {
            $GLOBALS['FLOWFORM_LAST_MAIL_ERROR'] = $mailer->ErrorInfo ?: 'Unknown PHPMailer error.';
            error_log('FlowForm mail error: ' . $GLOBALS['FLOWFORM_LAST_MAIL_ERROR']);
        }

        return $sent;
    } catch (Exception $exception) {
        $GLOBALS['FLOWFORM_LAST_MAIL_ERROR'] = $exception->getMessage();
        error_log('FlowForm mail error: ' . $GLOBALS['FLOWFORM_LAST_MAIL_ERROR']);
        return false;
    } catch (Throwable $exception) {
        $GLOBALS['FLOWFORM_LAST_MAIL_ERROR'] = $exception->getMessage();
        error_log('FlowForm mail error: ' . $GLOBALS['FLOWFORM_LAST_MAIL_ERROR']);
        return false;
    }
}

function lastMailError(): string
{
    return (string) ($GLOBALS['FLOWFORM_LAST_MAIL_ERROR'] ?? '');
}

function wrapMailBody(string $title, string $body): string
{
    return sprintf(
        '<div style="background:#f4efe6;padding:32px;font-family:Arial,sans-serif;color:#1f2933;">'
        . '<div style="max-width:620px;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #d8d0c3;">'
        . '<div style="padding:24px 28px;background:#0f766e;color:#ffffff;">'
        . '<h1 style="margin:0;font-size:24px;">%s</h1>'
        . '</div>'
        . '<div style="padding:28px;line-height:1.7;font-size:15px;">%s</div>'
        . '<div style="padding:18px 28px;background:#f8f4ed;color:#5c6b73;font-size:12px;">FlowForm automated notification</div>'
        . '</div>'
        . '</div>',
        e($title),
        $body
    );
}
