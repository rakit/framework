<?php

namespace Rakit\Framework\Dumper;

class HtmlDumper extends BaseDumper implements DumperInterface
{

    public function render(\Exception $e)
    {
        $trace = $e->getTrace();
        $main_cause = array_shift($trace);
        $index = count($e->getTrace());

        if($e instanceof \ErrorException AND count($trace) > 0) {
            $main_cause = array_shift($trace);
            $index -= 1;
        }

        $dom_main_cause = $this->renderMainCause($index, $e, $main_cause);
        $dom_traces = $this->renderTraces($trace);
        $message = $e->getMessage();
        $class = get_class($e);

        $result = "
            <html>
                <head>
                    <style>
                        body {
                            background: #efefef;
                        }

                        .w > h3 {
                            font-size: 18px;
                            color: #666;
                            margin: 10px 0px 20px;
                            line-height: 1.5;
                            padding: 0px 30px;
                            text-align: left;
                        }

                        .w > h4 {
                            font-size: 14px;
                            color: #aaa;
                            margin: 10px 0px 0px;
                        }

                        .w > h3:before,
                        .w > h3:after {
                            content: '\"';
                            position: absolute;
                            top: 20px;
                            left: 0px;
                            opacity: .5;
                            display: inline-block;
                            padding: 5px;
                            font-size: 26px;
                        }

                        .w > h3:after {
                            left: auto;
                            right: 0px;
                        }

                        .w, .w ul, .w ul li {
                            box-sizing: border-box;
                            -webkit-box-sizing: border-box;
                            -moz-box-sizing: border-box;
                            font-family: sans-serif;
                            cursor: default;
                            position: relative;
                        }

                        .w {
                            max-width: 600px;
                            margin: 30px auto; 
                        }

                        .w ul {
                            padding: 0px;
                            margin: 0px;
                            float: left;
                            width: 100%;
                            margin-bottom: 50px;
                            box-shadow: 0px 5px 10px 0px rgba(0,0,0,.1);
                        }

                        .w ul li {
                            padding: 10px 20px 10px 60px;
                            float: left;
                            clear: both;
                            width: 100%;
                            background: white;
                            font-size: .7em;
                            color: #444;
                            border-bottom: 1px dashed #efefef;
                            list-style-type: none;
                        }

                        .w ul li:last-child {
                            border: none;
                        }

                        .w ul li h4 {
                            font-size: 1.3em;
                            margin: 0px;
                        }


                        .w ul li span {
                            opacity: .5;
                        }

                        .w ul li h4 span {
                            opacity: .5;
                        }

                        .w ul li.m {
                            font-size: 1em;
                        }

                        .w ul li:not(.m):hover {
                            background: #f7f7f7;
                        }

                        .w ul li em {
                            display: inline-block;
                            width: 50px;
                            text-align: right;
                            position: absolute;
                            left: 0px;
                            top: 10px;
                            font-size: 1.3em;
                            font-weight: bold;
                            opacity: .6;
                        }
                    </style>
                </head>
                <body>
                    <div class='w'>
                        <h4>{$class}</h4>
                        <h3>{$message}</h3>
                        <ul>{$dom_main_cause}{$dom_traces}</ul>
                    </div>
                </body>
            </html>
        ";

        return $result;
    }

    protected function renderMainCause($index, \Exception $e, array $trace)
    {
        $message = $this->getMessage($trace);
        $file = $this->getPossibilityFile($trace, $e);
        $line = $this->getPossibilityLine($trace, $e);

        return "<li class='m' id='{$index}'>".$this->renderTrace($index, $message, $file, $line)."</li>";
    }

    protected function renderTraces(array $traces)
    {
        $dom_traces = "";
        $count_traces = count($traces);
        foreach($traces as $i => $trace) {
            $no = $count_traces-$i;
            $message = $this->getMessage($trace);
            $file = $this->getPossibilityFile($trace);
            $line = $this->getPossibilityLine($trace);

            $dom_traces .= '<li id="'.$no.'">'.$this->renderTrace($no, $message, $file, $line).'</li>';
        }
        return $dom_traces;
    }

    protected function renderTrace($index, $message, $file, $line = null)
    {
        $dom_index = "<em>#{$index}</em>";
        $dom_message = "<h4>{$message}</h4>";
        $info_line = $line? " on line <strong>{$line}</strong>": ", unknown line";
        $dom_info = "<span><strong>{$file}</strong>{$info_line}</span>";
        
        return "{$dom_index}{$dom_message}{$dom_info}";
    }

    protected function getMessage($trace_data)
    {
        $args_string = implode(', ', $this->getArgsDefinition($trace_data));

        if(isset($trace_data['class'])) {
            $message = $trace_data['class'];
            if(isset($trace_data['function'])) {
                $message .= '<span>'.$trace_data['type'].'</span>'.$trace_data['function'].'('.$args_string.')';
            }
        } elseif($trace_data['function']) {
            $message = $trace_data['function'].'('.$args_string.')';
        } else {
            $message = "";
        }

        return $message;
    }

    protected function getArgsDefinition(array $trace_data)
    {
        $args = isset($trace_data['args'])? $trace_data['args'] : [];
        $args_definitions = [];
        foreach ($args as $arg) {
            if (is_object($arg)) {
                $args_definitions[] = get_class($arg);
            } elseif(is_array($arg)) {
                $args_definitions[] = 'Array('.count($arg).')';
            } elseif(is_bool($arg)) {
                $args_definitions[] = $arg? 'true' : 'false';
            } elseif(is_string($arg)) {
                $args_definitions[] = '"'.$arg.'"';
            } else {
                $args_definitions[] = $arg;
            }
        }

        return $args_definitions;
    }

}