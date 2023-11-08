<?php

if (! function_exists('array_flatten')) {
    /**
     * @param  array<mixed>  $arr
     * @param  string        $glue
     *
     * @return array<mixed>
     */
    function array_flatten(array $arr, string $glue = '.'): array
    {
        $ritit = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($arr));

        $result = array();
        foreach ($ritit as $leafValue) {
            $keys = array();
            foreach (range(0, $ritit->getDepth()) as $depth) {
                $keys[] = $ritit->getSubIterator($depth)->key();
            }
            $result[ join($glue, $keys) ] = $leafValue;
        }

        return $result;
    }
}
