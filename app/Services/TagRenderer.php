<?php

namespace App\Services;
use App\Models\OsmInfo;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use Ujamii\OsmOpeningHours\OsmStringToOpeningHoursConverter;

class TagRenderer
{
    public function __construct(private readonly OsmInfo $osmInfo)
    {
    }


    // phone: as is
    // atm=yes taginfo
    // name: print
    // name:am print, too ?
    // opening_hours: opening_hours.js https://github.com/opening-hours/opening_hours.js/
    // operator: print
    // website: print

    public function getTagTexts(): array
    {
        $tags = $this->osmInfo->tags;
        $lines = [];
        foreach ($tags as $key=>$value) {
            if ($key === 'opening_hours') {
                $key = 'Opening Times';
                $value = $value . ' - ' . self::parseOpeningHours($value);
                $lines[] = $key . ": " . $value;
                continue;
            }

            $tagInfo = $this->queryTagInfo($key, $value);
            if ($tagInfo !== null) {
                $lines[] = $tagInfo;
            }
        }

        return $lines;
    }

    private function parseOpeningHours($value)
    {
        $timeZone = $this->getTimeZone($this->osmInfo->lat, $this->osmInfo->lon);

        $now = (new \DateTimeImmutable('now'))->setTimezone(new DateTimeZone($timeZone));
        $hours = OsmStringToOpeningHoursConverter::openingHoursFromOsmString($value);
        if ($hours->isOpenAt($now)) {
            return 'currently open';
        } else {
            return 'currently (' . $now->format('Y-m-d H:i:s') . ' local time) closed, next open: ' . $hours->nextOpen($now)->format('Y-m-d H:i:s');
        }
    }

    /**
     * @source https://stackoverflow.com/a/15535345
     */
    private function getTimeZone($cur_lat, $cur_long, $country_code = '')
    {
        $timezone_ids = ($country_code) ? DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $country_code)
            : DateTimeZone::listIdentifiers();

        if($timezone_ids && is_array($timezone_ids) && isset($timezone_ids[0])) {

            $time_zone = '';
            $tz_distance = 0;

            //only one identifier?
            if (count($timezone_ids) == 1) {
                $time_zone = $timezone_ids[0];
            } else {

                foreach($timezone_ids as $timezone_id) {
                    $timezone = new DateTimeZone($timezone_id);
                    $location = $timezone->getLocation();
                    $tz_lat   = $location['latitude'];
                    $tz_long  = $location['longitude'];

                    $theta    = $cur_long - $tz_long;
                    $distance = (sin(deg2rad($cur_lat)) * sin(deg2rad($tz_lat)))
                        + (cos(deg2rad($cur_lat)) * cos(deg2rad($tz_lat)) * cos(deg2rad($theta)));
                    $distance = acos($distance);
                    $distance = abs(rad2deg($distance));
                    // echo '<br />'.$timezone_id.' '.$distance;

                    if (!$time_zone || $tz_distance > $distance) {
                        $time_zone   = $timezone_id;
                        $tz_distance = $distance;
                    }

                }
            }
            return  $time_zone;
        }
        return 'unknown';
    }

    private function queryTagInfo($key, $value)
    {
        $row = DB::connection('sqlite_taginfo')
            ->table('wikipages')
            ->select('description')
            ->where('lang', 'en')
            ->where('key', $key)
            ->where('value', $value)
            ->get()->first();

        if ($row === null) {
            return null;
        }

        return $row->description;
    }
}
