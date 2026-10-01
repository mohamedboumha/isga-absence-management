<?php
$f = __DIR__.'/build/manifest.json';
var_dump($f, file_exists($f), is_readable($f), get_current_user(), posix_getpwuid(posix_geteuid())['name']);
