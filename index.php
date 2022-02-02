<?php 
require_once ('/home/admin/web/bx24.autopodium.ua/public_html/public/auto_podium/dealTab/crest.php');

$fields = array(
    'PLACEMENT' => 'CRM_DEAL_DETAIL_TAB',
    'HANDLER'   => 'https://bx24.autopodium.ua/auto_podium/dealTab/app.php',
    'TITLE'     => 'Автомобили',
);
$placements['CRM_DEAL_DETAIL_TAB'] = CRest::callAuth('placement.bind', $fields);

echo '<pre>';
print_r($placements);
echo '</pre>';