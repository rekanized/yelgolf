<?php

namespace App\Services;

use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class UDiscCourseImporter
{
    public function import(string $url): Course
    {
        $normalizedUrl = $this->normalizeUrl($url);

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (compatible; YelgolfBot/1.0; +https://yelgolf.local)',
            'Accept-Language' => 'en-US,en;q=0.9',
        ])->timeout(20)->get($normalizedUrl);

        if (! $response->successful()) {
            throw new RuntimeException('UDisc did not return a successful response.');
        }

        $importData = $this->extractImportData($response->body(), $normalizedUrl);

        return DB::transaction(function () use ($importData, $normalizedUrl): Course {
            $course = Course::query()->updateOrCreate(
                ['udisc_url' => $normalizedUrl],
                $importData['course'],
            );

            if ($importData['sync_holes']) {
                $course->holes()->delete();

                if ($importData['holes'] !== []) {
                    $course->holes()->createMany($importData['holes']);
                }
            }

            return $course->fresh('holes');
        });
    }

    public function extractCourseData(string $html, string $url): array
    {
        return $this->extractImportData($html, $url)['course'];
    }

    private function extractImportData(string $html, string $url): array
    {
        if ($structuredImportData = $this->extractStructuredImportData($html, $url)) {
            return $structuredImportData;
        }

        $normalizedUrl = $this->normalizeUrl($url);
        $title = $this->extractTitle($html);
        $pageText = $this->normalizePageText($html);

        if (! preg_match('/^(.+?) - (.+?) \| UDisc Disc Golf Course Directory$/u', $title, $matches)) {
            throw new RuntimeException('The UDisc page title could not be parsed.');
        }

        $ratingStats = $this->extractRatingStats($pageText);
        [$latitude, $longitude] = $this->extractCoordinates($html);

        return [
            'course' => [
                'name' => html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'slug' => $this->extractSlug($normalizedUrl),
                'udisc_id' => $this->extractShortId($normalizedUrl),
                'photos' => $this->extractPhotoUrls($html),
                'target_type' => null,
                'tee_types' => null,
                'land_types' => null,
                'property_type' => null,
                'difficulty_levels' => null,
                'has_bathroom' => null,
                'has_drinking_water' => null,
                'is_cart_friendly' => null,
                'is_dog_friendly' => null,
                'is_stroller_friendly' => null,
                'accessibility' => null,
                'accessibility_description' => null,
                'location_name' => html_entity_decode(trim($matches[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'description' => $this->extractAboutText($pageText),
                'holes_count' => $this->extractHolesCount($pageText),
                'rating' => $ratingStats['rating'],
                'ratings_count' => $ratingStats['ratings_count'],
                'latitude' => $latitude,
                'longitude' => $longitude,
                'established_year' => $this->extractEstablishedYear($pageText),
                'imported_at' => now(),
            ],
            'holes' => [],
            'sync_holes' => false,
        ];
    }

    private function normalizeUrl(string $url): string
    {
        $trimmed = trim($url);
        $parts = parse_url($trimmed);

        if (! is_array($parts) || ($parts['host'] ?? null) !== 'udisc.com') {
            throw new InvalidArgumentException('Please enter a valid UDisc course URL.');
        }

        $path = trim($parts['path'] ?? '', '/');

        if (! Str::startsWith($path, 'courses/')) {
            throw new InvalidArgumentException('The URL must point to a UDisc course page.');
        }

        $segments = explode('/', $path);
        $slug = $segments[1] ?? null;

        if (! $slug || ! preg_match('/^[A-Za-z0-9\-]+$/', $slug)) {
            throw new InvalidArgumentException('The UDisc course URL is not in the expected format.');
        }

        return sprintf('https://udisc.com/courses/%s', $slug);
    }

    private function extractSlug(string $url): string
    {
        return Str::after($url, 'https://udisc.com/courses/');
    }

    private function extractShortId(string $url): ?string
    {
        $slug = $this->extractSlug($url);

        if (! Str::contains($slug, '-')) {
            return null;
        }

        return Str::afterLast($slug, '-');
    }

    private function extractTitle(string $html): string
    {
        if (! preg_match('/<title>([^<]+)<\/title>/iu', $html, $matches)) {
            throw new RuntimeException('The UDisc page is missing a title.');
        }

        return html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function extractAboutText(string $pageText): ?string
    {
        preg_match_all(
            '/About the course\s*(.+?)\s*(?:Uppskattad rating|Show more|Established in|Layout maps|Location|See the maps)/u',
            $pageText,
            $matches,
        );

        foreach (array_reverse($matches[1] ?? []) as $candidate) {
            $text = $this->cleanText($candidate);

            if (Str::contains($text, 'About the course')) {
                $text = $this->cleanText(Str::afterLast($text, 'About the course'));
            }

            if ($text !== '') {
                return $text;
            }
        }

        return null;
    }

    private function extractHolesCount(string $pageText): ?int
    {
        if (preg_match('/(\d+) holes/iu', $pageText, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function extractRatingStats(string $pageText): array
    {
        if (preg_match('/([0-9]+\.[0-9])\s*\(([0-9,]+) ratings\)/u', $pageText, $matches)) {
            return [
                'rating' => (float) $matches[1],
                'ratings_count' => (int) str_replace(',', '', $matches[2]),
            ];
        }

        return [
            'rating' => null,
            'ratings_count' => null,
        ];
    }

    private function extractCoordinates(string $html): array
    {
        if (preg_match('/https:\/\/www\.google\.com\/maps\/place\/(-?[0-9]+\.[0-9]+),(-?[0-9]+\.[0-9]+)/', $html, $matches)) {
            return [(float) $matches[1], (float) $matches[2]];
        }

        return [null, null];
    }

    private function extractEstablishedYear(string $pageText): ?int
    {
        if (preg_match('/Established in (\d{4})/u', $pageText, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function normalizePageText(string $html): string
    {
        return $this->cleanText(strip_tags($html));
    }

    private function extractPhotoUrls(string $html): array
    {
        preg_match_all(
            '/https:\/\/(?:udisc-parse\.s3\.amazonaws\.com|d22ksth68ujgu2\.cloudfront\.net)\/[^\s"\'<>]+\.(?:jpg|jpeg|png|webp)/iu',
            $html,
            $matches,
        );

        $photos = collect($matches[0] ?? [])
            ->map(fn (string $url) => html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'))
            ->filter(function (string $url): bool {
                $blockedFragments = [
                    'user_icon_default',
                    '_Player-',
                    '/league/',
                    'T_Player-',
                ];

                foreach ($blockedFragments as $fragment) {
                    if (str_contains($url, $fragment)) {
                        return false;
                    }
                }

                return true;
            })
            ->unique(fn (string $url) => $this->photoDeduplicationKey($url))
            ->take(8)
            ->values()
            ->all();

        return $photos;
    }

    private function extractStructuredImportData(string $html, string $url): ?array
    {
        foreach ($this->extractStructuredPayloads($html) as $payload) {
            $loaderData = $this->extractStructuredLoaderData($payload);

            if (! is_array($loaderData)) {
                continue;
            }

            $courseRouteData = $loaderData['routes/courses/$slug'] ?? null;
            $courseIndexData = $loaderData['routes/courses/$slug/index'] ?? null;
            $courseData = is_array($courseRouteData) ? ($courseRouteData['course'] ?? null) : null;

            if (! is_array($courseData) || ! is_array($courseIndexData)) {
                continue;
            }

            $courseDetailData = is_array($courseIndexData['courseDetail'] ?? null)
                ? $courseIndexData['courseDetail']
                : [];

            return [
                'course' => $this->buildStructuredCourseAttributes($courseData, $courseDetailData, $html, $url),
                'holes' => $this->buildStructuredHoleAttributes($courseIndexData['layouts'] ?? []),
                'sync_holes' => true,
            ];
        }

        return null;
    }

    private function extractStructuredPayloads(string $html): array
    {
        preg_match_all('/streamController\.enqueue\("(.*?)"\)/s', $html, $matches);

        return array_map(
            fn (string $payload) => stripcslashes($payload),
            $matches[1] ?? [],
        );
    }

    private function extractStructuredLoaderData(string $payload): ?array
    {
        $table = json_decode($payload, true);

        if (! is_array($table) || $table === []) {
            return null;
        }

        $cache = [];
        $root = $this->decodeStructuredReference(0, $table, $cache);

        return is_array($root) ? ($root['loaderData'] ?? null) : null;
    }

    private function decodeStructuredReference(mixed $reference, array $table, array &$cache): mixed
    {
        if (! is_int($reference)) {
            return $reference;
        }

        if ($reference < 0) {
            return null;
        }

        if (array_key_exists($reference, $cache)) {
            return $cache[$reference];
        }

        $value = $table[$reference] ?? null;

        if (! is_array($value)) {
            return $cache[$reference] = $value;
        }

        $isAssociative = array_keys($value) !== range(0, count($value) - 1);

        if (! $isAssociative) {
            if (($value[0] ?? null) === 'D' && isset($value[1])) {
                return $cache[$reference] = $value[1];
            }

            if (($value[0] ?? null) === 'P') {
                return $cache[$reference] = null;
            }

            return $cache[$reference] = array_map(
                fn (mixed $item) => $this->decodeStructuredReference($item, $table, $cache),
                $value,
            );
        }

        $decoded = [];

        foreach ($value as $keyReference => $valueReference) {
            $key = Str::startsWith((string) $keyReference, '_')
                ? $this->decodeStructuredReference((int) substr((string) $keyReference, 1), $table, $cache)
                : $keyReference;

            if (! is_string($key)) {
                continue;
            }

            $decoded[$key] = $this->decodeStructuredReference($valueReference, $table, $cache);
        }

        return $cache[$reference] = $decoded;
    }

    private function buildStructuredCourseAttributes(array $courseData, array $courseDetailData, string $html, string $url): array
    {
        [$latitude, $longitude] = $this->extractCoordinates($html);

        return [
            'name' => $courseData['name'] ?? $this->extractTitleNameFallback($html),
            'slug' => $this->extractSlug($this->normalizeUrl($url)),
            'udisc_id' => $courseData['shortId'] ?? $this->extractShortId($url),
            'photos' => $this->extractPhotoUrls($html),
            'target_type' => $courseDetailData['targetTypeDescription'] ?? null,
            'tee_types' => $this->normalizeStringList($courseDetailData['activeTeeTypes'] ?? null),
            'land_types' => $this->normalizeStringList($courseDetailData['landType'] ?? null, ['other']),
            'property_type' => $courseDetailData['propertyType'] ?? null,
            'difficulty_levels' => $this->normalizeStringList($courseDetailData['difficultyBins'] ?? null),
            'has_bathroom' => $courseData['hasBathroom'] ?? null,
            'has_drinking_water' => $courseData['hasDrinkingWater'] ?? null,
            'is_cart_friendly' => $courseData['isCartFriendly'] ?? null,
            'is_dog_friendly' => $courseData['isDogFriendly'] ?? null,
            'is_stroller_friendly' => $courseDetailData['isStrollerFriendly'] ?? null,
            'accessibility' => $courseDetailData['accessibility'] ?? null,
            'accessibility_description' => $courseDetailData['accessibilityDescription'] ?? null,
            'location_name' => $courseData['locationText'] ?? null,
            'description' => $courseData['longDescription'] ?? $this->extractAboutText($this->normalizePageText($html)),
            'holes_count' => isset($courseData['holeCount']) ? (int) $courseData['holeCount'] : $this->extractHolesCount($this->normalizePageText($html)),
            'rating' => isset($courseData['ratingAverage']) ? (float) $courseData['ratingAverage'] : null,
            'ratings_count' => isset($courseData['ratingCount']) ? (int) $courseData['ratingCount'] : null,
            'latitude' => isset($courseData['latitude']) ? (float) $courseData['latitude'] : $latitude,
            'longitude' => isset($courseData['longitude']) ? (float) $courseData['longitude'] : $longitude,
            'established_year' => isset($courseData['yearEstablished']) ? (int) $courseData['yearEstablished'] : null,
            'imported_at' => now(),
        ];
    }

    private function buildStructuredHoleAttributes(mixed $layouts): array
    {
        if (! is_array($layouts)) {
            return [];
        }

        $holes = [];

        foreach (array_values($layouts) as $layoutIndex => $layout) {
            if (! is_array($layout) || ! is_array($layout['holes'] ?? null)) {
                continue;
            }

            foreach (array_values($layout['holes']) as $holeIndex => $hole) {
                if (! is_array($hole)) {
                    continue;
                }

                $holeLabel = isset($hole['name']) ? trim((string) $hole['name']) : '';
                $holeNumber = ctype_digit($holeLabel) ? (int) $holeLabel : null;

                $holes[] = [
                    'udisc_hole_id' => $hole['holeId'] ?? null,
                    'layout_id' => isset($layout['layoutId']) ? (int) $layout['layoutId'] : null,
                    'layout_name' => $layout['layoutName'] ?? $layout['name'] ?? null,
                    'layout_difficulty' => $layout['difficultyBin'] ?? null,
                    'layout_order' => $layoutIndex + 1,
                    'sort_order' => $holeIndex + 1,
                    'number' => $holeNumber,
                    'hole_label' => $holeLabel !== '' ? $holeLabel : null,
                    'par' => isset($hole['par']) ? (int) $hole['par'] : null,
                    'distance_meters' => $this->extractHoleDistance($hole, 'meters'),
                    'distance_feet' => $this->extractHoleDistance($hole, 'feet'),
                ];
            }
        }

        return $holes;
    }

    private function extractHoleDistance(array $hole, string $unit): ?float
    {
        $distance = $hole['holeDistance'][$unit] ?? null;

        if (is_numeric($distance)) {
            return round((float) $distance, 2);
        }

        if ($unit === 'meters' && is_numeric($hole['customDistance'] ?? null)) {
            return round((float) $hole['customDistance'], 2);
        }

        if ($unit === 'meters' && is_numeric($hole['distance'] ?? null)) {
            return round((float) $hole['distance'], 2);
        }

        return null;
    }

    private function normalizeStringList(mixed $values, array $excludedValues = []): ?array
    {
        if (! is_array($values)) {
            return null;
        }

        $normalized = collect($values)
            ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => trim($value))
            ->reject(fn (string $value): bool => in_array($value, $excludedValues, true))
            ->unique()
            ->values()
            ->all();

        return $normalized !== [] ? $normalized : null;
    }

    private function extractTitleNameFallback(string $html): ?string
    {
        $title = $this->extractTitle($html);

        if (preg_match('/^(.+?) - .+? \| UDisc Disc Golf Course Directory$/u', $title, $matches)) {
            return html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return null;
    }

    private function photoDeduplicationKey(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;
        $filename = basename($path);

        $filename = preg_replace('/^[a-z0-9-]+_/', '', $filename) ?? $filename;
        $filename = preg_replace('/^[tmr]_(?=[A-Z0-9_\-])/i', '', $filename) ?? $filename;

        return strtolower($filename);
    }

    private function cleanText(?string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) $text));
    }
}