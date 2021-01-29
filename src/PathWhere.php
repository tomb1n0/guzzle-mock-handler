<?php

namespace Tomb1n0\GuzzleMockHandler;

class PathWhere
{
    private $key;
    private $segmentIndex;
    private $regex;

    public function __construct($key, $regex, $path)
    {
        $this->key = $key;
        $this->regex = $regex;
        $this->segmentIndex = $this->getSegmentIndexFromPath($path);
    }

    private function getSegmentIndexFromPath($path)
    {
        $pathSegments = explode('/', trim($path, '/') . '/');
        foreach ($pathSegments as $index => $segment) {
            if ($segment === '{' . $this->key . '}') {

                return $index;
            }
        }
    }

    public function getSegmentFromPath($path = '')
    {
        $pathSegments = explode('/', trim($path, '/') . '/');

        return $pathSegments[$this->segmentIndex];
    }

    public function matches($path)
    {
        $segment = $this->getSegmentFromPath($path);

        preg_match('/' . $this->regex . '/', $segment, $matches);

        return count($matches) > 0;
    }

    public function replaceWhereInPath($path)
    {
        $pathSegments = explode('/', trim($path, '/') . '/');
        $pathSegments[$this->segmentIndex] = $this->regex;

        return implode('/', $pathSegments);
    }
}
