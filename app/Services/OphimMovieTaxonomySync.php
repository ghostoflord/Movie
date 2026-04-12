<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Country;
use App\Models\Movie;
use Illuminate\Support\Str;

/**
 * Đồng bộ thể loại / quốc gia từ payload OPhim (mảng object có id, name, slug) vào bảng + pivot.
 */
class OphimMovieTaxonomySync
{
    /**
     * @param  array<int, mixed>  $ophimCategories
     * @param  array<int, mixed>  $ophimCountries
     */
    public static function sync(Movie $movie, array $ophimCategories, array $ophimCountries): void
    {
        $categoryIds = [];
        foreach ($ophimCategories as $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $slug = trim((string) ($row['slug'] ?? ''));
            if ($slug === '') {
                $slug = Str::slug($name) ?: 'cat-'.md5($name);
            }
            $extId = $row['id'] ?? $row['_id'] ?? null;

            $cat = Category::query()->where('slug', $slug)->first();
            if ($cat) {
                $cat->name = $name;
                if ($extId !== null) {
                    $cat->ophim_id = $extId;
                }
                $cat->save();
            } else {
                $cat = Category::query()->create([
                    'name' => $name,
                    'slug' => $slug,
                    'ophim_id' => $extId,
                    'description' => '',
                    'icon' => '',
                    'title' => $name,
                ]);
            }
            $categoryIds[] = $cat->id;
        }
        $movie->movieCategories()->sync($categoryIds);

        $countryIds = [];
        foreach ($ophimCountries as $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $slug = trim((string) ($row['slug'] ?? ''));
            if ($slug === '') {
                $slug = Str::slug($name) ?: 'ct-'.md5($name);
            }
            $extId = $row['id'] ?? $row['_id'] ?? null;

            $country = Country::query()->where('slug', $slug)->first();
            if ($country) {
                $country->name = $name;
                if ($extId !== null) {
                    $country->ophim_id = $extId;
                }
                $country->save();
            } else {
                $country = Country::query()->create([
                    'name' => $name,
                    'slug' => $slug,
                    'ophim_id' => $extId,
                ]);
            }
            $countryIds[] = $country->id;
        }
        $movie->movieCountries()->sync($countryIds);
    }
}
