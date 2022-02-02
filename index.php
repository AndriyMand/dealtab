<?php 
require_once ('/home/www/script-test/auto_podium/src/crest.php');

$fields = array(
    'PLACEMENT' => 'CRM_DEAL_DETAIL_TAB',
    'HANDLER'   => 'https://app.auspex.com.ua/script-test/auto_podium/dealTab/app.php',
    'TITLE'     => 'Автомобили',
);
$placements['CRM_DEAL_DETAIL_TAB'] = CRest::callAuth('placement.bind', $fields);

echo '<pre>';
print_r($placements);
echo '</pre>';