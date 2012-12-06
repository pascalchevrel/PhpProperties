<?php

/* PropertiesParser
 *
 * Licence: MPL 2/GPL 2.0/LGPL 2.1
 * Author: Pascal Chevrel, Mozilla <pascal@mozilla.com>, Mozilla
 * Date : 2012-12-05
 * version: 0.1
 * Description:
 * Class to extract key/value pairs from a java/js style .properties file
 * @returns array
 *
*/

namespace xformat;

class Properties
{
    public $file;

    public function __construct($file=false)
    {
        $this->source = is_file($file) ? $file : false;
    }

    private function fileToArray()
    {
        if (!$this->source) return false;

        $source = file($this->source, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $source = array_map(
            function($elm){
                return trim($elm);
            }, $source);

        return $source;
    }

    public function extractProperties()
    {
        $source = $this->fileToArray();

        // We parse the $source array, remove white space and delimiting quotes, skip comments
        foreach ($source as $value) {
            if (substr($value[1], 0, 1) == '#') continue;

            $temp = explode('=', $value, 2);
            $temp = array_map(function($elm){ return trim($elm);}, $temp);

            if (count($temp) == 2) {
                if (substr($temp[1], -1) == '"' && substr($temp[1], 0, 1) == '"') {
                    $temp[1] = substr($temp[1], 1, -1);
                }
                $finalArray[$temp[0]] = $temp[1];
            }
        }

        return $finalArray;
    }
}
