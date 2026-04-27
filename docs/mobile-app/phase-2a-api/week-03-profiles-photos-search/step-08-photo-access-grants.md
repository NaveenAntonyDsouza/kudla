# Step 8 — `photo_access_grants` Table + `PhotoAccessService`

## Goal
New table to track per-viewer photo access (granted via approved photo requests or future share mechanisms). Used by `PhotoResource` to decide blur state.

## Prerequisites
- [ ] [step-07 — PhotoResource](step-07-photo-resource.md) complete

## Procedure

### 1. Migration

```bash
php artisan make:migration create_photo_access_grants_table
```

```php
public function up(): void
{
    Schema::create('photo_access_grants', function (Blueprint $t) {
        $t->id();
        $t->foreignId('grantor_profile_id')->constrained('profiles')->onDelete('cascade');
        $t->foreignId('grantee_profile_id')->constrained('profiles')->onDelete('cascade');
        $t->timestamp('granted_at')->useCurrent();
        $t->unique(['grantor_profile_id', 'grantee_profile_id']);
        $t->index('grantee_profile_id');
    });
}

public function down(): void
{
    Schema::dropIfExists('photo_access_grants');
}
```

Run: `php artisan migrate`.

### 2. Model

`app/Models/PhotoAccessGrant.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhotoAccessGrant extends Model
{
    protected $fillable = ['grantor_profile_id', 'grantee_profile_id', 'granted_at'];
    protected $casts = ['granted_at' => 'datetime'];
    public $timestamps = false;
}
```

### 3. `PhotoAccessService`

`app/Services/PhotoAccessService.php`:

```php
<?php

namespace App\Services;

use App\Models\PhotoAccessGrant;
use App\Models\Profile;

class PhotoAccessService
{
    public function grant(Profile $grantor, Profile $grantee): void
    {
        PhotoAccessGrant::updateOrCreate(
            ['grantor_profile_id' => $grantor->id, 'grantee_profile_id' => $grantee->id],
            ['granted_at' => now()],
        );
    }

    public function revoke(Profile $grantor, Profile $grantee): void
    {
        PhotoAccessGrant::where('grantor_profile_id', $grantor->id)
            ->where('grantee_profile_id', $grantee->id)
            ->delete();
    }

    public function hasAccess(Profile $grantor, Profile $grantee): bool
    {
        return PhotoAccessGrant::where('grantor_profile_id', $grantor->id)
            ->where('grantee_profile_id', $grantee->id)
            ->exists();
    }
}
```

## Verification

- [ ] Migration runs
- [ ] `grant()` and `hasAccess()` work via tinker
- [ ] Unique constraint prevents duplicates

## Commit

```bash
git add database/migrations/ app/Models/PhotoAccessGrant.php app/Services/PhotoAccessService.php
git commit -m "phase-2a wk-03: step-08 photo_access_grants table + PhotoAccessService"
```

## Next step
→ [step-09-photo-crud-endpoints.md](step-09-photo-crud-endpoints.md)
