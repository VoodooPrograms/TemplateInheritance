<?php

declare(strict_types=1);

class ViewSupport
{
    protected string $assetsPath;
    public TemplateInheritance $template;

    public function __construct(TemplateInheritance $template, string $assetsPath = 'assets') {
        $this->template = $template;
        $this->assetsPath = $assetsPath;
    }

    private function isPrintable($var): bool
    {
        if (is_array($var)) {
            foreach ($var as $v) {
                if (!$this->isPrintable($v))
                    return false;
            }
            return true;
        }
        if (is_object($var)) {
            if (method_exists($var, '__toString'))
                return true;
            return false;
        }
        if (is_numeric($var)) {
            return true;
        }
        if (is_string($var)) {
            return true;
        }
        //if no match, return false
        return false;
    }

    public function uppercase(string $text): string
    {
        return strtoupper($text);
    }

    public function lowercase($text): string
    {
        return strtolower($text);
    }

    public function date($format = "l jS \of F Y h:i:s A"): void
    {
        echo date($format);
    }

    public function print($var): void
    {
        if(is_array($var)){
            foreach ($var as $v)
                if ($this->isPrintable($v)) {
                    $this->print($v);
                    if (next($var))
                        echo " ";
                } else
                    echo "Error: not printable";
        } else if ($this->isPrintable($var)) {
            echo $var.'<br/>';
        }
    }
}
