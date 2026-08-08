<?php

/*
|--------------------------------------------------------------------------
| Fund-wide financial configuration
|--------------------------------------------------------------------------
|
| These are application-level constants and defaults. Anything that a group
| administrator is allowed to change lives in a versioned group policy
| (see App\Domain\Policies), never here.
|
*/

return [

    /*
     | The common valuation unit: one gram of 18K gold. This is an
     | application-defined unit and is deliberately NOT "XAU" (a troy ounce of
     | pure gold).
     */
    'gold_unit' => 'XAU18G',

    /*
     | Pure-gold content of 18K gold, and the troy ounce in grams. These are
     | mathematical constants, not exchange rates.
     */
    'gold' => [
        'purity_18k' => '0.75',
        'troy_ounce_grams' => '31.1034768',
    ],

    /*
     | Units that may carry a manually entered gold rate, plus every currency
     | the application accepts for treasuries, wallets and transactions.
     */
    'valuation_units' => ['XAU18G', 'USD', 'USDT', 'IRT'],

    'currencies' => [
        'USDT' => ['scale' => 6, 'label' => 'Tether USD'],
        'USD' => ['scale' => 2, 'label' => 'US Dollar'],
        'IRT' => ['scale' => 0, 'label' => 'Iranian Toman'],
        'XAU18G' => ['scale' => 8, 'label' => 'Gram of 18K gold'],
    ],

    'default_functional_currency' => env('DEFAULT_FUNCTIONAL_CURRENCY', 'IRT'),

    /*
     | Storage scales. Money is NUMERIC(30,8) and rates NUMERIC(30,12) in
     | PostgreSQL; PHP mirrors those scales with bcmath decimal strings.
     */
    'scale' => [
        'money' => 8,
        'rate' => 12,
        'gold' => 8,
    ],

    /*
     | Supported blockchain networks and their explorer URL templates.
     */
    'networks' => [
        'TRON' => [
            'label' => 'TRON (TRC-20)',
            'explorer_tx' => 'https://tronscan.org/#/transaction/:hash',
            'explorer_address' => 'https://tronscan.org/#/address/:address',
            'address_pattern' => '/^T[1-9A-HJ-NP-Za-km-z]{33}$/',
        ],
        'ETHEREUM' => [
            'label' => 'Ethereum (ERC-20)',
            'explorer_tx' => 'https://etherscan.io/tx/:hash',
            'explorer_address' => 'https://etherscan.io/address/:address',
            'address_pattern' => '/^0x[a-fA-F0-9]{40}$/',
        ],
        'BSC' => [
            'label' => 'BNB Smart Chain (BEP-20)',
            'explorer_tx' => 'https://bscscan.com/tx/:hash',
            'explorer_address' => 'https://bscscan.com/address/:address',
            'address_pattern' => '/^0x[a-fA-F0-9]{40}$/',
        ],
    ],

    /*
     | Blockchain verification driver: "manual" records the claimed hash and
     | leaves confirmation to an administrator; "trongrid" queries TronGrid.
     | Either way a transaction only becomes financially effective once it is
     | verified, and a (network, hash) pair can never be credited twice.
     */
    'blockchain' => [
        'verifier' => env('BLOCKCHAIN_VERIFIER', 'manual'),
        'min_confirmations' => (int) env('BLOCKCHAIN_MIN_CONFIRMATIONS', 19),
        'trongrid' => [
            'endpoint' => env('TRONGRID_ENDPOINT', 'https://api.trongrid.io'),
            'api_key' => env('TRONGRID_API_KEY'),
            'usdt_contract' => env('TRONGRID_USDT_CONTRACT', 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'),
        ],
    ],

    /*
     | Interest rate bounds enforced by the policy validator (percent).
     */
    'policy_bounds' => [
        'interest_rate_min' => '0',
        'interest_rate_max' => '100',
        'max_term_months' => 600,
    ],

    /*
     | Receipt uploads.
     */
    'receipts' => [
        'mimes' => ['jpeg', 'jpg', 'png', 'pdf'],
        'max_kilobytes' => 10240,
    ],

];
