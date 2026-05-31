<?php

namespace SimpleJWTLogin\Libraries;

class ParseRequest
{
    const UPLOAD_ERR_CANT_WRITE = 7;

    /**
     * @param array $server
     *
     * @return array|array[]
     */
    public static function process($server)
    {
        $serverContentType = isset($server['CONTENT_TYPE'])
            ? $server['CONTENT_TYPE']
            : 'application/x-www-form-urlencoded';

        $contentParts = explode(';', $serverContentType);

        $boundary = '';
        $encoding = '';

        $contentType = array_shift($contentParts);

        foreach ($contentParts as $part) {
            if (strpos($part, 'boundary') !== false) {
                $part = explode('=', $part, 2);
                if (!empty($part[1])) {
                    $boundary = '--' . $part[1];
                }
            } elseif (strpos($part, 'charset') !== false) {
                $part = explode('=', $part, 2);
                if (!empty($part[1])) {
                    $encoding = $part[1];
                }
            }
            if ($boundary !== '' && $encoding !== '') {
                break;
            }
        }

        if ($contentType == 'multipart/form-data') {
            return self::fetchFromMultipart($boundary);
        }

        // can be handled by built in PHP functionality
        $content = file_get_contents('php://input');

        $variables = json_decode($content, true);

        if (empty($variables)) {
            parse_str($content, $variables);
        }

        return ['variables' => $variables, 'files' => []];
    }

    /**
     * @param string $boundary
     *
     * @return array|array[]
     */
    private static function fetchFromMultipart($boundary)
    {
        $result = ['variables' => [], 'files' => []];

        $raw = file_get_contents('php://input');
        if ($raw === false) {
            return $result;
        }

        $lines     = preg_split('/\r\n|\n|\r/', $raw);
        $index     = 0;
        $lineCount = count($lines);

        $sanity = isset($lines[$index]) ? $lines[$index] : '';
        $index++;

        // malformed file, boundary should be first item
        if (rtrim($sanity) !== $boundary) {
            return $result;
        }

        $rawHeaders = '';

        while ($index < $lineCount) {
            $chunk = $lines[$index];
            $index++;

            if ($chunk === $boundary) {
                continue;
            }

            if (!empty(trim($chunk))) {
                $rawHeaders .= $chunk . "\r\n";
                continue;
            }

            $result     = self::parseRawHeader($lines, $lineCount, $index, $rawHeaders, $boundary, $result);
            $rawHeaders = '';
        }

        return $result;
    }

    /**
     * @param array  $lines
     * @param int    $lineCount
     * @param int    $index
     * @param string $rawHeaders
     * @param string $boundary
     * @param array  $result
     *
     * @return array
     */
    private static function parseRawHeader($lines, $lineCount, &$index, $rawHeaders, $boundary, $result)
    {
        $variables = $result['variables'];
        $headers = [];

        foreach (explode("\r\n", $rawHeaders) as $header) {
            if (strpos($header, ':') === false) {
                continue;
            }
            list($name, $value) = explode(':', $header, 2);
            $headers[strtolower($name)] = ltrim($value, ' ');
        }

        if (!isset($headers['content-disposition'])) {
            return ['variables' => $variables];
        }

        if (!preg_match(
            '/^(.+); *name="([^"]+)"(; *filename="([^"]+)")?/',
            $headers['content-disposition'],
            $matches
        )) {
            return ['variables' => $variables];
        }

        $name     = isset($matches[2]) ? $matches[2] : ''; // @phpstan-ignore-line
        $filename = isset($matches[4]) ? $matches[4] : '';

        if (!empty($filename)) {
            return ['variables' => $variables];
        }

        $variables = self::fetchVariables($lines, $lineCount, $index, $boundary, $name, $variables, $headers);

        return ['variables' => $variables];
    }

    /**
     * @param array  $lines
     * @param int    $lineCount
     * @param int    $index
     * @param string $boundary
     * @param string $name
     * @param array  $variables
     * @param array  $headers
     *
     * @return array
     */
    private static function fetchVariables($lines, $lineCount, &$index, $boundary, $name, $variables, $headers)
    {
        $fullValue = '';
        $lastLine  = null;

        while ($index < $lineCount) {
            $chunk = $lines[$index];
            $index++;

            if (strpos($chunk, $boundary) === 0) {
                break;
            }

            if ($lastLine !== null) {
                $fullValue .= $lastLine;
            }

            $lastLine = $chunk;
        }

        if ($lastLine !== null) {
            $fullValue .= rtrim($lastLine, "\r\n");
        }

        if (isset($headers['content-type'])) {
            $encoding = '';

            foreach (explode(';', $headers['content-type']) as $part) {
                if (strpos($part, 'charset') !== false) {
                    $part = explode('=', $part);
                    if (isset($part[1])) {
                        $encoding = $part[1];
                    }
                    break;
                }
            }

            if ($encoding !== '' && strtoupper($encoding) !== 'UTF-8' && strtoupper($encoding) !== 'UTF8') {
                $tmp = mb_convert_encoding($fullValue, 'UTF-8', $encoding);
                if ($tmp !== false) {
                    $fullValue = $tmp;
                }
            }
        }

        $fullValue = $name . '=' . $fullValue;

        $tmp = [];
        parse_str($fullValue, $tmp);

        return self::expandVariables(explode('[', $name), $variables, $tmp);
    }

    /**
     * @param array $names
     * @param mixed $variables
     * @param array $values
     *
     * @return array
     */
    private static function expandVariables(array $names, $variables, array $values)
    {
        if (!is_array($variables)) {
            return $values;
        }

        $name = rtrim(array_shift($names), ']');
        if ($name !== '') {
            $name = $name . '=p';

            $tmp = [];
            parse_str($name, $tmp);

            $tmp  = array_keys($tmp);
            $name = reset($tmp);
        }

        if ($name === '') {
            $variables[] = reset($values);
        } elseif (isset($variables[$name]) && isset($values[$name])) {
            $variables[$name] = self::expandVariables($names, $variables[$name], $values[$name]);
        } elseif (isset($values[$name])) {
            $variables[$name] = $values[$name];
        }

        return $variables;
    }
}
