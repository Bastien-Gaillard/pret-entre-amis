<?php

declare(strict_types=1);

use App\Csrf;

/** Échappe une valeur pour l'afficher dans du HTML. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

function redirect(string $path): never
{
    header('Location: ' . $path, true, 303);
    exit;
}

/** 2026-11-09 → 09/11/2026 */
function date_fr(?string $date): string
{
    if ($date === null || $date === '') {
        return '—';
    }
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', substr($date, 0, 10));

    return $d ? $d->format('d/m/Y') : e($date);
}

/** Nombre de jours entre aujourd'hui et une date (négatif si passée). */
function days_from_today(string $date): int
{
    $today = new DateTimeImmutable('today');
    $target = new DateTimeImmutable($date);

    return (int) $today->diff($target)->format('%r%a');
}

const STATUS_LABELS = [
    'en_cours'  => 'En cours',
    'en_retard' => 'En retard',
    'rendu'     => 'Rendu',
];

function status_badge(string $status): string
{
    return sprintf('<span class="badge badge-%s">%s</span>', e($status), e(STATUS_LABELS[$status] ?? $status));
}
