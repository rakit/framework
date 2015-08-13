<?php

namespace Rakit\Framework\Dumper;

class CliDumper extends BaseDumper implements DumperInterface
{

    public function render(\Exception $e)
    {
        $trace = $e->getTrace();
        $main_cause = array_shift($trace);
        $index = count($e->getTrace());
        $result = "";

        $message = $e->getMessage();
        $class = get_class($e);

        // $result .= str_repeat("=", 60);
        $result .= "\n\n  You got an '{$class}'\n";
        $result .= "  \"{$message}\"\n";

        foreach($trace as $i => $data) {
            $file = $this->getPossibilityFile($data);
            $line = $this->getPossibilityLine($data);
            $message = $this->getMessage($data);
            $result .= "\n".str_pad(count($trace)-$i, 3, ' ', STR_PAD_LEFT).") ".$message;
            $result .= "\n     {$file} [{$line}]\n";
        }

        $result .= "\n\n";
        // $result .= str_repeat("=", 60);

        return $result;
    }

    protected function getMessage($trace_data)
    {
        if(isset($trace_data['class'])) {
            $message = $trace_data['class'];
            if(isset($trace_data['function'])) {
                $message .= $trace_data['type'].$trace_data['function'].'()';
            }
        } elseif($trace_data['function']) {
            $message = $trace_data['function'].'()';
        } else {
            $message = "";
        }

        return $message;
    }

}