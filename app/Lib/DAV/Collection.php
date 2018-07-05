<?php

namespace App\Lib\DAV;

use Sabre\DAV;

class Collection extends DAV\Collection
{

    private $path;

    function __construct($path)
    {

        $this->path = $path;

    }

    function getChildren()
    {

        $children = array();
        // Loop through the directory, and create objects for each node
        foreach (scandir($this->path) as $node) {

            // Ignoring files staring with .
            if ($node[0] === '.') {
                continue;
            }
            $children[] = $this->getChild($node);

        }

        return $children;

    }

    function getChild($name)
    {

        $path = $this->path . '/' . $name;

        // We have to throw a NotFound exception if the file didn't exist
        if (!file_exists($path)) {
            throw new DAV\Exception\NotFound('The file with name: ' . $name . ' could not be found');
        }

        // Some added security
        if ($name[0] == '.') {
            throw new DAV\Exception\NotFound('Access denied');
        }

        if (is_dir($path)) {

            return new Collection($path);

        }
        else {

            return new File($path);

        }

    }

    function childExists($name)
    {

        return file_exists($this->path . '/' . $name);

    }

    function getName()
    {

        return basename($this->path);

    }

}