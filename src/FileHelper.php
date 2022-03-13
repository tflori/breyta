<?php

namespace Breyta;

class FileHelper
{
    const NAMESPACE_PATTERN = '/^[ \t]*namespace[ \t]+([a-z_][a-z0-9_]*(?:\\\\[a-z_][a-z0-9_]*)*)[ \t]*;$/mi';
    const CLASS_PATTERN = '/^[ \t]*class[ \t]+([a-z_][a-z0-9_]*)(?:[ \t\n]+extends [a-z0-9_]+)?[ \t\n]*{$/mi';

    /**
     * Get the first full qualified class name in $file
     *
     * @param string $file
     * @return null|string
     */
    public static function getClassFromFile(string $file): ?string
    {
        $fp = fopen($file, 'r');
        $content = fread($fp, 4096); // has to be in the first 4096 characters
        $name = [];
        if (preg_match(self::NAMESPACE_PATTERN, $content, $match)) {
            $name[] = $match[1];
        }

        if (!preg_match(self::CLASS_PATTERN, $content, $match)) {
            return null;
        }

        $name[] = $match[1];
        return  implode('\\', $name);
    }

    /**
     * Find all migrations in $path
     *
     * @param string $path
     * @return array
     */
    public static function findMigrationFiles(string $path): array
    {
        $result = [];

        /** @var \SplFileInfo $fileInfo */
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) as $fileInfo) {
            if (is_dir($fileInfo->getPathname()) ||
                $fileInfo->getFilename()[0] === '.' ||
                substr($fileInfo->getFilename(), -4) !== '.php'
            ) {
                continue;
            }

            $className = self::getClassFromFile($fileInfo->getPathname());
            if (!$className) {
                continue;
            }

            require_once $fileInfo->getPathname();
            if (!is_subclass_of($className, AbstractMigration::class)) {
                continue;
            }

            $file = substr($fileInfo->getPathname(), strlen($path) + 1);
            $result[] = $file;
        }

        return $result;
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
