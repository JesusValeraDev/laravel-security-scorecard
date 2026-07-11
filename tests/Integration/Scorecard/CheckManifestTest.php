<?php

declare(strict_types=1);

namespace Tests\Integration\Scorecard;

use Modules\Scorecard\Application\ScanRunner;
use Modules\Scorecard\Infrastructure\Http\Livewire\CheckCard;
use Modules\Scorecard\Infrastructure\Http\Livewire\ScanForm;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CheckManifestTest extends TestCase
{
    #[Test]
    public function every_check_declares_at_least_one_probe(): void
    {
        foreach (app(ScanRunner::class)->manifest() as $entry) {
            $this->assertNotEmpty(
                $entry->probes,
                "Check \"{$entry->id}\" declares no probes, so the published request list would hide it."
            );
        }
    }

    #[Test]
    public function the_published_list_covers_every_registered_check(): void
    {
        // Cards are grouped by request, so count the checks inside them — otherwise grouping
        // could quietly drop one and the list would still look complete.
        $published = array_sum(array_map(
            static fn (CheckCard $card): int => count($card->reveals),
            new ScanForm()->checkCards(),
        ));

        $this->assertSame(app(ScanRunner::class)->checkCount(), $published);
    }

    #[Test]
    public function checks_that_share_a_request_share_a_card(): void
    {
        $requests = array_map(
            static fn (CheckCard $card): string => implode(' ', $card->requests),
            new ScanForm()->checkCards(),
        );

        $this->assertSame(array_unique($requests), $requests, 'A request is listed on more than one card.');
    }

    #[Test]
    public function every_check_has_plain_english_copy_rather_than_falling_back_to_its_title(): void
    {
        // A new check with no REVEALS entry falls back to its title, which reads as an
        // assertion ("… is not public") and not as what the request would give away.
        $titles = app(ScanRunner::class)->checkTitles();

        foreach (new ScanForm()->checkCards() as $card) {
            foreach ($card->reveals as $reveals) {
                $this->assertNotContains(
                    $reveals,
                    $titles,
                    "A check is missing its REVEALS copy in ScanForm and fell back to its title: \"{$reveals}\"."
                );
            }
        }
    }
}
