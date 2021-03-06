<?php

function getPropertyAssignmentTemplate($type) {
    $propertyAssignmentTemplate = <<<'GST'
        if (property_exists($shopifyJson%className%, '%variableName%')) {
            $%classVariableName%->set%capVariableName%($shopifyJson%className%->%variableName%);
        }

GST;

    $arrayAssignmentTemplate = <<<'AAT'
        if (property_exists($shopifyJson%className%, '%variableName%')
            && !empty($shopifyJson%className%->%variableName%)
        ) {
            $%camelName% = array_map('trim', explode(',', $shopifyJson%className%->%variableName%));
            $%classVariableName%->set%capVariableName%($%camelName%);
        }

AAT;

    $objectAssignTemplate = <<<'OAT'
        if (property_exists($shopifyJson%className%, '%variableName%')) {
            $%camelName% = $this->%camelName%Transformer
                ->fromShopifyJson%capVariableName%($shopifyJson%className%->%variableName%);
            $%classVariableName%->set%capVariableName%($%camelName%);
        }

OAT;

    $dateAssignmentTemplate = <<<'DAT'
        if (property_exists($shopifyJson%className%, '%variableName%')
            && !empty($shopifyJson%className%->%variableName%)
        ) {
            $%camelName% = new DateTime($shopifyJson%className%->%variableName%);
            $%classVariableName%->set%capVariableName%($%camelName%);
        }

DAT;

    switch ($type) {
        case 'string':
        case 'int':
        case 'float':
        case 'bool':
            $propertyTemplate = $propertyAssignmentTemplate;
            break;
        case 'array':
            $propertyTemplate = $arrayAssignmentTemplate;
            break;
        case 'DateTime':
            $propertyTemplate = $dateAssignmentTemplate;
            break;
        default:
            $propertyTemplate = $objectAssignTemplate;
    }

    return $propertyTemplate;
}

function getSelfArrayAssignmentTemplate($type, $hintType = null) {
    $arrayPropertyAssignmentTemplate = <<<'AGST'
        if (!is_null($this->%camelName%)) {
            $array['%variableName%'] = $this->%camelName%;
        }

AGST;

    $arrayArrayAssignmentTemplate = <<<'AAAT'
        if (!empty($this->%camelName%)) {
            $array['%variableName%'] = array_map('trim', implode(',', $this->%camelName%));
        }

AAAT;

    $arrayDateAssignmentTemplate = <<<'ADAT'
        if (!is_null($this->%camelName%)) {
            $array['%variableName%'] = $this->%camelName%->format(DateTime::ISO8601);
        }

ADAT;

    $arrayArrayObjectAssignmentTemplate = <<<'AAOAT'
        if (!empty($this->%camelName%)) {
            $array['%variableName%'] = array_map([$this->%variableName%Transformer, 'toArray'], $this->%variableName%);
        }

AAOAT;

    $arrayObjectAssignmentTemplate = <<<'AOAT'
        if (!is_null($this->%camelName%)) {
            $array = $this->%camelName%Transformer->toArray($this->%camelName%);
        }

AOAT;


    switch ($type) {
        case 'array':
            $arrayTemplate = $arrayArrayAssignmentTemplate;
            break;
        case 'DateTime':
            $arrayTemplate = $arrayDateAssignmentTemplate;
            break;
        default:
            $arrayTemplate = $arrayPropertyAssignmentTemplate;
    }

    if (strpos($hintType, 'array|') !== false) {
        $arrayTemplate = $arrayArrayObjectAssignmentTemplate;
    } elseif (strpos($type, 'Model') !== false) {
        $arrayTemplate = $arrayObjectAssignmentTemplate;
    }

    return $arrayTemplate;
}

function getObjectArrayAssignmentTemplate($type, $hintType = null) {
    $arrayAssignmentTemplate = <<<'AGST'
        $array['%variableName%'] = $%classVariableName%->%getVerb%%capVariableName%();
AGST;

    $arrayArrayStringAssignmentTemplate = <<<'AASAT'
        if ($%classVariableName%->%getVerb%%capVariableName%() !== null) {
            $array['%variableName%'] = implode(',', $%classVariableName%->%getVerb%%capVariableName%());
        }
AASAT;

    $arrayDateTimeAssignmentTemplate = <<<'ADTAT'
        $array['%variableName%'] = ($%classVariableName%->%getVerb%%capVariableName%()) ? $%classVariableName%->%getVerb%%capVariableName%()->format(DateTime::ISO8601) : null;
ADTAT;

    $arrayObjectAssignmentTemplate = <<<'ADTAT'
        if ($%classVariableName%->%getVerb%%capVariableName%() !== null) {
            $array['%variableName%'] = $this->%variableName%Transformer->toArray($%classVariableName%->%getVerb%%capVariableName%());
        }
ADTAT;

    $arrayArrayObjectAssignmentTemplate = <<<'AAOAT'
        if ($%classVariableName%->%getVerb%%capVariableName%() !== null) {
            $array['%variableName%'] = array_map([$this->%variableName%Transformer, 'toArray'], $%classVariableName%->%getVerb%%capVariableName%());
        }

AAOAT;

    switch ($type) {
        case 'string':
        case 'int':
        case 'float':
        case 'bool':
            $arrayTemplate = $arrayAssignmentTemplate;
            break;
        case 'DateTime':
            $arrayTemplate = $arrayDateTimeAssignmentTemplate;
            break;
        default:
            $arrayTemplate = $arrayObjectAssignmentTemplate;
    }

    if (strpos($hintType, 'array|string[]') !== false) {
        $arrayTemplate = $arrayArrayStringAssignmentTemplate;
    } else if (strpos($hintType, 'array|') !== false) {
        $arrayTemplate = $arrayArrayObjectAssignmentTemplate;
    } else if (strpos($type, 'Model') !== false) {
        $arrayTemplate = $arrayObjectAssignmentTemplate;
    }

    return $arrayTemplate;
}
