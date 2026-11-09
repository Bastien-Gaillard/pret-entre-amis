<?php

namespace App\Twig;

use App\Entity\Loan;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class AppExtension extends AbstractExtension
{
    private const MONTHS = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];

    public function getFilters(): array
    {
        return [
            new TwigFilter('date_fr', $this->dateFr(...)),
            new TwigFilter('initials', $this->initials(...)),
            new TwigFilter('avatar_hue', $this->avatarHue(...)),
            new TwigFilter('due_label', $this->dueLabel(...)),
        ];
    }

    /** 2026-11-09 → « 9 nov. » (l'année n'est affichée que si ce n'est pas l'année en cours). */
    public function dateFr(?\DateTimeInterface $date, bool $withYear = false): string
    {
        if ($date === null) {
            return '—';
        }
        $label = $date->format('j') . ' ' . self::MONTHS[(int) $date->format('n') - 1];
        if ($withYear || $date->format('Y') !== date('Y')) {
            $label .= ' ' . $date->format('Y');
        }

        return $label;
    }

    public function initials(string $name): string
    {
        $words = preg_split('/[\s-]+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: ['?'];
        $initials = mb_substr($words[0], 0, 1) . (isset($words[1]) ? mb_substr($words[1], 0, 1) : '');

        return mb_strtoupper($initials);
    }

    /** Teinte stable (0-359) dérivée du nom : chaque ami garde toujours la même couleur. */
    public function avatarHue(string $name): int
    {
        return crc32(mb_strtolower($name)) % 360;
    }

    public function dueLabel(Loan $loan): string
    {
        $days = $loan->daysUntilDue();

        return match (true) {
            $loan->isReturned() => 'Rendu le ' . $this->dateFr($loan->getReturnedAt()),
            $days === null => 'Pas de date de retour',
            $days < -1 => sprintf('%d jours de retard', -$days),
            $days === -1 => '1 jour de retard',
            $days === 0 => "À rendre aujourd'hui",
            $days === 1 => 'À rendre demain',
            default => sprintf('À rendre dans %d jours', $days),
        };
    }
}
