<?php

require_once __DIR__.'/lib/strings.php';

$className = 'GetCustomersRequest';
$json = <<<JS
{
  "ids": "3,5",
  "since_id": 7,
  "created_at_min": "2014-04-25T16:15:47-04:00",
  "created_at_max": "2014-04-25T16:15:47-04:00",
  "updated_at_min": "2014-04-25T16:15:47-04:00",
  "updated_at_max": "2014-04-25T16:15:47-04:00",
  "limit": 11,
  "page": 13,
  "customer_fields": {"foo": "bar"}
}
JS;

$jsonArray = json_decode($json, true);

$propertiesTemplate = <<<'PT'
    /** @var %hintType% $%variableName% */
    protected $%variableName%;
PT;

$witherTemplate = <<<'W'
    /**
     * @param %hintType% $%variableName%
     * @return %className%
     */
    public function with%capVariableName%(%type% $%variableName%): %className%
    {
        $new = clone $this;
        $new->%variableName% = $%variableName%;

        return $new;
    }

W;

$propertyAssignmentTemplate = <<<'GST'
        if (!is_null($this->%camelName%)) {
            $array['%variableName%'] = $this->%camelName%;
        }

GST;

$arrayAssignmentTemplate = <<<'AAT'
        if (!empty($this->%camelName%)) {
            $array['%variableName%'] = implode(',', $this->%camelName%);
        }

AAT;

$dateAssignmentTemplate = <<<'DAT'
        if (!is_null($this->%camelName%)) {
            $array['%variableName%'] = $this->%camelName%->format(DateTime::ISO8601);
        }

DAT;

$properties = [];
$withers = [];
$arrayAssignments = [];
foreach ($jsonArray as $variableName => $value) {
    // Compute replacement values
    list($hintType, $type, $getVerb, $quantNoun) = determineTypeVariables($value, $variableName);
    $capVariableName = snakeToPascal($quantNoun);
    $camelName = snakeToCamel($quantNoun);

    // Determine array assignment template
    switch ($type) {
        case 'array':
            $template = $arrayAssignmentTemplate;
            break;
        case 'DateTime':
            $template = $dateAssignmentTemplate;
            break;
        default:
            $template = $propertyAssignmentTemplate;
    }

    // Prepare array assignments
    $assignmentPlaceHolders = ['%className%', '%variableName%', '%capVariableName%', '%camelName%'];
    $assignmentReplacements = [$className, $variableName, $capVariableName, $camelName];
    $arrayAssignments[] = str_replace($assignmentPlaceHolders, $assignmentReplacements, $template);

    // Prepare property
    $propertyPlaceHolders = ['%hintType%', '%variableName%'];
    $propertyReplacements = [$hintType, $camelName];
    $properties[] = str_replace($propertyPlaceHolders, $propertyReplacements, $propertiesTemplate);

    // Prepare getter and setter
    $witherPlaceHolders = ['%className%', '%hintType%', '%type%', '%getVerb%', '%capVariableName%', '%variableName%'];
    $witherReplacements = [$className, $hintType, $type, $getVerb, $capVariableName, $camelName];
    $withers[] = str_replace($witherPlaceHolders, $witherReplacements, $witherTemplate);
}

$classTemplate = <<<'CT'
class %className%
{
%properties%

    /**
     * @return array
     */
    public function toArray(): array
    {
        $array = [];

%arrayAssignments%

        return $array;
    }

%withers%
}
CT;

$classPlaceHolders = ['%className%', '%properties%', '%withers%', '%arrayAssignments%'];
$classReplacements = [$className, implode(PHP_EOL, $properties), implode(PHP_EOL, $withers), implode(PHP_EOL, $arrayAssignments)];
$classContent = str_replace($classPlaceHolders, $classReplacements, $classTemplate);

echo $classContent;
