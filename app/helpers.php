<?php

if (! function_exists('pickRandom')) {
    function pickRandom(array $haystack): string
    {
        $index = array_rand($haystack);

        return $haystack[$index];
    }
}
