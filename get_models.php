<?php 
require_once ('/home/www/script-test/auto_podium/src/crest.php');

$request = $_REQUEST;

if ( !empty( $request['markId'] ) ) {
	
	$data = array(
        'IBLOCK_TYPE_ID' => 'lists',
        'IBLOCK_ID'  => 33,
		'FILTER' => array(
			'PROPERTY_163' => $request['markId'],
		)
	);
	$getData = CRest::getObjectList($data, 'lists.element.get');
	
	$modelsList = array();
	
	if ( $getData ) {
	    foreach ( $getData as $record ) {
	        if ( !empty( $record['PROPERTY_163'] ) ) {
	            $current = current($record['PROPERTY_163']);
	            if ( $current == $request['markId'] ) {
	                $modelsList[$record['ID']] = $record['NAME'];
	            }
	        }
	    }
	}
	
	if ( !empty( $modelsList ) ) {
	    asort($modelsList);
	    foreach ( $modelsList as $modelId => $modelName ) {
	    ?>
			<option value="<?=$modelName?>"><?=$modelName?></option>
		<?php
	    }
	} else { ?>
		<option value="">-пусто-</option>
	<?php } 
	
}

