<?php

/* PropertiesParser
 *
 * Licence: MPL 2/GPL 2.0/LGPL 2.1
 * Author: Pascal Chevrel, Mozilla <pascal@mozilla.com>, Mozilla
 * Date : 2012-12-07
 * version: 1.1beta
 * Description:
 * Class to extract key/value pairs from a java/js style .properties file
 * Supports comment extraction and multiline properties
 *
 */

namespace xformat;

class Properties
{
    public  $source;
    private $parsed_source;

    public function __construct($file=false)
    {
        $this->setSourceFile($file);
    }

    private function setSourceFile($file)
    {
        $this->source = is_file($file) ? $file : false;
        if ($this->source) {
            $this->parsed_source = $this->fileToArray();
        } else {
            $this->parsed_source = false;
        }
    }

    private function fileToArray()
    {
        $source = file($this->source, 
                       FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $source = $this->trimArrayElements($source);
        return $source;
    }

    public function extractData()
    {
        $analysis = array();

        /* First pass, we categorize each line */
        foreach ($this->parsed_source as $line_nb => $line) {

            if ($this->stringStart('#', $line)) {
                $analysis[$line_nb] = array('comment', trim(substr($line, 1)));
                continue;
            }

            // Property name, check for escaped equal sign
            if (substr_count($line, '=') > substr_count($line, '\=')) {

                $temp = explode('=', $line, 2);
                $temp = $this->trimArrayElements($temp);

                if (count($temp) == 2) {
                    $temp[1] = $this->removeQuotes($temp[1]);
                    $analysis[$line_nb] = array('property', $temp[0], $temp[1]);
                }
                unset($temp);
                continue;
            }

            // Multiline data
            if (substr_count($line, '=') == 0) {
                $analysis[$line_nb] = array('multiline', '', $line);
                continue;
            }
        }

        /* Second pass, we associate comments to entities */
        $counter = $this->getNumberLinesMatching('comment', $analysis);

        while ($counter > 0) {

            foreach ($analysis as $line_nb => $line) {

                if ($line[0] == 'comment'
                    && isset($analysis[$line_nb+1][0])
                    && $analysis[$line_nb+1][0] == 'comment') {
                    $analysis[$line_nb][1] .= ' ' . $analysis[$line_nb+1][1];
                    $analysis[$line_nb+1][0] = 'erase';
                    break;
                } elseif ($line[0] == 'comment'
                          && isset($analysis[$line_nb+1][0])
                          && $analysis[$line_nb+1][0] == 'property') {
                    $analysis[$line_nb+1][3] = $line[1];
                    $analysis[$line_nb][0] = 'erase';
                }
            }

            $counter  = $this->getNumberLinesMatching('comment', $analysis);
            $analysis = $this->deleteFields('erase', $analysis);
        }

        /* Third pass, we merge multiline strings */

        // We remove the backslashes at end of strings if they exist
        $analysis = $this->stripBackslashes($analysis);

        // count # of multilines
        $counter = $this->getNumberLinesMatching('multiline', $analysis);

        while ($counter > 0) {
            foreach ($analysis as $line_nb => $line) {
                if ($line[0] == 'multiline'
                    && isset($analysis[$line_nb-1][0])
                    && $analysis[$line_nb-1][0] == 'property') {
                    $analysis[$line_nb-1][2] .= ' ' . trim($line[2]);
                    $analysis[$line_nb][0] = 'erase';
                    break;
                }
            }

            $counter  = $this->getNumberLinesMatching('multiline', $analysis);
            $analysis = $this->deleteFields('erase', $analysis);
        }

        /* Step 4, we clean up strings from escaped characters in properties */
        $analysis = $this->unescapeProperties($analysis);
        
        /* Step 5, we only have properties now, remove redondant field 0 */
        foreach ($analysis as $k => $v) {
            array_splice($analysis[$k], 0, 1);
        }

        return $analysis;
    }

    private function unescapeProperties($analysis)
    {
        foreach ($analysis as $k => $v) {
            $analysis[$k][2] = str_replace('\=', '=', $v[2]);
        }
        return $analysis;
    }

    public function stripBackslashes($tablo)
    {
        foreach ($tablo as $line_nb => $line) {
            if (substr($line[2], -1) == '\\') {
                $tablo[$line_nb][2] = trim(substr($line[2], 0, -1));
            }
        }
        
        return $tablo;
    }


    public static function stringStart($needle, $string) {
        return (substr($string, 0, 1) == $needle) ?  true : false; 
    }

    public static function removeQuotes($string)
    {
        if (substr($string, -1) == '"' && substr($string, 0, 1) == '"') {
            $string = substr($string, 1, -1);
        }
        
        return $string; 
    }

    public static function trimArrayElements($tablo)
    {
        $tablo = array_map(
        function ($elm) {
            return trim($elm);
        }, $tablo);

        return $tablo;
    }

    private function getNumberLinesMatching($line_type, $analysis)
    {
        $counter = 0;
        foreach ($analysis as $v) {
            if ($v[0] == $line_type) { 
                $counter++; 
            }
        }
        return $counter;
    }

    private function deleteFields($field, $analysis)
    {
        foreach ($analysis as $k => $v) {
            if ($v[0] == $field) { 
                unset($analysis[$k]);
            }
        }

        return array_values($analysis);
    }

    public function getProperties($file=false)
    {
        if ($file) {
            $this->setSourceFile($file);
        }

        $source = $this->extractData();
        $data   = array();

        foreach ($source as $value) {
            $data[$value[0]] = $value[1];
        }

        return $data;
    }
}
