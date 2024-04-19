<?php

namespace Professionator;

class Utils
{

    public static function escape($value)
    {
        if (is_null($value)) {
            return 'nil';
        }
        if ($value === true) {
            return 'true';
        } elseif ($value === false) {
            return 'false';
        }

        if (ctype_digit($value . '')) {
            return $value;
        }

        if (is_numeric($value)) {
            return $value . '';
        }

        if (is_string($value)) {
            return "'" . str_replace("'", "\\'", $value) . "'";
        }

        throw new \Exception('Unknown type for value: ' . $value);
    }

    /**
     * Pass in a suffixId and get back the text for the suffix
     * EG pass in 1203 and get back "of the Bear"
     *
     * @param mixed $variant
     * @return void
     */
    public static function getSuffixText(mixed $variant)
    {
        // TODO
        return null;
    }

    public static function getStringBetween(string $html, string $string, string $string1): ?string
    {
        if (($stringPos = strpos($html, $string)) !== false) {
            $string = substr($html, $stringPos + strlen($string));
            if (($stringPos1 = strpos($string, $string1)) !== false) {
                return substr($string, $stringPos1 + strlen($string1));
            }
        }

        return null;
    }

    public static function getFileContents(string $url): string
    {
        $cacheFileName = md5($url);

        $cacheFilePath = Files::cache("/urls/$cacheFileName");

        if (file_exists($cacheFilePath)) {
            return file_get_contents($cacheFilePath);
        }

        $contents = @file_get_contents($url);

        if (!$contents) {
            throw new \Exception("Could not get contents from url: $url");
        }

        file_put_contents($cacheFilePath, $contents);

        return $contents;

    }

    public static function getLocationNameFromId(mixed $location): ?string
    {

        $url = "https://www.wowhead.com/classic/zone=$location";
        $contents = self::getFileContents($url);

        if (preg_match('/<title>(.*?) - Zone - .*World of Warcraft<\/title>/', $contents, $matches)) {
            return $matches[1];
        }

        return null;

    }

    /**
     * Pass in a string like "Elwynn Forest" and get back "EF"
     */
    public static function abbreviate(?string $string): ?string
    {
        if ($string) {
            $return = '';

            foreach (explode(' ', $string) as $word) {
                $return .= $word[0];
            }

            return strtoupper($return);

        }
        return $string;
    }

    public static function surroundValuesWith(array $array, string $surroundWith): array
    {
        $return = [];

        foreach ($array as $key => $value) {
            $return[$key] = $surroundWith . $value . $surroundWith;
        }

        return $return;
    }

}