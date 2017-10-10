<?php

$class = 'RedirectFields';
$scopes = [
    'id',
    'path',
    'target',
];

$constTemplate = 'const %s = \'%s\';'.PHP_EOL;

foreach ($scopes as $s) {
    echo sprintf($constTemplate, strtoupper($s), $s);
}

$withTemplate = <<<'W'

    /**
     * @return %s
     */
    public function with%s(): %s
    {
        $new = clone $this;
        $new->fields[] = self::%s;

        return $new;
    }
W;

foreach ($scopes as $s) {
    $pascalCase = str_replace(' ', '', ucwords(str_replace('_', ' ', $s)));
    $upperCase = strtoupper($s);
    echo sprintf($withTemplate, $class, $pascalCase, $class, $upperCase);
}
