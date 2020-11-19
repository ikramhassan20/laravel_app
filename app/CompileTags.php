<?php


namespace App;

trait CompileTags
{
    public static function tagsCount($filterType ,$userId = null,$deleteColumnName)
    {
        try {
            $tags = self::where($filterType, $userId)->where($deleteColumnName,false)->pluck('tags')->map(function ($item) {
                return explode(',', $item);
            })->flatten();
            $uniques = $tags->unique();
            $tagsCount = [];

            foreach ($uniques as $unique) {
                if($unique!="")
                $tagsCount[$unique] = collect($tags)->filter(function ($tag) use($unique) {
                    return ($tag === $unique) ? $tag : null;
                })->count();
            }

            $arraytoReturn = collect($tagsCount)->sortByDesc(function ($item) {
                return $item;

            })->toArray();
            $arraytoReturn = array_chunk($arraytoReturn,10,true);
            return $arraytoReturn[0];
        } catch (\Exception $exception) {
            //
        }

        return [];
    }
}