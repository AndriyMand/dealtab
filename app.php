<?php 
// ini_set ( 'display_errors', 1 );
// ini_set ( 'display_startup_errors', 1 );

require_once ('/home/www/script-test/auto_podium/src/crest.php');

function json_encode_advanced(array $arr, $sequential_keys = true, $quotes = true, $beautiful_json = true) {
    
    $output = "{";
    $count = 0;
    foreach ($arr as $key => $value) {
        
        $output .= ($quotes ? '"' : '') . $key . ($quotes ? '"' : '') . ' : ';
        
        if (is_array($value)) {
            $output .= json_encode_advanced($value, $sequential_keys, $quotes, $beautiful_json);
        } else if (is_bool($value)) {
            $output .= ($value ? 'true' : 'false');
        } else if (is_numeric($value)) {
            $output .= $value;
        } else {
            $value = str_replace('"','\"', $value);
            $value = str_replace("'",'\"', $value);
            $value = str_replace("`",'\"', $value);
            $output .= ($quotes || $beautiful_json ? '"' : '') . $value . ($quotes || $beautiful_json ? '"' : '');
        }
        
        if (++$count < count($arr)) {
            $output .= ', ';
        }
    }
    
    $output .= "}";
    
    return $output;
}

$currentDealId = 0;
if (!empty($_REQUEST['PLACEMENT_OPTIONS'])) {
	$options = json_decode($_REQUEST['PLACEMENT_OPTIONS'], true);
	if (!empty($options['ID'])) {
		$currentDealId = $options['ID'];
	}
} elseif (!empty($_REQUEST['dealId'])) {
	$currentDealId = $_REQUEST['dealId'];
}

// if ( $_SERVER['REMOTE_ADDR'] == '91.245.77.88' ) {
//         echo '<pre>';
//         print_r($_REQUEST);
//         echo '</pre>';
//         echo '<pre>';
//         print_r($options);
//         echo '</pre>';
// }

$whatChangedBlockId = 29;

$data = array(
    'IBLOCK_TYPE_ID' => 'lists',
    'IBLOCK_ID' => $whatChangedBlockId,
);
$changedListFields = CRest::call('lists.field.get', $data);

$data = array(
    'id' => $currentDealId,
);
$currentDealName = CRest::call('crm.deal.get', $data)['result']['TITLE'];
$currentDealName = str_replace("'", "\'", $currentDealName);

$changedListFieldsData = array();
if ( !empty($changedListFields['result']) ) {
	foreach ($changedListFields['result'] as $field) {
		$changedListFieldsData['fields'][$field['FIELD_ID']] = $field['NAME'];

		if ( $field['TYPE'] == 'L' ) {
			foreach ($field['DISPLAY_VALUES_FORM'] as $key => $val) {
				$changedListFieldsData['lists'][$field['FIELD_ID']][$key] = $val;
			}
		}
	}
}

$jsonFieldsList = json_encode_advanced( $changedListFieldsData );

$tableData = array();

$data = array(
    'IBLOCK_TYPE_ID' => 'lists',
    'IBLOCK_ID'  => $whatChangedBlockId,
    'FILTER' => array(
        'PROPERTY_125' => $currentDealId
    ),
);
$getData = CRest::getObjectList($data, 'lists.element.get');


$data = array(
    'IBLOCK_TYPE_ID' => 'lists',
    'IBLOCK_ID'  => 31,
    'FILTER' => array(),
);
$marksDb = CRest::getObjectList($data, 'lists.element.get');
$marksArray = array();
$markElRecIds = array();
foreach ( $marksDb as $markId => $mark ) {
    $markElId = current($mark['PROPERTY_165']);
    $marksArray[$markId] = $mark['NAME'];
    $markElRecIds[$markId] = $markElId;
}

if ( !empty($getData) ) {
    foreach ( $getData as $record ) {
        $elementData = array();
        
        $dealId = !empty($record['PROPERTY_125']) ? current($record['PROPERTY_125']) : 0;
        
        foreach ( $record as $fieldName => $fieldValue ) {
            if ( is_array($fieldValue) ) {
                $tmpArr = $fieldValue;
                $fieldValue = array();
                if ( !empty($tmpArr['TEXT']) ) {
                    $elementData[$fieldName] = $tmpArr['TEXT'];
                } else {
                    foreach ( $tmpArr as $val ) {
                        if ( !empty( $changedListFieldsData['lists'][$fieldName][$val] ) ) {
                            $fieldValue[] = $changedListFieldsData['lists'][$fieldName][$val];
                        } else {
                            if ( !empty($val['TEXT']) ) {
                                $fieldValue[] = $val['TEXT'];
                            } else {
                                $fieldValue[] = $val;
                            }
                        }
                    }
                    $elementData[$fieldName] = implode(',', $fieldValue);
                }
            } else {
                if ( !empty( $changedListFieldsData['lists'][$fieldName][$fieldValue] ) ) {
                    $fieldValue = $changedListFieldsData['lists'][$fieldName][$fieldValue];
                }
                $elementData[$fieldName] = $fieldValue;
            }
        }
        $tableData[$record['ID']] = $elementData;
        $tableData[$record['ID']]['dealId'] = $dealId;
    }
}

$searchExceptions    = array(
    'PROPERTY_103',
    'PROPERTY_119',
    'PROPERTY_125',
    'PROPERTY_121',
    'PROPERTY_129',
    'PROPERTY_131',
    'PROPERTY_133',
    'PROPERTY_157',
    'PROPERTY_149',
    'PROPERTY_147',
    'PROPERTY_111',
    'PROPERTY_137',
    'PROPERTY_151',
    'PROPERTY_153',
    'PROPERTY_139',
    'PROPERTY_141',
    'DATE_CREATE',
    
    'PROPERTY_189',
    'PROPERTY_191',
    'PROPERTY_193',
    'PROPERTY_195',
    'PROPERTY_197',
);
$searchFromToSpecial = array(
    'PROPERTY_145', // цена(usd)
);
$searchMultiple      = array(
);
$resultsExceptions   = array(
    'NAME',
    'PROPERTY_119',
    'PROPERTY_121',
    'PROPERTY_133',
    
    'PROPERTY_189',
    'PROPERTY_191',
    'PROPERTY_193',
    'PROPERTY_195',
    'PROPERTY_197',
);

unset($changedListFieldsData['fields']['NAME']);


$userNames = CRest::getUsersNames();

$userListJson = json_encode_advanced( $userNames );
    
    
// echo '<pre>';
// print_r($changedListFieldsData);
// echo '</pre>';
    
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Склад</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
 
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
	
	<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.10.0/js/bootstrap-select.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.10.0/css/bootstrap-select.min.css" rel="stylesheet" />
    	
  <style>
	.colored {
		background-color: antiquewhite !important;
	}
	.disabled {
		background-color: #ddd;
	}
  </style>
</head>
<body id="app">

	<div style="margin-left: 10px;">
		<input type="hidden" id="currentUser" value="">
		<form id="form_search">
			<input type="hidden" name="currentDealId" value="<?=$currentDealId?>">
		  	<table class="table">
    			<thead>
    				<tr>
    					<th>Марка</th>
    					<th>Модель</th>
    					<?php foreach ($changedListFieldsData['fields'] as $property => $fieldName) { ?>
    						<?php if ( in_array($property, $searchExceptions) ) { continue; } ?>
							<?php if ( in_array($property, $searchFromToSpecial) ) { ?>
								<th><?=$fieldName?> от</th><th><?=$fieldName?> до</th>
        					<?php continue;
                                } ?>
        					<th>
        						<?= $fieldName ?>
        					</th>
    					<?php } ?>
    					<th>Привлёк в базу</th>
    					<th></th>
    				</tr>
    			</thead>
    			<tbody>
    				<tr>
    					<td>
    						<select name="PROPERTY_139" id="mark_selector" class="form-control">
    						<option value="">- не вибрано -</option>
							<?php foreach ($marksArray as $value => $name) { ?>
								<option value="<?=$markElRecIds[$value]?>" data-recordid="<?=$value?>"><?=$name?></option>
							<?php } ?>
							</select>
						</td>
    					<td>
    						<select name="PROPERTY_141" id="model_selector" class="form-control">
    						<option value="">- пусто -</option>
							</select>
						</td>
    					<?php foreach ($changedListFieldsData['fields'] as $property => $fieldName ) { ?>
    						<?php if ( in_array($property, $searchExceptions) ) { continue; } ?>
    						
    						<?php if ( in_array($property, $searchFromToSpecial) ) { ?>
            						<td>
                						<?php if ( !empty($changedListFieldsData['lists'][$property]) ) { ?>
                							<select name="<?=$property?>[]" class="form-control selectpicker" multiple>
                								<option value="">- не вибрано -</option>
                							<?php foreach ($changedListFieldsData['lists'][$property] as $value => $name) { ?>
                								<option value="<?=$value?>"><?=$name?></option>
                							<?php } ?>
                							</select>
                						<?php } else { ?>
                							<input type="text" class="form-control" name=">=<?=$property?>" value="">
                						<?php } ?>
                					</td>
                					<td>
                						<?php if ( !empty($changedListFieldsData['lists'][$property]) ) { ?>
                							<select name="<?=$property?>[]" class="form-control selectpicker" multiple>
                								<option value="">- не вибрано -</option>
                							<?php foreach ($changedListFieldsData['lists'][$property] as $value => $name) { ?>
                								<option value="<?=$value?>"><?=$name?></option>
                							<?php } ?>
                							</select>
                						<?php } else { ?>
                							<input type="text" class="form-control" name="<=<?=$property?>" value="">
                						<?php } ?>
                					</td>
        					<?php continue;
                            } ?>
        					
        					<td>
        						<?php if ( !empty($changedListFieldsData['lists'][$property]) ) { ?>
        							<select name="<?=$property?>[]" class="form-control selectpicker" data-live-search="true" autocomplete="off" data-selected-text-format="count" data-count-selected-text="Выбрано ({0})" multiple>
        								<option value="">- не вибрано -</option>
        							<?php foreach ($changedListFieldsData['lists'][$property] as $value => $name) { ?>
        								<option value="<?=$value?>"><?=$name?></option>
        							<?php } ?>
        							</select>
        						<?php } else { ?>
        							<input type="text" class="form-control" name="<?=$property?>" value="">
        						<?php } ?>
        					</td>
    					<?php } ?>
    					<td>
    						<select name="PROPERTY_131" class="form-control selectpicker" data-live-search="true" autocomplete="off" data-selected-text-format="count" data-count-selected-text="Выбрано ({0})">
    							<option value="">- не вибрано -</option>
    							<?php foreach ( $userNames as $id => $name) { ?>
    								<option value="<?=$id?>"><?=$name?></option>
    							<?php } ?>
							</select>
    					</td>
    					<td><button type="button" class="btn btn-primary" id="saveBtn">Зберегти</button></td>
    					<td><button type="button" class="btn btn-primary" id="searchBtn">Пошук</button></td>
    				</tr>
    			</tbody>
		  	</table>
		</form>
	
		<form id="form_results">
		  <table class="table">
			<thead>
				<tr>
					<th></th>
					<?php foreach ($changedListFieldsData['fields'] as $fieldCode => $fieldName) {?>
						<?php if ( in_array($fieldCode, $resultsExceptions) ) { continue; } ?>
						<th><?=$fieldName?></th>
					<?php } ?>
				</tr>
			</thead>
			<tbody id="search_result">

				<?php foreach ($tableData as $recordId => $recordData) {?>
					<?php 
						$colored = '';
						$checked = '';
						if ( $recordData['dealId'] ) {
							$colored = 'disabled';
							//$checked = 'disabled';
							if( $recordData['dealId'] == $currentDealId ) {
								$colored = 'colored';
								$checked = 'checked';
							}
						}
					?>
					<tr data-record="<?=$recordId?>" class="<?=$colored?>">
						<td class="clickable"><input type="checkbox" class="storage-input" value="1" name="<?=$recordId?>" <?=$checked?>></td>
						<?php
						  foreach ($changedListFieldsData['fields'] as $fieldCode => $fieldName) {
						      if ( in_array($fieldCode, $resultsExceptions) ) { continue; }
						      
						      $value = $recordData[$fieldCode];
						      
						      if ( $fieldName == 'Прикреплена сделка' && $value ) {
						          $value = "<a href='https://auto-podium.bitrix24.ua/crm/deal/details/$value/'>Сделка№$value</a>";
						      } elseif ( $fieldName == 'Марка' ) {
						          $value = "<a target='_blank' href='https://auto-podium.bitrix24.ua/company/lists/29/element/0/$recordId/'>$value</a>";
						      } elseif ( $fieldName == 'История' && $value ) {
						          $value = "<div class='parent_block'>
                                                <a href='javascript:void(0);' class='html_show'>Развернуть</a>
                                                <a href='javascript:void(0);' class='html_hide' style='display: none;'>Свернуть</a>
                                                <div class='panel panel-default hidden_html' style='display: none;'>
                                                    <div class='panel-body'>
                                                        $value
                                                    </div>
                                                </div>
                                            </div>";
						      } elseif ( $fieldName == 'Привлёк в базу' && $value ) {
						          
						          $value = "<a target='_blank' href='https://auto-podium.bitrix24.ua/company/personal/user/5/'>{$userNames[$value]}</a>";;
						      }
				        ?>
							<td class="clickable"><?=$value?></td>
						<?php } ?>
					</tr>
				<?php } ?>
			</tbody>
		  </table>
		  <input type="hidden" id="currentDealId" value="<?=$currentDealId?>">
		</form>
	</div>
	<div id="wait" style="display:none; position: fixed; top: 0; right: 0; bottom: 0; z-index: 90; left: 0; background: #e9e9e9; opacity: 0.5;">
		<div id="loading-img" style="display: table; margin: 0 auto; margin-top: 250px;">
			<img src="loader.gif" width="64" height="64" />
		</div>
	</div>

</body>
<script src="//api.bitrix24.com/api/v1/dev/"></script>
<script>

function resizeWindow(defaultHeight)
{
	var frameWidth = document.getElementById("app").offsetWidth;
	var minHeight = $('#app').height();
	if ( defaultHeight ) {
		BX24.resizeWindow(frameWidth, defaultHeight);
	} else {
		BX24.resizeWindow(frameWidth, minHeight+100);
	}
}

$( document ).ready(function() {

	$('.selectpicker').selectpicker({
        noneSelectedText : 'Не вибрано',
//         selectAllText : 'Всех',
//         deselectAllText : 'Никого',
    });

	var jsonFieldsList = <?=$jsonFieldsList?>;

// 	function resizeWindow() {
// 		var currentSize = BX24.getScrollSize();
// 		minHeight = $("#app").outerHeight(true) + 100;
// 		if (minHeight < 700) minHeight = 700;
// 		BX24.resizeWindow(document.getElementById("app").offsetWidth, minHeight);
// 	}
	resizeWindow(700);
	
	$(document).ajaxStart(function(){
		$("#wait").css("display", "block");
	});
	$(document).ajaxComplete(function(){
		$("#wait").css("display", "none");
	});

    $(document).on('click', 'input[type="checkbox"]', function(){
		var selectedTr = $(this).closest('tr');
		selectedTr.toggleClass("colored");

		if($(this).prop("checked") == true){
			console.log('checked');
			selectedTr.addClass("colored");
		}
		else if($(this).prop("checked") == false){
			selectedTr.removeClass("colored");
			console.log('unchecked');
		} else {
			console.log('else');
		}
		
	});

    BX24.callMethod('user.current', {}, function(res){
        var userName = res.data().NAME + ' ' + res.data().LAST_NAME;
    	$('#currentUser').val(userName);

    	console.log($('#currentUser').val());
	});

    $(document).on('click', '.addComment', function(){
		var comment     = $(this).closest('div').find('textarea').val();
		var currentUser = $('#currentUser').val();
		var recordId    = $(this).closest('tr').data('record');

// 		commentData
		return false;
		$.ajax({
			type: 'post',
			url: 'ajax.php',
			data: {
				commentAdd: 1,
				comment: comment,
				currentUser: currentUser,
				recordId: recordId,
				jsonFieldsList: jsonFieldsList,
			},
			success : function(result, textStatus) {
				if (textStatus === "success") {
					$('#search_result').html(result);
				} else {
					console.log('error');
				}
			}
		});
		
	});
    
    $(document).on('click', '#searchBtn', function(){
		var formData = $("#form_search").serialize();
		var userList  = <?=$userListJson?>;
		
		$.ajax({
			type: 'post',
			url: 'ajax.php',
			data: {
				jsonFieldsList: jsonFieldsList,
				formData: formData,
				userList: userList,
			},
			success : function(result, textStatus) {
				if (textStatus === "success") {
					$('#search_result').html(result);
				} else {
					console.log('error');
				}
				resizeWindow();
			}
		});
		
	});

    $(document).on('click', '.html_show', function(){
    	$(this).closest('.parent_block').find('.hidden_html').show();
    	$(this).closest('.parent_block').find('.html_hide').show();
    	$(this).hide();
	});

    $(document).on('click', '.html_hide', function(){
    	$(this).closest('.parent_block').find('.hidden_html').hide();
    	$(this).closest('.parent_block').find('.html_show').show();
    	$(this).hide();
	});


    $(document).on('change', '#mark_selector', function(){
		var markId = $(this).find(':selected').data('recordid');
		console.log('markId: ' + markId);
		
		$.ajax({
			type: 'post',
			url: 'get_models.php',
			data: {
				markId: markId,
			},
			success : function(data) {
// 				$('#model_selector').empty();
// 				console.log(data);
		  		$('#model_selector').html(data);
			}
		});
		
	});
	
	$(document).on('click', '#saveBtn', function(){
		var currentDealId = $('#currentDealId').val();
		var formData = $("#form_results").serialize();
		
		$.ajax({
			type: 'post',
			url: 'ajax.php',
			data: {
				currentDealId: currentDealId,
				formData: formData,
				jsonFieldsList: jsonFieldsList,
			},
			success : function(errors, textStatus) {
				if (textStatus === "success") {
					if ( errors ) {
						alert(errors);
						console.log(errors);
					} else {
						alert('Дані успішно збережено!');
						console.log(errors);
					}

				} else {
					console.log('error');
				}
			}
		});
		
	});
});

</script>

</html>



