<?php

namespace Automaticko\ACL\Services;

use Illuminate\Foundation\Application as App;
use Automaticko\ACL\Exceptions\ConfigExportNotAllowedException;

class ConfigExportService
{
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function export(array $array, $filename, $path = null)
    {
        if (!$this->app->runningInConsole()) {
            throw new ConfigExportNotAllowedException(trans('acl.exceptions.config_export_not_allowed'));
        }

        $path_info = pathinfo($filename);

        if (empty($path_info['extension'])) {
            $filename = $path_info['filename'] . '.php';
        }

        $path      = $path ?: config_path();
        $file_path = $path . DIRECTORY_SEPARATOR . $filename;

        $string = $this->toString($array);

        return file_put_contents($file_path, $string);
    }

    public function toString(array $array)
    {
        $string = "<?php\n\n";
        $string .= "return [\n";
        $string .= $this->arrayToString($array);
        $string .= "];\n";

        return $string;
    }

    protected function arrayToString(array $array, $tabs = 1)
    {
        $string = '';
        foreach ($array as $index => $value) {
            if (is_integer($index) || defined($index)) {
                $sub_string = $this->indent("{$index} => ", $tabs);
            } else {
                $sub_string = $this->indent("'{$index}' => ", $tabs);
            }
            if (is_array($value)) {
                $sub_string .= "[\n";
                $sub_string .= $this->arrayToString($value, $tabs + 1);
                $sub_string .= $this->indent("],\n", $tabs);
            } else {
                if (is_string($value)) {
                    if (strpos($value, '![CDATA[') === 0 && strrpos($value, ']]', -1) === strlen($value) - 2) {
                        $value = substr($value, strlen('![CDATA['), strlen($value) - strlen('![CDATA[') - strlen(']]'));
                        $sub_string .= "{$value},\n";
                    } else {
                        $sub_string .= "'{$value}',\n";
                    }
                } elseif (is_numeric($value)) {
                    $sub_string .= "{$value},\n";
                } elseif (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                    $sub_string .= "{$value},\n";
                } elseif (null === $value) {
                    $sub_string .= "'',\n";
                }
            }

            $string .= $sub_string;
        }

        return $string;
    }

    protected function indentString($size = 0, $as_spaces = true, $tab_size = 4)
    {
        $spaces = '';

        if ($as_spaces) {
            for ($i = 0; $i < $size * $tab_size; $i += 1) {
                $spaces .= ' ';
            }
        } else {
            for ($i = 0; $i < $size; $i += 1) {
                $spaces .= "\t";
            }
        }

        return $spaces;
    }

    protected function indent($string, $size)
    {
        return $this->indentString($size) . $string;
    }
}
