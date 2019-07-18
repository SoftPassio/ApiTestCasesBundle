<?php

namespace SoftPassio\ApiTestCasesBundle\Api\Cases;

final class PathBuilder
{
    public static function build(...$segments)
    {
        return implode(DIRECTORY_SEPARATOR, $segments);
    }
}
