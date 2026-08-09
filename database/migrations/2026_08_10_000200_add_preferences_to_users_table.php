<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Display/convenience preferences only. None of these affect business
        // logic: financial dates stay on config('app.timezone'), a fund's real
        // currency is whatever its own records say. See .ai/PROJECT.md.
        Schema::table('users', function (Blueprint $table) {
            $table->string('preferred_locale')->nullable()->after('system_role');
            $table->string('preferred_currency')->nullable()->after('preferred_locale');
            $table->string('timezone')->nullable()->after('preferred_currency');
            $table->jsonb('weekend_days')->nullable()->after('timezone');
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_preferred_locale_check CHECK (preferred_locale IN ('en','fa'))");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_preferred_currency_check CHECK (preferred_currency IN ('USDT','USD','IRT','XAU18G'))");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['preferred_locale', 'preferred_currency', 'timezone', 'weekend_days']);
        });
    }
};
