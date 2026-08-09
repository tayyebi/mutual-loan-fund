<?php

namespace Tests\Feature;

use App\Domain\ExchangeRates\Exceptions\MissingRateException;
use App\Domain\ExchangeRates\RateService;
use App\Domain\Money\Decimal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Valuation in grams of 18K gold: resolved with fallback, never invented, and
 * frozen onto each posted line so history stays true.
 */
class GoldValuationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_missing_rate_is_refused_rather_than_invented(): void
    {
        $this->expectException(MissingRateException::class);

        app(RateService::class)->quote('IRT', Carbon::today());
    }

    public function test_the_latest_earlier_rate_is_carried_forward_and_labelled(): void
    {
        $rates = app(RateService::class);

        $this->rate('IRT', '96600000', Carbon::today()->subDays(3));

        $quote = $rates->quote('IRT', Carbon::today());

        $this->assertTrue($quote->isFallback());
        $this->assertSame(3, $quote->ageInDays());
        $this->assertTrue($quote->unitsPerGram18k->equals('96600000'));

        // A rate entered for the day itself is not a fallback.
        $this->rate('IRT', '97000000', Carbon::today());
        $this->assertFalse($rates->quote('IRT', Carbon::today())->isFallback());
    }

    public function test_a_rate_entered_for_a_later_date_does_not_apply_to_earlier_operations(): void
    {
        $rates = app(RateService::class);

        $this->rate('IRT', '90000000', Carbon::today()->subDays(10));
        $this->rate('IRT', '96600000', Carbon::today());

        $this->assertTrue(
            $rates->quote('IRT', Carbon::today()->subDays(5))->unitsPerGram18k->equals('90000000')
        );
    }

    public function test_amounts_convert_through_the_gold_reference(): void
    {
        $rates = app(RateService::class);

        $this->rate('IRT', '96600000');
        $this->rate('USDT', '96.6');

        // 10,000,000 IRT at 96,600,000 IRT per gram.
        $grams = $rates->toGold(Decimal::of('10000000'), 'IRT');
        $this->assertSame('0.10351967', $grams->toString());

        // The same value expressed in USDT: 1 USDT is worth 1,000,000 IRT here.
        $converted = $rates->convert(Decimal::of('10000000'), 'IRT', 'USDT');
        $this->assertSame('10.00000000', $converted->toString());
    }

    public function test_a_posted_valuation_survives_a_later_rate_change(): void
    {
        $admin = $this->user();
        $fund = $this->fund($admin);
        $treasury = $this->treasury($fund, $admin);

        $this->rate('USDT', '95');

        $transaction = $this->contribute($admin->membershipFor($fund), $treasury, '500', $admin);
        $line = $transaction->journalEntry->lines->first();

        $snapshotRate = (string) $line->gold_rate_snapshot;
        $snapshotValue = (string) $line->gold_value_snapshot;

        $this->assertTrue(Decimal::of($snapshotRate)->equals('95'));
        // 500 USDT ÷ 95 per gram.
        $this->assertTrue(Decimal::of($snapshotValue)->equals('5.26315789'));

        // Gold doubles the next day.
        $this->rate('USDT', '190', Carbon::tomorrow());

        $line->refresh();

        $this->assertSame($snapshotRate, (string) $line->gold_rate_snapshot);
        $this->assertSame($snapshotValue, (string) $line->gold_value_snapshot);
    }

    public function test_postings_still_work_before_any_rate_exists(): void
    {
        $admin = $this->user();
        // A fund whose functional currency matches the treasury needs no conversion.
        $fund = $this->fund($admin, 'Toman Fund', ['accounting' => ['functional_currency' => 'IRT']]);
        $treasury = $this->treasury($fund, $admin, 'IRT', \App\Models\Treasury::TYPE_BANK, 'IRT Bank');

        $transaction = $this->contribute($admin->membershipFor($fund), $treasury, '5000000', $admin);

        $line = $transaction->journalEntry->lines->first();

        // The entry posts; only the gold valuation is absent, and it is left null
        // rather than guessed.
        $this->assertNull($line->gold_value_snapshot);
        $this->assertTrue(Decimal::of((string) $line->functional_debit)->equals('5000000'));
    }

    public function test_a_troy_ounce_quote_is_converted_and_its_source_kept(): void
    {
        $rates = app(RateService::class);
        $actor = $this->user('Rate Keeper');

        $rate = $rates->record('USD', null, Carbon::today(), $actor, Decimal::of('3000', Decimal::RATE_SCALE));

        $this->assertSame('72.338663', Decimal::of((string) $rate->units_per_gram_18k)->withScale(6)->toString());
        $this->assertTrue(Decimal::of((string) $rate->source_troy_ounce_24k)->equals('3000'));
    }
}
