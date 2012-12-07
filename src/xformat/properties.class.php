<?php

/* PropertiesParser
 *
 * Licence: MPL 2/GPL 2.0/LGPL 2.1
 * Author: Pascal Chevrel, Mozilla <pascal@mozilla.com>, Mozilla
 * Date : 2012-12-07
 * version: 1.0
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
        $source = file($this->source, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $source = array_map(
            function($elm) {
                return trim($elm);
            }, $source);

        return $source;
    }

    public function extractData()
    {
        $analysis = array();

        /* First pass, we categorize each line */
        foreach ($this->parsed_source as $line_nb => $line) {

            // Line comments
            if (substr($line[0], 0, 1) == '#') {
                $analysis[$line_nb] = array('comment', trim(substr($line, 1)));
                continue;
            }

            // Property name, check for escaped equal sign
            if (substr_count($line, '=') > substr_count($line, '\=')) {

                $temp = explode('=', $line, 2);
                $temp = array_map(
                    function($elm) {
                        return trim($elm);
                    }, $temp);

                if (count($temp) == 2) {
                    if (substr($temp[1], -1) == '"'
                        && substr($temp[1], 0, 1) == '"')
                    {
                        $temp[1] = substr($temp[1], 1, -1);
                    }
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

        // count # of comments
        $counter = 0;
        foreach ($analysis as $v) {
            if ($v[0] == 'comment') $counter++;
        }

        while ($counter > 0) {

            foreach ($analysis as $line_nb => $line) {

                if ($line[0] == 'comment'
                    && isset($analysis[$line_nb+1][0])
                    && $analysis[$line_nb+1][0] == 'comment')
                {
                    $analysis[$line_nb][1] .= ' ' . $analysis[$line_nb+1][1];
                    $analysis[$line_nb+1][0] = 'erase';
                    break;
                } elseif ($line[0] == 'comment'
                          && isset($analysis[$line_nb+1][0])
                          && $analysis[$line_nb+1][0] == 'property')
                {
                    $analysis[$line_nb+1][3] = $line[1];
                    $analysis[$line_nb][0] = 'erase';
                }
            }

            $counter = 0;
            foreach ($analysis as $k => $v) {
                if ($v[0] == 'comment') $counter++;
                if ($v[0] == 'erase') unset($analysis[$k]);
            }

            $analysis = array_values($analysis);
        }

        /* Third pass, we merge multiline strings */

        // We remove the backslashes at end of strings if they exist
        foreach ($analysis as $line_nb => $line) {
            if (substr($line[2], -1) == '\\') {
                $analysis[$line_nb][2] = trim(substr($line[2], 0, -1));
            }
        }

        // count # of multilines
        $counter = 0;
        foreach ($analysis as $v) {
            if ($v[0] == 'multiline') $counter++;
        }

        while ($counter > 0) {
            foreach ($analysis as $line_nb => $line) {
                if ($line[0] == 'multiline'
                    && isset($analysis[$line_nb-1][0])
                    && $analysis[$line_nb-1][0] == 'property')
                {
                    $analysis[$line_nb-1][2] .= ' ' . trim($line[2]);
                    $analysis[$line_nb][0] = 'erase';
                    break;
                }
            }

            $counter = 0;
            foreach ($analysis as $k => $v) {
                if ($v[0] == 'multiline') $counter++;
                if ($v[0] == 'erase') unset($analysis[$k]);
            }

            $analysis = array_values($analysis);
        }

        /* Step 4, we clean up strings from escaped characters in properties */
        foreach ($analysis as $k => $v) {
            $analysis[$k][2] = str_replace('\=', '=', $v[2]);
        }

        /* Step 5, we only have properties now, let's removed field 0 that is redondant */
        foreach ($analysis as $k => $v) {
            array_splice($analysis[$k], 0, 1);
        }

        return $analysis;
    }

    public function getProperties($file=false)
    {
        if ($file) $this->setSourceFile($file);

        $source = $this->extractData();
        $data   = array();

        foreach ($source as $value) {
            $data[$value[0]] = $value[1];
        }

        return $data;
    }
}