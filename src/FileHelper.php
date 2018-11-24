<?php

namespace Breyta;

class FileHelper
{
    /**
     * Get the first full qualified class name in $file
     *
     * @param string $file
     * @return null|string
     */
    public static function getClassFromFile(string $file): ?string
    {
        $fp = fopen($file, 'r');
        $buffer = '';
        $i = 0;
        $class = $namespace = null;

        while (!$class) {
            if (feof($fp)) {
                return null;
            }

            $buffer .= fread($fp, 512);
            $tokens = token_get_all($buffer);

            if (strpos($buffer, '{') === false) {
                continue;
            }

            for (; $i < count($tokens); $i++) {
                if ($tokens[$i][0] === T_NAMESPACE) {
                    for ($j = $i + 1; $j < count($tokens); $j++) {
                        if ($tokens[$j][0] === T_STRING) {
                            $namespace .= '\\' . $tokens[$j][1];
                        } else {
                            if ($tokens[$j] === '{' || $tokens[$j] === ';') {
                                break;
                            }
                        }
                    }
                }

                if ($tokens[$i][0] === T_CLASS) {
                    for ($j = $i + 1; $j < count($tokens); $j++) {
                        if ($tokens[$j] === '{') {
                            $class = $tokens[$i+2][1];
                        }
                    }
                }
            }
        }

        return $class ? $namespace . '\\' . $class : null;
    }
}
