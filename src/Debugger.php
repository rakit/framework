<?php namespace Rakit\Framework;

class Debugger {

    public function render(\Exception $e)
    {
        $trace = $e->getTrace();
        $main_cause = array_shift($trace);

        $dom_main_cause = $this->renderMainCause($e, $main_cause);
        $dom_traces = $this->renderTraces($trace);
        $message = $e->getMessage();

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
                            margin: 10px 0px;
                        }
                        .w > h3:before,
                        .w > h3:after {
                            content: '\"';
                            opacity: .5;
                            display: inline-block;
                            padding: 5px;
                            font-size: 26px;
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
                        }

                        .w ul li h4 {
                            font-size: 1.3em;
                            margin: 0px;
                        }


                        .w ul li span {
                            opacity: .5;
                        }

                        .w ul li.m {
                            font-size: 1em;
                            background: #3FB671;
                            color: white;
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
                        <h3>{$message}</h3>
                        <ul>{$dom_main_cause}{$dom_traces}</ul>
                    </div>
                </body>
            </html>
        ";

        return $result;
    }

    protected function renderMainCause(\Exception $e, array $trace)
    {
        $message = $this->getMessage($trace);
        $file = $this->getPossibilityFile($trace, $e);
        $line = $this->getPossibilityLine($trace, $e);
        $count_traces = count($e->getTrace());

        return "<li class='m' id='{$count_traces}'>".$this->renderTrace($count_traces, $message, $file, $line)."</li>";
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
        if(isset($trace_data['class'])) {
            $message = $trace_data['class'];
            if(isset($trace_data['function'])) {
                $message .= '->'.$trace_data['function'].'()';
            }
        } elseif($trace_data['function']) {
            $message = $trace_data['function'].'()';
        } else {
            $message = "";
        }

        return $message;
    }

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