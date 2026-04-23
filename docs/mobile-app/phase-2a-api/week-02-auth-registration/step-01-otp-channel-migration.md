# Step 1 — OTP Channel Migration (Add Email Support)

## Goal
Extend `otp_verifications` table so both phone and email OTPs live in the same table. Currently phone OTPs are DB-backed; email OTPs live in session (broken for stateless API).

## Prerequisites
- [ ] Week 1 acceptance ✅
- [ ] Current `otp_verifications` table schema understood (check migration + `App\Models\OtpVerification`)

## Procedure

### 1. Create migration

```bash
php artisan make:migration add_channel_to_otp_verifications_table
```

Edit the generated file:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->string('channel', 10)->default('phone')->after('otp_code');
            $table->string('destination')->nullable()->after('channel');
            $table->index(['channel', 'destination']);
        });

        // Backfill: destination = phone column value, channel = 'phone'
        DB::table('otp_verifications')->update([
            'channel' => 'phone',
            'destination' => DB::raw('phone'),
        ]);

        // After backfill, destination is required
        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->string('destination')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->dropIndex(['channel', 'destination']);
            $table->dropColumn(['channel', 'destination']);
        });
    }
};
```

### 2. Pre-migration backup

**This is a habit to establish:** every schema change backs up production DB first.

```bash
# In admin panel → System → Database Backup → click "Download SQL Backup"
# OR via tinker:
php artisan tinker
>>> Artisan::call('backup:run');  # if spatie/laravel-backup is configured
```

File the backup somewhere safe. Keep for at least 30 days.

### 3. Run migration locally

```bash
php artisan migrate
```

Verify:

```bash
php artisan tinker
>>> Schema::getColumnListing('otp_verifications');
# Should include: 'id', 'phone', 'otp_code', 'channel', 'destination', 'expires_at', 'verified_at', 'created_at', 'updated_at'

>>> DB::table('otp_verifications')->select('channel', 'destination', 'phone')->limit(3)->get();
# All existing rows: channel='phone', destination=phone value
```

### 4. Update model

Edit `app/Models/OtpVerification.php`. Add to `$fillable`:

```php
protected $fillable = [
    'phone',
    'otp_code',
    'channel',       // ADD
    'destination',   // ADD
    'expires_at',
    'verified_at',
];
```

### 5. Prepare for deployment

**Don't deploy this migration yet.** We deploy once at end of Phase 2a. For now, it lives on the branch.

Document the deploy step in your TODO list:
- End of Phase 2a: run migration, verify backfill, monitor OTP flows for 24h

## Verification

- [ ] Migration runs without errors locally
- [ ] Existing phone OTP records have `channel='phone'` and `destination=<phone value>`
- [ ] Index on `(channel, destination)` exists
- [ ] Model has new fields in `$fillable`
- [ ] Old web OTP flow still works (phone registration OTP via web)

## Common issues

| Issue | Fix |
|-------|-----|
| `update()` fails because `destination` is NOT NULL but new column | Run UPDATE before adding NOT NULL. See the two-step pattern in the migration above. |
| Fresh DB has no rows to backfill | Not a problem; UPDATE with empty table is a no-op |
| `phone` column doesn't exist | Check actual column name in current migration for `otp_verifications` — might be `mobile` or something else |

## Commit

```bash
git add database/migrations/ app/Models/OtpVerification.php
git commit -m "phase-2a wk-02: step-01 add channel+destination to otp_verifications"
```

## Next step
→ [step-02-otp-service-refactor.md](step-02-otp-service-refactor.md)
