<?php

function getPropertyAssignmentTemplate($type) {
    $propertyAssignmentTemplate = <<<'GST'
        if (property_exists($shopifyJson%className%, '%variableName%')) {
            $%classVariableName%->set%capVariableName%($shopifyJson%className%->%variableName%);
        }

GST;

    $arrayAssignmentTemplate = <<<'AAT'
        if (property_exists($shopifyJson%className%, '%variableName%')) {
            $%camelName% = explode(',', $shopifyJson%className%->%variableName%);
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
        if (property_exists($shopifyJson%className%, '%variableName%')) {
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

function getSelfArrayAssignmentTemplate($type) {
    $arrayPropertyAssignmentTemplate = <<<'AGST'
        if (!is_null($this->%camelName%)) {
            $array['%variableName%'] = $this->%camelName%;
        }

AGST;

    $arrayArrayAssignmentTemplate = <<<'AAAT'
        if (!empty($this->%camelName%)) {
            $array['%variableName%'] = implode(',', $this->%camelName%);
        }

AAAT;

    $arrayDateAssignmentTemplate = <<<'ADAT'
        if (!is_null($this->%camelName%)) {
            $array['%variableName%'] = $this->%camelName%->format(DateTime::ISO8601);
        }

ADAT;

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

    return $arrayTemplate;
}

function getObjectArrayAssignmentTemplate() {
    $arrayAssignmentTemplate = <<<'AGST'
        $array['%variableName%'] = $%classVariableName%->%getVerb%%capVariableName%();
AGST;

    return $arrayAssignmentTemplate;
}
