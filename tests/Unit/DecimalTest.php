<?php

namespace Tests\Unit;

use App\Domain\ExchangeRates\GoldConverter;
use App\Domain\Money\Decimal;
use App\Domain\Money\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Money arithmetic. These are the guarantees every balance in the application
 * rests on.
 */
class DecimalTest extends TestCase
{
    public function test_it_adds_without_binary_floating_point_error(): void
    {
        $sum = Decimal::of('0.1')->plus('0.2');

        // The canonical reason floats are banned here: 0.1 + 0.2 != 0.3 in binary.
        $this->assertSame('0.30000000', $sum->toString());
        $this->assertTrue($sum->equals('0.3'));
    }

    public function test_it_refuses_a_float(): void
    {
        $this->expectException(\TypeError::class);

        // @phpstan-ignore-next-line — the point of the test is that this is refused.
        Decimal::of(0.1);
    }

    public function test_it_refuses_a_non_numeric_string(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Decimal::of('1,000');
    }

    public function test_parse_accepts_human_input_and_blank(): void
    {
        $this->assertSame('1000.00000000', Decimal::parse('1,000')->toString());
        $this->assertNull(Decimal::parse(''));
        $this->assertNull(Decimal::parse(null));
    }

    public function test_it_rounds_half_up_rather_than_truncating(): void
    {
        // bcmath truncates, which would bias every conversion towards zero.
        $this->assertSame('0.13', Decimal::of('0.125', 2)->toString());
        $this->assertSame('-0.13', Decimal::of('-0.125', 2)->toString());
    }

    public function test_division_keeps_precision_then_rounds_once(): void
    {
        $third = Decimal::of('1')->dividedBy('3');

        $this->assertSame('0.33333333', $third->toString());
        $this->assertSame('0.33', $third->withScale(2)->toString());
    }

    public function test_it_refuses_division_by_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Decimal::of('1')->dividedBy('0');
    }

    public function test_comparisons_ignore_trailing_zeros(): void
    {
        $this->assertTrue(Decimal::of('5')->equals('5.00000000'));
        $this->assertTrue(Decimal::of('5.01')->greaterThan('5'));
        $this->assertTrue(Decimal::of('-1')->isNegative());
        $this->assertTrue(Decimal::zero()->isZero());
    }

    public function test_it_formats_for_display_without_losing_the_stored_value(): void
    {
        $amount = Decimal::of('920000000');

        $this->assertSame('920,000,000', $amount->format(0));
        $this->assertSame('920000000.00000000', $amount->toString());
    }

    public function test_money_refuses_to_mix_currencies(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::of('1000', 'USDT')->plus(Money::of('920000000', 'IRT'));
    }

    public function test_money_adds_within_one_currency(): void
    {
        $total = Money::of('500', 'USDT')->plus(Money::of('100', 'USDT'));

        $this->assertSame('600.00000000', $total->amount->toString());
        $this->assertSame('USDT', $total->currency);
    }

    public function test_troy_ounce_of_24k_converts_to_the_18k_gram_reference(): void
    {
        // 1 troy oz 24K = 31.1034768 g pure = 41.4713024 g of 18K equivalent.
        $this->assertSame(
            '41.4713024000',
            GoldConverter::gram18kPerTroyOunce24k()->withScale(10)->toString()
        );

        // A market quote of 3,000 USD per troy ounce of 24K.
        $perGram18k = GoldConverter::troyOunce24kToGram18k(Decimal::of('3000', Decimal::RATE_SCALE));

        $this->assertSame('72.3387', $perGram18k->withScale(4)->toString());

        // And back again.
        $this->assertSame(
            '3000.0000',
            GoldConverter::gram18kToTroyOunce24k($perGram18k)->withScale(4)->toString()
        );
    }
}
