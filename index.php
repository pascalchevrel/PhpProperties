<?php
require __DIR__ . '/src/xformat/Properties.php';

header('Content-type: text/plain; charset=UTF-8');

$file = __DIR__ . '/test.properties';

echo "Properties example source:\n";
echo "--------------------------\n";
readfile($file);

$source = new \xformat\Properties('test.properties');

echo "Extracted data:\n";
echo "---------------\n";
var_dump($source->getProperties());


echo "\nFull Extracted data with comments:\n";
echo "----------------------------------\n";
var_dump($source->extractData());

