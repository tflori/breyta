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
                        } elseif ($tokens[$j] === '{' || $tokens[$j] === ';') {
                            break;
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

        return $class ? substr($namespace, 1) . '\\' . $class : null;
    }

    /**
     * Read the time in the filename of $path
     *
     * Required is only the date (the time 00:00Z is presumed) in ISO format (YYYY-MM-DD).
     *
     * The date may be followed by the time with or without a timezone (time zone Z is presumed). We don't recommend
     * to use colons in time - for this we allow ':', '.' or '-' as dividers in time and time zone.
     *
     * Returns the timestamp (of course in UTC!)
     *
     * @param string $path
     * @return int|null
     */
    public static function getTimeFromFileName(string $path): ?int
    {
        static $regex;
        if (!$regex) {
            $regex = '/' .
                     '(\d{4}-[01]\d-[0-3]\d)' . // the date has to be given
                     '(?>(?> |T)' . // optional subgroup of time requires a divider
                     '([0-2]\d[:.-][0-5]\d(?>[:.-][0-5]\d)?)' . // time requires hh:mm optional with :ss
                     '(Z|[+-][0-2]\d(?>[:.-][0-5]\d)?)?' . // optional subgroup of time zone
                     ')?' . // end subgroup of time
                     '/i';
        }
        $fileName = pathinfo($path, PATHINFO_FILENAME);
        if (!preg_match($regex, $fileName, $match)) {
            return null;
        }

        return strtotime(
            $match[1] . 'T' .
            (isset($match[2]) ? str_replace(['-', '.'], ':', $match[2]) : '00:00') .
            (isset($match[3]) ? str_replace(['-', '.'], ':', $match[3]) : 'Z')
        );
    }
}
