<?php

require_once __DIR__.'/lib/strings.php';

$className = 'Shop';
$unwrap = true;
$classVariableName = lcfirst($className);
$snakeClassName = pascalToSnake($className);
$json = <<<JS
{
    "id": 21887169,
    "name": "store:596ab301c9809",
    "email": "accounts@paulchiu.net",
    "domain": "store-596ab301c9809.myshopify.com",
    "created_at": "2017-07-16T10:29:53+10:00",
    "province": "Queensland",
    "country": "AU",
    "address1": "123 Fake Street",
    "zip": "4000",
    "city": "Brisbane",
    "source": null,
    "phone": "0410464875",
    "updated_at": "2017-07-16T11:25:02+10:00",
    "customer_email": null,
    "latitude": -27.4697707,
    "longitude": 153.0251235,
    "primary_location_id": 46246099,
    "primary_locale": "en",
    "address2": "",
    "country_code": "AU",
    "country_name": "Australia",
    "currency": "AUD",
    "timezone": "(GMT+10:00) Brisbane",
    "iana_timezone": "Australia/Brisbane",
    "shop_owner": "Paul Chiu",
    "money_format": "foo",
    "money_with_currency_format": "foo",
    "weight_unit": "kg",
    "province_code": "QLD",
    "taxes_included": false,
    "tax_shipping": false,
    "county_taxes": true,
    "plan_display_name": "affiliate",
    "plan_name": "affiliate",
    "has_discounts": false,
    "has_gift_cards": false,
    "myshopify_domain": "store-596ab301c9809.myshopify.com",
    "google_apps_domain": null,
    "google_apps_login_enabled": false,
    "money_in_emails_format": "foo",
    "money_with_currency_in_emails_format": "foo",
    "eligible_for_payments": true,
    "requires_extra_payments_agreement": false,
    "password_enabled": true,
    "has_storefront": true,
    "eligible_for_card_reader_giveaway": false,
    "finances": true,
    "setup_required": false,
    "force_ssl": true
}
JS;

$jsonArray = json_decode($json, true);

$dependencyTemplate = <<<'DT'
    /** @var %capVariableName% $%camelName%Transformer */
    protected $%camelName%Transformer;

DT;

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

$unwrapResponseTemplate = <<<'UT'
    /**
     * @param ResponseInterface $response
     * @return %className%Model
     * @throws MissingExpectedAttributeException
     */
    public function fromResponse(ResponseInterface $response): %className%Model
    {
        $stdClass = json_decode($response->getBody()->getContents());

        if (!property_exists($stdClass, '%snakeClassName%')) {
            throw new MissingExpectedAttributeException('%snakeClassName%');
        }

        return $this->fromShopifyJson%className%($stdClass->%snakeClassName%);
    }
UT;

$responseTemplate = <<<'RT'
    /**
     * @param ResponseInterface $response
     * @return %className%Model
     */
    public function fromResponse(ResponseInterface $response): %className%Model
    {
        $stdClass = json_decode($response->getBody()->getContents());
        return $this->fromShopifyJson%className%($stdClass);
    }
RT;

$dependencies = [];
$propertyAssignments = [];
foreach ($jsonArray as $variableName => $value) {
    // Compute replacement values
    list($hintType, $type, $getVerb, $quantNoun) = determineTypeVariables($value, $variableName);
    $capVariableName = snakeToPascal($quantNoun);
    $camelName = snakeToCamel($quantNoun);

    // Determine template
    switch ($type) {
        case 'string':
        case 'int':
        case 'float':
        case 'bool':
            $template = $propertyAssignmentTemplate;
            break;
        case 'array':
            $template = $arrayAssignmentTemplate;
            break;
        case 'DateTime':
            $template = $dateAssignmentTemplate;
            break;
        default:
            $template = $objectAssignTemplate;
    }

    // Do replacement
    $assignmentPlaceHolders = ['%className%', '%classVariableName%', '%variableName%', '%capVariableName%', '%camelName%'];
    $assignmentReplacements = [$className, $classVariableName, $variableName, $capVariableName, $camelName];
    $propertyAssignments[] = str_replace($assignmentPlaceHolders, $assignmentReplacements, $template);

    // Do object templates
    if (is_array($value)) {
        $dependencies[] = str_replace($assignmentPlaceHolders, $assignmentReplacements, $dependencyTemplate);
    }
}

$responseTemplate = ($unwrap) ? $unwrapResponseTemplate : $responseTemplate;
$responsePlaceHolders = ['%className%', '%snakeClassName%'];
$responseReplacements = [$className, $snakeClassName];
$responseParser = str_replace($responsePlaceHolders, $responseReplacements, $responseTemplate);

$classTemplate = <<<'CT'
class %className%
{
%dependencies%

%responseParser%

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

$classPlaceHolders = ['%className%', '%classVariableName%', '%responseParser%', '%dependencies%', '%propertyAssignments%'];
$classReplacements = [$className, $classVariableName, $responseParser, implode(PHP_EOL, $dependencies), implode(PHP_EOL, $propertyAssignments)];
$classContent = str_replace($classPlaceHolders, $classReplacements, $classTemplate);

echo $classContent;
