<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Support\Analytics;
use App\Traits\ProfileQueryFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    use ProfileQueryFilters;

    /**
     * The members' search page. Since Oct 2026 it lives at the same addresses
     * as the visitors' (/search/advanced-search, /keyword-search, /id-search):
     * publicSearch() hands members over here with the tab.
     */
    public function index(Request $request, ?string $tab = null, bool $showResults = false)
    {
        $user = auth()->user();
        $profile = $user->profile;
        $prefs = $profile->partnerPreference;

        $defaults = self::memberSearchDefaults($profile);

        $activeTab = $tab ?? $request->get('tab', 'partner');

        // Load partner preferences into search form
        if ($request->boolean('load_prefs') && $prefs) {
            $mapped = $this->prefsToSearchParams($prefs);
            $request->merge($mapped);

            // Override defaults for age/height selects
            if ($prefs->age_from) $defaults['age_from'] = $prefs->age_from;
            if ($prefs->age_to) $defaults['age_to'] = $prefs->age_to;
            if ($prefs->height_from_cm) $defaults['height_from'] = $prefs->height_from_cm;
            if ($prefs->height_to_cm) $defaults['height_to'] = $prefs->height_to_cm;
        }

        // If search was submitted, show results page (at /search/results since Oct 2026)
        if ($request->has('search') || ($showResults && ! $request->filled('keyword'))) {
            $query = $this->buildSearchQuery($request, $profile);
            $query = $this->applySortOrder($query, $request->get('sort', 'relevance'));
            $results = $query->paginate(20)->withQueryString();
            $currentSort = $request->get('sort', 'relevance');
            $this->trackSearch('filters', $request, $results);
            return view('search.results', compact('profile', 'results', 'currentSort'));
        }

        // Keyword search
        if ($request->filled('keyword')) {
            $keyword = (string) $request->keyword;
            $query = self::applyKeyword($this->baseQuery($profile), $keyword);
            $searchNote = null;
            // Nothing has every word? Show profiles that match any of them, and say so.
            if (count(self::keywordWords($keyword)) > 1 && (clone $query)->count() === 0) {
                $query = self::applyKeyword($this->baseQuery($profile), $keyword, matchAll: false);
                $searchNote = 'No profile matched all your words, so these match at least one of them.';
            }
            $query = $this->applySortOrder($query, $request->get('sort', 'relevance'));
            $results = $query->paginate(20)->withQueryString();
            $currentSort = $request->get('sort', 'relevance');
            $this->trackSearch('keyword', $request, $results);
            return view('search.results', compact('profile', 'results', 'currentSort', 'searchNote'));
        }

        // Search by ID
        $idResult = null;
        if ($request->filled('matri_id')) {
            // a search for one Matri ID shows it even when it's the same gotra / bari
            $idResult = $this->baseQuery($profile, respectLineagePreference: false)
                ->where('matri_id', self::matriIdFromInput((string) $request->matri_id))
                ->first();
            Analytics::track('search_performed', ['search_type' => 'id', 'results' => $idResult ? 1 : 0]);
        }

        return view('search.index', compact(
            'profile', 'prefs', 'defaults', 'idResult', 'activeTab'
        ));
    }

    private function buildSearchQuery(Request $request, Profile $profile)
    {
        $query = $this->baseQuery($profile);

        // Age filter
        $query->when($request->age_from, fn($q, $v) =>
            $q->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= ?', [(int) $v])
        );
        $query->when($request->age_to, fn($q, $v) =>
            $q->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) <= ?', [(int) $v])
        );

        // Height filter (compare by the cm prefix number)
        $query->when($request->height_from, function ($q, $v) {
            $cm = (int) $v;
            if ($cm > 0) {
                $q->whereRaw('CAST(height AS UNSIGNED) >= ?', [$cm]);
            }
        });
        $query->when($request->height_to, function ($q, $v) {
            $cm = (int) $v;
            if ($cm > 0) {
                $q->whereRaw('CAST(height AS UNSIGNED) <= ?', [$cm]);
            }
        });

        // Direct profile field filters
        // every spelling of the chosen status (Divorced = Divorcee, Widower = Widow/Widower)
        $query->when($request->marital_status, fn($q, $v) =>
            $q->whereIn('marital_status', \App\Support\MaritalStatus::expand((array) $v))
        );
        $query->when($request->mother_tongue, fn($q, $v) =>
            $q->whereIn('mother_tongue', array_filter((array) $v, fn($i) => $i !== 'Any'))
        );
        $query->when($request->body_type, fn($q, $v) =>
            $q->whereIn('body_type', array_filter((array) $v, fn($i) => $i !== 'Any'))
        );
        $query->when($request->physical_status, fn($q, $v) =>
            $q->whereIn('physical_status', array_filter((array) $v, fn($i) => $i !== 'Any'))
        );

        // Religion filters (with cascading)
        $religions = array_filter((array) ($request->religion ?? []), fn($i) => $i !== 'Any');
        if (!empty($religions)) {
            $query->whereHas('religiousInfo', function ($q) use ($request, $religions) {
                $q->whereIn('religion', $religions);

                $denominations = array_filter((array) ($request->denomination ?? []), fn($i) => $i !== 'Any');
                if (!empty($denominations)) {
                    // Match either the canonical denomination column OR a
                    // typed value stored via the Other → Specify pattern.
                    $q->where(function ($sq) use ($denominations) {
                        $sq->whereIn('denomination', $denominations)
                           ->orWhereIn('other_denomination_name', $denominations);
                    });
                }

                $castes = array_filter((array) ($request->caste ?? []), fn($i) => $i !== 'Any');
                if (!empty($castes)) {
                    $q->where(function ($sq) use ($castes) {
                        $sq->whereIn('caste', $castes)
                           ->orWhereIn('other_caste_name', $castes);
                    });
                }
            });
        }

        // Education filter
        $education = array_filter((array) ($request->education ?? []), fn($i) => $i !== 'Any');
        if (!empty($education)) {
            $query->whereHas('educationDetail', fn($q) =>
                $q->whereIn('highest_education', $education)
            );
        }

        // Occupation filter
        $occupation = array_filter((array) ($request->occupation ?? []), fn($i) => $i !== 'Any');
        if (!empty($occupation)) {
            $query->whereHas('educationDetail', fn($q) =>
                $q->whereIn('occupation', $occupation)
            );
        }

        // Income filter
        $income = array_filter((array) ($request->annual_income ?? []), fn($i) => $i !== 'Any');
        if (!empty($income)) {
            $query->whereHas('educationDetail', fn($q) =>
                $q->whereIn('annual_income', $income)
            );
        }

        // Location filters
        $query->when($request->working_country, fn($q, $v) =>
            $q->whereHas('educationDetail', fn($q2) => $q2->where('working_country', $v))
        );
        $query->when($request->working_state, fn($q, $v) =>
            $q->whereHas('educationDetail', fn($q2) => $q2->where('working_state', $v))
        );
        $query->when($request->working_district, fn($q, $v) =>
            $q->whereHas('educationDetail', fn($q2) => $q2->where('working_district', $v))
        );
        $query->when($request->native_country, fn($q, $v) =>
            $q->whereHas('locationInfo', fn($q2) => $q2->where('native_country', $v))
        );
        $query->when($request->native_state, fn($q, $v) =>
            $q->whereHas('locationInfo', fn($q2) => $q2->where('native_state', $v))
        );
        $query->when($request->native_district, fn($q, $v) =>
            $q->whereHas('locationInfo', fn($q2) => $q2->where('native_district', $v))
        );

        // Family status
        $familyStatus = array_filter((array) ($request->family_status ?? []), fn($i) => $i !== 'Any');
        if (!empty($familyStatus)) {
            $query->whereHas('familyDetail', fn($q) =>
                $q->whereIn('family_status', $familyStatus)
            );
        }

        // Lifestyle filters
        $query->when($request->diet, function ($q, $v) {
            $filtered = array_filter((array) $v, fn($i) => $i !== 'Any');
            if (!empty($filtered)) {
                $q->whereHas('lifestyleInfo', fn($q2) => $q2->whereIn('diet', $filtered));
            }
        });
        $query->when($request->smoking, function ($q, $v) {
            $filtered = array_filter((array) $v, fn($i) => $i !== 'Any');
            if (!empty($filtered)) {
                $q->whereHas('lifestyleInfo', fn($q2) => $q2->whereIn('smoking', $filtered));
            }
        });
        $query->when($request->drinking, function ($q, $v) {
            $filtered = array_filter((array) $v, fn($i) => $i !== 'Any');
            if (!empty($filtered)) {
                $q->whereHas('lifestyleInfo', fn($q2) => $q2->whereIn('drinking', $filtered));
            }
        });

        // With photo only
        if ($request->boolean('with_photo')) {
            $query->whereHas('primaryPhoto');
        }

        return $query;
    }

    /**
     * Apply sort order to query.
     * "relevance" (default) = Profile::scopeDefaultOrder(): paid boosts → finished → photo → recently active → newest
     * Uses subqueries instead of JOINs to avoid ambiguous column issues with baseQuery.
     */
    /**
     * Where a members' search with these parameters lives now: keyword →
     * Keyword Search, Matri ID → Search by ID, filters → Advanced Search results.
     */
    public static function memberSearchDefaults(Profile $profile): array
    {
        // Starting age / height range from the member's own (Advanced and Quick Search)
        $isMale = $profile->gender === 'male' || $profile->gender === 'Male';
        $age = $profile->age ?? 25;

        return [
            'age_from' => $isMale ? 18 : $age,
            'age_to' => $isMale ? $age : min($age + 10, 70),
            'height_from' => $isMale ? '134 cm - 4 ft 05 inch' : ($profile->height ?? '134 cm - 4 ft 05 inch'),
            'height_to' => $isMale ? ($profile->height ?? '213 cm - 7 ft 00 inch') : '213 cm - 7 ft 00 inch',
        ];
    }

    public static function memberSearchUrl(array $query): string
    {
        $tab = $query['tab'] ?? null;
        $hadSearch = array_key_exists('search', $query);
        unset($query['tab'], $query['search']);
        if (filled($query['matri_id'] ?? null) || $tab === 'byid') {
            return route('search.byid', $query);
        }
        if (filled($query['keyword'] ?? null)) {
            return route('search.results.public', $query);
        }
        if ($tab === 'keyword') {
            return route('search.keyword');
        }
        // a search with filters → the results address; otherwise the form (e.g. load_prefs)
        $filters = array_diff_key($query, array_flip(['page', 'sort', 'load_prefs']));

        return $hadSearch || $filters ? route('search.results.public', $query) : route('search.advance', $query);
    }

    /**
     * /search (the members' old address): no parameters → Quick Search for
     * everyone (owner, Oct 2026); an old link with a search in it → the page
     * that now has it, permanently.
     */
    public function legacy(Request $request)
    {
        if (! $request->query()) {
            return redirect()->route('search.quick');
        }

        return redirect(self::memberSearchUrl($request->query()), 301);
    }

    /**
     * "Save this search" for a visitor: log in (or register), then come back to
     * the same search as a member, where the Save button is.
     */
    public function saveForLater(Request $request)
    {
        $request->session()->put('url.intended', self::memberSearchUrl($request->query()));

        return redirect()->route('login');
    }

    /** Relevance (default), newest, recently active — for members and visitors alike (no age sorts). */
    private function applySortOrder(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'newest' => $query->completedFirst()->orderBy('profiles.created_at', 'desc'),

            'recently_active' => $query->completedFirst()
                ->orderByRaw('(SELECT last_login_at FROM users WHERE users.id = profiles.user_id) IS NULL ASC')
                ->orderByRaw('(SELECT last_login_at FROM users WHERE users.id = profiles.user_id) DESC'),

            // Default: paid boosts → finished → with photo → recently active → newest
            // (Profile::scopeDefaultOrder — one definition for every listing)
            default => $query->defaultOrder(),
        };
    }

    /**
     * Map PartnerPreference fields to search form parameter names.
     */
    /**
     * "search_performed" for analytics (GA4 + PostHog): which filters were
     * used and how many results — never the values or the keyword typed.
     * First page only, so paging through results isn't counted again.
     */
    private function trackSearch(string $type, Request $request, $results): void
    {
        if ($results->currentPage() !== 1) {
            return;
        }

        $filters = collect($request->except(['search', 'sort', 'page', 'tab', '_token', 'keyword']))
            ->filter(fn ($v) => filled($v) && $v !== 'Any' && $v !== ['Any'])
            ->keys()->sort()->values();

        Analytics::track('search_performed', array_filter([
            'search_type' => $type,
            'tab' => $type === 'filters' ? $request->get('tab', 'partner') : null,
            'filters' => $filters->implode(','),
            'filter_count' => $filters->count(),
            'results' => $results->total(),
            'sort' => $request->get('sort', 'relevance'),
        ], fn ($v) => $v !== null && $v !== ''));
    }

    private function prefsToSearchParams($prefs): array
    {
        $params = [];

        // Multi-select arrays → search form arrays
        if ($prefs->religions) $params['religion'] = $prefs->religions;
        if ($prefs->denomination) $params['denomination'] = $prefs->denomination;
        if ($prefs->caste) $params['caste'] = $prefs->caste;
        if ($prefs->marital_status) $params['marital_status'] = $prefs->marital_status;
        if ($prefs->mother_tongues) $params['mother_tongue'] = $prefs->mother_tongues;
        if ($prefs->education_levels || $prefs->educational_qualifications) $params['education'] = array_values(array_unique(array_merge((array) $prefs->education_levels, (array) $prefs->educational_qualifications)));
        if ($prefs->occupations) $params['occupation'] = $prefs->occupations;
        if ($prefs->diet) $params['diet'] = $prefs->diet;
        if ($prefs->family_status) $params['family_status'] = $prefs->family_status;
        if ($prefs->physical_status) $params['physical_status'] = $prefs->physical_status;
        if ($prefs->body_type) $params['body_type'] = $prefs->body_type;

        // Single-value selects
        if ($prefs->working_countries) {
            $params['working_country'] = $prefs->working_countries[0] ?? null;
        }
        if ($prefs->working_states) {
            $params['working_state'] = $prefs->working_states[0] ?? null;
        }
        if ($prefs->working_districts) {
            $params['working_district'] = $prefs->working_districts[0] ?? null;
        }
        if ($prefs->native_countries) {
            $params['native_country'] = $prefs->native_countries[0] ?? null;
        }

        return $params;
    }

    /**
     * Public search page — accessible without login (for SEO + staff sharing).
     * Logged-in users get redirected to the full search page.
     * Non-logged-in users see profile cards with "Login to view" badges.
     */
    public function publicSearch(string $tab)
    {
        // One address for visitors and members (Oct 2026). Members: Quick Search
        // shows the same short form (its results open as member results); the
        // other three show the members' search page right here.
        $member = auth()->check() && auth()->user()->profile;
        if ($member && $tab !== 'partner') {
            // a link that already holds a search (old links, saved searches) → the results address
            if (request()->has('search') || ($tab === 'keyword' && request()->filled('keyword'))) {
                return redirect()->route('search.results.public', request()->except(['search', 'tab']));
            }

            return $this->index(request(), ['keyword' => 'keyword', 'byid' => 'byid'][$tab] ?? 'partner');
        }
        // A visitor opening a member's results link (shared, or after logging out)
        if (! $member && request()->has('search')) {
            return redirect()->route('search.results.public', request()->except(['search', 'tab']));
        }

        $activeTab = $tab;
        // Search by ID shows its one profile right here, as for members (Oct 2026)
        $idResult = $tab === 'byid' && request()->filled('matri_id')
            ? $this->visitorBaseQuery()->where('matri_id', self::matriIdFromInput((string) request('matri_id')))->first()
            : null;
        // Quick / Advanced Search start members at their own range, visitors at every age (Oct 2026)
        $quickDefaults = $member
            ? self::memberSearchDefaults(auth()->user()->profile)
            : ['age_from' => \App\Support\MinimumAge::lowest(), 'age_to' => 70, 'height_from' => '', 'height_to' => ''];

        return view('search.public', compact('activeTab', 'quickDefaults', 'idResult'));
    }

    /**
     * Profiles a visitor may find. Visitors are never premium and have no
     * religion, denomination or mother tongue on record, so profiles shown
     * only to premium members or only to their own community stay hidden.
     */
    private function visitorBaseQuery(): Builder
    {
        return Profile::where('is_active', true)
            ->approved()
            ->where(fn($q) => $q->where('is_hidden', false)->orWhereNull('is_hidden'))
            ->where(fn($q) => $q->where('show_profile_to', '!=', 'premium')->orWhereNull('show_profile_to'))
            ->where(fn($q) => $q->where('only_same_religion', false)->orWhereNull('only_same_religion'))
            ->where(fn($q) => $q->where('only_same_denomination', false)->orWhereNull('only_same_denomination'))
            ->where(fn($q) => $q->where('only_same_mother_tongue', false)->orWhereNull('only_same_mother_tongue'))
            ->with(['primaryPhoto', 'religiousInfo', 'educationDetail', 'locationInfo']);
    }

    /** A Matri ID typed any way ("km 100123", "KM-100123") → "KM100123". */
    public static function matriIdFromInput(string $input): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $input));
    }

    /**
     * Read a possibly-multi (array) public-search filter value.
     * Returns the cleaned list of chosen values, or null when the filter
     * should be skipped entirely — i.e. "Any" was picked, or nothing was.
     * Tolerates legacy single-string params (?caste=Brahmin) via the cast.
     */
    private function multiParam(string $key): ?array
    {
        $raw = (array) request($key, []);
        if (in_array('Any', $raw, true)) {
            return null;
        }
        $vals = array_values(array_filter($raw, fn($v) => $v !== ''));

        return empty($vals) ? null : $vals;
    }

    /**
     * Public search results page (separate from form).
     */
    public function publicResults()
    {
        // A Matri ID search shows its one profile on the Search by ID page (Oct 2026)
        if (request()->filled('matri_id')) {
            return redirect()->route('search.byid', ['matri_id' => request('matri_id')]);
        }
        // Members get their results at this same address (Oct 2026)
        if (auth()->check() && auth()->user()->profile) {
            return $this->index(request(), request()->filled('keyword') ? 'keyword' : 'partner', showResults: true);
        }

        $query = $this->visitorBaseQuery();

        // Apply filters from query params. Caste/denomination are multi-select
        // aware ("Any"/empty = no filter) and also match the "Other → Specify"
        // typed value stored in the other_*_name columns.
        if ($castes = $this->multiParam('caste')) {
            $query->whereHas('religiousInfo', fn($q) => $q->where(fn($sq) =>
                $sq->whereIn('caste', $castes)->orWhereIn('other_caste_name', $castes)
            ));
        }
        if ($denoms = $this->multiParam('denomination')) {
            $query->whereHas('religiousInfo', fn($q) => $q->where(fn($sq) =>
                $sq->whereIn('denomination', $denoms)->orWhereIn('other_denomination_name', $denoms)
            ));
        }
        if ($religion = request('religion')) {
            if (is_array($religion)) {
                $query->whereHas('religiousInfo', fn($q) => $q->whereIn('religion', $religion));
            } else {
                $query->whereHas('religiousInfo', fn($q) => $q->where('religion', $religion));
            }
        }
        if ($gender = request('gender')) {
            $query->where('gender', $gender);
        }
        if ($ageFrom = request('age_from')) {
            $query->whereDate('date_of_birth', '<=', now()->subYears((int) $ageFrom));
        }
        if ($ageTo = request('age_to')) {
            $query->whereDate('date_of_birth', '>=', now()->subYears((int) $ageTo + 1));
        }
        // Mother tongue lives on the profiles table (cf. buildSearchQuery), not
        // lifestyle_info — the old whereHas('lifestyleInfo', ...) hit a missing column.
        if ($tongues = $this->multiParam('mother_tongue')) {
            $query->whereIn('mother_tongue', $tongues);
        }
        // the keyword is applied after every other filter (below), so the
        // "any word" fallback keeps those filters
        $keyword = trim((string) request('keyword', ''));
        if ($maritals = $this->multiParam('marital_status')) {
            $query->whereIn('marital_status', \App\Support\MaritalStatus::expand($maritals));
        }
        if ($education = $this->multiParam('education')) {
            // a qualification (B.Sc) or a level (Bachelor's) — preferences hold both
            $query->whereHas('educationDetail', fn($q) => $q->where(fn($w) => $w->whereIn('highest_education', $education)->orWhereIn('education_level', $education)));
        }
        if ($occupation = $this->multiParam('occupation')) {
            $query->whereHas('educationDetail', fn($q) => $q->whereIn('occupation', $occupation));
        }
        if ($workingCountries = $this->multiParam('working_country')) {
            $query->whereHas('educationDetail', fn($q) => $q->whereIn('working_country', $workingCountries));
        }
        if ($workingState = request('working_state')) {
            $query->whereHas('educationDetail', fn($q) => $q->where('working_state', $workingState));
        }
        if ($workingDistrict = request('working_district')) {
            $query->whereHas('educationDetail', fn($q) => $q->where('working_district', $workingDistrict));
        }
        if ($nativeCountry = request('native_country')) {
            $query->whereHas('locationInfo', fn($q) => $q->where('native_country', $nativeCountry));
        }
        if ($nativeState = request('native_state')) {
            $query->whereHas('locationInfo', fn($q) => $q->where('native_state', $nativeState));
        }
        if ($nativeDistrict = request('native_district')) {
            $query->whereHas('locationInfo', fn($q) => $q->where('native_district', $nativeDistrict));
        }
        if ($heightFrom = request('height_from')) {
            $query->whereRaw('CAST(height AS UNSIGNED) >= ?', [(int) $heightFrom]);
        }
        if ($heightTo = request('height_to')) {
            $query->whereRaw('CAST(height AS UNSIGNED) <= ?', [(int) $heightTo]);
        }

        // "Add More Criteria" filters (Advance Search) — all multi-select.
        // Mirrors the logged-in buildSearchQuery: body_type/physical_status are
        // columns on profiles; the rest live on related tables.
        if ($bodyType = $this->multiParam('body_type')) {
            $query->whereIn('body_type', $bodyType);
        }
        if ($physicalStatus = $this->multiParam('physical_status')) {
            $query->whereIn('physical_status', $physicalStatus);
        }
        if ($familyStatus = $this->multiParam('family_status')) {
            $query->whereHas('familyDetail', fn($q) => $q->whereIn('family_status', $familyStatus));
        }
        if ($income = $this->multiParam('annual_income')) {
            $query->whereHas('educationDetail', fn($q) => $q->whereIn('annual_income', $income));
        }
        if ($diet = $this->multiParam('diet')) {
            $query->whereHas('lifestyleInfo', fn($q) => $q->whereIn('diet', $diet));
        }
        if ($smoking = $this->multiParam('smoking')) {
            $query->whereHas('lifestyleInfo', fn($q) => $q->whereIn('smoking', $smoking));
        }
        if ($drinking = $this->multiParam('drinking')) {
            $query->whereHas('lifestyleInfo', fn($q) => $q->whereIn('drinking', $drinking));
        }
        // With photo only (Oct 2026): for a visitor that means a main photo they can
        // actually see — privacy "visible to all", the default when none is set
        // (same rule as App\Support\PhotoVisibility for guests)
        if (request()->boolean('with_photo')) {
            $query->whereHas('primaryPhoto')->where(fn ($q) => $q
                ->whereDoesntHave('photoPrivacySetting')
                ->orWhereHas('photoPrivacySetting', fn ($p) => $p->whereRaw(
                    "COALESCE(NULLIF(profile_photo_privacy, ''), NULLIF(privacy_level, ''), 'visible_to_all') = 'visible_to_all'"
                )));
        }

        // Keyword, as for members: every word must match; if nothing does, show
        // profiles matching any of them and say so (Oct 2026)
        $searchNote = null;
        if ($keyword !== '') {
            $allWords = self::applyKeyword(clone $query, $keyword);
            if (count(self::keywordWords($keyword)) > 1 && (clone $allWords)->count() === 0) {
                $query = self::applyKeyword($query, $keyword, matchAll: false);
                $searchNote = 'No profile matched all your words, so these match at least one of them.';
            } else {
                $query = $allWords;
            }
        }

        $currentSort = in_array(request('sort'), ['newest', 'recently_active'], true) ? request('sort') : 'relevance';
        $results = $this->applySortOrder($query, $currentSort)->paginate(20)->withQueryString();

        // Pick a single human label for the results header, tolerating
        // array (multi-select) values for caste/denomination/religion.
        $firstVal = function ($v) {
            foreach ((array) $v as $item) {
                if ($item !== '' && $item !== 'Any') {
                    return $item;
                }
            }
            return null;
        };
        $filterLabel = $firstVal(request('caste')) ?? $firstVal(request('denomination')) ?? $firstVal(request('religion')) ?? null;

        // Determine which search form to link back to
        $searchType = request('search_type', 'quick');
        $modifySearchUrl = match ($searchType) {
            'advance' => route('search.advance', request()->except(['page', 'search_type'])),
            'keyword' => route('search.keyword', request()->except(['page', 'search_type'])),
            'byid' => route('search.byid', request()->except(['page', 'search_type'])),
            default => route('search.quick', request()->except(['page', 'search_type'])),
        };

        return view('search.public-results', compact('results', 'filterLabel', 'modifySearchUrl', 'currentSort', 'searchNote'));
    }

    /**
     * Keyword search (Oct 2026: 46% of typed searches found nothing).
     * - A Matri ID typed any way ("km 100123", "KM-100123") finds that profile.
     * - Several words: every word must match some field (name, caste, place,
     *   job, education...), in any order; or any word, for the fallback.
     */
    public static function applyKeyword(Builder $query, string $keyword, bool $matchAll = true): Builder
    {
        $compact = strtoupper(preg_replace('/[\s\-_.]+/', '', trim($keyword)));
        if (preg_match('/^[A-Z]{2,4}\d{3,}$/', $compact)) {
            return $query->where('matri_id', 'like', '%' . $compact . '%');
        }

        $words = self::keywordWords($keyword);
        if ($words === []) {
            return $query;
        }

        return $query->where(function ($outer) use ($words, $matchAll) {
            foreach ($words as $word) {
                $like = '%' . addcslashes($word, '%_\\') . '%';
                $match = function ($q) use ($like) {
                    $q->where('full_name', 'like', $like)
                        ->orWhere('about_me', 'like', $like)
                        ->orWhere('matri_id', 'like', $like)
                        ->orWhereHas('religiousInfo', fn ($r) => $r->where('religion', 'like', $like)->orWhere('caste', 'like', $like)
                            ->orWhere('sub_caste', 'like', $like)->orWhere('other_caste_name', 'like', $like)
                            ->orWhere('denomination', 'like', $like)->orWhere('other_denomination_name', 'like', $like))
                        ->orWhereHas('educationDetail', fn ($e) => $e->where('highest_education', 'like', $like)->orWhere('occupation', 'like', $like)
                            ->orWhere('occupation_detail', 'like', $like)->orWhere('employer_name', 'like', $like)
                            ->orWhere('working_city', 'like', $like)->orWhere('working_district', 'like', $like))
                        ->orWhereHas('locationInfo', fn ($l) => $l->where('native_place', 'like', $like)->orWhere('native_district', 'like', $like)
                            ->orWhere('native_state', 'like', $like));
                };
                $matchAll ? $outer->where($match) : $outer->orWhere($match);
            }
        });
    }

    /** The words worth searching for: 2+ letters, no filler words, at most 6. */
    public static function keywordWords(string $keyword): array
    {
        $skip = ['and', 'or', 'the', 'in', 'at', 'from', 'of', 'for', 'with', 'a', 'an', 'to'];

        return array_slice(array_values(array_unique(array_filter(
            preg_split('/[\s,;\/]+/', mb_strtolower(trim($keyword))) ?: [],
            fn ($w) => mb_strlen($w) >= 2 && ! in_array($w, $skip, true)
        ))), 0, 6);
    }
}
