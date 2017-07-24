<?php

require_once __DIR__.'/lib/strings.php';

$className = 'Shop';
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

$propertiesTemplate = <<<'PT'
    /** @var %hintType% $%variableName% */
    protected $%variableName%;
PT;

$getterSetterTemplate = <<<'GST'
    /**
     * @return %hintType%
     */
    public function %getVerb%%capVariableName%():? %type%
    {
        return $this->%variableName%;
    }

    /**
     * @param %hintType% $%variableName%
     * @return %className%
     */
    public function set%capVariableName%(%type% $%variableName%): %className%
    {
        $this->%variableName% = $%variableName%;
        return $this;
    }

GST;

$properties = [];
$gettersAndSetters = [];
foreach ($jsonArray as $variableName => $value) {
    // Compute replacement values
    list($hintType, $type, $getVerb, $quantNoun) = determineTypeVariables($value, $variableName);
    $capVariableName = snakeToPascal($quantNoun);
    $camelName = snakeToCamel($quantNoun);

    // Prepare property
    $propertyPlaceHolders = ['%hintType%', '%variableName%'];
    $propertyReplacements = [$hintType, $camelName];
    $properties[] = str_replace($propertyPlaceHolders, $propertyReplacements, $propertiesTemplate);

    // Prepare getter and setter
    $getterSetterPlaceHolders = ['%className%', '%hintType%', '%type%', '%getVerb%', '%capVariableName%', '%variableName%'];
    $getterSetterReplacements = [$className, $hintType, $type, $getVerb, $capVariableName, $camelName];
    $gettersAndSetters[] = str_replace($getterSetterPlaceHolders, $getterSetterReplacements, $getterSetterTemplate);
}

$classTemplate = <<<'CT'
class %className%
{
%properties%

%gettersAndSetters%
}
CT;

$classPlaceHolders = ['%className%', '%properties%', '%gettersAndSetters%'];
$classReplacements = [$className, implode(PHP_EOL, $properties), implode(PHP_EOL, $gettersAndSetters)];
$classContent = str_replace($classPlaceHolders, $classReplacements, $classTemplate);

echo $classContent;
