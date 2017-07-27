<?php

require_once __DIR__.'/lib/strings.php';
require_once __DIR__.'/lib/typed-templates.php';

$className = 'CreateNewCustomersRequest';
$json = <<<JS
{
  "customer_model": {"foo": "bar"},
  "nullable_send_email_invite": true,
  "array_metafields": [{"foo": "bar"}],
  "nullable_password": "newpass",
  "nullable_password_confirmation": "newpass"
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

$properties = [];
$withers = [];
$arrayAssignments = [];
foreach ($jsonArray as $variableName => $value) {
    // Compute replacement values
    list($hintType, $type, $getVerb, $quantNoun, $nullable, $variableName) = determineTypeVariables($value, $variableName);
    $capVariableName = snakeToPascal($quantNoun);
    $camelName = snakeToCamel($quantNoun);

    // Prepare array assignments
    $assignmentPlaceHolders = ['%className%', '%variableName%', '%capVariableName%', '%camelName%'];
    $assignmentReplacements = [$className, $variableName, $capVariableName, $camelName];
    $template = getSelfArrayAssignmentTemplate($type);
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
