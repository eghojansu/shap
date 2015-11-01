<?php

require 'vendor/autoload.php';

(new ShapApp\Website(['DIR'=>__DIR__.'/app/','DEV'=>true]))->up();
