<?php 
// ini_set ( 'display_errors', 1 );
// ini_set ( 'display_startup_errors', 1 );

require_once ('/home/admin/web/bx24.autopodium.ua/public_html/public/auto_podium/dealTab/crest.php');

$request = $_REQUEST;
$whatChangedBlockId    = 29;

if ( !empty( $request['currentDealId'] ) ) {
	
	$formData = array();
	if ( !empty( $request['formData'] ) ) {
		parse_str($request['formData'], $formData);
	}
	
	$selectedRecords = array();
	foreach ( $formData as $recordId => $isChecked ) {
		$selectedRecords[] = $recordId;
	}
	
	$changedListFieldsData = $request['jsonFieldsList'];
	
	$data = array(
	    'id' => $request['currentDealId'],
	);
	$dealData = CRest::call('crm.deal.get', $data)['result'];
	
	$oldRelations = array();
	$newRelations = array();
	$generalList  = array();
	
	$data = array(
            'IBLOCK_TYPE_ID' => 'lists',
            'IBLOCK_ID'  => $whatChangedBlockId,
			'FILTER' => array(
				'PROPERTY_125' => $request['currentDealId'],
			)
	);
	$getData = CRest::getObjectList($data, 'lists.element.get');
	if ( !empty($getData) ) {
	    foreach ($getData as $recordId => $record ) {
	        $oldRelations[] = $recordId;
	    }
	    $generalList = CRest::formatList($getData, $generalList);
	}
	
	if ( !empty($selectedRecords) ) {
		$data = array(
			'IBLOCK_TYPE_ID' => 'lists',
		    'IBLOCK_ID'  => $whatChangedBlockId,
			'FILTER' => array(
				"ID" => $selectedRecords
			)
		);
		$getData = CRest::getObjectList($data, 'lists.element.get');
		
		if ( !empty($getData) ) {
		    $tmpGeneralList = CRest::formatList($getData, $generalList);
		    
		    foreach ( $tmpGeneralList as $recordId => $record ) {
		        $generalList[$recordId] = $record;
		    }
		}
	}
	
	$listFields = array();
	if ( !empty($request['jsonFieldsList']) ) {
	    $listFields = json_decode($request['jsonFieldsList'], true);
	}
	
// 	echo '<pre>';
// 	print_r($generalList);
// 	echo '</pre>';
	
	$relatedCarsList = array();
	$fullCarsList    = array();
	
	$linksToSite = array();
	$recordsIds = array();
	
	$linkToSiteArray = array();
	$cmd = array();
	foreach ($generalList as $recordId => $record) {
	    
// 	    	echo '<pre>';
// 	    	print_r($record);
// 	    	echo '</pre>';
	    
	    
	    $record['PROPERTY_129'] = !empty($record['PROPERTY_129']) ? current($record['PROPERTY_129'])['TEXT'] : '';
	    
	    $marka = !empty($record['PROPERTY_139']) ? (is_array($record['PROPERTY_139']) ? $changedListFieldsData['lists']['PROPERTY_139'][current($record['PROPERTY_139'])] : $record['PROPERTY_139']) : '';
	    $model = !empty($record['PROPERTY_141']) ? (is_array($record['PROPERTY_141']) ? current($record['PROPERTY_141']) : $record['PROPERTY_141']) : '';
	    $linkToSite = !empty($record['PROPERTY_121']) ? (is_array($record['PROPERTY_121']) ? current($record['PROPERTY_121']) : $record['PROPERTY_121']) : '';
	    $price = !empty($record['PROPERTY_145']) ? (is_array($record['PROPERTY_145']) ? current($record['PROPERTY_145']) : $record['PROPERTY_145']) : 0;
	    $dealsHistoryField = !empty($record['PROPERTY_191']) ? $record['PROPERTY_191'] : array();
	    
	    $linkToSiteArray[$recordId] = $linkToSite ? "<a href='$linkToSite'>Ссылка на сайт</a>" : '';
	    
	    $year = '';
	    if (!empty($record['PROPERTY_135'])) {
	        $currentYearId = is_array($record['PROPERTY_135']) ? current($record['PROPERTY_135']) : $record['PROPERTY_135'];
	        $year = !empty($listFields['lists']['PROPERTY_135'][$currentYearId]) ? $listFields['lists']['PROPERTY_135'][$currentYearId] : '';
	    }
	    $actionText = '';
	    $fullCarsList[$recordId] = "$marka $model $year $$price";
		if ( in_array($recordId, $selectedRecords) ) {
		    $newRelations[] = $recordId;
			$record['PROPERTY_125'] = $request['currentDealId'];
			$relatedCarsList[] = "$marka $model $year";
			if ( !in_array($recordId, $oldRelations) ) {
			    $newRecordHistory = "Добавлено к сделке <a href='/crm/deal/details/{$dealData['ID']}/'>[#{$dealData['ID']}]{$dealData['TITLE']}</a>";
			    if ( $record['PROPERTY_129'] ) {
			        $record['PROPERTY_129'] = $record['PROPERTY_129'] . '<br><br>' . date('d.m.Y H:i:s') . '<br>' . $newRecordHistory;
			    } else {
			        $record['PROPERTY_129'] = date('d.m.Y H:i:s') . '<br>' . $newRecordHistory;
			    }
			    
			    $record['PROPERTY_189'] = date('d.m.Y H:i:s');
    			$actionText = 'Добавлено к сделке';
    			
    			$dealsHistoryField[] = $dealData['ID'];
    			$dealsHistoryField = array_unique($dealsHistoryField);
    			$record['PROPERTY_191'] = $dealsHistoryField;
    			$record['PROPERTY_193'] = count($dealsHistoryField);
    			
			}
			
			$linksToSite[] = $linkToSite;
			$recordsIds[]  = $recordId;
			
			
		} else {
		    if ( in_array($recordId, $oldRelations) ) {
		        $newRecordHistory = "Удалено из сделки <a href='/crm/deal/details/{$dealData['ID']}/'>[#{$dealData['ID']}]{$dealData['TITLE']}</a>";
		        if ( $record['PROPERTY_129'] ) {
		            $record['PROPERTY_129'] = $record['PROPERTY_129'] . '<br><br>' . date('d.m.Y H:i:s') . '<br>' . $newRecordHistory;
		        } else {
		            $record['PROPERTY_129'] = date('d.m.Y H:i:s') . '<br>' . $newRecordHistory;
		        }
    			$actionText = 'Удалено из сделки';
		    }
			$record['PROPERTY_125'] = 0;
			
		}
		
		$fields = array(
			'IBLOCK_TYPE_ID' => 'lists',
		    'IBLOCK_ID'  => $whatChangedBlockId,
			'ELEMENT_ID' => $recordId,
			'FIELDS'     => $record
		);
		$cmd["#{$recordId}"] = array(
		    'method' => 'lists.element.update',
		    'params' => $fields
		);
		
		if ( $actionText ) {
    		$fields = array(
    		    'IBLOCK_TYPE_ID' => 'lists',
    		    'IBLOCK_ID'  => 51, // История
    		    'ELEMENT_CODE'  => rand(-999,999).rand(-999,999).rand(-999,999).rand(-999,999),
    		    'FIELDS'     => array(
    		        'NAME'         => $actionText,
    		        'PROPERTY_173' => $recordId, // Автомобиль
    		        'PROPERTY_175' => $dealData['ID'], // Сделка
    		        'PROPERTY_185' => $dealData['ASSIGNED_BY_ID'], // Менеджер
    		        'PROPERTY_187' => $linkToSiteArray[$recordId], // Ссилка на сайт
    		    )
    		);
    		$cmd["#history_{$recordId}"] = array(
    		    'method' => 'lists.element.add',
    		    'params' => $fields
    		);
		}
		
	}


	$whatDeleted = array_diff($oldRelations, $newRelations);
	$whatAdded   = array_diff($newRelations, $oldRelations);
	
	
	$history = '';
	$commentHistory = '';
	if (!empty($whatDeleted)) {
	    $names = array();
	    $urlNames = array();
	    foreach ($whatDeleted as $recordId) {
	        $names[]    = $fullCarsList[$recordId] . " 	(https://auto-podium.bitrix24.ua/company/lists/29/element/0/$recordId/)";
	        $urlNames[] = "<a href='/company/lists/29/element/0/$recordId/'>{$fullCarsList[$recordId]}</a> " . $linkToSiteArray[$recordId];
	    }
	    $history        .= "Удалили:\n" . implode("\n", $names);
	    $commentHistory .= "<br>Удалили:<br>" . implode("<br>", $urlNames);
	}
	if (!empty($whatAdded)) {
	    $names = array();
	    $urlNames = array();
	    foreach ($whatAdded as $recordId) {
	        $names[]    = $fullCarsList[$recordId] . " 	(https://auto-podium.bitrix24.ua/company/lists/29/element/0/$recordId/)";
	        $urlNames[] = "<a href='/company/lists/29/element/0/$recordId/'>{$fullCarsList[$recordId]}</a> " . $linkToSiteArray[$recordId];
	    }
	    $history        .= "\nДобавили:\n" . implode("\n", $names);
	    $commentHistory .= "<br>Добавили:<br>" . implode("<br>", $urlNames);
	}
	
	$fields = array(
	    'id' => $request['currentDealId'],
	    'fields' => array(
	        'UF_CRM_1587528120' => !empty($relatedCarsList) ? $relatedCarsList : array(0), // Назви привязаних автомобілів
	        'UF_CRM_1613063252' => !empty($linksToSite) ? $linksToSite : array(0),
	        'UF_CRM_1613063226' => !empty($recordsIds) ? $recordsIds : array(0),
	    )
	);
	
	
// 	if ( $_SERVER['REMOTE_ADDR'] == '91.245.77.88' ) {
// 	    echo '<pre>';
// 	    print_r($fields);
// 	    echo '</pre>';
// 	}
	
	if ($history) {
	    if ( $dealData['UF_CRM_1587529790'] ) {
    	    $fields['fields']['UF_CRM_1587529790'] = $dealData['UF_CRM_1587529790'];
    	    $fields['fields']['UF_CRM_1587529790'][] = date('d.m.Y H:i:s') . "\n" . $history;
    	    $fields['fields']['COMMENTS'] = $dealData['COMMENTS'] . "<br><br>" . date('d.m.Y H:i:s') . $commentHistory;
    	    
	    } else {
	        $fields['fields']['UF_CRM_1587529790'] = array( date('d.m.Y H:i:s') . "\n" . $history);
	        $fields['fields']['COMMENTS'] = date('d.m.Y H:i:s') . $commentHistory;
	    }
	}
	
	$cmd["dealUpdate#{$recordId}"] = array(
	    'method' => 'crm.deal.update',
	    'params' => $fields
	);
// 	$cmd["dealUpdate#{$recordId}"] = 'crm.deal.update?'.http_build_query($fields);
	
	$errors = array();
	
	if ( !empty($cmd) ) {
	    $batchGet = CRest::callBatch($cmd, 0)['result'];
	    
	    if ( !empty($batchGet['result_error']) ) {
	        foreach ( $batchGet['result_error'] as $index => $result ) {
	            $errors[] = $result['error_description'];
    		}
	    }
	} else {
	   echo "Не вдалося зберегти"; 
	}

	if ( !empty($errors) ) {
		echo 'Відбулися помилки в записах: ' . implode(',', $errors);
	}
	
} elseif (  !empty( $request['jsonFieldsList'] ) ) {
    
    parse_str($request['formData'], $filter);
    $filter = array_filter($filter); 
    
//     echo '<pre>';
//     print_r($filter);
//     echo '</pre>';
    
    $changedListFieldsData = $request['jsonFieldsList'];
    
    $currentDealId = 0;
    if ( isset($filter['currentDealId']) ) {
        $currentDealId = $filter['currentDealId'];
        unset($filter['currentDealId']);
    }
    
    $data = array(
        'IBLOCK_TYPE_ID' => 'lists',
        'IBLOCK_ID'  => $whatChangedBlockId,
        'FILTER' => $filter
    );
    
//     echo '<pre>';
//     print_r($data);
//     echo '</pre>';
    
    
    $getData = CRest::getObjectList($data, 'lists.element.get');
    
    
    if ( $currentDealId ) {
        $data = array(
            'IBLOCK_TYPE_ID' => 'lists',
            'IBLOCK_ID'  => $whatChangedBlockId,
            'FILTER' => array(
                'PROPERTY_125' => $currentDealId,
            )
        );
        $tmpGetData = CRest::getObjectList($data, 'lists.element.get');
        
        if ( !empty($tmpGetData) ) {
            foreach ($tmpGetData as $recordId => $record ) {
                $getData[$recordId] = $record;
            }
        }
    }
    
    //////////////////
    
    $tableData = array();
    
    
    
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
    
    if ( isset($changedListFieldsData['fields']['NAME']) ) {
        unset($changedListFieldsData['fields']['NAME']);
    }
    
//     if ( isset($changedListFieldsData['fields']['PROPERTY_125']) ) {
//         unset($changedListFieldsData['fields']['PROPERTY_125']);
//     }
    $resultsExceptions = array(
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
    
    ?>
    <?php foreach ($tableData as $recordId => $recordData) { ?>
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
                                    $value = "<a target='_blank' href='https://auto-podium.bitrix24.ua/crm/deal/details/$value/'>Сделка№$value</a>";
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
						          
						          $value = "<a target='_blank' href='https://auto-podium.bitrix24.ua/company/personal/user/5/'>{$request['userList'][$value]}</a>";;
						      }
				        ?>
							<td class="clickable"><?=$value?></td>
						<?php } ?>
					</tr>
	<?php } ?>
    <?php
} elseif ( !empty( $request['commentAdd'])) {
    
}

