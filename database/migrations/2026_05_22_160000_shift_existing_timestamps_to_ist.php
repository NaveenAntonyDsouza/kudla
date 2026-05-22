<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One-time shift of every stored absolute-instant timestamp from UTC to
 * IST (+5:30), paired with config/app.php switching timezone from UTC to
 * Asia/Kolkata in the same deploy.
 *
 * WHY
 * ---
 * The app stored + displayed times in UTC. For a 100%-India deployment
 * the clean architecture is to run the whole app in IST (now(), Eloquent
 * casts, SQL date grouping, filters all become IST automatically with no
 * per-callsite conversion). Flipping app.timezone alone would misread the
 * existing UTC strings as IST (5.5h early), so the stored values must be
 * shifted +5:30 to preserve the exact instant each represents.
 *
 * WHAT SHIFTS
 * -----------
 * Every `timestamp`/`datetime` column = an absolute instant. All 161 such
 * columns (verified: 100% `timestamp` type) get +330 minutes.
 *
 * WHAT DOES NOT (and why it's correctly excluded by the type filter):
 *   - `date` columns (date_of_birth, subscriptions.starts_at/expires_at,
 *     follow_up_date, valid_from/until, wedding_date, staff_targets.month,
 *     outstation leave dates) — calendar dates, not instants. Shifting
 *     would be wrong.
 *   - INT unix-timestamp columns (sessions.last_activity, jobs.*,
 *     job_batches.created_at, cache.expiration) — absolute epoch seconds,
 *     timezone-independent.
 *
 * SAFETY
 * ------
 * - MySQL session tz is a stable SYSTEM and Laravel sets no connection tz,
 *   so DATE_ADD on these `timestamp` columns shifts the wall-clock value
 *   Laravel reads by exactly +5:30 (write + read + this UPDATE all share
 *   one session tz). IST/UTC have no DST, so no boundary edge cases.
 * - A site_settings marker (timezone_shifted_to_ist) guards against a
 *   double-apply (which would be +11h). The migration framework already
 *   prevents re-running; the marker protects against a manual re-invoke.
 * - Wrapped in a transaction. Take a full DB backup before deploying
 *   (done in the deploy runbook).
 * - Existing Sanctum tokens keep working (token hash unchanged; only
 *   created/last_used/expires shift, preserving their relative ages).
 *
 * Today's earlier display-conversion code (FilamentTimezone::set,
 * the ->displayTz() Carbon macro, ->timezone(config('app.display_timezone'))
 * calls) all become harmless identities once app.timezone == display tz
 * == Asia/Kolkata, so nothing needs to be reverted.
 */
return new class extends Migration
{
    /** UTC -> IST offset in minutes. */
    private const SHIFT_MINUTES = 330;

    /** site_settings marker key — prevents a catastrophic double-shift. */
    private const MARKER_KEY = 'timezone_shifted_to_ist';

    public function up(): void
    {
        if ($this->alreadyShifted()) {
            return;
        }

        $byTable = $this->instantColumnsByTable();

        DB::transaction(function () use ($byTable) {
            foreach ($byTable as $table => $cols) {
                $this->shiftTable($table, $cols, self::SHIFT_MINUTES);
            }

            // Insert the marker AFTER the shift loop so it isn't itself
            // shifted; created with the (now IST) current time.
            DB::table('site_settings')->updateOrInsert(
                ['key' => self::MARKER_KEY],
                ['value' => '1', 'created_at' => now(), 'updated_at' => now()],
            );
        });
    }

    public function down(): void
    {
        // Only reverse if we actually shifted (marker present).
        if (! $this->alreadyShifted()) {
            return;
        }

        $byTable = $this->instantColumnsByTable();

        DB::transaction(function () use ($byTable) {
            foreach ($byTable as $table => $cols) {
                $this->shiftTable($table, $cols, -self::SHIFT_MINUTES);
            }

            DB::table('site_settings')->where('key', self::MARKER_KEY)->delete();
        });
    }

    private function alreadyShifted(): bool
    {
        return DB::table('site_settings')->where('key', self::MARKER_KEY)->exists();
    }

    /**
     * @return array<string, array<int, string>>  table => [datetime/timestamp columns]
     */
    private function instantColumnsByTable(): array
    {
        $columns = DB::select(
            'SELECT TABLE_NAME, COLUMN_NAME
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = ?
               AND DATA_TYPE IN (?, ?)
             ORDER BY TABLE_NAME, COLUMN_NAME',
            [DB::getDatabaseName(), 'datetime', 'timestamp'],
        );

        $byTable = [];
        foreach ($columns as $col) {
            $byTable[$col->TABLE_NAME][] = $col->COLUMN_NAME;
        }

        return $byTable;
    }

    /**
     * Shift all of one table's instant columns by $minutes in a single
     * UPDATE. Negative $minutes reverses (down()).
     *
     * @param  array<int, string>  $cols
     */
    private function shiftTable(string $table, array $cols, int $minutes): void
    {
        $fn = $minutes >= 0 ? 'DATE_ADD' : 'DATE_SUB';
        $abs = abs($minutes);

        $sets = [];
        $notNull = [];
        foreach ($cols as $c) {
            $sets[] = "`{$c}` = {$fn}(`{$c}`, INTERVAL {$abs} MINUTE)";
            $notNull[] = "`{$c}` IS NOT NULL";
        }

        DB::statement(
            "UPDATE `{$table}` SET ".implode(', ', $sets)
            .' WHERE '.implode(' OR ', $notNull)
        );
    }
};
