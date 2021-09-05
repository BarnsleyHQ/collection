<?php

use AlexBarnsley\Collection;

if (! function_exists('collect')) {
    function collect($value = [])
    {
        return new Collection($value);
    }
}
