<?php

require_once __DIR__.'/lib/strings.php';

$className = 'AccessToken';
$classVariableName = lcfirst($className);
$json = <<<JS
{
  "access_token": "f85632530bf277ec9ac6f649fc327f17",
  "scope": "write_orders,read_customers",
  "expires_in": 86399,
  "associated_user_scope": "write_orders,read_customers",
  "associated_user": {
    "id": 902541635,
    "first_name": "John",
    "last_name": "Smith",
    "email": "john@example.com",
    "account_owner": true
  }
}
JS;

$jsonArray = json_decode($json, true);

$propertiesTemplate = <<<'PT'
PT;

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

$propertyAssignments = [];
foreach ($jsonArray as $variableName => $value) {
    // Compute replacement values
    list($hintType, $type, $getVerb, $quantNoun) = determineTypeVariables($value, $variableName);
    $capVariableName = snakeToPascal($quantNoun);
    $camelName = snakeToCamel($quantNoun);

    // Determine template
    $template = $propertyAssignmentTemplate;
    if ($type === 'array') {
        $template = $arrayAssignmentTemplate;
    }

    // Do replacement
    $assignmentPlaceHolders = ['%className%', '%classVariableName%', '%variableName%', '%capVariableName%', '%camelName%'];
    $assignmentReplacements = [$className, $classVariableName, $variableName, $capVariableName, $camelName];
    $propertyAssignments[] = str_replace($assignmentPlaceHolders, $assignmentReplacements, $template);
}

$classTemplate = <<<'CT'
class %className%
{
    /**
     * @param ResponseInterface $response
     * @return %className%Model
     */
    public function fromResponse(ResponseInterface $response): %className%Model
    {
        $stdClass = json_decode($response->getBody()->getContents());
        return $this->fromShopifyJson%className%($stdClass);
    }

    /**
     * @param stdClass $shopifyJson%className%
     * @return %className%Model
     */
    public function fromShopifyJson%className%(stdClass $shopifyJson%className%): %className%Model
    {
        $%classVariableName% = new %className%Model();

%propertyAssignments%
        return $%classVariableName%;
    }
}
CT;

$classPlaceHolders = ['%className%', '%classVariableName%', '%propertyAssignments%'];
$classReplacements = [$className, $classVariableName, implode(PHP_EOL, $propertyAssignments)];
$classContent = str_replace($classPlaceHolders, $classReplacements, $classTemplate);

echo $classContent;
