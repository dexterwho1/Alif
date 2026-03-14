<?php

declare(strict_types=1);

const ADMIN_EMAIL = 'contact@alif.fr';
const FROM_EMAIL = 'contact@alif.fr';
const FROM_NAME = 'ALIF';

function sanitize_string(?string $value): string
{
    return trim((string) $value);
}

function redirect_with_status(string $target, string $status): never
{
    $target = basename($target) ?: 'index.html';
    $separator = str_contains($target, '?') ? '&' : '?';
    header('Location: ' . $target . $separator . 'status=' . rawurlencode($status) . '#inscription');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_status('index.html', 'error');
}

$redirectPath = sanitize_string($_POST['redirect_path'] ?? 'index.html');

if (!empty($_POST['_honey'] ?? '')) {
    redirect_with_status($redirectPath, 'success');
}

$lastName = sanitize_string($_POST['last_name'] ?? '');
$firstName = sanitize_string($_POST['first_name'] ?? '');
$company = sanitize_string($_POST['company'] ?? '');
$companyType = sanitize_string($_POST['company_type'] ?? '');
$siret = sanitize_string($_POST['siret'] ?? '');
$vat = sanitize_string($_POST['vat'] ?? '');
$phone = sanitize_string($_POST['phone'] ?? '');
$email = filter_var(sanitize_string($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: '';
$isForeignCompany = isset($_POST['foreign_company']);
$hasNoVat = isset($_POST['no_vat']);

$errors = [];

if ($lastName === '') {
    $errors[] = 'Nom';
}
if ($firstName === '') {
    $errors[] = 'Prénom';
}
if ($company === '') {
    $errors[] = 'Société';
}
if ($companyType === '') {
    $errors[] = 'Type de société';
}
if (!$isForeignCompany && $siret === '') {
    $errors[] = 'Numéro SIRET';
}
if (!$hasNoVat && $vat === '') {
    $errors[] = 'Numéro TVA';
}
if ($phone === '') {
    $errors[] = 'Téléphone';
}
if ($email === '') {
    $errors[] = 'Email';
}

if ($errors !== []) {
    redirect_with_status($redirectPath, 'error');
}

$siretValue = $isForeignCompany ? 'Pays UE - Hors UE' : $siret;
$vatValue = $hasNoVat ? 'Aucun' : $vat;

$adminSubject = $company !== '' ? "Nouvelle pré-inscription ALIF - {$company}" : 'Nouvelle pré-inscription ALIF';
$clientSubject = "Confirmation de votre demande d'inscription sur la plateforme ALIF";

$adminMessage = nl2br(htmlspecialchars(implode("\n", [
    'Bonjour,',
    '',
    "Un nouvel utilisateur vient de soumettre le formulaire d'inscription.",
    'Voici les informations renseignées :',
    '',
    "Informations sur l'entreprise :",
    "Nom : {$lastName}",
    "Prénom : {$firstName}",
    "Nom de la société : {$company}",
    "Type de société : {$companyType}",
    "N° SIRET : {$siretValue}",
    "N° TVA : {$vatValue}",
    '',
    'Informations de contact :',
    "Téléphone : {$phone}",
    "Email : {$email}",
    '',
    'Merci de traiter cette demande selon les procédures internes ALIF.',
    '',
    'Bien cordialement,',
    'Support ALIF',
])));

$clientMessage = '
<html lang="fr">
<body style="margin:0;padding:32px;background:#f5f7f8;font-family:Arial,sans-serif;color:#111827;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;margin:0 auto;background:#ffffff;border-radius:24px;padding:40px;">
        <tr>
            <td style="font-size:15px;line-height:1.7;color:#1f2937;">
                <p style="margin:0 0 18px;">Bonjour ' . htmlspecialchars($firstName . ' ' . $lastName) . ',</p>
                <p style="margin:0 0 18px;"><strong>Bienvenue sur ALIF.</strong></p>
                <p style="margin:0 0 18px;">Nous vous remercions pour votre demande de pré-inscription. Celle-ci a bien été enregistrée et est actuellement en cours de vérification.</p>
                <p style="margin:0 0 18px;">Conformément à notre politique d\'admission, une vérification préalable des informations communiquées sera effectuée afin de garantir la qualité et la sécurité de notre environnement.</p>
                <p style="margin:0 0 18px;">Une fois votre dossier validé, vous recevrez un email de confirmation.</p>
                <p style="margin:0 0 18px;">Nous restons à votre disposition pour toute question complémentaire.</p>
                <p style="margin:0 0 18px;">Nous vous remercions pour votre confiance.</p>
                <p style="margin:24px 0 0;">Bien cordialement,<br><strong>L\'équipe ALIF</strong></p>
            </td>
        </tr>
    </table>
</body>
</html>';

$headersAdmin = [
    'MIME-Version: 1.0',
    'Content-type: text/html; charset=UTF-8',
    'From: ' . FROM_NAME . ' <' . FROM_EMAIL . '>',
    'Reply-To: ' . $firstName . ' ' . $lastName . ' <' . $email . '>',
];

$headersClient = [
    'MIME-Version: 1.0',
    'Content-type: text/html; charset=UTF-8',
    'From: ' . FROM_NAME . ' <' . FROM_EMAIL . '>',
    'Reply-To: ' . FROM_NAME . ' <' . ADMIN_EMAIL . '>',
];

$adminSent = mail(ADMIN_EMAIL, '=?UTF-8?B?' . base64_encode($adminSubject) . '?=', $adminMessage, implode("\r\n", $headersAdmin));
$clientSent = mail($email, '=?UTF-8?B?' . base64_encode($clientSubject) . '?=', $clientMessage, implode("\r\n", $headersClient));

redirect_with_status($redirectPath, $adminSent && $clientSent ? 'success' : 'error');
