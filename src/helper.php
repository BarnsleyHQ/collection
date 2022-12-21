<?php

use BarnsleyHQ\Collection;

if (! function_exists('collect')) {
    function collect($value = [])
    {
        return new Collection($value);
    }
}
