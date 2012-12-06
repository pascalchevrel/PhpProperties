<?php

function array_to_table($arr) {
    $list = '<table border="1" style="border-collapse:collapse;">
                <tr><th>Key</th><th>Value</th><th>Comment</th></tr>';

    foreach ($arr as $v) {
        $v[2] = isset($v[2]) ? $v[2] : '';
        $list .= "<tr><td>$v[0]</td><td>$v[1]</td><td>$v[2]</td></tr>";
    }
    $list .= '</table>';
    return $list;
}

/**
 * nicer var_dump()
 */
function dump($var)
{
    ob_start();
    print_r($var);
    $content = ob_get_contents();
    ob_end_clean();
    echo '
    <style>
        span.dump {
            display:inline-block;
            min-width:4em;
            color:orange;
        }

        span.dump-arrow {
            display:inline;
            min-width:auto;
            color:lightgray;
            padding:0 0.5em;
        }

        pre.dump {
            background-color:black;
            color: lightblue;
            display:table;
            font-family:monospace;
            padding: 0.5em;
        }

    </style>
    <script>
    function showhide(foobar) {
        if(foobar.innerHTML == "hide") {
            foobar.parentNode.style.display = "inline-block";
            foobar.parentNode.style.width = "100px";
            foobar.parentNode.style.height = "0.9em";
            foobar.parentNode.style.overflow = "hidden";
            foobar.innerHTML = "show";
        } else {
            foobar.parentNode.style.display = "auto";
            foobar.parentNode.style.width = "auto";
            foobar.parentNode.style.height = "auto";
            foobar.parentNode.style.overflow = "auto";
            foobar.innerHTML = "hide";
        }
    }
    </script>
    ';

    echo '<pre class="dump">';
    echo '<span class="hide" onclick="showhide(this);">hide</span>';
    echo "<br>(" . count($var) . " elements)<br>";
    echo '<code>';

    $content = str_replace('[', '<span class="dump">[', $content);
    $content = str_replace(']', ']</span>', $content);
    $content = str_replace(' => ', '<span class="dump-arrow">=></span>', $content);
    echo $content;
    echo '</code></pre>';
}
