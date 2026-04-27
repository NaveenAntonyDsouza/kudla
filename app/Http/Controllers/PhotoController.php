<?php

namespace App\Http\Controllers;

use App\Models\PhotoPrivacySetting;
use App\Models\ProfilePhoto;
use App\Models\SiteSetting;
use App\Services\ImageProcessingService;
use App\Services\PhotoStorageService;
use App\Services\WatermarkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
    public function __construct(
        private WatermarkService $watermarkService,
        private ImageProcessingService $imageProcessor,
        private PhotoStorageService $photoStorage,
    ) {}

    public function index()
    {
        $profile = auth()->user()->profile;
        $photos = $profile->profilePhotos()->orderBy('display_order')->get();

        $profilePhoto = $photos->where('photo_type', 'profile')->where('is_visible', true)->where('approval_status', 'approved')->first();
        $albumPhotos = $photos->where('photo_type', 'album')->where('is_visible', true)->where('approval_status', 'approved')->values();
        $familyPhotos = $photos->where('photo_type', 'family')->where('is_visible', true)->where('approval_status', 'approved')->values();
        $pendingPhotos = $photos->where('approval_status', 'pending')->values();
        $rejectedPhotos = $photos->where('approval_status', 'rejected')->values();
        $archivedPhotos = $photos->where('is_visible', false)->where('approval_status', '!=', 'rejected')->values();

        $privacy = $profile->photoPrivacySetting;

        return view('photos.manage', compact(
            'profile', 'profilePhoto', 'albumPhotos', 'familyPhotos',
            'pendingPhotos', 'rejectedPhotos', 'archivedPhotos', 'privacy'
        ));
    }

    public function upload(Request $request)
    {
        $profile = auth()->user()->profile;

        // Photo size limit is sourced from config so web + API + admin
        // settings stay aligned. See config/matrimony.php and the
        // matching API rule in app/Http/Requests/Api/V1/Photo/UploadPhotoRequest.php.
        $maxKilobytes = (int) config('matrimony.max_photo_size_mb', 5) * 1024;

        $request->validate([
            'photo' => "required|image|mimes:jpg,jpeg,png,gif,webp|max:{$maxKilobytes}",
            'photo_type' => 'required|in:profile,album,family',
        ]);

        $type = $request->input('photo_type');

        // Determine approval status based on site settings
        $settingKey = 'auto_approve_' . $type . '_photos';
        $autoApprove = SiteSetting::getValue($settingKey, '1') === '1';
        $approvalStatus = $autoApprove ? ProfilePhoto::STATUS_APPROVED : ProfilePhoto::STATUS_PENDING;

        // For profile type, archive existing before uploading new
        if ($type === 'profile') {
            $profile->profilePhotos()->visible()->ofType('profile')
                ->update(['is_visible' => false, 'is_primary' => false]);
        }

        // Check count limits (for album/family)
        if ($type !== 'profile') {
            $currentCount = $profile->profilePhotos()->visible()->approved()->ofType($type)->count();
            $max = ProfilePhoto::maxForType($type);
            if ($currentCount >= $max) {
                return back()->withErrors(['photo' => "Maximum {$max} {$type} photo(s) allowed."]);
            }
        }

        // Process upload — generates 3 size variants + preserves original.
        // Watermark (if enabled) is applied before resize so all variants carry it.
        // Storage driver picked per-upload from SiteSetting `active_storage_driver`.
        $folder = "photos/{$profile->id}";
        $activeDriver = $this->photoStorage->getActiveDriver();
        // Fallback to public if the selected driver isn't configured (safer than failing)
        if (!$this->photoStorage->isDriverConfigured($activeDriver)) {
            $activeDriver = PhotoStorageService::DRIVER_LOCAL;
        }
        $paths = $this->imageProcessor->processUpload($request->file('photo'), $folder, $activeDriver);

        // For profile type, clear any primary flag and set this as primary
        $isPrimary = false;
        if ($type === 'profile' && $autoApprove) {
            $profile->profilePhotos()->update(['is_primary' => false]);
            $isPrimary = true;
        }

        // Get next display order
        $nextOrder = $profile->profilePhotos()->ofType($type)->max('display_order') + 1;

        ProfilePhoto::create([
            'profile_id' => $profile->id,
            'photo_type' => $type,
            'photo_url' => $paths['full'],
            'thumbnail_url' => $paths['thumb'],
            'medium_url' => $paths['medium'],
            'original_url' => $paths['original'],
            'storage_driver' => $paths['driver'], // remember which disk this photo lives on
            'is_primary' => $isPrimary,
            'is_visible' => true,
            'display_order' => $nextOrder,
            'approval_status' => $approvalStatus,
            'approved_at' => $autoApprove ? now() : null,
        ]);

        $tab = in_array($type, ['album', 'family']) ? $type : 'album';

        $message = $autoApprove
            ? ucfirst($type) . ' photo uploaded successfully!'
            : ucfirst($type) . ' photo uploaded and sent for admin approval.';

        return redirect()->route('photos.manage', ['tab' => $request->input('tab', $tab)])
            ->with('success', $message);
    }

    public function destroy(ProfilePhoto $photo)
    {
        $this->authorizePhoto($photo);

        $photo->update(['is_visible' => false, 'is_primary' => false]);

        return back()->with('success', 'Photo archived.');
    }

    public function restore(ProfilePhoto $photo)
    {
        $this->authorizePhoto($photo);

        // Check if restoring would exceed limit
        $currentCount = $photo->profile->profilePhotos()->visible()->approved()->ofType($photo->photo_type)->count();
        $max = ProfilePhoto::maxForType($photo->photo_type);
        if ($currentCount >= $max) {
            return back()->withErrors(['photo' => "Cannot restore: maximum {$max} {$photo->photo_type} photo(s) reached."]);
        }

        $photo->update(['is_visible' => true]);

        // Auto-set as primary if no other photo is primary
        if (! $photo->profile->profilePhotos()->where('is_primary', true)->where('is_visible', true)->approved()->exists()) {
            $photo->update(['is_primary' => true]);
        }

        return back()->with('success', 'Photo restored.');
    }

    public function setPrimary(ProfilePhoto $photo)
    {
        $this->authorizePhoto($photo);

        if (! $photo->is_visible) {
            return back()->withErrors(['photo' => 'Cannot set archived photo as primary.']);
        }

        if (! $photo->isApproved()) {
            return back()->withErrors(['photo' => 'Cannot set unapproved photo as primary.']);
        }

        // Unset all primary, set this one
        $photo->profile->profilePhotos()->update(['is_primary' => false]);
        $photo->update(['is_primary' => true]);

        return back()->with('success', 'Profile photo updated.');
    }

    public function updatePrivacy(Request $request)
    {
        $profile = auth()->user()->profile;
        $levels = 'in:visible_to_all,interest_accepted,hidden';

        // Support BOTH legacy single-level + new per-type fields.
        $validated = $request->validate([
            'privacy_level' => "nullable|$levels",
            'profile_photo_privacy' => "nullable|$levels",
            'album_photos_privacy' => "nullable|$levels",
            'family_photos_privacy' => "nullable|$levels",
        ]);

        $payload = array_filter([
            'privacy_level' => $validated['privacy_level'] ?? null,
            'profile_photo_privacy' => $validated['profile_photo_privacy'] ?? null,
            'album_photos_privacy' => $validated['album_photos_privacy'] ?? null,
            'family_photos_privacy' => $validated['family_photos_privacy'] ?? null,
        ], fn ($v) => $v !== null);

        if (empty($payload)) {
            return back()->withErrors(['privacy' => 'No privacy changes submitted.']);
        }

        PhotoPrivacySetting::updateOrCreate(
            ['profile_id' => $profile->id],
            $payload
        );

        return back()->with('success', 'Privacy settings updated.');
    }

    public function deletePermanently(ProfilePhoto $photo)
    {
        $this->authorizePhoto($photo);

        // Delete all 4 variants from the driver where THIS photo lives
        // (different photos may be on different drivers in hybrid mode)
        $driver = $photo->storage_driver ?: 'public';
        $this->imageProcessor->deleteVariants($photo->getAllStoragePaths(), $driver);

        $photo->delete();

        return back()->with('success', 'Photo permanently deleted.');
    }

    private function authorizePhoto(ProfilePhoto $photo): void
    {
        if ($photo->profile_id !== auth()->user()->profile->id) {
            abort(403);
        }
    }
}
