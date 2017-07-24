<?php

function snakeToPascal($string) {
    return str_replace('_', '', ucwords($string, '_'));
}

function pascalToSnake($string) {
	return ltrim(strtolower(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '_$0', $string)), '_');
}

function snakeToCamel($string) {
    return lcfirst(snakeToPascal($string));
}

function pluralize($string) {
    $lastChar = strtolower($string[strlen($string)-1]);

    if ($lastChar === 's') {
        return $string;
    }

    switch ($lastChar) {
        case 'y':
            return substr($string,0,-1).'ies';
        case 's':
            return $string.'es';
        default:
            return $string.'s';
    }
}

function determineTypeVariables($value, $key = null) {
    $hintType = $type = 'string';
    $getVerb = 'get';
    $quantNoun = $key;

    if (is_integer($value)) {
        $hintType = $type = 'int';
    } elseif (is_float($value)) {
        $hintType = $type = 'float';
    } elseif (is_bool($value)) {
        $hintType = $type = 'bool';
        $getVerb = 'is';
    } elseif (is_string($value) && strpos($value, ',') !== false) {
        $arrayValues = array_map('trim', explode(',', $value));
        $arrayValueType = determineTypeVariables($arrayValues[0])[0];
        $hintType = sprintf('array|%s[]', $arrayValueType);
        $type = 'array';
        $quantNoun = pluralize($key);
    } elseif (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $value) === 1) {
        $hintType = $type = 'DateTime';
    } elseif (is_array($value)) {
        $hintType = $type = snakeToPascal($key);
    }

    return [$hintType, $type, $getVerb, $quantNoun];
}

