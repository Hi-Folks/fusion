<?php

declare(strict_types=1);

namespace HiFolks\Fusion;

use Symfony\Component\Console\Output\OutputInterface;

use function Termwind\render;
use function Termwind\renderUsing;

final readonly class Output
{
    public function __construct(private OutputInterface $output) {}

    public function title(string $title): void
    {
        $this->render("<span class='font-bold text-green'>" . $this->escape($title) . '</span>');
    }

    public function sectionTitle(string $title): void
    {
        $this->render("<strong class='mx-1 text-green-500'>" . $this->escape($title) . '</strong>');
    }

    public function p(string $text): void
    {
        $this->render("<div class='mx-1 text-green-300'>" . $this->escape($text) . '</div>');
    }

    public function labelValue(string $label, string|int $value = ''): void
    {
        $safeLabel = $this->escape($label);
        $safeValue = $this->escape($value);

        $this->render("
        <div class='flex space-x-1'>
            <span class='font-bold'>{$safeLabel}</span>
            <span class='flex-1 content-repeat-[.] text-gray'></span>
            <span class='font-bold text-green'>{$safeValue}</span>
        </div>");
    }

    public function line(string $line): void
    {
        $this->render("<div class='mx-1 pt-1 pb-2 text-yellow-200'>" . $this->escape($line) . '</div>');
    }

    private function render(string $html): void
    {
        renderUsing($this->output);
        render($html);
    }

    private function escape(string|int $value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8',
        );
    }
}
