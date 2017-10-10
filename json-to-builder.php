<?php

require_once __DIR__.'/lib/strings.php';
require_once __DIR__.'/lib/typed-templates.php';

$className = 'GetMetafieldsRequest';
$filterEmpty = true;
$wrapAsModel = 'metafields';
$json = <<<JS
{
  "limit": 1,
  "since_id": 1,
    "path": "\/leopard",
    "target": "\/pages\/macosx",
  "metafield_fields": {"foo": "bar"}
}
JS;

$jsonArray = json_decode($json, true);

$dependencyTemplate = <<<'DT'
    /** @var %capVariableName% $%camelName%Transformer */
    protected $%camelName%Transformer;
DT;

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

$filterEmptyTemplate = <<<'FET'
        $array = array_filter($array);
FET;

$wrapReturnTemplate = <<<'WRT'
        return ['%s' => $array];
WRT;

$normalReturnTemplate = <<<'NRT'
        return $array;
NRT;


$properties = [];
$withers = [];
$arrayAssignments = [];
$dependencies = [];
foreach ($jsonArray as $variableName => $value) {
    // Compute replacement values
    list($hintType, $type, $getVerb, $quantNoun, $nullable, $variableName) = determineTypeVariables($value, $variableName);
    $capVariableName = snakeToPascal($quantNoun);
    $camelName = snakeToCamel($quantNoun);

    // Prepare array assignments
    $assignmentPlaceHolders = ['%className%', '%variableName%', '%capVariableName%', '%camelName%'];
    $assignmentReplacements = [$className, $variableName, $capVariableName, $camelName];
    $template = getSelfArrayAssignmentTemplate($type, $hintType);
    $arrayAssignments[] = str_replace($assignmentPlaceHolders, $assignmentReplacements, $template);

    // Prepare property
    $propertyPlaceHolders = ['%hintType%', '%variableName%'];
    $propertyReplacements = [$hintType, $camelName];
    $properties[] = str_replace($propertyPlaceHolders, $propertyReplacements, $propertiesTemplate);

    // Prepare getter and setter
    $witherPlaceHolders = ['%className%', '%hintType%', '%type%', '%getVerb%', '%capVariableName%', '%variableName%'];
    $witherReplacements = [$className, $hintType, $type, $getVerb, $capVariableName, $camelName];
    $withers[] = str_replace($witherPlaceHolders, $witherReplacements, $witherTemplate);

    // Do object templates
    if (is_array($value)) {
        $dependencies[] = str_replace($assignmentPlaceHolders, $assignmentReplacements, $dependencyTemplate);
    }
}

$filterEmptyContent = ($filterEmpty) ? $filterEmptyTemplate : '';
$returnContent = ($wrapAsModel) ? sprintf($wrapReturnTemplate, $wrapAsModel) : $normalReturnTemplate;

$classTemplate = <<<'CT'
class %className%
{
%dependencies%

%properties%

    /**
     * @return array
     */
    public function toArray(): array
    {
        $array = [];

%arrayAssignments%

%arrayFilterEmpty%

%returnContent%
    }

%withers%
}
CT;

$classPlaceHolders = ['%className%', '%properties%', '%withers%', '%arrayAssignments%', '%arrayFilterEmpty%', '%dependencies%', '%returnContent%'];
$classReplacements = [$className, implode(PHP_EOL, $properties), implode(PHP_EOL, $withers), implode(PHP_EOL, $arrayAssignments), $filterEmptyContent, implode(PHP_EOL, $dependencies), $returnContent];
$classContent = str_replace($classPlaceHolders, $classReplacements, $classTemplate);

echo $classContent;
