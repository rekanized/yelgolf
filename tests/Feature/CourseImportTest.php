<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Services\UDiscCourseImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

class CourseImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_importer_persists_course_details_from_udisc(): void
    {
        Http::fake([
            'udisc.com/*' => Http::response($this->structuredUdiscHtml(), 200),
        ]);

        $course = app(UDiscCourseImporter::class)->import('https://udisc.com/courses/haesthagen-M8Wu');

        $this->assertInstanceOf(Course::class, $course);
        $this->assertDatabaseHas('courses', [
            'name' => 'Hästhagen',
            'slug' => 'haesthagen-M8Wu',
            'udisc_id' => 'M8Wu',
            'location_name' => 'Örebro, Sweden',
            'holes_count' => 20,
            'established_year' => 1982,
            'target_type' => 'DISCatcher Pro (original)',
            'property_type' => 'mixedUse',
        ]);

        $this->assertSame(3.8, $course->rating);
        $this->assertSame(2789, $course->ratings_count);
        $this->assertEqualsWithDelta(59.2677595105919, $course->latitude, 0.0000001);
        $this->assertEqualsWithDelta(15.161870509788173, $course->longitude, 0.0000001);
        $this->assertSame([
            'https://udisc-parse.s3.amazonaws.com/hash_m_IMG_1001.jpg',
            'https://udisc-parse.s3.amazonaws.com/hash_m_IMG_1002.jpg',
        ], $course->photos);
        $this->assertSame(['Konstgräs'], $course->tee_types);
        $this->assertSame(['publicPark'], $course->land_types);
        $this->assertSame(['intermediate', 'challenging'], $course->difficulty_levels);
        $this->assertTrue($course->has_bathroom);
        $this->assertTrue($course->has_drinking_water);
        $this->assertTrue($course->is_cart_friendly);
        $this->assertTrue($course->is_dog_friendly);
        $this->assertTrue($course->is_stroller_friendly);
        $this->assertSame('limited', $course->accessibility);
        $this->assertSame('Alla hål ligger på jämn mark, vagn och rullstol bör fungera.', $course->accessibility_description);
        $this->assertCount(2, $course->holes);

        $this->assertDatabaseHas('holes', [
            'course_id' => $course->id,
            'layout_id' => 37576,
            'layout_name' => 'Hästhagen Främre',
            'layout_caddie_book_url' => 'https://udisc.com/courses/haesthagen-M8Wu/layouts/37576/caddie-book',
            'layout_difficulty' => 'intermediate',
            'number' => 1,
            'hole_label' => '1',
            'par' => 3,
        ]);

        $this->assertDatabaseHas('holes', [
            'course_id' => $course->id,
            'layout_id' => 37576,
            'sort_order' => 1,
            'distance_meters' => 58,
            'distance_feet' => 190.29,
        ]);
    }

    public function test_importer_rejects_non_udisc_urls(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(UDiscCourseImporter::class)->import('https://example.com/courses/haesthagen-M8Wu');
    }

    private function structuredUdiscHtml(): string
    {
        $payload = addslashes(json_encode($this->structuredPayloadTable(), JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Hästhagen - Örebro, Sweden | UDisc Disc Golf Course Directory</title>
    </head>
    <body>
        <main>
            <a href="https://www.google.com/maps/place/59.2677595105919,15.161870509788173">Get directions</a>
            <div>3.8 (2789 ratings)</div>
            <img src="https://udisc-parse.s3.amazonaws.com/hash_m_IMG_1001.jpg" alt="course photo">
            <img src="https://d22ksth68ujgu2.cloudfront.net/hash_m_IMG_1001.jpg" alt="duplicate host photo">
            <img src="https://udisc-parse.s3.amazonaws.com/hash_t_IMG_1001.jpg" alt="duplicate size photo">
            <img src="https://udisc-parse.s3.amazonaws.com/hash_m_IMG_1002.jpg" alt="second course photo">
            <img src="https://udisc-parse.s3.amazonaws.com/avatar_T_Player-20241110_182028.jpg" alt="filtered avatar photo">
            <script>
                window.__reactRouterContext = { streamController: { enqueue: function () {} } };
                window.__reactRouterContext.streamController.enqueue("__PAYLOAD__");
            </script>
        </main>
    </body>
</html>
HTML;

        return str_replace('__PAYLOAD__', $payload, $html);
    }

    private function structuredPayloadTable(): array
    {
        $table = [];

        $add = static function (mixed $value) use (&$table): int {
            $table[] = $value;

            return array_key_last($table);
        };

        $rootIndex = $add(null);
        $loaderDataKeyIndex = $add('loaderData');
        $loaderDataIndex = $add(null);
        $courseRouteKeyIndex = $add('routes/courses/$slug');
        $courseRouteIndex = $add(null);
        $courseKeyIndex = $add('course');
        $courseIndex = $add(null);

        $nameKeyIndex = $add('name');
        $locationKeyIndex = $add('locationText');
        $holeCountKeyIndex = $add('holeCount');
        $holeCountValueIndex = $add(20);
        $ratingAverageKeyIndex = $add('ratingAverage');
        $ratingCountKeyIndex = $add('ratingCount');
        $ratingCountValueIndex = $add(2789);
        $latitudeKeyIndex = $add('latitude');
        $longitudeKeyIndex = $add('longitude');
        $yearEstablishedKeyIndex = $add('yearEstablished');
        $yearEstablishedValueIndex = $add(1982);
        $shortIdKeyIndex = $add('shortId');
        $longDescriptionKeyIndex = $add('longDescription');
        $descriptionIndex = $add('The oldest course in Orebro, beautifully situated next to Svartån. It has 18 holes of varying length and elevation.');
        $hasBathroomKeyIndex = $add('hasBathroom');
        $hasDrinkingWaterKeyIndex = $add('hasDrinkingWater');
        $isCartFriendlyKeyIndex = $add('isCartFriendly');
        $isDogFriendlyKeyIndex = $add('isDogFriendly');

        $courseIndexRouteKeyIndex = $add('routes/courses/$slug/index');
        $courseIndexRouteIndex = $add(null);
        $courseDetailKeyIndex = $add('courseDetail');
        $courseDetailIndex = $add(null);
        $layoutsKeyIndex = $add('layouts');
        $layoutsIndex = $add(null);

        $landTypeKeyIndex = $add('landType');
        $landTypeIndex = $add(['publicPark', 'other']);
        $targetTypeDescriptionKeyIndex = $add('targetTypeDescription');
        $activeTeeTypesKeyIndex = $add('activeTeeTypes');
        $activeTeeTypesIndex = $add(['Konstgräs']);
        $propertyTypeKeyIndex = $add('propertyType');
        $difficultyBinsKeyIndex = $add('difficultyBins');
        $difficultyBinsIndex = $add(['intermediate', 'challenging']);
        $isStrollerFriendlyKeyIndex = $add('isStrollerFriendly');
        $accessibilityKeyIndex = $add('accessibility');
        $accessibilityDescriptionKeyIndex = $add('accessibilityDescription');

        $layoutIndex = $add(null);
        $layoutIdKeyIndex = $add('layoutId');
        $layoutIdValueIndex = $add(37576);
        $layoutNameKeyIndex = $add('layoutName');
        $difficultyBinKeyIndex = $add('difficultyBin');
        $holesKeyIndex = $add('holes');
        $holesIndex = $add(null);

        $holeIdKeyIndex = $add('holeId');
        $holeLabelKeyIndex = $add('name');
        $holeParKeyIndex = $add('par');
        $firstParValueIndex = $add(3);
        $secondParValueIndex = $add(4);
        $holeDistanceKeyIndex = $add('holeDistance');
        $metersKeyIndex = $add('meters');
        $feetKeyIndex = $add('feet');
        $firstDistanceIndex = $add(['_'.$metersKeyIndex => 58.0, '_'.$feetKeyIndex => 190.29]);
        $secondDistanceIndex = $add(['_'.$metersKeyIndex => 92.0, '_'.$feetKeyIndex => 301.84]);
        $firstHoleIndex = $add(['_'.$holeIdKeyIndex => '5AIq', '_'.$holeLabelKeyIndex => '1', '_'.$holeParKeyIndex => $firstParValueIndex, '_'.$holeDistanceKeyIndex => $firstDistanceIndex]);
        $secondHoleIndex = $add(['_'.$holeIdKeyIndex => '5AIs', '_'.$holeLabelKeyIndex => '2', '_'.$holeParKeyIndex => $secondParValueIndex, '_'.$holeDistanceKeyIndex => $secondDistanceIndex]);

        $table[$rootIndex] = ['_'.$loaderDataKeyIndex => $loaderDataIndex];
        $table[$loaderDataIndex] = [
            '_'.$courseRouteKeyIndex => $courseRouteIndex,
            '_'.$courseIndexRouteKeyIndex => $courseIndexRouteIndex,
        ];
        $table[$courseRouteIndex] = ['_'.$courseKeyIndex => $courseIndex];
        $table[$courseIndex] = [
            '_'.$nameKeyIndex => 'Hästhagen',
            '_'.$locationKeyIndex => 'Örebro, Sweden',
            '_'.$holeCountKeyIndex => $holeCountValueIndex,
            '_'.$ratingAverageKeyIndex => 3.8,
            '_'.$ratingCountKeyIndex => $ratingCountValueIndex,
            '_'.$latitudeKeyIndex => 59.2677595105919,
            '_'.$longitudeKeyIndex => 15.161870509788173,
            '_'.$yearEstablishedKeyIndex => $yearEstablishedValueIndex,
            '_'.$shortIdKeyIndex => 'M8Wu',
            '_'.$longDescriptionKeyIndex => $descriptionIndex,
            '_'.$hasBathroomKeyIndex => true,
            '_'.$hasDrinkingWaterKeyIndex => true,
            '_'.$isCartFriendlyKeyIndex => true,
            '_'.$isDogFriendlyKeyIndex => true,
        ];
        $table[$courseIndexRouteIndex] = [
            '_'.$courseDetailKeyIndex => $courseDetailIndex,
            '_'.$layoutsKeyIndex => $layoutsIndex,
        ];
        $table[$courseDetailIndex] = [
            '_'.$landTypeKeyIndex => $landTypeIndex,
            '_'.$targetTypeDescriptionKeyIndex => 'DISCatcher Pro (original)',
            '_'.$activeTeeTypesKeyIndex => $activeTeeTypesIndex,
            '_'.$propertyTypeKeyIndex => 'mixedUse',
            '_'.$difficultyBinsKeyIndex => $difficultyBinsIndex,
            '_'.$isStrollerFriendlyKeyIndex => true,
            '_'.$accessibilityKeyIndex => 'limited',
            '_'.$accessibilityDescriptionKeyIndex => 'Alla hål ligger på jämn mark, vagn och rullstol bör fungera.',
        ];
        $table[$layoutsIndex] = [$layoutIndex];
        $table[$layoutIndex] = [
            '_'.$layoutIdKeyIndex => $layoutIdValueIndex,
            '_'.$layoutNameKeyIndex => 'Hästhagen Främre',
            '_'.$difficultyBinKeyIndex => 'intermediate',
            '_'.$holesKeyIndex => $holesIndex,
        ];
        $table[$holesIndex] = [$firstHoleIndex, $secondHoleIndex];

        return $table;
    }
}
