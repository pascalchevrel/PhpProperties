<?php

function array_to_table($arr) {
    $list = '<table border="1" style="border-collapse:collapse;">';
    foreach ($arr as $k => $v) {
        $list .= "<tr><th>$k</th><td>$v</td></tr>";
    }
    $list .= '<table>';
    return $list;
}