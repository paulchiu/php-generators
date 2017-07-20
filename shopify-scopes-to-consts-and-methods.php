<?php

$scopes = [
    'read_content',
    'write_content',
    'read_themes',
    'write_themes',
    'read_products',
    'write_products',
    'read_customers',
    'write_customers',
    'read_orders',
    'write_orders',
    'read_draft_orders',
    'write_draft_orders',
    'read_script_tags',
    'write_script_tags',
    'read_fulfillments',
    'write_fulfillments',
    'read_shipping',
    'write_shipping',
    'read_analytics',
    'read_users',
    'write_users',
    'read_checkouts',
    'write_checkouts',
    'read_reports',
    'write_reports',
    'read_price_rules',
    'write_price_rules',
];

$constTemplate = 'const SCOPE_%s = \'%s\';'.PHP_EOL;

foreach ($scopes as $s) {
    echo sprintf($constTemplate, strtoupper($s), $s);
}

$withTemplate = <<<'W'


    /**
     * @return AuthorizePrompt
     */
    public function with%sScope(): AuthorizePrompt
    {
        $new = clone $this;
        $new->scopes[] = self::SCOPE_%s;

        return $new;
    }
W;

foreach ($scopes as $s) {
    $pascalCase = str_replace(' ', '', ucwords(str_replace('_', ' ', $s)));
    $upperCase = strtoupper($s);
    echo sprintf($withTemplate, $pascalCase, $upperCase);
}
