<?php

require __DIR__ . '/properties.class.php';
require __DIR__ . '/helpers.php';

$source = new \xformat\Properties('test.properties');

echo array_to_table($source->analyseSource());
dump($source->analyseSource());

