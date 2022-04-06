<?php
	include $_SERVER['DOCUMENT_ROOT']."/version.php";
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);
	include('Templates/mysqliConnection.php');
	include('PHP Modules/gerald_functions.php');
	include('PHP Modules/gerald_payablesFunction.php');
	ini_set("display_errors", "on");

	$employeeId = $idNumber;
	$batchId = $genbatchId;

	$sqlMain = "INSERT INTO `system_itemlocation`(`lotNumber`, `location`, `inputDateTime`, `idNumber`, `locationType`) VALUES";
	$sqlValuesArray = array();
	$counter = 0;	

	$lotNumberArray = array();

	// $sql = "SELECT poNumber, lotNumber, quantity, itemDescription, returnedQuantity FROM system_receivingHistory WHERE idNumber LIKE '".$employeeId."' AND status = 1";
	$sql = "SELECT poNumber, lotNumber, quantity, itemDescription, returnedQuantity FROM system_receivingHistory WHERE idNumber LIKE '".$employeeId."' AND batchId = ".$batchId."";
	$queryReceiving = $db->query($sql);
	if($queryReceiving->num_rows > 0)
	{
		while($resultReceiving = $queryReceiving->fetch_array())
		{
			$poNumber = $resultReceiving['poNumber'];
			$lotNumber = $resultReceiving['lotNumber'];
			$quantity = $resultReceiving['quantity'];
			$itemDescription = $resultReceiving['itemDescription'];
			$returnedQuantity = $resultReceiving['returnedQuantity'];
			
			$lotNumberArray[] = $lotNumber;
			
			$workScheduleId = $receivingProcessOrder = $startFromProcessOrder = '';
			$sql = "SELECT id, processOrder FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode IN(137,138,143,229) AND status = 0 ORDER BY processOrder LIMIT 1";
			$queryWorkSchedule = $db->query($sql);
			if($queryWorkSchedule->num_rows > 0)
			{
				$resultWorkSchedule = $queryWorkSchedule->fetch_array();
				$workScheduleId = $resultWorkSchedule['id'];
				$receivingProcessOrder = $startFromProcessOrder = $resultWorkSchedule['processOrder'];
			}
			
			finishProcess("",$workScheduleId, $quantity, $employeeId,'');
			
			updateAvailability($lotNumber);
			
			$workingQuantity = 0;
			$poId = $partId = $identifier = $poContentIds = $status = '';
			$sql = "SELECT poId, partId, workingQuantity, identifier, status, poContentId FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
			$queryLotList = $db->query($sql);
			if($queryLotList->num_rows > 0)
			{
				$resultLotList = $queryLotList->fetch_array();
				$poId = $resultLotList['poId'];
				$partId = $resultLotList['partId'];
				$workingQuantity = $resultLotList['workingQuantity'];
				$identifier = $resultLotList['identifier'];
				$poContentIds = $resultLotList['poContentId'];
				$status = $resultLotList['status'];
			}
			
			if($quantity < $workingQuantity)
			{
				$remarks = "Partial";
				// partialLote($lotNumber,($workingQuantity - $quantity),$startFromProcessOrder,$employeeId,$remarks,0);
				// updateAvailability($lotNumber);
			}
			
			$apiPoFlag = 0;
			$poContentIdArray = array();
			if($identifier == 1 OR ($identifier == 4 AND $status == 2))
			{
				$treatmentName = explode(",",$itemDescription);
				$treatmentNameArray = array();
				foreach($treatmentName as $treatment)
				{
					$treatmentNameArray[] = "'".trim($treatment)."'";
				}
				
				$sql = "SELECT poContentId FROM purchasing_openpolist WHERE poContentId IN(".$poContentIds.") AND dataThree IN(".implode(",",$treatmentNameArray).") AND type IN(2,5)";
				$queryApiPo = $db->query($sql);
				$apiPoFlag = $queryApiPo->num_rows;
				if($apiPoFlag > 0)
				{
					while($resultApiPo = $queryApiPo->fetch_array())
					{
						$poContentIdArray[] = $resultApiPo['poContentId'];
					}
				}
				else
				{
					$sql = "SELECT poContentId FROM purchasing_pocontents WHERE poContentId IN(".$poContentIds.") AND dataThree IN(".implode(",",$treatmentNameArray).")";
					if($status==2 AND $identifier==4)
					{
						$sql = "SELECT poContentId FROM purchasing_pocontents WHERE poContentId IN(".$poContentIds.")";
					}
					$queryApiPo = $db->query($sql);
					$apiPoFlag = $queryApiPo->num_rows;
					if($apiPoFlag > 0)
					{
						while($resultApiPo = $queryApiPo->fetch_array())
						{
							$poContentIdArray[] = $resultApiPo['poContentId'];
						}
					}
				}
				
				if($returnedQuantity > 0)
				{
					$sql = "INSERT INTO `system_returnfromsubcon`
									(	`lotNumber`,		`quantity`,					`status`)
							VALUES	(	'".$lotNumber."',	'".$returnedQuantity."',	'1')";
					$queryInsert = $db->query($sql);
					
					if($returnedQuantity <= $quantity)
					{
						//~ $sqlQCInspection = "SELECT processCode FROM `cadcam_process` WHERE `processName` LIKE '%inspection%' AND processName NOT LIKE '%after%' AND processSection = 4";
						//~ $sql = "SELECT processOrder FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processOrder < ".$startFromProcessOrder." AND processCode IN(".$sqlQCInspection.") ORDER BY processOrder DESC LIMIT 1";
						$sqlPackaging = "SELECT processCode FROM `cadcam_process` WHERE `processName` LIKE '%packaging%' AND processName NOT LIKE '%after%' AND processSection = 11";
						$sql = "SELECT processOrder FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processOrder < ".$startFromProcessOrder." AND processCode IN(".$sqlPackaging.") ORDER BY processOrder DESC LIMIT 1";
						$queryWorkSchedule = $db->query($sql);
						if($queryWorkSchedule->num_rows > 0)
						{
							$resultWorkSchedule = $queryWorkSchedule->fetch_array();
							$startFromProcessOrder = $resultWorkSchedule['processOrder'];
						}
						$remarks = "Returned Item Quantity ".$returnedQuantity;
						// partialLote($lotNumber,$returnedQuantity,$startFromProcessOrder,$employeeId,$remarks,1);
						// updateAvailability($lotNumber);
					}
				}
			}
			
			if(count($poContentIdArray) == 0 AND $identifier == 4)
			{
				$poContentIdArray = array($poId);
				$lotNo = '';
			}
			else
			{
				$lotNo = $lotNumber;
			}
			
			foreach($poContentIdArray as $poContentId)
			{
				$itemQuantity = 0;
				$sql = "SELECT itemQuantity FROM purchasing_pocontents WHERE poContentId = ".$poContentId." LIMIT 1";
				$queryPoContents = $db->query($sql);
				if($queryPoContents AND $queryPoContents->num_rows > 0)
				{
					$resultPoContents = $queryPoContents->fetch_assoc();
					$itemQuantity = $resultPoContents['itemQuantity'];
				}
				
				$supplierDr = $_POST['supplierDr'];
				$supplierSi = $_POST['supplierSi'];
				$sql = "INSERT INTO	`purchasing_receivingdata`
								(	`poContentId`,		`lotNumber`,		`supplierDr`,		`supplierSi`,
									`receiveQuantity`,	`receiveDate`,		`receiveTime`,		`employeeId`)
						VALUES	(	".$poContentId.",	'".$lotNo."',		'".$supplierDr."',	'".$supplierSi."',
									".$quantity.",	now(),				now(),				'".$employeeId."')";
				$queryInsert = $db->query($sql);
				if($queryInsert)
				{
					$totalReceiveQty = 0;
					$sql = "SELECT IFNULL(SUM(receiveQuantity),0) AS totalReceiveQty FROM purchasing_receivingdata WHERE poContentId = ".$poContentId." ";
					$queryTotalReceiveQty = $db->query($sql);
					if($queryTotalReceiveQty->num_rows > 0)
					{
						$resultTotalReceiveQty = $queryTotalReceiveQty->fetch_array();
						$totalReceiveQty = $resultTotalReceiveQty['totalReceiveQty'];
					}
					
					$balance = $itemQuantity - $totalReceiveQty;
					
					if($balance <= 0)
					{
						$sql = "UPDATE purchasing_pocontents SET itemStatus=1 WHERE poContentId = ".$poContentId;
						//~ $updateQuery = $db->query($sql);
						
						$sql = "SELECT itemStatus FROM purchasing_pocontents WHERE poNumber = '".$poNumber."' AND itemStatus = 0 LIMIT 1";
						$queryCheck = $db->query($sql);
						if($queryCheck->num_rows==0)
						{
							$sql = "UPDATE purchasing_podetailsnew SET poStatus = 3 WHERE poNumber = '".$poNumber."'";
							//~ $queryUpdate = $db->query($sql);
						}
					}
				}
			}
			
			//************************************************** Insert Payables (2019-08-16) **************************************************//
			$sql = "SELECT payableId, type FROM accounting_payablesnew WHERE lotNumber LIKE '".$lotNumber."' AND poContentIds LIKE '".implode(",",$poContentIdArray)."' LIMIT 1";
			$queryPayablesNew = $db->query($sql);
			if($queryPayablesNew AND $queryPayablesNew->num_rows == 0)
			{
				$supplierId = $supplierType = $poCurrency = '';
				$sql = "SELECT supplierId, supplierType, poCurrency FROM purchasing_podetailsnew WHERE poNumber LIKE '".$poNumber."' LIMIT 1";
				$queryPoDetailsNew = $db->query($sql);
				if($queryPoDetailsNew AND $queryPoDetailsNew->num_rows > 0)
				{
					$resultPoDetailsNew = $queryPoDetailsNew->fetch_assoc();
					$supplierId = $resultPoDetailsNew['supplierId'];
					$supplierType = $resultPoDetailsNew['supplierType'];
					$poCurrency = $resultPoDetailsNew['poCurrency'];
				}
				
				$supplierAlias = $supplierName = $taxStatus = '';
				if($supplierType==1)
				{
					$sql = "SELECT supplierAlias, supplierName, taxStatus FROM purchasing_supplier WHERE supplierId = ".$supplierId." LIMIT 1";
				}
				else if($supplierType==2)
				{
					$sql = "SELECT subconAlias, subconName, taxStatus FROM purchasing_subcon WHERE subconId = ".$supplierId." LIMIT 1";
				}
				if($sql!='')
				{
					$querySupplier = $db->query($sql);
					if($querySupplier AND $querySupplier->num_rows > 0)
					{
						$resultSupplier = $querySupplier->fetch_row();
						$supplierAlias = $resultSupplier[0];
						$supplierName = $resultSupplier[1];
						$taxStatus = $resultSupplier[2];
					}
				}
				
				$payableType = 0;
				if($identifier==1)
				{
					$payableType = 2;
				}
				else if($identifier==4)
				{
					$payableType = $status;
				}
				
				$sql = "
					INSERT INTO `accounting_payablesnew`
							(	`poContentIds`, 											 	`supplierName`, 	`type`, 			`poNumber`, `lotNumber`, 		`currency`,		`itemQuantity`, `unitPrice`, `quantity`,	 `taxStatus`)
					SELECT		GROUP_CONCAT(poContentId ORDER BY poContentId SEPARATOR ','),	'".$supplierName."','".$payableType."',	`poNumber`, '".$lotNumber."', '".$poCurrency."', `itemQuantity`, SUM(itemPrice), `itemQuantity`, '".$taxStatus."'
					FROM 	`purchasing_pocontents` WHERE `poNumber` LIKE '".$poNumber."' AND poContentId LIKE '".implode(",",$poContentIdArray)."' AND itemStatus != 2 GROUP BY lotNumber
					";
				$queryInsert = $db->query($sql);
			}
			
			$sql = "SELECT payableId, type FROM accounting_payablesnew WHERE lotNumber LIKE '".$lotNumber."' AND poContentIds LIKE '".implode(",",$poContentIdArray)."' LIMIT 1";
			$queryPayablesNew = $db->query($sql);
			if($queryPayablesNew AND $queryPayablesNew->num_rows > 0)
			{
				$resultPayablesNew = $queryPayablesNew->fetch_assoc();
				$payableId = $resultPayablesNew['payableId'];
				$type = $resultPayablesNew['type'];
				
				$receiveDate = date('Y-m-d');
				
				$cutOffMonth = date('Y-m');
				if(strtotime($receiveDate) > strtotime(date('Y-m-25',strtotime($receiveDate))))
				{
					$cutOffMonth = date('Y-m', strtotime('+1 month',strtotime($receiveDate)));
				}
				
				$accountTitleId = $type;
				
				if($accountTitleId==1)	$accountTitleId = 228;
				else if($accountTitleId==2)	$accountTitleId = 103;
				else if($accountTitleId==3)
				{
					$sql = "SELECT accountTitleId FROM purchasing_items WHERE itemId = ".$partId." AND accountTitleId != 0 LIMIT 1";
					$queryItems = $db->query($sql);
					if($queryItems AND $queryItems->num_rows > 0)
					{
						$resultItems = $queryItems->fetch_assoc();
						$accountTitleId = $resultItems['accountTitleId'];
					}
				}
				
				//~ if($lotNumber=='20-20-2000')	$cutOffMonth = '1980-08';
				$sql = "UPDATE accounting_payablesnew SET receiveDate = '".$receiveDate."', receiveQuantity = '".$quantity."', cutOffMonth = '".$cutOffMonth."', accountTitleId = '".$accountTitleId."', payableStatus = 1 WHERE payableId = ".$payableId." LIMIT 1";
				$queryUpdate = $db->query($sql);
				
				if($returnedQuantity > 0)
				{
					insertPayablesDetails($payableId,$lotNumber,$returnedQuantity,3);
				}
				
				//~ if($_GET['country']==1)//remove if 2021-09-21
				if(1==1)
				{
					//~ $sqlQCInspection = "SELECT processCode FROM `cadcam_process` WHERE `processName` LIKE '%inspection%' AND processSection = 4";
					$sqlQCInspection = "SELECT processCode FROM `cadcam_process` WHERE `processName` LIKE '%insp%' AND processSection = 4";
					$sql = "SELECT id, processOrder FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processOrder >= ".$receivingProcessOrder." AND processCode IN(".$sqlQCInspection.") ORDER BY processOrder ASC LIMIT 1";
					$queryWorkSchedule = $db->query($sql);
					if($queryWorkSchedule->num_rows > 0)
					{
						$resultWorkSchedule = $queryWorkSchedule->fetch_array();
						$id = $resultWorkSchedule['id'];
						$processOrder = $resultWorkSchedule['processOrder'];
						
						$sql = "DELETE FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processOrder > ".$processOrder." AND processCode IN(437,438) AND status = 0";
						$queryDelete = $db->query($sql);
						
						$sql = "
								INSERT INTO `ppic_workschedule`
										(	`poId`, `customerId`, `poNumber`, `lotNumber`, `partNumber`, `revisionId`,	`processCode`,	`processOrder`,			 `processSection`, 	`processRemarks`, `targetStart`, `targetFinish`, `standardTime`,	`receiveDate`, `deliveryDate`, `recoveryDate`, `availability`, `urgentFlag`, `subconFlag`, `partLevelFlag`, `poContentIds`, `status`)
								SELECT		`poId`, `customerId`, `poNumber`, `lotNumber`, `partNumber`, `revisionId`,	'437',			'".($processOrder+1)."', '37', 				'".$payableId."', `targetStart`, `targetFinish`, '0', 				`receiveDate`, `deliveryDate`, `recoveryDate`, `availability`, `urgentFlag`, `subconFlag`, `partLevelFlag`, `poContentIds`, `status`
								FROM	`ppic_workschedule` WHERE id = ".$id." LIMIT 1";
						$queryInsert = $db->query($sql);
						if($queryInsert)
						{
							$paymentProcessId = $db->insert_id;
							
							if($identifier == 4)
							{
								$sql = "
									INSERT INTO view_workschedule
											(	`id`,					`lotNumber`,	`processCode`,	`processOrder`,				`targetFinish`,	`status`,	`processSection`,	`availability`,	`customerAlias`,`poNumber`,	`partNumber`,`partName`,	`dataOne`,		`dataTwo`,		`dataSeven`,		`decimalOne`)
									SELECT 		'".$paymentProcessId."',`lotNumber`,	'437',			'".($processOrder+1)."',	`targetFinish`,	`status`,	37,	`availability`, `customerAlias`,`poNumber`,	`partNumber`,`partName`,	`dataOne`,		`dataTwo`,		`dataSeven`,		`decimalOne`
									FROM	view_workschedule
									WHERE 	id = ".$id." LIMIT 1
									";
								$queryInsert = $db->query($sql);
							}
							
							$sql = "SET @newProcessOrder = ".($processOrder+1).";";
							$query = $db->query($sql);
							
							$sql = "UPDATE ppic_workschedule SET processOrder = @newProcessOrder := ( @newProcessOrder +1 ) WHERE lotNumber LIKE '".$lotNumber."' AND processOrder > ".$processOrder." AND id != ".$paymentProcessId." ORDER BY processOrder";
							$queryUpdate = $db->query($sql);
						}
					}
				}
			}
			//************************************************** Insert Payables (2019-08-1) **************************************************//

			if(isset($_POST['itemLocation']))
			{
				$sqlValuesArray[] = "('".$lotNumber."','".$_POST['itemLocation']."',NOW(), '".$employeeId."', 0)";
			}
			if(isset($_POST['itemBucket']))
			{
				$sqlValuesArray[] = "('".$lotNumber."','".$_POST['itemBucket']."',NOW(), '".$employeeId."', 1)";
			}
			//$sqlValuesArray[] = $sqlValues;
			$counter++;
			
			if($counter == 50)
			{
				$sqlInsert = $sqlMain." ".implode(",",$sqlValuesArray);
				$queryInsert = $db->query($sqlInsert);
				$sqlValuesArray = array();
				$counter = 0;
			}
		}
		if($counter > 0)
		{
			$sqlInsert = $sqlMain." ".implode(",",$sqlValuesArray);
			$queryInsert = $db->query($sqlInsert);
		}
	}	
?>