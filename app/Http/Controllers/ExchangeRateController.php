<?php

namespace App\Http\Controllers;

use App\Domain\ExchangeRates\GoldConverter;
use App\Domain\ExchangeRates\RateService;
use App\Domain\Money\Decimal;
use App\Models\ExchangeRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Exchange rates are global application data: they carry no group_id and are
 * shared by every fund.
 */
class ExchangeRateController extends Controller
{
    public function index(Request $request, RateService $rates): View
    {
        $date = $request->query('date') ? Carbon::parse($request->query('date')) : Carbon::today();

        return view('exchange-rates.index', [
            'date' => $date,
            'quotes' => $rates->currentQuotes($date),
            'units' => $rates->quotableUnits(),
            'goldUnit' => $rates->goldUnit(),
            'recent' => ExchangeRate::query()
                ->with('creator')
                ->orderByDesc('effective_date')
                ->orderByDesc('id')
                ->limit(50)
                ->get(),
            'gramsPerTroyOunce' => GoldConverter::gram18kPerTroyOunce24k(),
            'canManage' => $request->user()->can('manage-exchange-rates'),
        ]);
    }

    public function store(Request $request, RateService $rates): RedirectResponse
    {
        $this->authorize('manage-exchange-rates');

        $data = $request->validate([
            'unit' => ['required', 'string', 'in:'.implode(',', $rates->quotableUnits())],
            'units_per_gram_18k' => ['nullable', 'string', 'regex:/^\d+(\.\d+)?$/'],
            'troy_ounce_24k' => ['nullable', 'string', 'regex:/^\d+(\.\d+)?$/'],
            'effective_date' => ['required', 'date'],
            'source_note' => ['nullable', 'string', 'max:200'],
        ]);

        try {
            $rates->record(
                $data['unit'],
                Decimal::parse($data['units_per_gram_18k'] ?? null, Decimal::RATE_SCALE),
                Carbon::parse($data['effective_date']),
                $request->user(),
                Decimal::parse($data['troy_ounce_24k'] ?? null, Decimal::RATE_SCALE),
                $data['source_note'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['units_per_gram_18k' => $e->getMessage()]);
        }

        return back()->with('status', 'Rate recorded.');
    }
}
