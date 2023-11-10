<?php

if (! function_exists('array_flatten')) {
    /**
     * Convert a multidimensional array to a single level array with the separator specified as glue.
     *
     * @param  array<mixed>  $arr
     * @param  string        $glue  The separator between levels.
     *
     * @return array<mixed>
     */
    function array_flatten(array $arr, string $glue = '.'): array
    {
        $ritit = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($arr));

        $result = [];
        foreach ($ritit as $leafValue) {
            $keys = [];
            foreach (range(0, $ritit->getDepth()) as $depth) {
                $keys[] = $ritit->getSubIterator($depth)->key();
            }

            $result[ join($glue, $keys) ] = $leafValue;
        }

        return $result;
    }
}

if (! function_exists('array_extrude')) {
    /**
     * Takes an array with separators and converts it into a multidimensional array.
     *
     * @param  array<mixed>  $arr
     *
     * @return array<string|int, mixed>
     */
    function array_extrude(array $arr, string $separator = '.'): array
    {
        $result = [];
        foreach ($arr as $dotNotation) {
            $exploded = explode($separator, $dotNotation);
            \Illuminate\Support\Arr::set($result, implode($separator, array_splice($exploded, 0,-1)) ?: count($result), last($exploded));
        }

        return $result;
    }
}
