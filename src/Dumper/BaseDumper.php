<?php

namespace Rakit\Framework\Dumper;

abstract class BaseDumper
{

    protected function getPossibilityFile(array $trace_data, \Exception $e = null)
    {
        if(isset($trace_data['file'])) {
            $file = $trace_data['file'];
        } elseif($e AND $e->getFile()) {
            $file = $e->getFile();
        } elseif(isset($trace_data['class'])) {
            $ref = new \ReflectionClass($trace_data['class']);
            $file = $ref->getFileName();
        } else {
            $file = "unknown file";
        }

        return $file;
    }

    protected function getPossibilityLine(array $trace_data, \Exception $e = null)
    {
        if(isset($trace_data['line'])) {
            $line = $trace_data['line'];
        } elseif($e AND $e->getLine()) {
            $line = $e->getLine();
        } else {
            $line = null;
        }

        return $line;
    }
    
}