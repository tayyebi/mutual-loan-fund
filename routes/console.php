<?php

use Illuminate\Support\Facades\Schedule;

/*
| Migrations already run once when the app container boots (see
| docker/php/entrypoint.sh). This is the safety net for the common deploy
| path — `git pull` without a container restart — so a migration that shipped
| with the pulled code is still picked up on its own, without anyone having to
| remember to restart anything. migrate is a no-op when nothing is pending, so
| running it on a schedule costs nothing the rest of the time.
*/
// env() already casts the string "true"/"false" to a real bool, matching the
// same AUTO_MIGRATE flag entrypoint.sh checks for the boot-time migration.
if (env('AUTO_MIGRATE', true)) {
    Schedule::command('migrate --force')
        ->everyFiveMinutes()
        ->withoutOverlapping()
        ->onOneServer();
}
