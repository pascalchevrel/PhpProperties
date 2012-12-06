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
    public $source;
    private $parsed_source;

    public function __construct($file=false)
    {
        $this->source = is_file($file) ? $file : false;
        if($file) {
            $this->parsed_source = $this->fileToArray();
        }
    }

    private function fileToArray()
    {
        $source = file($this->source, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $source = array_map(
            function($elm){
                return trim($elm);
            }, $source);

        return $source;
    }

    public function analyseSource()
    {
        $analysis = array();
        foreach ($this->parsed_source as $line_nb => $line) {

            // Line comments
            if (substr($line[0], 0, 1) == '#') {
                $analysis[$line_nb] = 'comment';
                continue;
            }

            // Property name, check for escaped equal sign
            if (substr_count($line, '=') > substr_count($line, '\=')) {
                $analysis[$line_nb] = 'propertyName';
                continue;
            }

            // Multiline data
            if (substr_count($line, '=') == 0) {
                $analysis[$line_nb] = 'multilineData';
                continue;
            }
        }

        return $analysis;
    }

    /*
     * This method is a quick and dirty parser that does a one-pass analyse
     * of the properties file.
     * Its logic is being moved to specialized methods that will allow a
     * multipass analysis of properties file so as to handle multiline properties,
     * comments and other edge cases
     */
    public function extractProperties()
    {
        // We parse the $parsed_source array, remove white space and delimiting quotes, skip comments
        foreach ($this->parsed_source as $value) {
            if (substr($value[1], 0, 1) == '#') continue;

            $temp = explode('=', $value, 2);
            $temp = array_map(
                function($elm){
                    return trim($elm);
                }, $temp);

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
