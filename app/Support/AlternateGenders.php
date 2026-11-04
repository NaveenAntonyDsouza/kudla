<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

/**
 * Visitors' lists alternate a bride and a groom (owner's choice, 4 Nov 2026):
 * Kudla has about three men for every woman, so a mixed list looked like a
 * page of men. Each side keeps its own order (paid boosts, complete, photo,
 * recently active...); when one side runs out, the other carries on alone.
 * Members see only the other side, so this is for visitors and Google.
 */
class AlternateGenders
{
    /** Bride first: the scarcer side, so the page looks balanced from the top. */
    public const ORDER = ['female', 'male'];

    /**
     * Ids in their query order → alternating ids.
     *
     * @param  iterable<int|string, string>  $genderById  [id => gender], in order
     * @return array<int, int|string>
     */
    public static function order(iterable $genderById): array
    {
        $sides = array_fill_keys(self::ORDER, []);
        $other = [];
        foreach ($genderById as $id => $gender) {
            isset($sides[$gender]) ? $sides[$gender][] = $id : $other[] = $id;
        }

        $out = [];
        for ($i = 0, $n = max(array_map('count', $sides)); $i < $n; $i++) {
            foreach (self::ORDER as $gender) {
                if (isset($sides[$gender][$i])) {
                    $out[] = $sides[$gender][$i];
                }
            }
        }

        return [...$out, ...$other];
    }

    /** An ordered profile query, a page at a time, alternating. */
    public static function paginate(Builder $query, int $perPage = 20): LengthAwarePaginator
    {
        $ids = self::order((clone $query)->pluck('profiles.gender', 'profiles.id'));
        $page = max(1, Paginator::resolveCurrentPage());
        $slice = array_slice($ids, ($page - 1) * $perPage, $perPage);

        $items = $slice === [] ? collect() : self::inOrder((clone $query)->whereIn('profiles.id', $slice)->get(), $slice);

        return new LengthAwarePaginator($items, count($ids), $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);
    }

    /** Already-loaded profiles, alternating (each side keeps its order). */
    public static function collection(Collection $profiles): Collection
    {
        $order = self::order($profiles->mapWithKeys(fn ($p) => [$p->getKey() => $p->gender]));

        return self::inOrder($profiles, $order);
    }

    private static function inOrder(Collection $profiles, array $ids): Collection
    {
        $position = array_flip($ids);

        return $profiles->sortBy(fn ($p) => $position[$p->getKey()] ?? PHP_INT_MAX)->values();
    }
}
