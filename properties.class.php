<?php

/* PropertiesParser
 *
 * Licence: MPL 2/GPL 2.0/LGPL 2.1
 * Author: Pascal Chevrel, Mozilla <pascal@mozilla.com>, Mozilla
 * Date : 2012-12-05
 * version: 1.0
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
                    function($elm){
                        return trim($elm);
                    }, $temp);

                if (count($temp) == 2) {
                    if (substr($temp[1], -1) == '"' && substr($temp[1], 0, 1) == '"') {
                        $temp[1] = substr($temp[1], 1, -1);
                    }
                    $analysis[$line_nb] = array($temp[0], $temp[1]);
                }
                unset($temp);
                continue;
            }

            // Multiline data
            if (substr_count($line, '=') == 0) {
                $analysis[$line_nb] = array('multiline', $line);
                continue;
            }
        }


        /* Second pass, we associate comments to entities */

        // count # of comments
        $comment_count = 0;
        foreach ($analysis as $v) {
            if ($v[0] == 'comment') $comment_count++;
        }

        while($comment_count > 0) {

            foreach ($analysis as $line_nb => $line) {

                if ($line[0] == 'comment' && isset($analysis[$line_nb+1][0]) && $analysis[$line_nb+1][0] == 'comment') {
                    $analysis[$line_nb][1] .= ' ' . $analysis[$line_nb+1][1];
                    $analysis[$line_nb+1][0] = 'erase';
                    break;
                } elseif ($line[0] == 'comment' && isset($analysis[$line_nb+1][0]) && $analysis[$line_nb+1][0] != 'multiline') {
                    $analysis[$line_nb+1][2] = $line[1];
                    $analysis[$line_nb][0] = 'erase';
                }
            }

            $comment_count = 0;

            foreach ($analysis as $k => $v) {
                if ($v[0] == 'comment') $comment_count++;
                if ($v[0] == 'erase') unset($analysis[$k]);
            }

            $analysis = array_values($analysis);
        }

        /* Third pass, we merge multiline strings */

        // We remove the backslashes at end of strings if they exist
        foreach ($analysis as $line_nb => $line) {
            if (substr($line[1], -1) == '\\') {
                $analysis[$line_nb][1] = trim(substr($line[1], 0, -1));
            }
        }

        // count # of multilines
        $counter = 0;
        foreach ($analysis as $v) {
            if ($v[0] == 'multiline') $counter++;
        }

        while($counter > 0) {

            foreach ($analysis as $line_nb => $line) {

                if ($line[0] == 'multiline'
                    && isset($analysis[$line_nb-1][0])
                    && $analysis[$line_nb-1][0] != 'multiline'
                    && $analysis[$line_nb-1][0] != 'comment')
                {
                    $analysis[$line_nb-1][1] .= ' ' . trim($line[1]);
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
            $analysis[$k][1] = str_replace('\=', '=', $v[1]);
        }

        return $analysis;
    }

    public function rebuildSource() {
        $source = $source->analyseSource();

        $comments = array();

        foreach ($source as $k => $v) {
            if ($v == 'comment') {
                $comments[] = $k;
                continue;
            }

            if ($v == 'propertyName') {

            }
        }
    }
}
