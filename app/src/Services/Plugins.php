<?php

namespace Anchorcms\Services;

class Plugins
{

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function getPlugins()
    {
        $plugins = [];

        $if = new \FilesystemIterator($this->path, \FilesystemIterator::SKIP_DOTS);

        foreach ($if as $file) {
            if ($file->isDir()) {
                $key = $file->getBasename();

                $name = ucwords(str_replace(['-', '_'], ' ', $key));

                $plugins[$key] = $name;
            }
        }

        return $plugins;
    }

    public function init()
    {
        $if = new \FilesystemIterator($this->path, \FilesystemIterator::SKIP_DOTS);

        foreach ($fi as $file) {
            $this->load($file);
        }
    }

    public function load($file)
    {
        // todo
    }
}
