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
