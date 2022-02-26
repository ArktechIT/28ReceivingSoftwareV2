<?php
	include($_SERVER['DOCUMENT_ROOT']."/version.php");
	$path = $_SERVER['DOCUMENT_ROOT']."/".v."/Common Data/";
	set_include_path($path);	
	include('PHP Modules/mysqliConnection.php');
	include('PHP Modules/raymond_templatefunctions.php');
	include('PHP Modules/gerald_classes.php');

	function updateCustomerBooking($bookingId)
	{
		include('PHP Modules/mysqliConnection.php');

		$sql = "
			SELECT GROUP_CONCAT(DISTINCT d.customerAlias) as customer FROM engineering_bookingdetails as a
			INNER JOIN ppic_lotlist as b ON b.lotNumber = a.lotNumber AND b.identifier = 1
			INNER JOIN sales_polist as c ON c.poId = b.poId
			INNER JOIN sales_customer as d ON d.customerId = c.customerId
			WHERE bookingId = ".$bookingId."
			GROUP BY a.bookingId
		";
		$queryCustomerBooking = $db->query($sql);
		if($queryCustomerBooking AND $queryCustomerBooking->num_rows > 0)
		{
			$resultCustomerBooking = $queryCustomerBooking->fetch_assoc();
			$customer = $resultCustomerBooking['customer'];

			$sql = "UPDATE engineering_booking SET customer = '".$customer."' WHERE bookingId = ".$bookingId." LIMIT 1";
			$queryUpdate = $db->query($sql);
		}
	}

	function checkTheEmployeePerformance($datesearch1,$datesearch2,$view=0,$employeeId="",$section="",$process="")
	{
		include('PHP Modules/mysqliConnection.php');
		// if (function_exists('computePauseTime')){ echo "Function Exists"; }
		// else{ include('gerald_functions.php'); }
		
		$actualTime_sum=0;
		$standardTime_sum=0;
		$details_lot=array();
		$details_wid=array();
		$details_act=array();
		$details_st=array();
		$details_qty=array();
		$details_pCode=array();
		$details_partId=array();
		$details_CodePart=array();
		$sql = "SELECT id, lotNumber, processCode, actualStart, actualEnd, processSection, quantity FROM ppic_workschedule WHERE id = ".$id;
		
		$sql = "SELECT id, lotNumber, processCode, actualStart, actualEnd, processSection, quantity FROM ppic_workschedule WHERE actualEnd between '".$datesearch1." 00:00:00' AND '".$datesearch2." 23:59:59'";
		if(trim($employeeId)!="")$sql = "SELECT id, lotNumber, processCode, actualStart, actualEnd, processSection, quantity FROM ppic_workschedule WHERE actualEnd between '".$datesearch1." 00:00:00' AND '".$datesearch2." 23:59:59' and employeeId='".$employeeId."'";
		if($view==1)echo "<br>".$sql;
		$workScheduleQuery = $db->query($sql);
		while($workScheduleQueryResult = $workScheduleQuery->fetch_array())
		{
			$id = $workScheduleQueryResult['id'];
			$lotNumber = $workScheduleQueryResult['lotNumber'];
			$quantity = $workScheduleQueryResult['quantity'];
			$processCode = $workScheduleQueryResult['processCode'];
			$actualStart = $workScheduleQueryResult['actualStart'];
			$actualEnd = $workScheduleQueryResult['actualEnd'];
			$processSection = $workScheduleQueryResult['processSection'];

			$sql = "SELECT partId, identifier FROM ppic_lotlist WHERE lotNumber = '".$lotNumber."'";
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				$resultLotList = $queryLotList->fetch_assoc();
				$partId = $resultLotList['partId'];
				$identifier = $resultLotList['identifier'];
			}

			$diffData = 0;
			$diffData = computePauseTime($id);
			$actualTime = 0;
			if($actualStart == "0000-00-00 00:00:00" AND $actualEnd != '0000-00-00 00:00:00') $actualStart = $actualEnd;
			if($actualEnd == "0000-00-00 00:00:00" AND $actualEnd != '0000-00-00 00:00:00') $actualEnd = $actualStart;
			$actualStartTime = strtotime($actualStart);
			$actualEndTime = strtotime($actualEnd);
			$actualTime = (($actualEndTime - $actualStartTime) - $diffData);

			$standardTime = 0;
			if($identifier == 1) $standardTime = getStandardTime($partId,$processCode,$quantity,$processSection,$lotNumber);	

			// $actualTime;
			// $standardTime;
			$actualTime_sum=$actualTime_sum+$actualTime;
			$standardTime_sum=$standardTime_sum+$standardTime;
			if($view==2){$details_lot[]=$lotNumber;$details_wid[]=$id;$details_act[]=$actualTime;$details_st[]=$standardTime;$details_qty[]=$quantity;$details_pCode[]=$processCode;$details_partId[]=$partId;$details_CodePart[]=$processCode."`".$partId;}
			if($view==1)echo "<br>".$lotNumber."~".$id."~".$actualTime."~".$standardTime;
		}
		if($view==2){return array ($actualTime_sum,$standardTime_sum,$details_lot,$details_wid,$details_act,$details_st,$details_qty,$details_pCode,$details_partId,$details_CodePart);}
		else{return array ($actualTime_sum,$standardTime_sum);}
		
	}
	function taskView($idNumber, $taskDates="")
	{
		include('PHP Modules/mysqliConnection.php');
		$emp = new PMSDBController;

		if($taskDates == "") $taskDates = date('Y-m-d');
		$getemp = $emp->setId($idNumber)->getEmployee();
		$employeeId = $getemp['employeeId'];

		$sql = "SELECT taskFlag FROM hr_employee WHERE idNumber = '".$idNumber."'";
		$queryTaskFlag = $db->query($sql);
		if($queryTaskFlag AND $queryTaskFlag->num_rows > 0)
		{
			$resultTaskFlag = $queryTaskFlag->fetch_assoc();
			$taskFlag = $resultTaskFlag['taskFlag'];
		}

		$checked = ($taskFlag == 1) ? "checked" : "";
		echo "<div class='tableFixHead'>";
			echo "<input type='hidden' id='dateSTR' value='".$taskDates."'>";
			echo "<table class='table table-bordered table-condensed table-striped'>";
				echo "<thead class='w3-indigo theadCalendarTask'>";
					echo "<tr>";
						echo "<th style='vertical-align:middle;' class='w3-center' rowspan=2>".date("F d, Y", strtotime($taskDates))."</th>"; 
						echo "<th style='vertical-align:middle;' class='w3-center' colspan=2>".displayText("L3254", "utf8", 0, 0, 1)."</th>"; 
						echo "<th style='vertical-align:middle;' class='w3-center' rowspan=2><input ".$checked." type='checkbox' class='w3-check' id='taskFlag'></th>"; 
					echo "</tr>";
					echo "<tr>";
						echo "<th style='vertical-align:middle;' class='w3-center'>".displayText("L249", "utf8", 0, 0, 1)."</th>";
						echo "<th style='vertical-align:middle;' class='w3-center'>".displayText("L250", "utf8", 0, 0, 1)."</th>";
					echo "</tr>";
				echo "</thead>";
				echo "<tbody class='tbodyCalendarTask'>";
				
				$shift = $emp->setId($idNumber)->setDate($taskDates)->getShift();
				$shiftIN = $shift['shiftIn'];
				if($shiftIN == '') 
				{
					$shiftIN = $shift['shiftIn'] = "07:00:00";
					$shiftOUT = $shift['shiftOut'] = "16:30:00";
				}

				$shiftIN = date("H:i:s", strtotime($shiftIN." -2 Hours"));
				
				$flag = 1;
				for ($i=0; $i <= 14; $i++) 
				{ 
					$timeDatas = date("H:i:s", strtotime($shiftIN." + ".$i." Hours"));
					$timeExplode = explode(":",$timeDatas)[0];

					$startArray = $endArray = $detailsArray = $colorValueArray = $classValueArray = [];
					$shiftId = 1;
					$samedayout = 0;
					$sql = "SELECT shiftId FROM hr_shiftcalendar WHERE shiftDate = '".$taskDates."' AND employeeId = ".$employeeId;
					$queryShift = $db->query($sql);
					if($queryShift AND $queryShift->num_rows > 0)
					{
						$resultShift = $queryShift->fetch_assoc();
						$shiftId = $resultShift['shiftId'];

						$sql = "SELECT  samedayout FROM hr_shift WHERE shiftId = ".$shiftId;
						$queryShiftCheck = $db->query($sql);
						if($queryShiftCheck AND $queryShiftCheck->num_rows > 0)
						{
							$resultShiftCheck = $queryShiftCheck->fetch_assoc();
							$samedayout = $resultShiftCheck['samedayout'];
						}
					}

					$sql = "SELECT shiftIn, shiftOut FROM hr_shift WHERE shiftIn LIKE '".$timeExplode."%' AND shiftId = ".$shiftId;
					$queryShiftCalendar = $db->query($sql);
					if($queryShiftCalendar AND $queryShiftCalendar->num_rows > 0)
					{
						$resultShiftCalendar = $queryShiftCalendar->fetch_assoc();
						$startArray[] = $resultShiftCalendar['shiftIn'];
						$endArray[] = "";
						$detailsArray[] = displayText("L3570", "utf8", 0, 0, 1); //"SHIFT IN";
						$colorValueArray[] = "#F4D03F";
					}

					$sql = "SELECT timeIn FROM hr_dtr WHERE employeeId = '".$idNumber."' AND timeIn LIKE '".$taskDates." ".$timeExplode."%' ORDER BY dtrId ASC LIMIT 1";
					$queryIN = $db->query($sql);
					if($queryIN AND $queryIN->num_rows > 0)
					{
						$resultIN = $queryIN->fetch_assoc();
						$startArray[] = explode(" ", $resultIN['timeIn'])[1];
						$endArray[] =  "";
						$detailsArray[] = displayText("L3383", "utf8", 0, 0, 1); //"TIME IN";
						$colorValueArray[] = "#2196F3";
					}

					$sql = "SELECT timeIn FROM hr_wtr WHERE employeeId = '".$idNumber."' AND timeIn LIKE '".$taskDates." ".$timeExplode."%' ORDER BY dtrId ASC LIMIT 1";
					$queryIN = $db->query($sql);
					if($queryIN AND $queryIN->num_rows > 0)
					{
						$resultIN = $queryIN->fetch_assoc();
						$startArray[] = explode(" ", $resultIN['timeIn'])[1];
						$endArray[] =  "";
						$detailsArray[] = displayText("L4223", "utf8", 0, 0, 1); //"LOG IN";
						$colorValueArray[] = "#2196F3";
					}

					$sql = "SELECT * FROM hr_tasklist WHERE employeeId = '".$idNumber."' AND appointmentDate LIKE '".$taskDates." ".$timeExplode."%' ORDER BY appointmentDate";
					$queryTask = $db->query($sql);
					if($queryTask AND $queryTask->num_rows > 0)
					{
						while($resultTask = $queryTask->fetch_assoc())
						{
							$appointmentId = $resultTask['appointmentId'];
							$appointmentReason = $resultTask['appointmentReason'];
							$appointmentTimeStart = explode(" ",$resultTask['appointmentDate'])[1];
							$appointmentTimeEnd = explode(" ",$resultTask['finish'])[1];
							
							$startArray[] = $appointmentTimeStart;
							$endArray[] = $appointmentTimeEnd;
							$detailsArray[] = $appointmentReason;
							$colorValueArray[] = "";
						}
					}

					$sql = "SELECT shiftIn, shiftOut FROM hr_shift WHERE shiftOut LIKE '".$timeExplode."%' AND shiftId = ".$shiftId;
					$queryShiftCalendar = $db->query($sql);
					if($queryShiftCalendar AND $queryShiftCalendar->num_rows > 0)
					{
						$resultShiftCalendar = $queryShiftCalendar->fetch_assoc();
						$startArray[] = "";
						$endArray[] = $resultShiftCalendar['shiftOut'];
						$detailsArray[] = displayText("L3571", "utf8", 0, 0, 1); //"SHIFT OUT";
						$colorValueArray[] = "#F4D03F";
					}

					if($samedayout == 1)
					{
						$sql = "SELECT timeOut FROM hr_wtr WHERE employeeId = '".$idNumber."' AND timeOut LIKE '".date('Y-m-d', strtotime($taskDates." +1 Day"))." ".$timeExplode."%' ORDER BY dtrId DESC LIMIT 1";
					}
					else
					{
						$sql = "SELECT timeOut FROM hr_wtr WHERE employeeId = '".$idNumber."' AND timeOut LIKE '".$taskDates." ".$timeExplode."%' ORDER BY dtrId DESC LIMIT 1";
					}
					$queryOut = $db->query($sql);
					if($queryOut AND $queryOut->num_rows > 0)
					{
						$resultOut = $queryOut->fetch_assoc();
						$startArray[] =  "";
						$endArray[] = explode(" ", $resultOut['timeOut'])[1];
						$detailsArray[] = displayText("L4222", "utf8", 0, 0, 1); //"LOG OUT";
						$colorValueArray[] = "#2196F3";
					}

					if($samedayout == 1)
					{
						$sql = "SELECT timeOut FROM hr_dtr WHERE employeeId = '".$idNumber."' AND timeOut LIKE '".date('Y-m-d', strtotime($taskDates." +1 Day"))." ".$timeExplode."%' ORDER BY dtrId DESC LIMIT 1";
					}
					else
					{
						$sql = "SELECT timeOut FROM hr_dtr WHERE employeeId = '".$idNumber."' AND timeOut LIKE '".$taskDates." ".$timeExplode."%' ORDER BY dtrId DESC LIMIT 1";
					}
					$queryOut = $db->query($sql);
					if($queryOut AND $queryOut->num_rows > 0)
					{
						$resultOut = $queryOut->fetch_assoc();
						$startArray[] =  "";
						$endArray[] = explode(" ", $resultOut['timeOut'])[1];
						$detailsArray[] = displayText("L3384", "utf8", 0, 0, 1); //"TIME OUT";
						$colorValueArray[] = "#2196F3";
					}

					$start = $end = $details = $colorValue = $colorClassValue = "";
					if($startArray != NULL) $start = implode("<br>", $startArray);
					if($endArray != NULL) $end = implode("<br>", $endArray);
					if($detailsArray != NULL) $details = implode("<br>", $detailsArray);
					if($colorValueArray != NULL) $colorValue = implode(" ", $colorValueArray);
					if($classValueArray != NULL) $colorClassValue = implode(" ", $classValueArray);

					$rowCount = count($startArray);
					
					if($detailsArray == NULL AND $taskFlag == 1) continue;

					if($rowCount > 1)
					{
						echo "<tr>";
							echo "<td style='vertical-align:middle;' class='w3-center' rowspan=".($rowCount + 1)."><b>".$timeDatas."</b></td>";
						echo "</tr>";
						$taskCount = 0;
						foreach ($startArray as $key) 
						{
							echo "<tr style='background-color:".$colorValueArray[$taskCount].";' class='".$classValueArray[$taskCount]."'>";   
								echo "<td style='vertical-align:middle;' class='w3-center'>".$key."</td>";
								echo "<td style='vertical-align:middle;' class='w3-center'>".$endArray[$taskCount]."</td>";  
								echo "<td style='vertical-align:middle;' class='w3-center'>".$detailsArray[$taskCount]."</td>";  
							echo "</tr>";
							$taskCount++;
						}
					}
					else
					{
						echo "<tr>";
							echo "<td style='vertical-align:middle;' class='w3-center'><b>".$timeDatas."</b></td>";
							echo "<td style='vertical-align:middle; background-color:".$colorValue.";' class='w3-center'>".$start."</td>";
							echo "<td style='vertical-align:middle; background-color:".$colorValue.";' class='w3-center'>".$end."</td>";
							echo "<td style='vertical-align:middle; background-color:".$colorValue.";' class='w3-center'>".$details."</td>";
						echo "</tr>";
					}
				}
				echo "</tbody>";
			echo "</table>";
		echo "</div>";
		?>
		<script>
		function scrollBottom()
		{
			var d = new Date();
			var m = d.getMinutes();
			var h = d.getHours();
			var s = d.getSeconds();
			if (h == 11 && m == 00  && s == 00)
			{
				$(".tableFixHead").scrollTop($(".tableFixHead").height());
			}
		}

		$(document).ready(function(){
			var taskDates = "<?php echo $taskDates; ?>";

			var today = new Date();
			var dd = String(today.getDate()).padStart(2, '0');
			var mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
			var yyyy = today.getFullYear();

			today = yyyy + '-' + mm + '-' + dd;
			
			if(taskDates == today)
			{
				setTimeout(function(){
					window.setInterval(function(){
						scrollBottom();
					},1000);
				},800);
			}

			$("#taskFlag").change(function(){
				var idNumber = "<?php echo $idNumber; ?>";
				if($(this).is(":checked"))
				{
					var check = 1;
				}
				else
				{
					var check = 0;
				}

				$.ajax({
					url     : '/<?php echo v; ?>/Common Data/PHP Modules/Calendar/raymond_calendarAJAX.php',
					type    : 'POST',
					data    : {
						renderDay       : 4,
						checkVal       	: check,
						idNumber        : idNumber
					},
					success : function(data){
								location.reload();
					}
				});
			});
		});
		</script>
		<?php
	}

	function createTemporaryMaterial($lotNumber)
	{
		include('PHP Modules/mysqliConnection.php');
		
		$sql = "
			SELECT 
				a.lotNumber, a.workingQuantity, a.partId,
				b.x, b.y, b.partNumber,
				c.metalThickness,
				d.materialType,
				e.customerId
			FROM ppic_lotlist as a
			INNER JOIN cadcam_parts as b ON b.partId = a.partId AND (b.partNumber LIKE '2024%' OR b.partNumber LIKE '5052%' OR b.partNumber LIKE '%6082T6%')
			INNER JOIN cadcam_materialspecs as c ON c.materialSpecId = b.materialSpecId
			INNER JOIN engineering_materialtype as d ON d.materialTypeId = c.materialTypeId
			INNER JOIN sales_polist as e ON e.poId = a.poId AND e.poNumber LIKE 'IPO%' AND e.customerId IN(28,45)
			WHERE a.lotNumber LIKE '".$lotNumber."' AND a.identifier = 1
		";
		$queryIPOPrime = $db->query($sql);
		if($queryIPOPrime AND $queryIPOPrime->num_rows > 0)
		{
			$resultIPOPrime = $queryIPOPrime->fetch_assoc();
			$lotNumber = $resultIPOPrime['lotNumber'];
			$workingQuantity = $resultIPOPrime['workingQuantity'];
			$partId = $resultIPOPrime['partId'];
			$dataThree = $resultIPOPrime['x'];
			$dataFour = $resultIPOPrime['y'];
			$partNumber = $resultIPOPrime['partNumber'];
			$dataTwo = $resultIPOPrime['metalThickness'];
			$dataOne = $resultIPOPrime['materialType'];
			$customerId = $resultIPOPrime['customerId'];
			
			if($customerId==28 AND stristr($partNumber,'6082T6')!==FALSE)
			{
				$supplierAlias = 'B/E Phils.';
				
				$treatmentId = '';
				$treatmentIdArray = array();
				$sql = "SELECT processCode, surfaceArea FROM cadcam_subconlist WHERE partId = ".$partId." AND active = 0";
				$querySubconList = $db->query($sql);
				if($querySubconList AND $querySubconList->num_rows > 0)
				{
					while($resultSubconList = $querySubconList->fetch_assoc())
					{
						$processCode = $resultSubconList['processCode'];
						$surfaceArea = $resultSubconList['surfaceArea'];
						
						$surfaceAreaComputed = round((($dataThree * $dataFour) / 10000),2);
						
						//~ $sidesNumber = ($surfaceAreaComputed > $surfaceArea) ? 2 : 1;
						$sidesNumber = ($surfaceArea > $surfaceAreaComputed) ? 2 : 1;//2019-03-01
						
						if($processCode==270)	$sidesNumber = 2;
						
						$sql = "SELECT treatmentId FROM engineering_subcontreatment WHERE processCode = ".$processCode." AND sidesNumber = ".$sidesNumber."";
						$querySubconTreatment = $db->query($sql);
						if($querySubconTreatment AND $querySubconTreatment->num_rows > 0)
						{
							while($resultSubconTreatment = $querySubconTreatment->fetch_assoc())
							{
								$treatmentId = $resultSubconTreatment['treatmentId'];
								if(in_array($treatmentId,$treatmentIdArray))	break;
								
								$treatmentIdArray[] = $treatmentId;
							}
						}
					}
				}
				
				$dataFive = '';
				if($treatmentId!='')
				{
					$sql = "SELECT treatmentName FROM cadcam_treatmentprocess WHERE treatmentId = ".$treatmentId." LIMIT 1";
					$queryTreatmentProcess = $db->query($sql);
					if($queryTreatmentProcess AND $queryTreatmentProcess->num_rows > 0)
					{
						$resultTreatmentProcess = $queryTreatmentProcess->fetch_assoc();
						$dataFive = $resultTreatmentProcess['treatmentName'];
					}
				}
				$inputType = 5;
			}
			else if($customerId==45 AND (stristr($partNumber,'2024')!==FALSE OR stristr($partNumber,'5052')!==FALSE))
			{
				$supplierAlias = 'Jamco';
				$dataFive = '1 Side Prime';
				$inputType = 3;
			}
			
			$sql = "
				INSERT INTO `warehouse_temporaryinventory`
						(	`supplierAlias`,		`stockDate`,		`stockTime`,				`type`,					`dataOne`,				`dataTwo`,						`dataThree`,
							`dataFour`,				`dataFive`,			`quantity`,					`linkedBalQty`,			`tempBalQty`,			`idNumber`, 					`lotNumber`,			`inputType`)
				VALUES	(	'".$supplierAlias."',	NOW(),				NOW(),						1,						'".$dataOne."',			'".$dataTwo."',					'".$dataThree."',
							'".$dataFour."',		'".$dataFive."',	'".$workingQuantity."',		'".$workingQuantity."',	'".$workingQuantity."',	'".$_SESSION['idNumber']."',	'".$lotNumber."',		'".$inputType."')
			";
			$queryInsert = $db->query($sql);
		}
	}

	function showMessage($messageType)
	{
		header('Location: ../../Common Software/jazmin_message.php?id='.$messageType);
	}
	
	//~ ini_set('display_errors','on');
	function dateDiffInterval($start, $end)
	{
		$start_ts = strtotime($start);
		$end_ts = strtotime($end);
		$diff = $end_ts - $start_ts;
		
		return round($diff / 86400);
	}

	function viewSectionCapacityData($sectionId, $date, $returnValue, $dataCount = 0, $idArray=Array())
	{
		include('PHP Modules/mysqliConnection.php');
		
		$idQuery = "";
		if($idArray != NULL) $idQuery = " AND idNumber IN ('".implode("','", $idArray)."')";
		
		if(in_array($sectionId, Array(4)))
		{
			$idQuery .= " AND idNumber NOT IN ('0215', '0377', '0239', '0774', '0326', '0206')";
		}

		if(in_array($sectionId, Array(11)))
		{
			$idQuery .= " AND idNumber NOT IN ('0207', '0282', '0446', '0369')";
		}

		if(in_array($sectionId, Array(12)))
		{
			$idQuery .= " AND idNumber NOT IN ('0534', '0590', '0495', '0266')";
		}

		if(in_array($sectionId, Array(40)))
		{
			$idQuery .= " AND idNumber NOT IN ('0063')";
        }
        
		if(in_array($sectionId, Array(46)))
		{
			$idQuery .= " AND idNumber NOT IN ('0762','0766')";
		}
        
		if(in_array($sectionId, Array(6)))
		{
			$idQuery .= " AND idNumber NOT IN ('0746','0083','0294')";
        }
        
		if(in_array($sectionId, Array(1)))
		{
			$idQuery .= " AND idNumber NOT IN ('0049')";
		}
		
		$idNumberArray = Array();
		// $sql = "SELECT idNumber FROM hr_employee WHERE sectionId = ".$sectionId." AND position NOT IN (3,4,5,8,9,10,11,12,13,14,15,18,19,21,22,25,26,27,28,30,32,33,34,36,37,38,39,40,41,44,46,47,48,49,50,51,52,53,55) AND status = 1 ".$idQuery;
		$sql = "SELECT idNumber FROM hr_employee WHERE sectionId = ".$sectionId." AND status = 1 ".$idQuery;
		$queryEmployee = $db->query($sql);
		if($queryEmployee->num_rows>0)
		{
			while($resultEmployee = $queryEmployee->fetch_assoc())
			{
				$idNumberArray[] = $resultEmployee['idNumber'];
			}
        }
        
        if(in_array($sectionId, Array(12)))
		{
			$idNumberArray[] = '0746';
			$idNumberArray[] = '0747';
			$idNumberArray[] = '0753';
		}
		
		if(in_array($sectionId, Array(48)))
		{
			$idNumberArray = Array('0534', '0590', '0495');
		}

		if(in_array($sectionId, Array(86)))
		{
			$idNumberArray = Array('0638', '0138','0297');
		}

		if(in_array($sectionId, Array(381)))
		{
			$idNumberArray = Array('0462');
		}

		if(in_array($sectionId, Array(3)))
		{
			$idNumberArray = Array('0456', '0462', '0638', '0138', '0759');
		}
		
		if(in_array($sectionId, Array(0)))
		{
			//~ $idNumberArray = Array('0456','0766','0733','0758','0762');
			$idNumberArray = Array('0766','0733','0758','0762');
		}

        $employeeIdPresentArray = $employeeIdAbsentArray = Array();
        array_unique($idNumberArray);
		foreach ($idNumberArray as $key) 
		{
			$sql = "SELECT employeeId from hr_dtr WHERE timeIn LIKE '".$date."%' AND employeeId = '".$key."' LIMIT 1";
			$queryDtr = $db->query($sql);
			if($queryDtr->num_rows>0)
			{
				$employeeIdPresentArray[] = $key;
			}
			else
			{
				$employeeIdAbsentArray[] = $key;
			}
		}

		if($dataCount == 0) 
		{
			$count = count($idNumberArray);
			$empData = $idNumberArray;
		}

		if($dataCount == 1)
		{
			$count = count($employeeIdPresentArray);
			$empData = $employeeIdPresentArray;
		}

		if($dataCount == 2) 
		{
			$count = count($employeeIdAbsentArray);
			$empData = $employeeIdAbsentArray;
		}
		
		if($dataCount == 3)
		{
			$count = count($idNumberArray);
			$empData = $idNumberArray;
			$sql = "SELECT DISTINCT employeeId FROM hr_leave WHERE employeeId IN('".implode("','",$idNumberArray)."') AND leaveDate >= '".$date."' AND leaveDate <= '".$date."'";
			$queryLeave = $db->query($sql);
			if($queryLeave AND $queryLeave->num_rows > 0)
			{
				$count -= $queryLeave->num_rows;
			}
		}

		$overTimeDataArray = Array ();
		$sql = "SELECT hoursPlan, overtimeCredited, status, employeeId FROM hr_overtime WHERE startTimePlan LIKE '".$date."%' AND employeeId IN ('".implode("', '", $empData)."')";
		$queryOvertime = $db->query($sql);
		if($queryOvertime AND $queryOvertime->num_rows > 0)
		{
			while($resultOvertime = $queryOvertime->fetch_assoc())
			{
				$employeeId = $resultOvertime['employeeId'];
				$status = $resultOvertime['status'];
				$hoursPlan = $resultOvertime['hoursPlan'];
				$overtTimeCredited = $resultOvertime['overtTimeCredited'];

				if($status != 6 AND $overtTimeCredited == 0)
				{
					$overTimeData = $hoursPlan;
				}
				else if($status != 6 AND $overtTimeCredited > 0)
				{
					$overTimeData = $overtTimeCredited;
				}
				else
				{
					$overTimeData = 0;
				}

				// if($_SESSION['idNumber'] == "0412")
				// {
				// 	echo "<br>".$overTimeData." = ".$employeeId;
				// }

				$overTimeDataArray[] = $overTimeData;
			}
		}

		$ot = 0;
		if($overTimeDataArray != NULL)
		{
			$ot = array_sum($overTimeDataArray);
		}

		if(in_array($sectionId, Array(381)))
		{
			$employeeIdArray = Array ();
			$sql = "SELECT employeeId FROM hr_employee WHERE idNumber IN ('".implode("', '", $empData)."')";
			$queryEmpId = $db->query($sql);
			if($queryEmpId AND $queryEmpId->num_rows > 0)
			{
				while($resultEmpId = $queryEmpId->fetch_assoc())
				{
					$employeeIdArray[] = $resultEmpId['employeeId'];
				}
			}

			$sql = "SELECT DISTINCT shiftId FROM hr_shiftcalendar WHERE employeeId IN (".implode(", ",$employeeIdArray).") AND shiftDate = '".$date."'";
			$queryCountShift = $db->query($sql);
			$count = $queryCountShift->num_rows;
		}

		// if($sectionId == 1) $count = 7;   //2022-01-18 commented by rose so that it will not became fix
		// if($sectionId == 2) $count = 5;   //2022-01-18 commented by rose so that it will not became fix
		// if($sectionId == 6) $count = 5;   //2022-01-18 commented by rose so that it will not became fix
		// if($sectionId == 12) $count = 8;  //2022-01-18 commented by rose so that it will not became fix
		// if($sectionId == 48) $count = 2;  //2022-01-18 commented by rose so that it will not became fix
		// if($sectionId == 46) $count = 9;  //2022-01-18 commented by rose so that it will not became fix
		// if($sectionId == 86) $count = 3;  //2022-01-18 commented by rose so that it will not became fix

		// $hours = ($count * 8.5) + $ot;
		$hours = ($count * 8.5);

		$dateData = date("l",strtotime($date));
        $sql = "SELECT holidayId FROM hr_holiday WHERE holidayDate LIKE '".$date."' AND holidayType < 6 LIMIT 1";
        $queryHoliday = $db->query($sql);
        if($queryHoliday->num_rows > 0 OR $dateData == 'Sunday')
        {
            $hours = 0;
		}
		
		if($returnValue==0) return $count; // All Employee Present and absent
		if($returnValue==1) return $hours; // Capacity of present $dataCount = 1; or absent $dataCount= 2 employee 
		if($returnValue==2) return $empData; // idNumbers in array values
	}

	function viewSectionCapacity($sectionId, $date, $returnValue, $dataCount = 0, $idArray=Array())
	{
		include('PHP Modules/mysqliConnection.php');
		// count
		$idQuery = "";
		if($idArray != NULL) $idQuery = " AND idNumber IN ('".implode("','", $idArray)."')";
		$idNumberArray = Array();
		$sql = "SELECT idNumber FROM hr_employee WHERE sectionId = ".$sectionId." AND status = 1 ".$idQuery;
		$queryEmployee = $db->query($sql);
		if($queryEmployee->num_rows>0)
		{
			while($resultEmployee = $queryEmployee->fetch_assoc())
			{
				$idNumberArray[] = $resultEmployee['idNumber'];
			}
		}

		$employeeIdPresentArray = $employeeIdAbsentArray = Array();
		foreach ($idNumberArray as $key) 
		{
			$sql = "SELECT employeeId from hr_dtr WHERE timeIn LIKE '".$date."%' AND employeeId = '".$key."' LIMIT 1";
			$queryDtr = $db->query($sql);
			if($queryDtr->num_rows>0)
			{
				$employeeIdPresentArray[] = $key;
			}
			else
			{
				$employeeIdAbsentArray[] = $key;
			}
		}

		if($dataCount == 0) 
		{
			$count = count($idNumberArray);
			$empData = $idNumberArray;
		}

		if($dataCount == 1)
		{
			$count = count($employeeIdPresentArray);
			$empData = $employeeIdPresentArray;
		}

		if($dataCount == 2) 
		{
			$count = count($employeeIdAbsentArray);
			$empData = $employeeIdAbsentArray;
		}

		$hours = $count * 8.5;
		if($returnValue==0) return $count; // All Employee Present and absent
		if($returnValue==1) return $hours; // Capacity of present $dataCount = 1; or absent $dataCount= 2 employee 
		if($returnValue==2) return $empData; // idNumbers in array values
	}
	// CHICHA 09-05-18
	function updateSTAnalysis($id)
	{
		include('PHP Modules/mysqliConnection.php');
		
		$noSTArray = $goodSTArray = $overSTArray = $belowSTArray = Array();
		$plusMinus = $firstTime = $secondTime = $totalTime = 0;
		$sql = "SELECT id, lotNumber, processCode, processSection, quantity, actualStart, actualEnd FROM ppic_workschedule WHERE status = 1 AND id = ".$id." LIMIT 1";
		$queryPpic = $db->query($sql);
		if($queryPpic->num_rows>0)
		{
			$resultPpic = $queryPpic->fetch_assoc();
			$workId = $resultPpic['id'];
			$lotNumber = $resultPpic['lotNumber'];
			$processCode = $resultPpic['processCode'];
			$processSection = $resultPpic['processSection'];
			$quantity = $resultPpic['quantity'];
			$actualStart = $resultPpic['actualStart'];
			$actualFinish = $resultPpic['actualEnd'];

			$dteStart = new DateTime($actualStart);
			$dteEnd   = new DateTime($actualFinish); 
			$datediff = $dteEnd->diff($dteStart)->format('%hH %iM %sS');

			$firstTime = strtotime($resultPpic['actualStart']);
			$secondTime = strtotime($resultPpic['actualEnd']);
			$totalTime =  ($secondTime - $firstTime);

			$diffData  = 0;
			$diffData = computePauseTime($workId);

			$actualTime = 0;
			if($actualStart == "0000-00-00 00:00:00" AND $actualFinish != '0000-00-00 00:00:00') $actualStart = $actualFinish;
			if($actualFinish == "0000-00-00 00:00:00" AND $actualStart != '0000-00-00 00:00:00') $actualFinish = $actualStart;

			$actualStartTime = strtotime($actualStart);
			$actualEndTime = strtotime($actualFinish);
			$actualTime = (($actualEndTime - $actualStartTime) - $diffData);

			if($processSection==23 OR in_array($processCode,array(312,430,431,432)))
			{
				$bookingId = '';
				if($processSection==23)	$quantity = 0;
				$sql = "SELECT bookingId, quantity FROM engineering_bookingdetails WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
				$queryBookingDetails = $db->query($sql);
				if($queryBookingDetails->num_rows > 0)
				{					
					$resultBookingDetails = $queryBookingDetails->fetch_array();
					$bookingId = $resultBookingDetails['bookingId'];
					if($processSection==23) $quantity = $resultBookingDetails['quantity'];
				}
			}
			
			if(in_array($processCode,array(312,430,431,432)))
			{
				$sql = "SELECT bookingId FROM engineering_bookingdetails WHERE bookingId LIKE '".$bookingId."' AND quantity > 0";
				$queryBookingDetails = $db->query($sql);
				if($queryBookingDetails->num_rows AND $queryBookingDetails->num_rows > 0) $actualTime = $actualTime/$queryBookingDetails->num_rows;
			}
		
			
			$sql = "SELECT partId FROM ppic_lotlist WHERE lotNumber = '".$lotNumber."' LIMIT 1";
			$queryLotlist = $db->query($sql);
			if($queryLotlist->num_rows>0);
			{
				$resultLotlist = $queryLotlist->fetch_assoc();
				$partId = $resultLotlist['partId'];
				
				$product = getStandardTime($partId,$processCode,$quantity,$processSection,$lotNumber);	
				if($actualStart == '0000-00-00 00:00:00') $product = 0;
				$plusMinus = ($product * 0.20);
				$color = "";
				if($product == 0)
				{
					// No ST
					$sql = "UPDATE ppic_workschedule SET stAnalysis = 0 WHERE id = ".$workId." LIMIT 1";
					$queryUpdate1 = $db->query($sql);
					return 0;
				}
				else if($totalTime < ($product - $plusMinus) OR $totalTime > ($product + $plusMinus))
				{
					if($actualTime < $product)
					{
						// Below ST
						$sql = "UPDATE ppic_workschedule SET stAnalysis = 3 WHERE id = ".$workId." LIMIT 1";
						$queryUpdate2 = $db->query($sql);
						return 3;
					}
					else
					{
						// Over ST
						$sql = "UPDATE ppic_workschedule SET stAnalysis = 2 WHERE id = ".$workId." LIMIT 1";
						$queryUpdate3 = $db->query($sql);
						return 2;
					}
				}
				else if($totalTime >= ($product - $plusMinus) AND $totalTime <= ($product + $plusMinus))
				{
					// Good ST
					$sql = "UPDATE ppic_workschedule SET stAnalysis = 1 WHERE id = ".$workId." LIMIT 1";
					$queryUpdate4 = $db->query($sql);
					return 1;
				}
			}
		}
	}
	
	function computeForecastFinish($sectionId)
	{
		include('PHP Modules/mysqliConnection.php');

		$dateNow = date("Y-m-d");
		$addQuery = " sectionId = ".$sectionId." AND ";
		if($sectionId == 3)
		{
			$idNumberArray = array("OJT-17-002", "17 PK14125", "0300");
		}
		else if($sectionId == 381)
		{
			$idNumberArray = array("0468", "0462", "OJT-17-004");
		}
		else if($sectionId == 86)
		{
			$idNumberArray = array("0298", "OJT-17-002","17 PK14125");
		}
		else if($sectionId == 43)
		{
			$idNumberArray = array("0353");
		}
		else if($sectionId == 28)
		{
			$idNumberArray = array("0312");
		}
		else if($sectionId == 0)
		{
			$idNumberArray = array("0484");
		}
		else if($sectionId == 47)
		{
			$idNumberArray = array("0368", "0456", "14 0612910");
		}
		else if($sectionId == 23)
		{
			$idNumberArray = array("17 PK15707");
		}
		else if($sectionId == 32)
		{
			$idNumberArray = array("17 PK14536");
		}
		else if($sectionId == 31)
		{
			$idNumberArray = array("0443");
		}
		else if($sectionId == 30)
		{
			$idNumberArray = array("0446");
		}
		else if($sectionId == 42)
		{
			$idNumberArray = array("0448", "0401", "0438", "OJT-16-010", "0483");
		}
		else
		{
			$idNumberArray = array();
			$sql = "SELECT idNumber FROM hr_employee WHERE status = 1 ".$addQuery;
			$queryEmployee = $db->query($sql);
			if($queryEmployee->num_rows > 0)
			{
				while($resultEmployee = $queryEmployee->fetch_array())
				{
					$idNumberArray[] = "'".$resultEmployee['idNumber']."'";
				}
			}
		}

		$noWorkFlag = 0;
		$day =  date('l', strtotime($key));
		$sql = "SELECT holidayId FROM hr_holiday WHERE holidayDate LIKE '".$dateNow."' AND holidayType < 6 LIMIT 1";
		$queryHoliday = $db->query($sql);
		if($queryHoliday->num_rows > 0 OR $day=='Sunday')
		{
			$noWorkFlag = 1;
			$categoryColor = "red";
		}
		

		$check = "";
		$totalCapacityOT = $totalPersonOT = 0;
		$sql = "SELECT SUM(hoursPlan) AS hoursPlan FROM hr_overtime WHERE employeeId IN('".implode("', '",$idNumberArray)."') AND startTimePlan LIKE '".$dateNow."%' ";
		$queryOT = $db->query($sql);
		if($queryOT->num_rows>0)
		{
			$resultOT = $queryOT->fetch_assoc();
			$hoursPlan = $resultOT['hoursPlan'];
		}

		$totalCapacity = $totalPerson = 0;
		$sql = "SELECT numberOfPerson, capacity FROM system_capacityschedule WHERE scheduleDate = '".$dateNow."' ".$addQuery." AND type = 0 ORDER BY listId";
		$queryCheck = $db->query($sql);
		if($queryCheck AND $queryCheck->num_rows > 0)
		{
			while($resultCheck = $queryCheck->fetch_assoc())
			{
				$person = $resultCheck['numberOfPerson'];
				$totalPerson += $person;
				$cap = $resultCheck['capacity'];

				$totalCapacity += ($person * 8.5) + $cap;
			}

			if($sectionProcess == 86)
			{
				$employeeCount = 3;
			}
			else
			{
				$employeeCount = $totalPerson + $totalPersonOT;
				$capacity = $totalCapacity;
			}
		}
		else
		{
			$employeeCount = count($idNumberArray);
			if($sectionProcess == 381)
			{
				$employeeCount = 2;
			}
			else
			{
				$employeeCount + $totalPersonOT;
			}
			// Blanking (TPP)
			if($sectionProcess == 86)
			{
				$employeeCount = 3;
			}
			else
			{
				$employeeCount + $totalPersonOT;
			}
			// Bending
			if($sectionProcess == 1)
			{
				// $employeeCount = 5;
			}
			else
			{
				$employeeCount + $totalPersonOT;
			}

			if($employeeCount > 0)
			{
				$employeeCount -= $leaveCount;
			}
			
			if($employeeCount > 0)
			{
				$employeeCount -= $obCount;
			}
			
			$time = 8.5;
			$capacity = ($employeeCount * $time);
		}
		
		$array2 = $array1 = Array();
		$sql = "SELECT * FROM view_workschedule WHERE processCode NOT IN (496) AND processSection = ".$sectionId." AND availability = 1 AND targetFinish <= '".$dateNow."' ORDER BY processOrder LIMIT 1";
		$queryWorkSched = $db->query($sql);
		if($queryWorkSched AND $queryWorkSched->num_rows > 0)
		{
			while($resultWorkSched = $queryWorkSched->fetch_assoc())
			{
				$lotNumberData = $resultWorkSched['lotNumber'];
				$processSection = $resultWorkSched['processSection'];
				
				$totalStandardTime = $x = 0;
				if($sectionId == 4) $addQuery = "lotNumber NOT LIKE 'MC%' AND ";
				$sql = "SELECT * FROM view_workschedule WHERE status = 0 AND viewFlag = 0 AND availability = 1 AND processSection = ".$processSection." AND processCode != 174 AND targetFinish >= '0000-00-00' AND ((targetFinish BETWEEN '0000-00-00' AND '".$dateNow."') OR urgentFlag = 10) AND availability!=10 ORDER BY urgentFlag DESC, targetFinish ASC, deliveryDate, partNumber, lotNumber, processOrder ASC";
				$querySched = $db->query($sql);
				$counterData = $querySched->num_rows;
				if($querySched AND $querySched->num_rows > 0)
				{
					while($resultSched = $querySched->fetch_assoc())
					{
						$lotNumber = $resultSched['lotNumber'];
						$processSection = $resultSched['processSection'];
						$processCode = $resultSched['processCode'];
						$targetFinish = $resultSched['targetFinish'];

						$sql = "SELECT partId, workingQuantity FROM ppic_lotlist WHERE lotNumber = '".$lotNumber."'";
						$queryLotList = $db->query($sql);
						if($queryLotList AND $queryLotList->num_rows > 0)
						{
							$resultLotList = $queryLotList->fetch_assoc();
							$partId = $resultLotList['partId'];
							$workingQuantity = $resultLotList['workingQuantity'];
							
							$totalStandardTime = getStandardTime($partId,$processCode,$workingQuantity,$processSection,$lotNumber);
							$dataST += round(($totalStandardTime/3600),2);
							if($dataST >= $capacity)
							{
								$dataST = round(($totalStandardTime/3600),2);
								$array2[] = $array1;
								$array1 = Array();
							}

							$array1[] = $lotNumber;
						}
					}
				}
			}

			if(count($array1) > 0)
			{
				$array2[] = $array1;
			}
		}

		$x = 0;
		foreach ($array2 as $value) 
		{
			echo "<hr>";
			if($x>0)
			{
				$dateForecast = addDays(1);
			}
			else
			{
				$dateForecast = $dateNow;
			}

			$z=0;
			foreach ($value as $key) 
			{
				// echo "<table border = 1>";
				//     echo "<tr>";
				//         echo "<th colspan='6' align='center'>".$key."</th>";
				//     echo "</tr>";
				//     echo "<tr>";
				//         echo "<th>#</th>";
				//         echo "<th>Process</th>";
				//         echo "<th>Group</th>";
				//         echo "<th>Target Finish</th>";
				//         echo "<th>Forecast Finish</th>";
				//         echo "<th>Standard Time</th>";
				//     echo "</tr>";
				//     echo "<tbody>";
					$interval = 0;
					$count = 0;
					$sql = "SELECT * FROM view_workschedule WHERE lotNumber = '".$key."' ORDER BY processOrder";
					$querySchedData = $db->query($sql);
					if($querySchedData AND $querySchedData->num_rows > 0)
					{
						while($resultSchedData = $querySchedData->fetch_assoc())
						{
							$processSection = $resultSchedData['processSection'];
							$processCode = $resultSchedData['processCode'];
							$processOrderNext = $resultSchedData['processOrder']+1;
							if($resultSchedData['targetFinish'] == '0000-00-00')
							{
								$targetFinish = $dateForecast;
							}
							else
							{
								$targetFinish = $resultSchedData['targetFinish'];
							}

							$sql = "SELECT * FROM view_workschedule WHERE lotNumber = '".$key."' AND processOrder = ".$processOrderNext;
							$querySchedDataOrder = $db->query($sql);
							if($querySchedDataOrder AND $querySchedDataOrder->num_rows > 0)
							{
								$resultSchedDataOrder = $querySchedDataOrder->fetch_assoc();
								if($resultSchedDataOrder['targetFinish'] == '0000-00-00')
								{
									$targetFinishCheck = $dateForecast;
								}
								else
								{
									$targetFinishCheck = $resultSchedDataOrder['targetFinish'];
								}
							}

							$interval += dateDiffInterval($targetFinish,$targetFinishCheck);

							$forecastDate = date("Y-m-d",strtotime($dateForecast."+".$interval." Days"));

							$sql = "SELECT partId, workingQuantity FROM ppic_lotlist WHERE lotNumber = '".$key."'";
							$queryLotList = $db->query($sql);
							if($queryLotList AND $queryLotList->num_rows > 0)
							{
								$resultLotList = $queryLotList->fetch_assoc();
								$partId = $resultLotList['partId'];
								$workingQuantity = $resultLotList['workingQuantity'];

								$standardTime = getStandardTime($partId,$processCode,$workingQuantity,$processSection,$key);
							}

							$sql = "SELECT processName FROM cadcam_process WHERE processCode = ".$processCode;
							$queryProcess = $db->query($sql);
							if($queryProcess AND $queryProcess->num_rows > 0)
							{
								$resultProcess = $queryProcess->fetch_assoc();
								$processName = $resultProcess['processName'];

							}

							$sql = "SELECT sectionName FROM ppic_section WHERE sectionId = ".$processSection;
							$queryProcessSection = $db->query($sql);
							if($queryProcessSection AND $queryProcessSection->num_rows > 0)
							{
								$resultProcessSection = $queryProcessSection->fetch_assoc();
								$sectionName = $resultProcessSection['sectionName'];

							}

						//     echo "<tr>";
						//         echo "<td>".++$count."</td>";
						//         echo "<td>".$processName."</td>";
						//         echo "<td>".$sectionName."</td>";
						//         echo "<td>".$targetFinish."</td>";
						//         echo "<td>".$forecastDate."</td>";
						//         echo "<td>".convertSeconds($standardTime)."</td>";
						//     echo "</tr>";
						}
					}
					$z++;
				//     echo "</tbody>";
				// echo "</table>";
				// echo "<br>";

				updateForecastFinish($key);
			}
			$x++;
		}
	}

	function updateForecastFinish($lotNumber)
	{
		include('PHP Modules/mysqliConnection.php');

		$sql = "SELECT * FROM view_workschedule WHERE lotNumber = '".$lotNumber."' ORDER BY processOrder";
		$querySchedData = $db->query($sql);
		if($querySchedData AND $querySchedData->num_rows > 0)
		{
			while($resultSchedData = $querySchedData->fetch_assoc())
			{
				$id = $resultSchedData['id'];
				$processSection = $resultSchedData['processSection'];
				$processCode = $resultSchedData['processCode'];
				$processOrderNext = $resultSchedData['processOrder']+1;
				if($resultSchedData['targetFinish'] == '0000-00-00')
				{
					$targetFinish = $dateForecast;
				}
				else
				{
					$targetFinish = $resultSchedData['targetFinish'];
				}

				$sql = "SELECT * FROM view_workschedule WHERE lotNumber = '".$lotNumber."' AND processOrder = ".$processOrderNext;
				$querySchedDataOrder = $db->query($sql);
				if($querySchedDataOrder AND $querySchedDataOrder->num_rows > 0)
				{
					$resultSchedDataOrder = $querySchedDataOrder->fetch_assoc();
					if($resultSchedDataOrder['targetFinish'] == '0000-00-00')
					{
						$targetFinishCheck = $dateForecast;
					}
					else
					{
						$targetFinishCheck = $resultSchedDataOrder['targetFinish'];
					}
				}

				$interval += dateDiffInterval($targetFinish,$targetFinishCheck);
				$forecastDate = date("Y-m-d",strtotime($dateForecast."+".$interval." Days"));

				$noWorkFlag = 0;
				$day =  date('l', strtotime($forecastDate));
				$sql = "SELECT holidayId FROM hr_holiday WHERE holidayDate = '".$forecastDate."' AND holidayType < 6 LIMIT 1";
				$queryHoliday = $db->query($sql);
				if($queryHoliday->num_rows > 0 OR $day=='Sunday')
				{
					$interval = $interval + 1;
					$forecastDate = date("Y-m-d",strtotime($dateForecast."+".$interval." Days"));
				}
				
				$sql = "UPDATE view_workschedule SET forecastFinish = '".$forecastDate."' WHERE id = ".$id;
				$queryUpdate = $db->query($sql);
			}
		}
	}
	
	function computePauseTime($id)
	{
		include('PHP Modules/mysqliConnection.php');

		$sql = "SELECT lotNumber, processCode, employeeId, actualStart, actualEnd FROM ppic_workschedule WHERE id = ".$id;
		$querySchedule = $db->query($sql);
		if($querySchedule AND $querySchedule->num_rows > 0)
		{
			$resultSchedule = $querySchedule->fetch_assoc();
			$lotNumber = $resultSchedule['lotNumber'];
			$processCode = $resultSchedule['processCode'];
			$idNumber = $resultSchedule['employeeId'];
			$actualStart = $resultSchedule['actualStart'];
			$actualEnd = $resultSchedule['actualEnd'];
			
			$diffData  = 0;
			$sql = "SELECT pauseTime, unpauseTime FROM system_lotonpause WHERE unpauseTime != '0000-00-00 00:00:00' AND statusFlag = 0 AND processCode = ".$processCode." AND lotNumber = '".$lotNumber."' AND idNumber = '".$idNumber."' ORDER BY listId DESC";
			$queryCheckPause = $db->query($sql);
			if($queryCheckPause AND $queryCheckPause->num_rows > 0)
			{
				while($resultCheckPause = $queryCheckPause->fetch_assoc())
				{
					$pauseTime = $resultCheckPause['pauseTime'];
					$unpauseTime = $resultCheckPause['unpauseTime'];
					
					// if($_SESSION['idNumber'] == "0412") echo $pauseTime." >= ".$actualStart." AND ".$unpauseTime." <= ".$actualEnd;
					if($pauseTime >= $actualStart AND $unpauseTime <= $actualEnd)
					{
						$startPause = strtotime($pauseTime);
						$endPause = strtotime($unpauseTime);
						$diffData += ($endPause - $startPause);
					}
				}
			}
		}

		return $diffData;
	}

	function lotdetailsz($lotNumber,$level=0) 
	{
		include('PHP Modules/mysqliConnection.php');		
		$poId="";$partId="";$identifier="";$workingQuantity="";$dateGenerated="";$productionTag="";$groupTag="";$parentLot="";$customerId="";$mainPart="";$poNumber="";$poQuantity="";$customerDeliveryDate="";$poDate="";$mainPrice="";$partNumber="";$partName="";$revisionId="";$customerAlias="";
		$sql = "select * from ppic_lotlist where lotNumber like '".$lotNumber."'";
		$ppicQuery = $db->query($sql);
		if($ppicQuery->num_rows > 0)
		{
			while($ppicQueryResult = $ppicQuery->fetch_assoc())
			{
				$poId=$ppicQueryResult['poId'];
				$partId=$ppicQueryResult['partId'];
				$identifier=$ppicQueryResult['identifier'];
				$workingQuantity=$ppicQueryResult['workingQuantity'];
				$dateGenerated=$ppicQueryResult['dateGenerated'];
				$productionTag=$ppicQueryResult['productionTag'];
				$groupTag=$ppicQueryResult['groupTag'];
				$parentLot=$ppicQueryResult['parentLot'];
				$status=$ppicQueryResult['status'];
			}
		}
		$orderNumber="";
		if($level==0)
		{
			return array($poId,$partId,$identifier,$workingQuantity,$productionTag,$groupTag,$parentLot,$dateGenerated);
		}
		
		else if($level==1)
		{
		//1-part, 2-accessory, 3-material return, 4-purchasingPOsupply, 5-engineering, 6-receive mats, 7-mixturecode
			if($identifier==1 or $identifier==5)
			{
				$sql2 = "select a.customerId,a.partId,a.poNumber,a.poQuantity,a.customerDeliveryDate,a.poDate,a.receiveDate,a.price
						,b.partNumber,b.partName,b.revisionId
						,c.customerAlias
						from sales_polist as a
						inner join cadcam_parts as b on b.partId=".$partId."
						inner join sales_customer as c on c.customerId=a.customerId
						where a.poId = ".$poId;
				$ppicQuery2 = $db->query($sql2);
				if($ppicQuery2->num_rows > 0)
				{	
					while($ppicQueryResult2 = $ppicQuery2->fetch_assoc())
					{
					
						$customerId=$ppicQueryResult2['customerId'];
						$mainPart=$ppicQueryResult2['partId'];
						$poNumber=$ppicQueryResult2['poNumber'];
						$poQuantity=$ppicQueryResult2['poQuantity'];
						$customerDeliveryDate=$ppicQueryResult2['customerDeliveryDate'];
						$poDate=$ppicQueryResult2['poDate'];
						$mainPrice=$ppicQueryResult2['price'];
						$partNumber=$ppicQueryResult2['partNumber'];
						$partName=$ppicQueryResult2['partName'];
						$revisionId=$ppicQueryResult2['revisionId'];
						$customerAlias=$ppicQueryResult2['customerAlias'];
					}
				}
				//$orderNumber = "select orderNumber from sales_poordernumber where poId=".$poId;
				$sql2B = "select orderNumber from sales_poordernumber where poId=".$poId;
				$ppicQuery2B = $db->query($sql2B);
				if($ppicQuery2B->num_rows > 0)
				{
					while($ppicQueryResult2B = $ppicQuery2B->fetch_assoc())
					{
						$orderNumber=$ppicQueryResult2B['orderNumber'];
					}
				}
			//return array($poId,$partId,$identifier,$workingQuantity,$productionTag,$groupTag,$parentLot,$dateGenerated,$customerId,$mainPart,$poNumber,$poQuantity,$customerDeliveryDate,$poDate,$mainPrice,$partNumber,$partName,$revisionId,$customerAlias);	
			}
			if($identifier==2)
			{
				$sql2 = "select a.customerId,a.partId,a.poNumber,a.poQuantity,a.customerDeliveryDate,a.poDate,a.receiveDate,a.price
						,b.accessoryNumber,b.accessoryName,b.revisionId
						,c.customerAlias
						from sales_polist as a
						inner join cadcam_accessories as b on b.accessoryId=".$partId."
						inner join sales_customer as c on c.customerId=a.customerId
						where a.poId = ".$poId;
				$ppicQuery2 = $db->query($sql2);
				if($ppicQuery2->num_rows > 0)
				{	
					while($ppicQueryResult2 = $ppicQuery2->fetch_assoc())
					{
						$customerId=$ppicQueryResult2['customerId'];
						$mainPart=$ppicQueryResult2['partId'];
						$poNumber=$ppicQueryResult2['poNumber'];
						$poQuantity=$ppicQueryResult2['poQuantity'];
						$customerDeliveryDate=$ppicQueryResult2['customerDeliveryDate'];
						$poDate=$ppicQueryResult2['poDate'];
						$mainPrice=$ppicQueryResult2['price'];
						$partNumber=$ppicQueryResult2['accessoryNumber'];
						$partName=$ppicQueryResult2['accessoryName'];
						$revisionId=$ppicQueryResult2['revisionId'];
						$customerAlias=$ppicQueryResult2['customerAlias'];
					}
				}
			//return array($poId,$partId,$identifier,$workingQuantity,$productionTag,$groupTag,$parentLot,$dateGenerated,$customerId,$mainPart,$poNumber,$poQuantity,$customerDeliveryDate,$poDate,$mainPrice,$partNumber,$partName,$revisionId,$customerAlias);	
			}
			if($identifier==3)
			{
				$sql2 = "select b.materialReturnNumber,b.materialType,b.materialThickness,b.materialLength,b.materialWidth
						from cadcam_materialreturn where b.materialReturnId=".$partId;
				$ppicQuery2 = $db->query($sql2);
				if($ppicQuery2->num_rows > 0)
				{	
					while($ppicQueryResult2 = $ppicQuery2->fetch_assoc())
					{
						$customerId="";
						$mainPart="";
						$poNumber="";
						$poQuantity="";
						$customerDeliveryDate="";
						$poDate="";
						$mainPrice="";
						$partNumber=$ppicQueryResult2['materialReturnNumber'];
						$partName=$ppicQueryResult2['materialType']." ".$ppicQueryResult2['materialThickness']."X".$ppicQueryResult2['materialLength']."X".$ppicQueryResult2['materialWidth'];
						$revisionId="";
						$customerAlias="";
					}
				}
			//return array($poId,$partId,$identifier,$workingQuantity,$productionTag,$groupTag,$parentLot,$dateGenerated,$customerId,$mainPart,$poNumber,$poQuantity,$customerDeliveryDate,$poDate,$mainPrice,$partNumber,$partName,$revisionId,$customerAlias);	
			}
			if($identifier==4)
			{
			//status=1:mats,2:subcon,3:supply
				if($status==1)
				{
					$sql2 = "select a.thickness,a.length,a.width,c.materialType
							from purchasing_material as a
							inner join cadcam_materialspecs as b on b.materialSpecId=a.materialSpecId
							inner join engineering_materialtype as c on c.materialTypeId=b.materialTypeId
							where a.materialId = ".$partId;
					$ppicQuery2 = $db->query($sql2);
					if($ppicQuery2->num_rows > 0)
					{	
						while($ppicQueryResult2 = $ppicQuery2->fetch_assoc())
						{
							$customerId="";
							$mainPart="";
							$poNumber="";
							$poQuantity="";
							$customerDeliveryDate="";
							$poDate="";
							$mainPrice="";
							$partNumber=$ppicQueryResult2['materialType'];
							$partName=$ppicQueryResult2['materialType']." ".$ppicQueryResult2['thickness']."X".$ppicQueryResult2['length']."X".$ppicQueryResult2['width'];
							$revisionId="";
							$customerAlias="";
						}
					}
				}
				if($status==2)
				{
					$sql2 = "select b.thickness,b.length,b.width,d.materialType,e.treatmentName
							from purchasing_materialtreatment as a
							inner join purchasing_material as b on b.materialId=a.materialId
							inner join cadcam_materialspecs as c on c.materialSpecId=b.materialSpecId
							inner join engineering_materialtype as d on d.materialTypeId=c.materialTypeId
							inner join cadcam_treatmentprocess as e on e.treatmentId=a.treatmentId
							where a.materialTreatmentId = ".$partId;
					$ppicQuery2 = $db->query($sql2);
					if($ppicQuery2->num_rows > 0)
					{	
						while($ppicQueryResult2 = $ppicQuery2->fetch_assoc())
						{
							$customerId="";
							$mainPart="";
							$poNumber="";
							$poQuantity="";
							$customerDeliveryDate="";
							$poDate="";
							$mainPrice="";
							$partNumber=$ppicQueryResult2['materialType'];
							$partName=$ppicQueryResult2['materialType']." ".$ppicQueryResult2['thickness']."X".$ppicQueryResult2['length']."X".$ppicQueryResult2['width']." ".$ppicQueryResult2['treatmentName'];
							$revisionId="";
							$customerAlias="";
						}
					}
				}
				if($status==3)
				{
					$sql2 = "select a.itemName,b.itemDescription
							from purchasing_items as a
							where a.itemId = ".$partId;
					$ppicQuery2 = $db->query($sql2);
					if($ppicQuery2->num_rows > 0)
					{	
						while($ppicQueryResult2 = $ppicQuery2->fetch_assoc())
						{
							$customerId="";
							$mainPart="";
							$poNumber="";
							$poQuantity="";
							$customerDeliveryDate="";
							$poDate="";
							$mainPrice="";
							$partNumber=$ppicQueryResult2['itemName'];
							$partName=$ppicQueryResult2['itemDescription'];
							$revisionId="";
							$customerAlias="";
						}
					}
				}
				
			//return array($poId,$partId,$identifier,$workingQuantity,$productionTag,$groupTag,$parentLot,$dateGenerated,$customerId,$mainPart,$poNumber,$poQuantity,$customerDeliveryDate,$poDate,$mainPrice,$partNumber,$partName,$revisionId,$customerAlias);	
			}
			if($identifier==6)
			{
				$sql2 = "select b.customerAlias,b.partNumber,b.materialType,b.thickness,b.length,b.width
						from system_receivematerial where b.listId=".$partId;
				$ppicQuery2 = $db->query($sql2);
				if($ppicQuery2->num_rows > 0)
				{	
					while($ppicQueryResult2 = $ppicQuery2->fetch_assoc())
					{
						$customerId="";
						$mainPart="";
						$poNumber="";
						$poQuantity="";
						$customerDeliveryDate="";
						$poDate="";
						$mainPrice="";
						$partNumber=$ppicQueryResult2['partNumber'];
						$partName=$ppicQueryResult2['materialType']." ".$ppicQueryResult2['thickness']."X".$ppicQueryResult2['length']."X".$ppicQueryResult2['width'];
						$revisionId="";
						$customerAlias=$ppicQueryResult2['customerAlias'];
					}
				}
			//return array($poId,$partId,$identifier,$workingQuantity,$productionTag,$groupTag,$parentLot,$dateGenerated,$customerId,$mainPart,$poNumber,$poQuantity,$customerDeliveryDate,$poDate,$mainPrice,$partNumber,$partName,$revisionId,$customerAlias);	
			}
			if($identifier==7)
			{
				$sql2 = "select b.mixtureCode
						from painting_mixture where b.mixtureId=".$partId;
				$ppicQuery2 = $db->query($sql2);
				if($ppicQuery2->num_rows > 0)
				{	
					while($ppicQueryResult2 = $ppicQuery2->fetch_assoc())
					{
						$customerId="";
						$mainPart="";
						$poNumber="";
						$poQuantity="";
						$customerDeliveryDate="";
						$poDate="";
						$mainPrice="";
						$partNumber=$ppicQueryResult2['mixtureCode'];
						$partName="";
						$revisionId="";
						$customerAlias="";
					}
				}
			//return array($poId,$partId,$identifier,$workingQuantity,$productionTag,$groupTag,$parentLot,$dateGenerated,$customerId,$mainPart,$poNumber,$poQuantity,$customerDeliveryDate,$poDate,$mainPrice,$partNumber,$partName,$revisionId,$customerAlias);	
			}
		//echo "".$poId."~".$partId."~".$identifier."~".$workingQuantity."~".$productionTag."~".$groupTag."~".$parentLot."~".$dateGenerated."~".$customerId."~".$mainPart."~".$poNumber."~".$poQuantity."~".$customerDeliveryDate."~".$poDate."~".$mainPrice."~".$partNumber."~".$partName."~".$revisionId."~".$customerAlias;		
		return array($poId,$partId,$identifier,$workingQuantity,$productionTag,$groupTag,$parentLot,$dateGenerated,$customerId,$mainPart,$poNumber,$poQuantity,$customerDeliveryDate,$poDate,$mainPrice,$partNumber,$partName,$revisionId,$customerAlias,$orderNumber);		
		}
	}

	function cocdetails($lotNo)
	{
		include('PHP Modules/mysqliConnection.php');
		//include("gerald_functions.php");
			$errorMessage ="";
			list ($poId,$partId,$identifier,$workingQuantity,$productionTag,$groupTag,$parentLot,$dateGenerated) = lotdetailsz($lotNo);
			$materialBatchNumber = getMaterial($lotNo,0);
			$sql = "SELECT cocNumber FROM cocDocuments WHERE cocLotNumber LIKE '".trim($materialBatchNumber)."' ORDER BY cocId DESC LIMIT 1 ";
			$queryCOCLotNumber = $db->query($sql);
			if($queryCOCLotNumber AND $queryCOCLotNumber->num_rows == 0)
			{
				$errorMessage = "No Mill Certificate!!";
			}
			
			$treatmentId = '';
			$sql = "SELECT treatmentId FROM cadcam_parts WHERE partId = ".$partId." LIMIT 1";
			$queryTreatment = $db->query($sql);
			if($queryTreatment AND $queryTreatment->num_rows > 0)
			{
				$resultTreatment = $queryTreatment->fetch_assoc();
				$treatmentId = $resultTreatment['treatmentId'];
			}
			
			if($treatmentId > 0)
			{
				$inventoryId = getMaterial($lotNo,1);
				
				$mprsFlag = 0;
				$count = 0;
				while($mprsFlag == 0)
				{
					$treatmentId = $sourceId = '';
					$sql = "SELECT dataFive, sourceId FROM warehouse_inventory WHERE inventoryId LIKE '".$inventoryId."' LIMIT 1";
					$queryInventory = $db->query($sql);
					if($queryInventory->num_rows > 0)
					{
						$resultInventory = $queryInventory->fetch_array();
						$treatmentId = $resultInventory['dataFive'];
						$sourceId = $resultInventory['sourceId'];
					}
					else
					{
						$sql = "SELECT dataFive, sourceId FROM warehouse_inventoryhistory WHERE inventoryId LIKE '".$inventoryId."' LIMIT 1";
						$queryInventoryHistory = $db->query($sql);
						if($queryInventoryHistory->num_rows > 0)
						{
							$resultInventoryHistory = $queryInventoryHistory->fetch_array();
							$treatmentId = $resultInventoryHistory['dataFive'];
							$sourceId = $resultInventoryHistory['sourceId'];
						}
					}
					
					if($treatmentId != '')
					{
						if($treatmentId == 'Raw')
						{
							$mprs = $inventoryId;
							if($count==0) { $mprs = $lotNo; }
							$mprsFlag = 1;
						}
						else
						{
							if($sourceId!='')
							{
								$sql = "SELECT lotNumber FROM ppic_lotlist WHERE lotNumber LIKE '".$sourceId."' AND ((identifier = 4 AND status = 2) or identifier = 1) LIMIT 1";
								$queryLotList = $db->query($sql);
								if($queryLotList AND $queryLotList->num_rows > 0)
								{
									$mprsFlag = 1;
									$mprs = $sourceId;
								}
								
								$sql = "SELECT materialsInSubconMPRS FROM warehouse_materialsubcondetails WHERE materialsInSubconMPRS LIKE '".$sourceId."' LIMIT 1";
								$querySubconMprs = $db->query($sql);
								if($querySubconMprs->num_rows > 0)
								{
									$mprsFlag = 1;
									$mprs = $sourceId;
								}
								else
								{
									$sql = "SELECT lotNumber FROM ppic_lotlist WHERE lotNumber LIKE '".$sourceId."' ";
									$getLotNumber = $db->query($sql);
									if($getLotNumber->num_rows > 0)
									{
										$bookingId = '';
										$sql = "SELECT bookingId FROM engineering_bookingdetails WHERE lotNumber LIKE '".$sourceId."' LIMIT 1";
										$queryBookingId = $db->query($sql);
										if($queryBookingId->num_rows > 0)
										{
											$resultBookingId = $queryBookingId->fetch_array();
											$bookingId = $resultBookingId['bookingId'];
										}
										
										$inventoryId = '';
										$sql = "SELECT inventoryId FROM engineering_booking WHERE bookingId = ".$bookingId." LIMIT 1";
										$queryInventoryId = $db->query($sql);
										if($queryInventoryId->num_rows > 0)
										{
											$resultInventoryId = $queryInventoryId->fetch_array();
											$inventoryId = $resultInventoryId['inventoryId'];
										}
									}
									else
									{
										$inventoryId = $sourceId;
									}
								}
							}
							else
							{
								$mprsFlag = 1;
								$mprs = '';
							}
						}
					}
					else
					{
						$mprsFlag = 1;
						$mprs = '';
					}
					$count++;
				}
				
				if($mprs!='')
				{
					$sql = "SELECT cocNumber FROM cocDocuments WHERE cocLotNumber LIKE '".trim($mprs)."' ORDER BY cocId DESC LIMIT 1";
					$queryCOCLotNumber = $db->query($sql);
					if($queryCOCLotNumber AND $queryCOCLotNumber->num_rows == 0)
					{
						//Rose START ---------------------------------------------
						$roseMPRS=explode("-",trim($mprs));
						if($roseMPRS[3]!="")
						{
							if($roseMPRS[3]!="1")
							{
								$sql = "SELECT cocNumber FROM cocDocuments WHERE cocLotNumber LIKE '".$roseMPRS[0]."-".$roseMPRS[1]."-".$roseMPRS[2]."' ORDER BY cocId DESC LIMIT 1";
								$queryCOCLotNumber = $db->query($sql);
								if($queryCOCLotNumber AND $queryCOCLotNumber->num_rows == 0)
								{
									$errorMessage = "No KCOC!!";
								}
							}
							else if($roseMPRS[3]>1)
							{
								$roseMPRS2=($roseMPRS[3]-1);
								$sql = "SELECT cocNumber FROM cocDocuments WHERE cocLotNumber LIKE '".$roseMPRS[0]."-".$roseMPRS[1]."-".$roseMPRS[2]."-".$roseMPRS2."' ORDER BY cocId DESC LIMIT 1";
								$queryCOCLotNumber = $db->query($sql);
								if($queryCOCLotNumber AND $queryCOCLotNumber->num_rows == 0)
								{
									$sql = "SELECT cocNumber FROM cocDocuments WHERE cocLotNumber LIKE '".$roseMPRS[0]."-".$roseMPRS[1]."-".$roseMPRS[2]."-%' ORDER BY cocId DESC LIMIT 1";
									$queryCOCLotNumber = $db->query($sql);
									if($queryCOCLotNumber AND $queryCOCLotNumber->num_rows == 0)
									{
										$sql = "SELECT cocNumber FROM cocDocuments WHERE cocLotNumber LIKE '".$roseMPRS[0]."-".$roseMPRS[1]."-".$roseMPRS[2]."' ORDER BY cocId DESC LIMIT 1";
										$queryCOCLotNumber = $db->query($sql);
										if($queryCOCLotNumber AND $queryCOCLotNumber->num_rows == 0)
										{
											$errorMessage = "No KCOC!!";
											//goto errorLabel;
										}
									}
									
								}
							}
						}
						else
						{
							$errorMessage = "No KCOC!!";
							//goto errorLabel;
						}
						//Rose END ---------------------------------------------					
					}
				}
			}
			else
			{
				$loteArray = array();
				$sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId = ".$poId." AND identifier = 1";
				$queryLotLot = $db->query($sql);
				if($queryLotLot AND $queryLotLot->num_rows > 0)
				{
					while($resultLotLot = $queryLotLot->fetch_assoc())
					{
						$loteArray[] = $resultLotLot['lotNumber'];
					}
				}
				
				$checkCOCFlag = 0;
				$sql = "SELECT id FROM ppic_workschedule WHERE lotNumber IN('".implode("','",$loteArray)."') AND processCode = 145";
				$queryWorkSchedule = $db->query($sql);
				if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
				{
					$checkCOCFlag = 1;
				}
				
				if($checkCOCFlag == 1)
				{
					
					//ROSE START
					$roseCheckDoc=0;
					if(count($loteArray)>1)
					{
						for($ros=0;$ros<count($loteArray);$ros++)
						{
							$sql = "SELECT cocNumber FROM cocDocuments WHERE cocLotNumber LIKE '".trim($loteArray[$ros])."' ORDER BY cocId DESC LIMIT 1";
							$queryCOCLotNumber = $db->query($sql);
							if($queryCOCLotNumber AND $queryCOCLotNumber->num_rows > 0)
							{
								$roseCheckDoc=1;
							}										
						}
						if($roseCheckDoc==0)
						{
							$errorMessage = "No KCOC!!";
						}
					}
					else
					{
						$sql = "SELECT cocNumber FROM cocDocuments WHERE cocLotNumber LIKE '".trim($lotNo)."' ORDER BY cocId DESC LIMIT 1";
						$queryCOCLotNumber = $db->query($sql);
						if($queryCOCLotNumber AND $queryCOCLotNumber->num_rows == 0)
						{
							$errorMessage = "No KCOC!!".implode(",",$loteArray);
						}
					}
				}
			}
		//~ }
		
		/*//QA Doc Error Checking Remove 2017-03-30
		$sql = "SELECT partId FROM cadcam_parts WHERE partId = (SELECT partId FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNo."') AND partName LIKE '%scrap%' LIMIT 1";
		$queryParts = $db->query($sql);
		if($queryParts->num_rows == 0)
		{
			$sql = "SELECT status FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNo."' AND processCode = 358 LIMIT 1";
			$queryWorkSchedule = $db->query($sql);
			if($queryWorkSchedule->num_rows > 0)
			{
				$resultWorkSchedule = $queryWorkSchedule->fetch_array();
				if($resultWorkSchedule['status']=='0')
				{
					$errorMessage = "QA Documentation Checking Process Not Finished!!";
					goto errorLabel;
				}
			}
			else
			{
				$errorMessage = "No QA Documentation Checking Process!!";
				goto errorLabel;
			}
		}
		*/
		
		return $errorMessage;
	}

	function insertEmployeePerformance($id)
	{
		include('PHP Modules/mysqliConnection.php');
		$dbase = new PMSDatabase;
		$ctrl = new PMSDBController;

		$sql = "SELECT lotNumber, processCode, actualStart, actualEnd, actualFinish, processSection, processRemarks, customerId, targetFinish, quantity, employeeId, employeeIdStart, stAnalysis FROM ppic_workschedule WHERE id = ".$id;
		$workScheduleQuery = $db->query($sql);
		$workScheduleQueryResult = $workScheduleQuery->fetch_array();
		$lotNumber = $workScheduleQueryResult['lotNumber'];
		$quantity = $workScheduleQueryResult['quantity'];
		$employeeId = $workScheduleQueryResult['employeeId'];
		$employeeIdStart = $workScheduleQueryResult['employeeIdStart'];
		$processCode = $workScheduleQueryResult['processCode'];
		$actualStart = $workScheduleQueryResult['actualStart'];
		$actualEnd = $workScheduleQueryResult['actualEnd'];
		$actualFinish = $workScheduleQueryResult['actualFinish'];
		$targetFinish = $workScheduleQueryResult['targetFinish'];
		$processSection = $workScheduleQueryResult['processSection'];
		$customerId = $workScheduleQueryResult['customerId'];
		$stAnalysis = $workScheduleQueryResult['stAnalysis'];

		if($employeeId == "") $employeeId = $employeeIdStart;

		$bookingId = 0;
		$sql = "SELECT bookingId FROM engineering_bookingdetails WHERE lotNumber = '".$lotNumber."'";
		$queryBooking = $db->query($sql);
		if($queryBooking AND $queryBooking->num_rows > 0)
		{
			$resultBooking = $queryBooking->fetch_assoc();
			$bookingId = $resultBooking['bookingId'];
		}

		$sql = "SELECT sectionName FROM ppic_section WHERE sectionId = ".$processSection;
		$querySection = $db->query($sql);
		if($querySection AND $querySection->num_rows > 0)
		{
			$resultSection = $querySection->fetch_assoc();
			$sectionName = $resultSection['sectionName'];
		}

		$sql = "SELECT processName FROM cadcam_process WHERE processCode = ".$processCode;
		$queryProcess = $db->query($sql);
		if($queryProcess AND $queryProcess->num_rows > 0)
		{
			$resultProcess = $queryProcess->fetch_assoc();
			$processName = $resultProcess['processName'];
		}

		$sql = "SELECT customerAlias FROM sales_customer WHERE customerId = ".$customerId;
		$queryCustomer = $db->query($sql);
		if($queryCustomer AND $queryCustomer->num_rows > 0)
		{
			$resultCustomer = $queryCustomer->fetch_assoc();
			$customerAlias = $resultCustomer['customerAlias'];
		}

		$sql = "SELECT partId, identifier FROM ppic_lotlist WHERE lotNumber = '".$lotNumber."'";
		$queryLotList = $db->query($sql);
		if($queryLotList AND $queryLotList->num_rows > 0)
		{
			$resultLotList = $queryLotList->fetch_assoc();
			$partId = $resultLotList['partId'];
			$identifier = $resultLotList['identifier'];

			$sql = "SELECT partNumber, revisionId FROM cadcam_parts WHERE partId = ".$partId;
			$queryParts = $db->query($sql);
			if($queryParts AND $queryParts->num_rows > 0)
			{
				$resultParts = $queryParts->fetch_assoc();
				$partNumber = $resultParts['partNumber'];
				$revisionId = $resultParts['revisionId'];
				if($partId==0)
				{
					$partNumber = "";			
					$partName = "";			
					$revisionId = "";
				}
			}

			$itemWeight = 0;
			if($identifier == 1)	
			{
				$sql = "SELECT * FROM cadcam_parts WHERE partId = ".$partId." ";
				$getParts = $db->query($sql);
				while($getPartsResult = $getParts->fetch_array())
				{
					if($partId==0)
					{
						$partNumber = "";			
						$partName = "";			
						$revisionId = "";
						$itemWeight = 0;
					}
					else
					{
					$partNumber = $getPartsResult['partNumber'];			
					$partName = $getPartsResult['partName'];			
					$revisionId = $getPartsResult['revisionId'];
					$itemWeight = (($getPartsResult['itemWeight']*$quantity)/1000);
					}
				}
				//Samuel Belen
				//if someting happen comment this code
				// $sql = "SELECT totalQty, lastUpdate FROM system_employeeProcessItem WHERE idNumber = '".$employeeId."' AND partId = ".$partId." AND processCode = ". $processCode;
				// $resultData = $dbase->setSQLQuery($sql)->getRecords();
				// if($resultData != NULL)
				// {
				// 	$resultTotalQty = $resultData[0]['totalQty'] + $quantity;
				// 	$sql = "UPDATE system_employeeProcessItem SET  totalQty = ".$resultTotalQty." , lastUpdate = '".$actualFinish."' WHERE idNumber = '".$employeeId."' AND partId = ".$partId." AND processCode = ". $processCode;	
				// 	$resultUpdate = $db->query($sql);					
				//  }
				//  else
				//  {
				// 	$dbase->setTableName("system_employeeProcessItem")
				// 	->setFieldsValues([
				// 		"idNumber"        => $employeeId,
				// 		"partId"          => $partId,
				// 		"processCode"     => $processCode,
				// 		"totalQty"        => $quantity,
				// 		"lastUpdate"      => $actualFinish
				// 	])
				// 	->insert();		
				// }
				//Until here

			}
			else if($identifier == 2)	
			{
				$sql = "SELECT * FROM cadcam_accessories WHERE accessoryId = ".$partId." ";
				$getAccessories = $db->query($sql);
				while($getAccessoriesResult = $getAccessories->fetch_array())
				{
					$partNumber = $getAccessoriesResult['accessoryNumber'];			
					$revisionId = '';		
				}
			}		
			else if($identifier == 6)	
			{
				$sql = "SELECT * FROM system_receivematerial WHERE listId = ".$partId." ";
				$getParts = $db->query($sql);
				while($getPartsResult = $getParts->fetch_array())
				{
					$partNumber = $getPartsResult['partNumber'];			
					$revisionId = $getPartsResult['batchNumber'];			
					$partName = "";		
					$customerAlias = "";
				}
			}
		}

		$diffData = 0;
		$diffData = computePauseTime($id);
		$actualTime = 0;
		if($actualStart == "0000-00-00 00:00:00" AND $actualEnd != '0000-00-00 00:00:00') $actualStart = $actualEnd;
		if($actualEnd == "0000-00-00 00:00:00" AND $actualEnd != '0000-00-00 00:00:00') $actualEnd = $actualStart;
		$actualStartTime = strtotime($actualStart);
		$actualEndTime = strtotime($actualEnd);
		$actualTime = (($actualEndTime - $actualStartTime) - $diffData);

		$standardTime = 0;
		if($identifier == 1) $standardTime = getStandardTime($partId,$processCode,$quantity,$processSection,$lotNumber);	

		if($stAnalysis == 0 AND $standardTime > 0 AND $quantity > 0) 
		{
			updateSTAnalysis($id);
			$sql = "SELECT stAnalysis FROM ppic_workschedule WHERE id = ".$id." LIMIT 1";
			$workScheduleQuery = $db->query($sql);
			$workScheduleQueryResult = $workScheduleQuery->fetch_array();
			$stAnalysis = $workScheduleQueryResult['stAnalysis'];
		}

		$employee = $ctrl->setId($employeeId)->getEmployee();

		$dbase->setTableName("system_employeeperformance")
				->setFieldsValues([
						"workId"            => $id,
						"bookingId"         => $bookingId,
						"customerAlias"     => $customerAlias,
						"lotNumber"         => $lotNumber,
						"partNumber"        => $partNumber,
						"revisionId"        => $revisionId,
						"quantity"          => $quantity,
						"weight"            => $itemWeight,
						"sectionId"         => $processSection,
						"sectionName"       => $sectionName,
						"processCode"       => $processCode,
						"processName"       => $processName,
						"idNumber"          => $employeeId,
						"employeeName"      => $employee['fullName'],
						"targetDate"        => $targetFinish,
						"actualFinish"      => $actualFinish,
						"actualStart"       => $actualStart,
						"actualEnd"         => $actualEnd,
						"actualTime"        => $actualTime,
						"standardTime"      => $standardTime,
						"stAnalysis"        => $stAnalysis
				])
				->insert();
			
	}

	function startProcess($lotNumber = "",  $currentWorkScheduleId, $workingQuantity=0, $employeeId='', $processRemarks='', $currentWorkScheduleIdArray = Array())
	{
		include('PHP Modules/mysqliConnection.php');
		if($_GET['country']=="1") 
		{
			$excemptedProcess = "141,174,95,313,366,367,364,437,438,461,597,598,599,600,601,602,603";
			$assemblyProcessArray = array(162);
		}
		else
		{
			$excemptedProcess = "141,95,313,366,367,364,438,461,597,598,599,600,601,602,603";
			$assemblyProcessArray = array(97,162,524,532,533,534,535,536,537,538,539,540,541,542,543,544,545,546,547,548,549,550,551,552,553,555,556);
		}

		if($currentWorkScheduleIdArray != NULL)
		{
			$currentWorkScheduleId = "";
			$dateTimeNow = date("Y-m-d H:i:s");
			$dateNow = date("Y-m-d");
			foreach ($currentWorkScheduleIdArray as $currentWorkScheduleId) 
			{
				$sql = "SELECT lotNumber, processCode, actualStart, processSection FROM ppic_workschedule WHERE id = ".$currentWorkScheduleId;
				$workScheduleQuery = $db->query($sql);
				$workScheduleQueryResult = $workScheduleQuery->fetch_array();
				$processCode = $workScheduleQueryResult['processCode'];
				$actualStart = $workScheduleQueryResult['actualStart'];
				$sectionId = $workScheduleQueryResult['processSection'];
				
				if($lotNumber == "")
				{
					$lotNumber = $workScheduleQueryResult['lotNumber'];
				}
				
				$actualStartParameter = "";
				if($actualStart == "0000-00-00 00:00:00")
				{
					$actualStartParameter = "actualStart = '".$dateTimeNow."',";
				}

				$sql = "UPDATE ppic_workschedule SET employeeIdStart = '".$employeeId."', actualStart = '".$dateTimeNow."', availability = 11 WHERE id = ".$currentWorkScheduleId;
				$updateQuery = $db->query($sql);
			}
		}
	}
		
	// ------------------------------- Execute When Process Was Finished (Ace) ----------------------------------
	function finishProcess($lotNumber = "",  $currentWorkScheduleId, $workingQuantity=0, $employeeId='', $processRemarks='', $currentWorkScheduleIdArray = Array(), $ngQty=0)
	{		
		// ------------------ Add Update Actual Start If 0000-00-00 ----------------------------------------
		
		include('PHP Modules/mysqliConnection.php');
		
		if($_GET['country']=="1") 
		{
			$excemptedProcess = "141,174,95,313,366,367,364,437,438,461,597,598,599,600,601,602,603";
			$assemblyProcessArray = array(162);
		}
		else
		{
			$excemptedProcess = "141,95,313,366,367,364,438,461,597,598,599,600,601,602,603";
			$assemblyProcessArray = array(97,162,524,532,533,534,535,536,537,538,539,540,541,542,543,544,545,546,547,548,549,550,551,552,553,555,556);
        }
        

		if($currentWorkScheduleIdArray != NULL)
		{
			$currentWorkScheduleId = "";
			$dateTimeNow = date("Y-m-d H:i:s");
			$dateNow = date("Y-m-d");
			foreach ($currentWorkScheduleIdArray as $workingQuantity => $value) 
			{
				foreach ($value as $currentWorkScheduleId)
				{
					$sql = "SELECT lotNumber, processCode, actualStart, processSection, processRemarks FROM ppic_workschedule WHERE id = ".$currentWorkScheduleId;
					$workScheduleQuery = $db->query($sql);
					$workScheduleQueryResult = $workScheduleQuery->fetch_array();
					$processCode = $workScheduleQueryResult['processCode'];
					$actualStart = $workScheduleQueryResult['actualStart'];
                    $sectionId = $workScheduleQueryResult['processSection'];
					
					if($lotNumber == "")
					{
						$lotNumber = $workScheduleQueryResult['lotNumber'];
                    }

                    if($ngQty > 0)
                    {
                        if(count($currentWorkScheduleIdArray) > 1)
                        {
                            $sql = "SELECT workingQuantity FROM ppic_lotlist WHERE lotNumber = '".$workScheduleQueryResult['lotNumber']."'";
                            $queryLotList = $db->query($sql);
                            if($queryLotList AND $queryLotList->num_rows > 0)
                            {
                                $resultLotList = $queryLotList->fetch_assoc();
                                $qtys = $resultLotList['workingQuantity'];

                                if($qtys == $workingQuantity)
                                {
                                    if($qtys == $ngQty)
                                    {
                                        $processRemarks = "Good : 0; NG : ".(floor($workingQuantity)).";";
                                    }
                                    else
                                    {
                                        $processRemarks = "Good : ".(floor($workingQuantity))."; NG : 0;";
                                    }
                                }
                                else
                                {
                                    if($qtys == $ngQty)
                                    {
                                        $processRemarks = "Good : 0; NG : ".(floor($workingQuantity)).";";
                                    }
                                    else
                                    {
                                        $processRemarks = "Good : ".($workingQuantity - $ngQty)."; NG : ".$ngQty;
                                    }
                                }
                            }
                        }
                    }
					
					$actualStartParameter = "";
					if($actualStart == "0000-00-00 00:00:00")
					{
						$actualStartParameter = "actualStart = '".$dateTimeNow."',";
					}

					$locationComputer = isset($_COOKIE['PC']) ? $_COOKIE['PC'] : "";
					if($locationComputer != '')
					{
						if(trim($processRemarks) != "")
						{
							$processRemarks .= "<br>Machine : ".$locationComputer;
						}
						else
						{
							$processRemarks = "Machine : ".$locationComputer;
						}
					}

					if(in_array($processCode,array(437,438,461,137,597,598,599,600,601,602,603)))
					{
						$sql = "UPDATE ppic_workschedule SET ".$actualStartParameter." actualEnd='".$dateTimeNow."', actualFinish='".$dateNow."', quantity=".($workingQuantity).", employeeId='".$employeeId."', status=1 WHERE id = ".$currentWorkScheduleId;
						$result = $db->query($sql);
					}
					else
					{
						// ------------------------------------------------------------------------------------------ Finish Process --------------------------------------------------------------------------------------
						$sql = "UPDATE ppic_workschedule SET ".$actualStartParameter." actualEnd='".$dateTimeNow."', actualFinish='".$dateNow."', quantity=".($workingQuantity).", employeeId='".$employeeId."', status=1, processRemarks = '".mysqli_real_escape_string($db,$processRemarks)."' WHERE id = ".$currentWorkScheduleId;
						$result = $db->query($sql);
					}

					// ----------------------------------------------------------------------------------------- Update Availability --------------------------------------------------------------------------------		
					if(!in_array($processCode,array(437,438,461,597,598,599,600,601,602,603)))
					{
						updateAvailability($lotNumber);
					}
					
					// ---------------------------------------------------------------------------------------- Update system_machineworkschedule ---------------------------------------------------------
					$sql = "UPDATE system_machineWorkschedule SET status = 1 WHERE inputDate = '".$dateNow."' AND workscheduleId = ".$currentWorkScheduleId;
					$updateQuery = $db->query($sql);

					$sql = "SELECT inputDate FROM system_machineWorkschedule WHERE inputDate > '".$dateNow."' AND workscheduleId = ".$currentWorkScheduleId;
					$queryCheckSched = $db->query($sql);
					if($queryCheckSched AND $queryCheckSched->num_rows > 0)
					{
						$resultCheckSched = $queryCheckSched->fetch_assoc();
						$inputDate = $resultCheckSched['inputDate'];

						$sql = "DELETE FROM system_machineWorkschedule WHERE inputDate = '".$inputDate."' AND workscheduleId = ".$currentWorkScheduleId;
						$deleteQuery = $db->query($sql);
					}
					
					// ---------------------------------------------------------------------------------------- Retrieve Next Process -------------------------------------------------------------------
					$sql = "SELECT id, processCode FROM ppic_workschedule WHERE status = 0 AND processCode NOT IN (".$excemptedProcess.") AND lotNumber = '".$lotNumber."' ORDER BY processOrder ASC LIMIT 1";
					$workScheduleQuery = $db->query($sql);
					$workScheduleQueryResult = $workScheduleQuery->fetch_array();
					$nextWorkScheduleId = $workScheduleQueryResult['id'];
					$nextProcessCode = $workScheduleQueryResult['processCode'];
					
					// ---------------------------------------------------------------------- Set Previous Process Actual Finish In Current Process --------------------------------------------------------------
					$sql = "UPDATE ppic_workschedule SET previousActualFinish='".$dateTimeNow."' WHERE id = ".$nextWorkScheduleId;
					$updateQuery = $db->query($sql);
					
					// --------------------------------------------- Remove Data In system_lotlist If Current Process Is Delivery Or Warehouse Storage ---------------------------------------------
					if($processCode == 144 OR  $processCode == 353)
					{			
						$sql = "DELETE FROM system_lotlist WHERE lotNumber = '".$lotNumber."'";
						$deleteQuery = $db->query($sql);
						
						if($processCode==144)
						{
							$sql = "UPDATE ppic_workschedule SET status = 1 WHERE lotNumber LIKE '".$lotNumber."' AND processCode = 496 AND status = 0";
							$queryUpdate = $db->query($sql);
						}
					}
					
					// -------------------------------------------- Delete Data In view_workschedule --------------------------------------------------------
					$sql = "DELETE FROM view_workschedule WHERE id = ".$currentWorkScheduleId;
					$deleteQuery = $db->query($sql);
					
					updateSTAnalysis($currentWorkScheduleId);

					// -------------------------------------------- Execute When Next Process Is Proceed To Assembly ------------------------------------
					if(in_array($nextProcessCode, $assemblyProcessArray))
					{	
						finishProcess("",  $nextWorkScheduleId, $workingQuantity, $employeeId, $processRemarks);
						// insertEmployeePerformance($nextWorkScheduleId);
                    }

					if($processCode == 187 AND $_GET['country'] == 2)
					{
						$sql = "SELECT id FROM ppic_workschedule WHERE lotNumber = '".$lotNumber."' AND processCode = 518 LIMIT 1";
						$queryCheckDueDate = $db->query($sql);
						if($queryCheckDueDate AND $queryCheckDueDate->num_rows > 0)
						{
							$resultCheckDueDate = $queryCheckDueDate->fetch_assoc();
							$workIdDue = $resultCheckDueDate['id'];

							$sql = "UPDATE ppic_workschedule SET actualStart = '".$dateTimeNow."', actualEnd='".$dateTimeNow."', actualFinish='".$dateNow."', quantity=".($workingQuantity).", employeeId='".$employeeId."', status = 1 WHERE id = ".$workIdDue." AND status = 0 LIMIT 1";
							$queryUpdate = $db->query($sql);
						}
					}

					insertEmployeePerformance($currentWorkScheduleId);
				}
			}
		}
		else
		{
			// ------------------------------------------------------------------------------------------ Retrieve Current Process (Create Function In The Future) ----------------------------------------------------------------------
            $dateTimeNow = date("Y-m-d H:i:s");
            $sql = "SELECT lotNumber, processCode, actualStart, processSection FROM ppic_workschedule WHERE id = ".$currentWorkScheduleId;
            $workScheduleQuery = $db->query($sql);
            if($workScheduleQuery AND $workScheduleQuery->num_rows > 0)
            {
                $workScheduleQueryResult = $workScheduleQuery->fetch_array();
                $processCode = $workScheduleQueryResult['processCode'];
                $actualStart = $workScheduleQueryResult['actualStart'];
                $sectionId = $workScheduleQueryResult['processSection'];
                
                if($lotNumber == "")
                {
                    $lotNumber = $workScheduleQueryResult['lotNumber'];
                }
                
                $actualStartParameter = "";
                if($actualStart == "0000-00-00 00:00:00")
                {
                    $actualStartParameter = "actualStart = now(),";
				}
				
				$locationComputer = isset($_COOKIE['PC']) ? $_COOKIE['PC'] : "";
				if($locationComputer != '')
				{
					if(trim($processRemarks) != "")
					{
						$processRemarks .= "<br>Machine : ".$locationComputer;
					}
					else
					{
						$processRemarks = "Machine : ".$locationComputer;
					}
				}
                
                if(in_array($processCode,array(437,438,461,137,138,229,597,598,599,600,601,602,603)))
                {
                    $sql = "UPDATE ppic_workschedule SET ".$actualStartParameter." actualEnd='".$dateTimeNow."', actualFinish=now(), quantity=".$workingQuantity.", employeeId='".$employeeId."', status=1 WHERE id = ".$currentWorkScheduleId;
                    $result = $db->query($sql);
                }
                else
                {
                    // ------------------------------------------------------------------------------------------ Finish Process --------------------------------------------------------------------------------------
                    $sql = "UPDATE ppic_workschedule SET ".$actualStartParameter." actualEnd='".$dateTimeNow."', actualFinish=now(), quantity=".$workingQuantity.", employeeId='".$employeeId."', status=1, processRemarks = '".mysqli_real_escape_string($db,$processRemarks)."' WHERE id = ".$currentWorkScheduleId;
                    $result = $db->query($sql);
                }
                
                // ----------------------------------------------------------------------------------------- Update Availability --------------------------------------------------------------------------------		
                if(!in_array($processCode,array(437,438,461,597,598,599,600,601,602,603)))
                {
                    updateAvailability($lotNumber);
                }
                
                // ---------------------------------------------------------------------------------------- Update system_machineworkschedule ---------------------------------------------------------
                $sql = "UPDATE system_machineWorkschedule SET status = 1 WHERE inputDate = '".date("Y-m-d")."' AND workscheduleId = ".$currentWorkScheduleId;
                $updateQuery = $db->query($sql);

                $sql = "SELECT inputDate FROM system_machineWorkschedule WHERE inputDate > '".date("Y-m-d")."' AND workscheduleId = ".$currentWorkScheduleId;
                $queryCheckSched = $db->query($sql);
                if($queryCheckSched AND $queryCheckSched->num_rows > 0)
                {
                    $resultCheckSched = $queryCheckSched->fetch_assoc();
                    $inputDate = $resultCheckSched['inputDate'];

                    $sql = "DELETE FROM system_machineWorkschedule WHERE inputDate = '".$inputDate."' AND workscheduleId = ".$currentWorkScheduleId;
                    $deleteQuery = $db->query($sql);
                }
                
                // ---------------------------------------------------------------------------------------- Retrieve Next Process -------------------------------------------------------------------
                $sql = "SELECT id, processCode FROM ppic_workschedule WHERE status = 0 AND processCode NOT IN (".$excemptedProcess.") AND lotNumber = '".$lotNumber."' ORDER BY processOrder ASC LIMIT 1";
                $workScheduleQuery = $db->query($sql);
                $workScheduleQueryResult = $workScheduleQuery->fetch_array();
                $nextWorkScheduleId = $workScheduleQueryResult['id'];
                $nextProcessCode = $workScheduleQueryResult['processCode'];
                
                // ---------------------------------------------------------------------- Set Previous Process Actual Finish In Current Process --------------------------------------------------------------
                $sql = "UPDATE ppic_workschedule SET previousActualFinish=now() WHERE id = ".$nextWorkScheduleId;
                $updateQuery = $db->query($sql);
                
                // --------------------------------------------- Remove Data In system_lotlist If Current Process Is Delivery Or Warehouse Storage ---------------------------------------------
                if($processCode == 144 OR  $processCode == 353)
                {			
                    $sql = "DELETE FROM system_lotlist WHERE lotNumber = '".$lotNumber."'";
                    $deleteQuery = $db->query($sql);
                    
                    if($processCode==144)
                    {
                        $sql = "UPDATE ppic_workschedule SET status = 1 WHERE lotNumber LIKE '".$lotNumber."' AND processCode = 496 AND status = 0";
                        $queryUpdate = $db->query($sql);
                    }
                }
                
                // -------------------------------------------- Delete Data In view_workschedule --------------------------------------------------------
                $sql = "DELETE FROM view_workschedule WHERE id = ".$currentWorkScheduleId;
                $deleteQuery = $db->query($sql);
                
				updateSTAnalysis($currentWorkScheduleId);
				
                // -------------------------------------------- Execute When Next Process Is Proceed To Assembly ------------------------------------
                if(in_array($nextProcessCode, $assemblyProcessArray))
                {	
                    finishProcess("",  $nextWorkScheduleId, $workingQuantity, $employeeId, $processRemarks);
					// insertEmployeePerformance($nextWorkScheduleId);
                }

				if($processCode == 187 AND $_GET['country'] == 2)
				{
					$sql = "SELECT id FROM ppic_workschedule WHERE lotNumber = '".$lotNumber."' AND processCode = 518 LIMIT 1";
					$queryCheckDueDate = $db->query($sql);
					if($queryCheckDueDate AND $queryCheckDueDate->num_rows > 0)
					{
						$resultCheckDueDate = $queryCheckDueDate->fetch_assoc();
						$workIdDue = $resultCheckDueDate['id'];

						$sql = "UPDATE ppic_workschedule SET actualStart = '".$dateTimeNow."', actualEnd='".$dateTimeNow."', actualFinish='".$dateNow."', quantity=".($workingQuantity).", employeeId='".$employeeId."', status = 1 WHERE id = ".$workIdDue." AND status = 0 LIMIT 1";
						$queryUpdate = $db->query($sql);
					}
				}

				insertEmployeePerformance($currentWorkScheduleId);
                
                /* Commented On October 4 Because Top 20 Is No Longer Required
                if(in_array($sectionId,array(1,2)))
                {			
                    fillGroupSchedule($sectionId);			
                }
                */	
            }	
		}
			
	}
	// ---------------------------- End Of Execute When Process Was Finished  (Ace) ---------------------------
	
	// -------------------------------------- Update Availability Of Lot Number After Finishing A Process (Ace) --------------------------------------
	function updateAvailability($lotNumber, $action = 0, $UpdateMeFlagA = 0)
	{
		include('PHP Modules/mysqliConnection.php');
		$UpdateMeFlag=0;
		if($UpdateMeFlagA==1)
		{
			$sql = "select poId from ppic_lotlist where lotNumber like '".$lotNumber."' and identifier IN (1,5)";
			$ppicQuery = $db->query($sql);
			if($ppicQuery->num_rows > 0)
			{	
				while($ppicQueryResult = $ppicQuery->fetch_assoc())
				{
					$poId=$ppicQueryResult['poId'];
					if($poId>0)
					{
						$sql2 = "select poId from view_workschedule where poId like '".$poId."' and processCode in (459,324)";//
						$ppicQuery2 = $db->query($sql2);
						if($ppicQuery2->num_rows > 0)
						{	
							$UpdateMeFlag=1;
						}
						else
						{
							$sql3 = "select id from ppic_workschedule where poId like '".$poId."' and processCode in (459,324)";//
							$ppicQuery3 = $db->query($sql3);
							if($ppicQuery3->num_rows == 0)
							{
								$UpdateMeFlag=1;
							}
							else
							{
								$sql4 = "select id from ppic_workschedule where poId like '".$poId."' and processCode in (459,324) and status=0";//
								$ppicQuery4 = $db->query($sql4);
								if($ppicQuery4->num_rows > 0)
								{
									$UpdateMeFlag=1;
								}
							}
						}
					}
				}				
			}
		}
		if($UpdateMeFlag==0)
		{
			// --------------------------------- Execute When Process Was Finished -----------------------------------------------
			if($action==0)
			{
				// --------------------------------- Retrieve Current Process And Section --------------------------------------
				$sql = "SELECT id, processSection, processOrder, processCode FROM ppic_workschedule WHERE processCode NOT IN(141,174,364,95,366,367,437,438,461,546,597,598,599,600,601,602,603) AND lotNumber LIKE '".$lotNumber."' AND status = 0 ORDER BY processOrder LIMIT 1";
				$workScheduleQuery = $db->query($sql);
				
				// -------------------------------------- Execute When There Is Still Unfinished Process -------------------------------------
				if($workScheduleQuery->num_rows > 0)
				{
					$workScheduleQueryResult = $workScheduleQuery->fetch_array();
					$workScheduleId = $workScheduleQueryResult['id'];
					$currentProcessOrder = $workScheduleQueryResult['processOrder'];
					$currentProcessCode = $workScheduleQueryResult['processCode'];
					$currentProcessSection = $workScheduleQueryResult['processSection'];
					// --------------------------------- End Of Retrieve Current Process And Section --------------------------------------
					
					// -------------------------------- Update Availability Of Current Process -------------------------------------------
					$sql = "UPDATE ppic_workschedule SET availability = 1 WHERE id = ".$workScheduleId;
					$updateQuery = $db->query($sql);
					
					$sql = "UPDATE view_workschedule SET availability = 1 WHERE id = ".$workScheduleId;
					$updateQuery = $db->query($sql);
					// -------------------------------- End Of Update Availability Of Current Process ------------------------------------
					
					$count = 0;
					// ------------------------------------ Update Availability Of Next Process -------------------
					$workscheduleIdArray = array();
					$sql = "SELECT id, processCode FROM ppic_workschedule WHERE processCode NOT IN(141,174,364,95,366,367,437,438,461,597,598,599,600,601,602,603) AND lotNumber LIKE '".$lotNumber."' AND status = 0 AND processOrder > ".$currentProcessOrder." ORDER BY processOrder";
					$nextProcessQuery = $db->query($sql);
					
					if($nextProcessQuery->num_rows > 0)
					{
						while($nextProcessQueryResult = $nextProcessQuery->fetch_array())
						{
							if($currentProcessCode==496 AND $count==0)
							{
								if($nextProcessQueryResult['processCode']==358)
								{
									$sql = "UPDATE ppic_workschedule SET availability = 1 WHERE id = ".$nextProcessQueryResult['id']."";
									$updateQuery = $db->query($sql);
									
									$sql = "UPDATE view_workschedule SET availability = 1 WHERE id = ".$nextProcessQueryResult['id']."";
									$updateQuery = $db->query($sql);

								}
								else
								{
									$sql = "UPDATE ppic_workschedule SET availability = 12 WHERE id = ".$nextProcessQueryResult['id']."";
									$updateQuery = $db->query($sql);
									
									$sql = "UPDATE view_workschedule SET availability = 12 WHERE id = ".$nextProcessQueryResult['id']."";
									$updateQuery = $db->query($sql);

								//~ $workscheduleIdArray[] = $nextProcessQueryResult['id'];//will remove if upcoming availability is activated
								}
							}
							else
							{
								$workscheduleIdArray[] = $nextProcessQueryResult['id'];
							}
							
							$count++;
						}
						
						$sql = "UPDATE ppic_workschedule SET availability = 0 WHERE availability != 0 AND id IN(".implode(", ",$workscheduleIdArray).")";
						$updateQuery = $db->query($sql);
						
						$sql = "UPDATE view_workschedule SET availability = 0 WHERE availability != 0 AND id IN(".implode(", ",$workscheduleIdArray).")";
						$updateQuery = $db->query($sql);
					}
					// --------------------------------- End Of Update Availability Level  And Availability Of Next Process -----------------
				}
				// -------------------------------------- Execute When All Process Was Finished  -------------------------------------
				else
				{
					// --------------------------------- Check If This Item Is A Subpart -----------------------------------------
						
						$sql = "SELECT parentLot FROM ppic_lotlist WHERE lotNumber = '".$lotNumber."'";
						$parentLotQuery = $db->query($sql);
						$parentLotQueryResult = $parentLotQuery->fetch_array();
						$parentLot = TRIM($parentLotQueryResult['parentLot']);
						
						// ------------------------------------ Execute If Item Is A Subpart -----------------------------------
						if($parentLot !="")
						{
							$unfinishedFlag = 0;
							$sql = "SELECT lotNumber FROM ppic_lotlist WHERE parentLot = '".$parentLot."'";
							$lotListQuery = $db->query($sql);
							while($lotListQueryResult = $lotListQuery->fetch_array())
							{
								// ------------------------------- Check If Lot Is Unfinished -------------------------------
								$sql = "SELECT id FROM view_workschedule WHERE processCode NOT IN(141,162,174,364,95,366,367,437,438,461,597,598,599,600,601,602,603) AND lotNumber LIKE '".$lotListQueryResult['lotNumber']."' AND status = 0 LIMIT 1";
								$workScheduleQuery = $db->query($sql);
								if($workScheduleQuery->num_rows > 0)
								{
									$unfinishedFlag = 1;
									break;
								}							
								// ------------------------------- End Of Check If Lot Is Unfinished ------------------------
							}
							
							// -------------------------- Execute When All Subparts Are Finished -----------------------------
							if($unfinishedFlag == 0)
							{
								// --------------------------------- Retrieve Current Process And Section --------------------------------------
								$sql = "SELECT id FROM ppic_workschedule WHERE processCode NOT IN(141,174,364,95,366,367,437,438,461,597,598,599,600,601,602,603) AND lotNumber LIKE '".$parentLot."' AND status = 0 ORDER BY processOrder LIMIT 1";
								$workScheduleQuery = $db->query($sql);
								$workScheduleQueryResult = $workScheduleQuery->fetch_array();
								$workScheduleId = $workScheduleQueryResult['id'];
								
								// ---------------------- Update Availability Of Main Part --------------------------------							
								$sql = "UPDATE ppic_workschedule SET availability = 1 WHERE id = ".$workScheduleId;
								$updateQuery = $db->query($sql);
								
								$sql = "UPDATE view_workschedule SET availability = 1 WHERE id = ".$workScheduleId;
								$updateQuery = $db->query($sql);									
								// -------------------- End Of Update Availability Of Main Part ----------------------------
							}
							// -------------------------- End Of Execute When All Subparts Are Finished ----------------------
							
						}					
						// ------------------------------------ End Of Execute If Item Is A Subpart ------------------------------
						
					// -----------------------------------------------------------------------------------------------------------
				}
			}
			// --------------------------------- End Of Execute When Process Was Finished -----------------------------------------------
			// --------------------------------- Execute When Process Was Started -------------------------------------------------------		
			else if($action==1)
			{
				// ------------------------------------ Select Current Process ----------------------------------------------------
				$sql = "SELECT id FROM ppic_workschedule WHERE processCode NOT IN(141,174,364,95,366,367,437,438,461,597,598,599,600,601,602,603) AND lotNumber LIKE '".$lotNumber."' AND status = 0 ORDER BY processOrder LIMIT 1";
				$workScheduleQuery = $db->query($sql);
				$workScheduleQueryResult = $workScheduleQuery->fetch_array();
				$workScheduleId = $workScheduleQueryResult['id'];
				
				// -------------------------------- Update Availability Of Current Process -------------------------------------------
				$sql = "UPDATE ppic_workschedule SET availability = 11 WHERE id = ".$workScheduleId;
				$updateQuery = $db->query($sql);
				
				$sql = "UPDATE view_workschedule SET availability = 11 WHERE id = ".$workScheduleId;
				$updateQuery = $db->query($sql);
				// -------------------------------- End Of Update Availability Of Current Process ------------------------------------	
			}
			// --------------------------------- End Of Execute When Process Was Started ------------------------------------------------
			else if($action==2)
			{
				$sql = "SELECT id FROM ppic_workschedule WHERE processCode NOT IN(141,174,364,95,366,367,437,438,461,597,598,599,600,601,602,603) AND lotNumber LIKE '".$lotNumber."' AND status = 0 ORDER BY processOrder LIMIT 1";
				$workScheduleQuery = $db->query($sql);
				$workScheduleQueryResult = $workScheduleQuery->fetch_array();
				$workScheduleId = $workScheduleQueryResult['id'];
				
				// -------------------------------- Update Availability Of Current Process -------------------------------------------
				$sql = "UPDATE ppic_workschedule SET availability = 13 WHERE id = ".$workScheduleId;
				$updateQuery = $db->query($sql);
				
				$sql = "UPDATE view_workschedule SET availability = 13 WHERE id = ".$workScheduleId;
				$updateQuery = $db->query($sql);
			}
		}
	}
	// -------------------------------------- End Of Update Availability Of Lot Number After Finishing A Process --------------------------------------
	
	// ------------------------------- Compute Items On Top 20 ------------------------------------------------------
	function fillGroupSchedule($sectionId)
	{
		include('PHP Modules/mysqliConnection.php');
		
		// ----------------------------------- Remove Unavailable Items Inside Top 20 Items --------------------------
		$sql = "UPDATE view_workschedule SET dataSix = '0' WHERE processSection = ".$sectionId." AND dataSix = '1' AND availability IN (0)";
		$updateQuery = $db->query($sql);
		
		// ----------------------------------- Check If Section Has Less Than 15 Items -------------------------------
		$sql = "SELECT id FROM view_workschedule WHERE processSection = ".$sectionId." AND dataSix = '1'";
		$workScheduleQuery = $db->query($sql);
		$scheduleCount = $workScheduleQuery->num_rows;
		
		// ----------------------------------- Add Items In Top 20 If Less Than 15 Items -----------------------------
		if($scheduleCount <= 15)
		{
			$limitCount = 20 - $scheduleCount;
			$sql = "SELECT id FROM view_workschedule WHERE status = 0 AND viewFlag = 0 AND availability IN (1) AND processCode != 174 AND ((targetFinish BETWEEN '0000-00-00' AND '2018-10-04') OR urgentFlag = 10) AND processSection = ".$sectionId." AND dataSix != '1' GROUP BY lotNumber ORDER BY urgentFlag DESC, targetFinish, deliveryDate, partNumber LIMIT ".$limitCount;
			$viewWorkScheduleQuery = $db->query($sql);			
			while($viewWorkScheduleQueryResult = $viewWorkScheduleQuery->fetch_array())
			{
				$sql = "UPDATE view_workschedule SET dataSix = '1' WHERE id = ".$viewWorkScheduleQueryResult['id'];				
				$updateQuery = $db->query($sql);
			}
		}		
	}
	// ------------------------------- End Of Compute Items On Top 20 ------------------------------------------------------
	
	// ------------------------------------------------- Update Current Process Of A Lot Number ----------------------------------------------------------------
	function updateCurrentStatus($lotNumber)
	{
		include('PHP Modules/mysqliConnection.php');
		
		// ---------------------------------------------- Retrieve Current Process Ignoring Some Process --------------------------------------------------
		$sql="SELECT processCode FROM ppic_workschedule WHERE processCode NOT IN(141,174,364,95,366,367,437,438,461,597,598,599,600,601,602,603) AND lotNumber = '".$lotNumber."' AND status = 0 ORDER BY processOrder ASC LIMIT 1";	
		$workScheduleQuery=$db->query($sql);
		if($workScheduleQuery->num_rows>0)
		{
			$workScheduleQueryResult = $workScheduleQuery->fetch_assoc();
			
			// ------------------------------------------ Retrieve Process Name ---------------------------------------------------------
			$sql = "SELECT processName FROM cadcam_process WHERE processCode = ".$workScheduleQueryResult['processCode'];
			$processNameQuery=$db->query($sql);
			if($processNameQuery->num_rows>0)
			{
				$processNameQueryResult = $processNameQuery->fetch_assoc();
				$processName = $processNameQueryResult['processName'];
			}
			
			// ------------------------------------------ Update Current Process Status ----------------------------------------------------
			$sql = "UPDATE view_workschedule SET currentStatus = '".$processName."' WHERE lotNumber = '".$lotNumber."'";
			//echo $sql."<br>";
			$updateQuery=$db->query($sql);
			// ------------------------------------------ Update Current Process Status ----------------------------------------------------
		}
	}
	// ------------------------------------------------- End Of Update Current Process Of A Lot Number
	
//------------------------------------------------------------------ Partial Booking Function ---------------------------------------------------------------------//
	function partialLotNumber($lot,$quantity,$startFromProcessOrder,$employeeId,$returnFlag=0,$ngFlag=0)
	{
		include('PHP Modules/mysqliConnection.php');
		
		$dashPosition = strrpos($lot, "-");	
		if($dashPosition>=9)
		{
			$originalLotNumber=substr($lot,0,$dashPosition);	
		}
		else
		{
			$originalLotNumber = $lot;			
		}
		// -------------------------------------------- Detect Latest Lot Number and Create New Lot Number -----------------------------------------------------------
		$sql="SELECT MAX( CAST(SUBSTRING(lotNumber,LOCATE('-',lotNumber,10)+1) AS SIGNED) ) as max	FROM ppic_lotlist where lotNumber like '".$originalLotNumber."-%'";	
		$lotQuery=$db->query($sql);
		if($lotQuery->num_rows>0)
		{
			$lotQueryResult = $lotQuery->fetch_assoc();
			$newLotNumber = $originalLotNumber."-".($lotQueryResult['max']+1);
		}
		else
		{
			$newLotNumber = $originalLotNumber."-1";
		}
		// ----------------------------------------------------------------------------------------------------------------------------------------------------------
		
		// ----------------------------------------- Insert Lot Data Into ppic_lotlist ----------------------------------------------------
		$sql="SELECT poId, partId, parentLot, partLevel, workingQuantity, identifier, status, poContentId, partialBatchId FROM ppic_lotlist where lotNumber like '".$lot."' AND workingQuantity > ".$quantity." LIMIT 1";
		$lotQuery=$db->query($sql);
		if($lotQuery->num_rows > 0)
		{
			$lotQueryResult = $lotQuery->fetch_assoc();
			
			$newQuantity = $lotQueryResult['workingQuantity']-$quantity;
			# RG 02-27-20
            if($ngFlag == 1) $newQuantity = $quantity;
			$sql = "insert into ppic_lotlist (lotNumber, poId , partId, parentLot, partLevel, workingQuantity, identifier, dateGenerated, status, bookingStatus, poContentId, partialBatchId) values ('".$newLotNumber."', ".$lotQueryResult['poId'].", ".$lotQueryResult['partId'].", '".$lotQueryResult['parentLot']."', ".$lotQueryResult['partLevel'].", ".$quantity.", ".$lotQueryResult['identifier'].", now(), ".$lotQueryResult['status'].", 1, '".$lotQueryResult['poContentId']."', '".$lotQueryResult['partialBatchId']."')";
			
			$query = $db->query($sql);
			// ---------------------------------------------------------------------------------------------------------------------------------
			
			// --------------------------------------- Update Working Quantity of Source Lot ---------------------------------------------------
			$sql = "UPDATE ppic_lotlist SET workingQuantity = ".$newQuantity." WHERE lotNumber like '".$lot."'";	
			
			$query = $db->query($sql);
			// ------------------------------------------------------------------------------------------------------ --------------------------

			// -------------------------------------------- Retrieve Work Schedule Data --------------------------------------------------------
			if($_GET['country']==1)
			{
				$excemptedProcess = "141,174";
			}
			else
			{
				$excemptedProcess   = "141";
			}
			$sql = "select poId, customerId, poNumber, partNumber, revisionId, receiveDate, deliveryDate, recoveryDate, urgentFlag, subconFlag, partLevelFlag from ppic_workschedule where lotNumber like '".$lot."' and processCode NOT IN (".$excemptedProcess.") LIMIT 1";
			$workScheduleDetailQuery=$db->query($sql);
			$workScheduleDetailQueryResult = $workScheduleDetailQuery->fetch_assoc();	
			// -------------------------------------------- End of Retrieve Work Schedule Data --------------------------------------------------------
			
			// ---------------------------------------- Insert Work Schedule Into ppic_workschedule -------------------------------------------
			$processOrder=1;
			if($_GET['country']==1)
			{
				$excemptedProcess = "141,174,95,364,366,367,368";
				//~ $excemptedProcess = "141,174,364,368";
			}
			else
			{
				$excemptedProcess   = "141,95,364,366,368";
			}
			
			$sql = "SELECT id FROM ppic_workschedule WHERE lotNumber LIKE '".$lot."' AND processCode IN(95,366,367,461,597,598,599,600,601,602,603) AND status = 0 ORDER BY processOrder";
			$queryWorkschedule = $db->query($sql);
			if($queryWorkschedule AND $queryWorkschedule->num_rows > 0)
			{
				while($resultWorkschedule = $queryWorkschedule->fetch_assoc())
				{
					$id = $resultWorkschedule['id'];
					
					$sql = "
						INSERT INTO ppic_workschedule
								(	poId, customerId, poNumber, partNumber, revisionId, processCode , processSection, processRemarks, targetFinish, receiveDate, deliveryDate, recoveryDate, urgentFlag, subconFlag, partLevelFlag, lotNumber,				processOrder)
						SELECT		poId, customerId, poNumber, partNumber, revisionId, processCode , processSection, processRemarks, targetFinish, receiveDate, deliveryDate, recoveryDate, urgentFlag, subconFlag, partLevelFlag, '".$newLotNumber."',	".($processOrder++)." 
						FROM	ppic_workschedule
						WHERE	id = ".$id." LIMIT 1 
					";
					$queryInsert = $db->query($sql);
				}
			}
			
			$sql="SELECT processCode, targetFinish, processSection, processRemarks FROM ppic_workschedule where lotNumber like '".$lot."' and processOrder>=".$startFromProcessOrder." AND processCode NOT IN(".$excemptedProcess.") ORDER BY processOrder";	
			$workScheduleQuery=$db->query($sql);
				while($workScheduleQueryResult = $workScheduleQuery->fetch_assoc())
				{
				$sql = "insert into ppic_workschedule (poId, customerId, poNumber, lotNumber, partNumber, revisionId, processCode , processOrder, processSection, processRemarks, targetFinish, receiveDate, deliveryDate, recoveryDate, urgentFlag, subconFlag, partLevelFlag) values (".$workScheduleDetailQueryResult['poId']." ,".$workScheduleDetailQueryResult['customerId'].", '".$workScheduleDetailQueryResult['poNumber']."' , '".$newLotNumber."', '".$workScheduleDetailQueryResult['partNumber']."' , '".$workScheduleDetailQueryResult['revisionId']."', '".$workScheduleQueryResult['processCode']."' , ".($processOrder++).", ".$workScheduleQueryResult['processSection'].", '".$workScheduleQueryResult['processRemarks']."', '".$workScheduleQueryResult['targetFinish']."', '".$workScheduleDetailQueryResult['receiveDate']."', '".$workScheduleDetailQueryResult['deliveryDate']."', '".$workScheduleDetailQueryResult['recoveryDate']."', ".$workScheduleDetailQueryResult['urgentFlag'].", ".$workScheduleDetailQueryResult['subconFlag'].", ".$workScheduleDetailQueryResult['partLevelFlag'].")";
				$query = $db->query($sql);
				}
			// --------------------------------------------------------------------------------------------------------------------------------
			// ------------------------------------------------ Insert Into PRS Log --------------------------------------------------------------------
			$sql="INSERT INTO ppic_prslog (lotNumber,employeeId,date,remarks,type,sourceLotNumber,partialQuantity) values ('".$newLotNumber."', '".$employeeId."', now(), 'Automated Partial', 3,'".$lot."', '".$newQuantity."')";	
			$query = $db->query($sql);
			// -----------------------------------------------------------------------------------------------------------------------------------------
			
			$sql = "SELECT id, processSection FROM `ppic_workschedule` WHERE lotNumber LIKE '".$newLotNumber."' AND processCode IN(312,430,431,432) ORDER BY processOrder LIMIT 1";
			$query = $db->query($sql);
			if($query AND $query->num_rows > 0)
			{
				$result = $query->fetch_assoc();
				$id = $result['id'];
				$processSection = $result['processSection'];
				
				$insert = "INSERT INTO `system_machineWorkschedule`(`workScheduleId`, `machineId`, `idNumber`, `sectionId`, `inputDate`, `inputTime`, `status`, `printStatus`) VALUES (".$id.",0,'".$employeeId."',".$processSection.",NOW(),NOW(),0,0)";
				$insertQuery = $db->query($insert);
			}
		}
		
		if($returnFlag > 0)
		{
			return $newLotNumber;
		}
		else
		{
			return $newLotNumber = '';
		}
	}
//--------------------------------------------------------- END Partial Booking Function ---------------------------------------------------------//
	
	function getWorkingQuantity($lotNumber)
	{
		include('PHP Modules/mysqliConnection.php');
		
		$workingQuantity = 0;
		
		// ------------------------------ Check If Item Is In A Group ---------------------------
		$sql = "SELECT groupTag FROM ppic_lotlist WHERE lotNumber = '".$lotNumber."'";
		$groupTagQuery = $db->query($sql);
		$groupTagQueryResult = $groupTagQuery->fetch_array();
		$groupTag = $groupTagQueryResult['groupTag'];

		if($groupTag!="")
		{
			$filterClause = "WHERE groupTag = '".$groupTag."' AND groupTag!=''";
		}
		else
		{
			$filterClause = "WHERE lotNumber = '".$lotNumber."' OR productionTag LIKE '".$lotNumber."'";
		}
		// ----------------------------- End Of Check If Item Is In A Group -------------------------
		
		$sql = "SELECT workingQuantity FROM ppic_lotlist ".$filterClause;
		$lotListQuery = $db->query($sql);
		if($lotListQuery->num_rows > 0)
		{
			while($lotListQueryResult = $lotListQuery->fetch_array())
			{
				$workingQuantity = $workingQuantity + $lotListQueryResult['workingQuantity'];				
			}			
		}
		
		return $workingQuantity;
	}
	
	function getLotNumber()
	{
		include('PHP Modules/mysqliConnection.php');
		
		$sql = "SELECT DATE_FORMAT(NOW(),'%y-%m') as ym";
		$ym = $db->query($sql);
		$ym = $ym->fetch_array();
		$ym = $ym['ym'];
		$lot = "wala";
		
		$sql = "SELECT  MAX( CAST(SUBSTRING(lotNumber,LOCATE('-',lotNumber,6)+1) AS SIGNED) ) as max FROM  ppic_lotlist where lotNumber like '$ym-%'";
		$query = $db->query($sql);
		$rnum = $query->num_rows;
		if($rnum > 0)
		{
			$rows = $query->fetch_array();
			if(!is_null($rows['max']))
			{
				$new = $rows['max']+1;
				if(strlen($new)==1)
				{
					$new = "00".$new;
				}
				if(strlen($new)==2)
				{
					$new="0".$new;
				}
				$lot=$ym."-".$new;
			}
			else
			{
				$lot=$ym."-001";
			}
		}
		else
		{
			$lot=$ym."-001";
		}
		return $lot;
	}	
	
	function getMaterialReturnNumber()
	{
		include('PHP Modules/mysqliConnection.php');
		
		$sql = "SELECT DATE_FORMAT(NOW(),'%y') as y";
		$y = $db->query($sql);
		$y = $y->fetch_array();
		$y = $y['y'];
		$lot = "wala";
		
		$sql = "SELECT  MAX( CAST(SUBSTRING(materialReturnNumber,LOCATE('-',materialReturnNumber,4)+1) AS SIGNED) ) AS max FROM  cadcam_materialreturn WHERE materialReturnNumber LIKE 'MR".$y."-%'";
		$query = $db->query($sql);
		$rnum = $query->num_rows;
		if($rnum > 0)
		{
			$rows = $query->fetch_array();
			if(!is_null($rows['max']))
			{
				$new = $rows['max']+1;
				if(strlen($new)==1)
				{
					$new = "000".$new;
				}
				if(strlen($new)==2)
				{
					$new="00".$new;
				}
				if(strlen($new)==3)
				{
					$new="0".$new;
				}
				$lot=$y."-".$new;
			}
			else
			{
				$lot=$y."-0001";
			}
		}
		else
		{
			$lot=$y."-0001";
		}
		return "MR".$lot;
	}
	
	function getMaterial($lotNumber,$type)//$type [ 0 = batchNo; 1 = Inventory Id; 2 = Both]
	{
		include('PHP Modules/mysqliConnection.php');
		
		if($lotNumber!='')
		{
			$sourceLotNumber = $lotNumber;
			$flag = 0;
			$sourceInventoryId = '';
			while($flag == 0)
			{
				$flag = 1;
				$sourceBookingId = '';
				$sql = "SELECT bookingId FROM engineering_bookingdetails WHERE lotNumber LIKE '".$sourceLotNumber."' LIMIT 1";
				$queryBookingDetails = $db->query($sql);
				if($queryBookingDetails->num_rows > 0)
				{
					$resultBookingDetails = $queryBookingDetails->fetch_array();
					$sourceBookingId = $resultBookingDetails['bookingId'];
					
					$sql = "SELECT inventoryId FROM engineering_booking WHERE bookingId LIKE '".$sourceBookingId."' LIMIT 1";
					$queryBooking = $db->query($sql);
					if($queryBooking->num_rows > 0)
					{
						$resultBooking = $queryBooking->fetch_array();
						$sourceInventoryId = $resultBooking['inventoryId'];
					}
				}
				else
				{
					$sql = "SELECT materialsInProductionMPRS FROM warehouse_materialproductiondetails WHERE materialsInProductionLotNo LIKE '".$sourceLotNumber."' LIMIT 1";
					$queryMaterialProductionDetails = $db->query($sql);
					if($queryMaterialProductionDetails->num_rows > 0)
					{
						$resultMaterialProductionDetails = $queryMaterialProductionDetails->fetch_array();
						$materialsInProductionMPRS = $resultMaterialProductionDetails['materialsInProductionMPRS'];
						
						$sql = "SELECT withdrawMaterialId FROM warehouse_materialwithdrawal WHERE TRIM(withdrawMaterialMPRS) LIKE '".trim($materialsInProductionMPRS)."' LIMIT 1";
						$queryMaterialwithdrawal = $db->query($sql);
						if($queryMaterialwithdrawal->num_rows > 0)
						{
							$resultMaterialwithdrawal = $queryMaterialwithdrawal->fetch_array();
							$sourceInventoryId = $resultMaterialwithdrawal['withdrawMaterialId'];
						}
						else
						{
							$sql = "SELECT oldWithdrawMaterialId FROM warehouse_materialwithdrawalhistory WHERE TRIM(oldWithdrawMaterialMPRS) LIKE '".trim($materialsInProductionMPRS)."' LIMIT 1";
							$queryMaterialwithdrawal = $db->query($sql);
							if($queryMaterialwithdrawal->num_rows > 0)
							{
								$resultMaterialwithdrawal = $queryMaterialwithdrawal->fetch_array();
								$sourceInventoryId = $resultMaterialwithdrawal['oldWithdrawMaterialId'];
							}
						}
					}
					else
					{
						$sourceLotNumberPrev = $sourceLotNumber;
						$sql = "SELECT processRemarks FROM ppic_workschedule WHERE lotNumber LIKE '".$sourceLotNumber."' AND processCode = 254 LIMIT 1";
						$queryWorkSchedule = $db->query($sql);
						if($queryWorkSchedule->num_rows > 0)
						{
							$resultWorkSchedule = $queryWorkSchedule->fetch_array();
							$sourceLotNumber = $resultWorkSchedule['processRemarks'];
							$flag = 0;
							
							if($sourceLotNumber=='')
							{
								$sql = "SELECT finishGoodproductionId FROM warehouse_finishgoodwithdrawaldetail WHERE finishGoodproductionLotNo LIKE '".$sourceLotNumberPrev."' AND finishGoodproductionId != '' LIMIT 1";
								$queryFinishGoodWithdrawalDetails = $db->query($sql);
								if($queryFinishGoodWithdrawalDetails AND $queryFinishGoodWithdrawalDetails->num_rows > 0)
								{
									$resultFinishGoodWithdrawalDetails = $queryFinishGoodWithdrawalDetails->fetch_assoc();
									$finishGoodproductionId = $resultFinishGoodWithdrawalDetails['finishGoodproductionId'];
									
									$sql = "SELECT sourceId FROM warehouse_inventory WHERE inventoryId LIKE '".$finishGoodproductionId."' LIMIT 1";
									$queryInventoryId = $db->query($sql);
									if($queryInventoryId->num_rows > 0)
									{
										$resultInventoryId = $queryInventoryId->fetch_array();
										$sourceLotNumber = $resultInventoryId['sourceId'];
									}
									else
									{
										$sql = "SELECT sourceId FROM warehouse_inventoryhistory WHERE inventoryId LIKE '".$finishGoodproductionId."' LIMIT 1";
										$queryInventoryId = $db->query($sql);
										if($queryInventoryId->num_rows > 0)
										{
											$resultInventoryId = $queryInventoryId->fetch_array();
											$sourceLotNumber = $resultInventoryId['sourceId'];
										}
									}
									
									$sql = "UPDATE ppic_workschedule SET processRemarks = '".$sourceLotNumber."' WHERE lotNumber LIKE '".$sourceLotNumberPrev."' AND processCode = 254 LIMIT 1";
									$queryUpdate = $db->query($sql);
								}
							}
						}
						else
						{
							$sql = "SELECT id FROM ppic_workschedule WHERE lotNumber LIKE '".$sourceLotNumber."' AND processCode IN(312,430,431,432) AND status = 0 LIMIT 1";
							$queryWorkSchedule = $db->query($sql);
							if($queryWorkSchedule->num_rows == 0)
							{
								$sql = "SELECT sourceLotNumber FROM ppic_prslog WHERE lotNumber LIKE '".$sourceLotNumber."' LIMIT 1";
								$queryPrsLog = $db->query($sql);
								if($queryPrsLog->num_rows > 0)
								{
									$resultPrsLog = $queryPrsLog->fetch_array();
									$sourceLotNumber = $resultPrsLog['sourceLotNumber'];
									$flag = 0;
								}
							}
						}
						
						if($flag==1)
						{
							$sql = "SELECT lotNumber FROM ppic_lotlist WHERE parentLot LIKE '".$sourceLotNumber."' AND identifier = 1 LIMIT 1";
							$queryLotList = $db->query($sql);
							if($queryLotList->num_rows > 0)
							{
								$resultLotList = $queryLotList->fetch_array();
								$sourceLotNumber = $resultLotList['lotNumber'];
								$flag = 0;
							}
						}
					}
				}
				
				if(trim($sourceLotNumber)=='')	$flag = 1;
			}
		}
		
		$batchNumber = 'N/A';
		$sql = "SELECT batchNumber, pvcStatus FROM warehouse_inventory WHERE inventoryId LIKE '".$sourceInventoryId."' LIMIT 1";
		$queryInventoryId = $db->query($sql);
		if($queryInventoryId->num_rows > 0)
		{
			$resultInventoryId = $queryInventoryId->fetch_array();
			$batchNumber = $resultInventoryId['batchNumber'];
		}
		else
		{
			$sql = "SELECT batchNumber, pvcStatus FROM warehouse_inventoryhistory WHERE inventoryId LIKE '".$sourceInventoryId."' LIMIT 1";
			$queryInventoryId = $db->query($sql);
			if($queryInventoryId->num_rows > 0)
			{
				$resultInventoryId = $queryInventoryId->fetch_array();
				$batchNumber = $resultInventoryId['batchNumber'];
			}
		}
		
		if($type==0)
		{
			return $batchNumber;
		}
		else if($type==1)
		{
			return $sourceInventoryId;
		}
		else if($type==2)
		{
			$array = array();
			$array['batchNumber'] = $batchNumber;
			$array['inventoryId'] = $sourceInventoryId;
			return $array;
		}
	}
	
	function checkRTV($lotNumber)
	{
		include('PHP Modules/mysqliConnection.php');
		
		$flag = 0;
		while($flag == 0)
		{
			$flag = 1;
			$cparId = '';
			$lotNumber = trim($lotNumber);
			if($lotNumber!='')
			{
				$sql = "SELECT cparId, lotNumber FROM qc_cparlotnumber WHERE prsNumber LIKE '".$lotNumber."' AND cparId != '' AND lotNumber != '' AND lotNumber != '".$lotNumber."' AND status != 2 LIMIT 1";
				$queryCpartLotNumber = $db->query($sql);
				if($queryCpartLotNumber AND $queryCpartLotNumber->num_rows > 0)
				{
					$resultCpartLotNumber = $queryCpartLotNumber->fetch_assoc();
					$cparId = $resultCpartLotNumber['cparId'];
					$lotNumber = $resultCpartLotNumber['lotNumber'];
					$flag = (strpos($cparId,'CPAR-CUS')!==FALSE) ? 1 : 0 ;
				}
				else
				{
					$sql = "SELECT sourceLotNumber FROM ppic_prslog WHERE lotNumber LIKE '".$lotNumber."' AND sourceLotNumber != '".$lotNumber."' AND type != 7 LIMIT 1";
					$queryPrsLog = $db->query($sql);
					if($queryPrsLog AND $queryPrsLog->num_rows > 0)
					{
						$resultPrsLog = $queryPrsLog->fetch_assoc();
						$lotNumber = $resultPrsLog['sourceLotNumber'];
						$flag = 0;
					}
				}
			}
		}
		return $cparId;
	}
	
	function monthlyPeriodDate($dateFrom,$paymentDay)
	{
		if($paymentDay==15)
		{
			$lastDate = date("Y-m-15",strtotime($dateFrom));
			$lastDate = date('Y-m-d',strtotime($lastDate. '+1 days'));
			$lastDate = addDays(-1,$lastDate);
			if(date("w",strtotime($lastDate)) == 6)	$lastDate = addDays(-1,$lastDate);	
		}
		else
		{
			$lastDate = date("Y-m-t",strtotime($dateFrom));
			$lastDate = date('Y-m-d',strtotime($lastDate. '+1 days'));
			$lastDate = addDays(-1,$lastDate);
			if(date("w",strtotime($lastDate)) == 6)	$lastDate = addDays(-1,$lastDate);	
		}
		
		return $lastDate;
	}
	
	function addDays($days,$date = '',$workingDaysType = '')
	{
		include('PHP Modules/mysqliConnection.php');
		if($date=='')	$date = date('Y-m-d');
		if($workingDaysType=='')	$workingDaysType = 'API';
		
		$sign = ($days > 0)	? '+' : '-';
		
		$j = 0;
		while($j < abs($days))
		{
			$flag = 0;
			while($flag==0)
			{
				$flag = 1;
				$date = date('Y-m-d',strtotime($date. $sign.'1 days'));
				$day =  date('l', strtotime($date));
				
				if($workingDaysType=='API')
				{
					// -------------------------- Check If Incremented / Decremented Date Is Holiday Or Sunday ----------------------
					if($_GET['country']=='1')//Philippines
					{
						$sql = "SELECT holidayId FROM hr_holiday WHERE holidayDate = '".$date."' AND holidayType < 6 LIMIT 1";
					}
					else if($_GET['country']=='2')//Japan
					{
						$sql = "SELECT holidayId FROM hr_holiday WHERE holidayDate = '".$date."' AND holidayType >= 6 LIMIT 1";
					}
					$dc = $db->query($sql);
					$dcnum = $dc->num_rows;
					// -------------------------- Increment / Decrement Date If Holiday Or Sunday ----------------------
					if($day=='Sunday' OR $dcnum > 0)
					{
						$flag = 0;
					}
				}
				else if($workingDaysType=='supplier')
				{
					// -------------------------- Check If Incremented / Decremented Date Is Holiday Or Saturday Or Sunday ----------------------
					if($_GET['country']=='1')//Philippines
					{
						$sql = "SELECT holidayId FROM hr_holiday WHERE holidayDate = '".$date."' AND holidayType IN(1,2) LIMIT 1";
					}
					else if($_GET['country']=='2')//Japan
					{
						$sql = "SELECT holidayId FROM hr_holiday WHERE holidayDate = '".$date."' AND holidayType IN(6,7) LIMIT 1";
					}
					$dc = $db->query($sql);
					$dcnum = $dc->num_rows;
					// -------------------------- Increment / Decrement Date If Holiday Or Saturday Or Sunday ----------------------
					if($day=='Sunday' OR $day=='Saturday' OR $dcnum > 0)
					{
						$flag = 0;
					}
				}
			}
			$j++;
		}
		return $date;
	}
	
	function checkMinimumStock($inventoryId,$displayFlag = 0)
	{
		/*	Used in the following files/directories:
		 *	1.	/Arktech Inventory System/Integrated Inventory Software/aedrian_withdrawalForm.php
		 */
		include('PHP Modules/mysqliConnection.php');
		
		/* commented by Gerald co Sir Ace 2020-07-10
		
		$remarksArray = array();
		
		$inventoryTable = '';
		$type = '';
		$supplyId = 0;
		$sql = "SELECT supplyId, type, dataOne, dataTwo, sourceId FROM warehouse_inventory WHERE inventoryId LIKE '".$inventoryId."' LIMIT 1";
		$queryInventory = $db->query($sql);
		if($queryInventory AND $queryInventory->num_rows > 0)
		{
			$remarksArray[] = "<td>Inventory Id</td><td> : ".$inventoryId." confirmed</td>";
			$resultInventory = $queryInventory->fetch_assoc();
			$supplyId = $resultInventory['supplyId'];
			$type = $resultInventory['type'];
			$dataOne = $resultInventory['dataOne'];
			$dataTwo = $resultInventory['dataTwo'];
			$sourceId = $resultInventory['sourceId'];
			
			$remarksArray[] = "<td>Name</td><td> : ".$dataOne."</td>";
			$remarksArray[] = "<td>Description</td><td> : ".$dataTwo."</td>";
			$inventoryTable = "warehouse_inventory";
		}
		else
		{
			$sql = "SELECT supplyId, type, dataOne, dataTwo, sourceId FROM warehouse_inventoryhistory WHERE inventoryId LIKE '".$inventoryId."' LIMIT 1";
			$queryInventory = $db->query($sql);
			if($queryInventory AND $queryInventory->num_rows > 0)
			{
				$remarksArray[] = "<td>Inventory Id</td><td> : ".$inventoryId." already closed</td>";
				$resultInventory = $queryInventory->fetch_assoc();
				$supplyId = $resultInventory['supplyId'];
				$type = $resultInventory['type'];
				$dataOne = $resultInventory['dataOne'];
				$dataTwo = $resultInventory['dataTwo'];
				$sourceId = $resultInventory['sourceId'];
				
				$remarksArray[] = "<td>Name</td><td> : ".$dataOne."</td>";
				$remarksArray[] = "<td>Description</td><td> : ".$dataTwo."</td>";
				$inventoryTable = "warehouse_inventoryhistory";
			}
			else
			{
				$remarksArray[] = "<td colspan='2'>Unknown Inventory Id ".$inventoryId." </td>";
			}
		}
		
		if($type==5)	$type = 3;
		
		if($supplyId==0)
		{
			$poContentId = '';
			$sql = "SELECT poId FROM ppic_lotlist WHERE lotNumber LIKE '".$sourceId."' AND identifier = 4 LIMIT 1";
			$queryLotList = $db->query($sql);
			if($queryLotList->num_rows > 0)
			{
				$resultLotList = $queryLotList->fetch_array();
				$poContentId = $resultLotList['poId'];
			}
			
			$listId = '';
			$sql = "SELECT productId FROM purchasing_pocontents WHERE poContentId = ".$poContentId." LIMIT 1";
			$queryPoContent = $db->query($sql);
			if($queryPoContent->num_rows > 0)
			{
				$resultPoContent = $queryPoContent->fetch_array();
				$listId = $resultPoContent['productId'];
			}
			
			$sql = "SELECT supplyId FROM purchasing_supplies WHERE listId = ".$listId." LIMIT 1";
			$querySupplies = $db->query($sql);
			if($querySupplies->num_rows > 0)
			{
				$resultSupplies = $querySupplies->fetch_array();
				$supplyId = $resultSupplies['supplyId'];
			}
			
			if($inventoryTable!='')
			{
				$sql = "UPDATE ".$inventoryTable." SET supplyId = ".$supplyId." WHERE inventoryId LIKE '".$inventoryId."' LIMIT 1";
				$queryUpdate = $db->query($sql);
			}
		}
		
		$inventoryQuantity = 0;
		$inventoryIdArray = array();
		$sql = "SELECT inventoryId, inventoryQuantity FROM warehouse_inventory WHERE supplyId = ".$supplyId." AND type = ".$type."";
		$queryInventory = $db->query($sql);
		if($queryInventory AND $queryInventory->num_rows > 0)
		{
			while($resultInventory = $queryInventory->fetch_assoc())
			{
				$inventoryIdArray[] = "'".$resultInventory['inventoryId']."'";
				$inventoryQuantity += $resultInventory['inventoryQuantity'];
			}
		}
		
		$remarksArray[] = "<td>Total Initial Stock</td><td> : ".$inventoryQuantity."</td>";
		
		$totalStock = $inventoryQuantity;		
		$totalWithdrawQty = 0;
		
		$partNumber = $revisionId = '';
		if($type==3)
		{
			$sql = "SELECT IFNULL(SUM(suppliesWithdrawalQuantity),0) AS totalWithdrawQty FROM warehouse_supplieswithdrawal WHERE suppliesWithdrawalId IN(".implode(",",$inventoryIdArray).")";
			$querySupplieswithdrawal = $db->query($sql);
			if($querySupplieswithdrawal AND $querySupplieswithdrawal->num_rows > 0)
			{
				$resultSupplieswithdrawal = $querySupplieswithdrawal->fetch_assoc();
				$totalWithdrawQty = $resultSupplieswithdrawal['totalWithdrawQty'];
			}
			
			$remarksArray[] = "<td>Total Withdrawn</td><td> : ".$totalWithdrawQty."</td>";
			
			$totalStock -= $totalWithdrawQty;
			
			$minimumStock = 0;
			$sql = "SELECT itemName, itemDescription, minimumStock FROM purchasing_items WHERE itemId = ".$supplyId." LIMIT 1";
			$queryItems = $db->query($sql);
			if($queryItems AND $queryItems->num_rows > 0)
			{
				$resultItems = $queryItems->fetch_assoc();
				$itemName = $resultItems['itemName'];
				$itemDescription = $resultItems['itemDescription'];
				$minimumStock = $resultItems['minimumStock'];
			}
			
			$remarksArray[] = "<td>Minimum Stock</td><td> : ".$minimumStock."</td>";
			
			$partNumber = $itemName;
			//~ $processCode = '391';//Supply PO Making
			$processCode = '461';//Purchase Order Making
		}
		else if($type==4)
		{
			$sql = "SELECT SUM(accessoryWithdrawalQuantity) AS totalWithdraw FROM warehouse_accessorywithdrawal WHERE accessoryWithdrawalId IN(".implode(",",$inventoryIdArray).")";
			$queryAccessorieswithdrawal = $db->query($sql);
			if($queryAccessorieswithdrawal AND $queryAccessorieswithdrawal->num_rows > 0)
			{
				$resultAccessorieswithdrawal = $queryAccessorieswithdrawal->fetch_assoc();
				$totalWithdraw = $resultAccessorieswithdrawal['totalWithdraw'];
			}
			
			$remarksArray[] = "<td>Total Withdrawn</td><td> : ".$totalWithdraw."</td>";
			
			$totalStock -= $totalWithdraw;
			
			$minimumStock = 0;
			$sql = "SELECT accessoryNumber, accessoryName, accessoryMinimumStock FROM cadcam_accessories WHERE accessoryId = ".$supplyId." LIMIT 1";
			$queryItems = $db->query($sql);
			if($queryItems AND $queryItems->num_rows > 0)
			{
				$resultItems = $queryItems->fetch_assoc();
				$accessoryNumber = $resultItems['accessoryNumber'];
				$accessoryName = $resultItems['accessoryName'];
				$minimumStock = $resultItems['accessoryMinimumStock'];
			}
			
			$remarksArray[] = "<td>Minimum Stock</td><td> : ".$minimumStock."</td>";
			
			$partNumber = $accessoryNumber;
			$processCode = '461';//Accessory PO Making
		}
		
		$remarksArray[] = "<td>Total Stock</td><td> : ".$totalStock."</td>";
		
		$poQuantity = $minimumStock;
		$sql = "SELECT supplyMOQ FROM purchasing_supplies WHERE supplyId = ".$supplyId." AND supplyMOQ > 0 AND supplyType = ".$type." LIMIT 1";
		$querySupplies = $db->query($sql);
		if($querySupplies AND $querySupplies->num_rows > 0)
		{
			$resultSupplies = $querySupplies->fetch_assoc();
			$poQuantity = $resultSupplies['supplyMOQ'];
		}
		
		$listIdArray = array();
		$sql = "SELECT listId FROM purchasing_supplies WHERE supplyId = ".$supplyId." AND supplyType = ".$type."";
		$querySupplies = $db->query($sql);
		if($querySupplies AND $querySupplies->num_rows > 0)
		{
			while($resultSupplies = $querySupplies->fetch_assoc())
			{
				$listIdArray[] = $resultSupplies['listId'];
			}
		}
		
		$totalPOQty = 0;
		$sql = "SELECT IFNULL(SUM(itemQuantity),0) AS totalPOQty FROM purchasing_pocontents WHERE productId IN(".implode(",",$listIdArray).") AND itemStatus = 0";
		$queryPoContent = $db->query($sql);
		if($queryPoContent AND $queryPoContent->num_rows > 0)
		{
			$resultPoContent = $queryPoContent->fetch_assoc();
			$totalPOQty = $resultPoContent['totalPOQty'];
			
			//~ $sql = "SELECT SUM(receiveQuantity) AS totalReceiveQty FROM purchasing_receivingdata WHERE poContentId IN(SELECT DISTINCT poContentId FROM purchasing_pocontents WHERE poContentId IN(".implode(",",$poContentIdArray).") AND productId IN(".implode(",",$listIdArray)."))";
			//~ $sql = "SELECT SUM(receiveQuantity) AS totalReceiveQty FROM purchasing_receivingdata WHERE poContentId IN(SELECT DISTINCT poContentId FROM purchasing_pocontents WHERE productId IN(".implode(",",$listIdArray)."))";
			//~ $queryTotalReceiveQty = $db->query($sql);
			//~ if($queryTotalReceiveQty->num_rows > 0)
			//~ {
				//~ $resultTotalReceiveQty = $queryTotalReceiveQty->fetch_array();
				//~ $totalPOQty -= $resultTotalReceiveQty['totalReceiveQty'];
			//~ }
		}
		
		$remarksArray[] = "<td>Total Open PO</td><td> : ".$totalPOQty."</td>";

		//~ $totalQty = 0;
		//~ $sql = "SELECT IFNULL(SUM(workingQuantity),0) AS totalQty FROM ppic_lotlist WHERE partId = ".$supplyId." AND identifier = 4 AND poId = 0 AND status = ".$type."";
		//~ $queryLotList = $db->query($sql);
		//~ if($queryLotList AND $queryLotList->num_rows > 0)
		//~ {
			//~ $resultLotList = $queryLotList->fetch_assoc();
			//~ $totalQty = $resultLotList['totalQty'];
		//~ }
		
		$totalQty = 0;
		$sql = "SELECT lotNumber, workingQuantity FROM ppic_lotlist WHERE partId = ".$supplyId." AND identifier = 4 AND poId = 0 AND status = ".$type."";
		$queryLotList = $db->query($sql);
		if($queryLotList AND $queryLotList->num_rows > 0)
		{
			while($resultLotList = $queryLotList->fetch_assoc())
			{
				$sql = "SELECT id FROM ppic_workschedule WHERE lotNumber LIKE '".$resultLotList['lotNumber']."' AND processCode = ".$processCode." AND status = 0 LIMIT 1";
				$queryWorkSchedule = $db->query($sql);
				if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
				{
					$totalQty += $resultLotList['lotNumber'];
				}
			}
		}
		
		//~ $processCode = '461';//2017-04-04 Pangsamantagal
		
		$remarksArray[] = "<td>Total Ongoing PO</td><td> : ".$totalQty."</td>";
		
		$remarksArray[] = "<td>Overall Quantity</td><td> : ".($totalStock + $totalPOQty + $totalQty)."</td>";
		//~ echo $totalStock." + ".$totalPOQty." + ".$totalQty." <= ".$minimumStock;
		if(($totalStock + $totalPOQty + $totalQty) <= $minimumStock)
		{
			$remarksArray[] = "<th colspan='2'>Trigger PO Making</th>";
			
			$lotNumberArray = array();
			//~ $sql = "SELECT lotNumber FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode = ".$processCode." AND status = 0";
			$sql = "SELECT lotNumber FROM ppic_workschedule WHERE processCode = ".$processCode." AND status = 0";
			$queryWorkSchedule = $db->query($sql);
			if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
			{
				while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
				{
					$lotNumberArray[] = "'".$resultWorkSchedule['lotNumber']."'";
				}
			}
			
			$sql = "SELECT lotNumber FROM ppic_lotlist WHERE lotNumber IN(".implode(",",$lotNumberArray).") AND poId = 0 AND partId = ".$supplyId." AND identifier = 4 AND status = ".$type." ORDER BY dateGenerated DESC LIMIT 1";
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				$resultLotList = $queryLotList->fetch_assoc();
				$lotNumber = $resultLotList['lotNumber'];
				
				$sql = "UPDATE ppic_lotlist SET workingQuantity = (workingQuantity+".$poQuantity.") WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
				if($displayFlag == 0)	$queryUpdate = $db->query($sql);
			}
			else
			{
				$targetFinish = addDays(1);
				$lot = getLotNumber();
				$repeatFlag = 1;
				while($repeatFlag == 1)
				{
					$repeatFlag = 0;				
					$sql = "INSERT INTO	ppic_lotlist
									(	lotNumber,	poId,	partId, 			parentLot,	partLevel,	workingQuantity,	identifier, dateGenerated,	status,		bookingStatus)
							VALUES	(	'".$lot."',	0,		'".$supplyId."',	'',			0,			'".$poQuantity."',	4,			now(),			".$type.",	0)";
					//~ echo $sql."<br>";
					if($displayFlag == 0)
					{
						$queryInsert = $db->query($sql);
						if(!$queryInsert)
						{
							$mysqliError = $db->error;
							if(strstr($mysqliError,'Duplicate entry'))
							{
								$lot = getLotNumber();
								$repeatFlag = 1;
							}
						}
					}
				}

				$sql = "INSERT INTO `ppic_workschedule`
								(	`lotNumber`,	`processCode`,		`processOrder`,	`targetFinish`,			`actualFinish`,	`status`,	`employeeId`,	`processSection`,	`availability`)
						VALUES	(	'".$lot."',		".$processCode.",	1,				'".$targetFinish."',	'',				0,			'',				5,					1)
					";
				//~ echo $sql."<br>";
				if($displayFlag == 0)
				{
					$queryInsert = $db->query($sql);
					if($queryInsert)
					{
						$sql = "
							INSERT INTO view_workschedule
									(	`id`,	`lotNumber`,	`processCode`,	`processOrder`,	`targetFinish`,	`status`,	`processSection`,	`availability`)
							SELECT 		`id`,	`lotNumber`,	`processCode`,	`processOrder`,	`targetFinish`,	`status`,	`processSection`,	`availability`
							FROM	ppic_workschedule
							WHERE 	lotNumber LIKE '".$lot."' ORDER BY processOrder
							";
						$queryInsert = $db->query($sql);
					}
				}
			}
		}
		else
		{
			$remarksArray[] = "<th colspan='2'>Not Reached Minimum Stock</th>";
		}
		
		if($displayFlag == 1)
		{
			echo "<br><table border='1'>".implode("<tr>",$remarksArray)."</table>";
		}
		*/
	}
	
	function createPurchasingLotNumber($supplyId,$type,$poQuantity=1)
	{
		include('PHP Modules/mysqliConnection.php');		
		$displayFlag = 0;	
		$processCode = '461';//Purchase Order Making	
			//597   PO Preparation
			//598   PO Printing
		//~ $poQuantity = 0;
		//~ $sql = "SELECT supplyMOQ FROM purchasing_supplies WHERE supplyId = ".$supplyId." AND supplyMOQ > 0 AND supplyType = ".$type." LIMIT 1";
		//~ $querySupplies = $db->query($sql);
		//~ if($querySupplies AND $querySupplies->num_rows > 0)
		//~ {
			//~ $resultSupplies = $querySupplies->fetch_assoc();
			//~ $poQuantity = $resultSupplies['supplyMOQ'];		
			
			$lotNumberArray = array();
			$sql = "SELECT lotNumber FROM ppic_workschedule WHERE processCode = ".$processCode." AND status = 0";
			$queryWorkSchedule = $db->query($sql);
			if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
			{
				while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
				{
					$lotNumberArray[] = "'".$resultWorkSchedule['lotNumber']."'";
				}
			}			
			
			$sql = "SELECT lotNumber FROM ppic_lotlist WHERE lotNumber IN(".implode(",",$lotNumberArray).") AND poId = 0 AND partId = ".$supplyId." AND identifier = 4 AND status = ".$type." ORDER BY dateGenerated DESC LIMIT 1";
			$queryLotList = $db->query($sql);
			//~ if($queryLotList AND $queryLotList->num_rows > 0 AND $type!=1)
			if($queryLotList AND $queryLotList->num_rows > 0 AND !in_array($type,array(1,4,3)))
			{
				$resultLotList = $queryLotList->fetch_assoc();
				$lotNumber = $resultLotList['lotNumber'];
				
				//~ if($type!=1)
				if(!in_array($type,array(1,4,3)))
				{
					$sql = "UPDATE ppic_lotlist SET workingQuantity = (workingQuantity+".$poQuantity.") WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
					if($displayFlag == 0)	$queryUpdate = $db->query($sql);
				}
				
				return $lotNumber;
			}
			else
			{
				$targetFinish = addDays(1);
				$lot = getLotNumber();
				$repeatFlag = 1;
				while($repeatFlag == 1)
				{
					$repeatFlag = 0;				
					$sql = "INSERT INTO	ppic_lotlist
									(	lotNumber,	poId,	partId, 			parentLot,	partLevel,	workingQuantity,	identifier, dateGenerated,	status,		bookingStatus)
							VALUES	(	'".$lot."',	0,		'".$supplyId."',	'',			0,			'".$poQuantity."',	4,			now(),			".$type.",	0)";
					//~ echo $sql."<br>";
					if($displayFlag == 0)
					{
						$queryInsert = $db->query($sql);
						if(!$queryInsert)
						{
							$mysqliError = $db->error;
							if(strstr($mysqliError,'Duplicate entry'))
							{
								$lot = getLotNumber(); // ------- Ace : Can Be Deleted If $lot = getLotNumber() Will Be Moved To The First Line Of The While Clause
								$repeatFlag = 1;
							}
						}
					}
				}
				//~ if($type==1)//2020-07-25 Rose insert new processCodes
				//~ if(in_array($type,array(1,4,3)) AND $_SESSION['idNumber']=='0346')//2020-08-15 include accessories gerald
				if(in_array($type,array(1,4,3)))//2020-08-15 include accessories gerald
				{
					$sql = "INSERT INTO ppic_workschedule (lotNumber,processCode,processOrder,targetFinish,actualFinish,status,employeeId,processSection,availability) VALUES ('".$lot."',597,1,'".$targetFinish."','',0,'',5,1)";	//~ echo $sql."<br>";					
					$queryInsert = $db->query($sql);
						if($queryInsert)
						{
							$sql = "INSERT INTO view_workschedule (id,lotNumber,processCode,processOrder,targetFinish,status,processSection,availability)
								SELECT id,lotNumber,processCode,processOrder,targetFinish,status,processSection,availability FROM ppic_workschedule WHERE lotNumber LIKE '".$lot."' and processCode=597";
							$queryInsert = $db->query($sql);
						}
					$sql2 = "INSERT INTO ppic_workschedule(lotNumber,processCode,processOrder,targetFinish,actualFinish,status,employeeId,processSection,availability) VALUES ('".$lot."',598,1,'".$targetFinish."','',0,'',5,1)";	//~ echo $sql."<br>";					
					$queryInsert2 = $db->query($sql2);
						if($queryInsert2)
						{
							$sql3 = "INSERT INTO view_workschedule (id,lotNumber,processCode,processOrder,targetFinish,status,processSection,availability)
								SELECT id,lotNumber,processCode,processOrder,targetFinish,status,processSection,availability FROM ppic_workschedule WHERE lotNumber LIKE '".$lot."' and processCode=598";
							$queryInsert3 = $db->query($sql3);
						}					
				}
				else
				{
					$sql = "INSERT INTO `ppic_workschedule`
									(	`lotNumber`,	`processCode`,		`processOrder`,	`targetFinish`,			`actualFinish`,	`status`,	`employeeId`,	`processSection`,	`availability`)
							VALUES	(	'".$lot."',		".$processCode.",	1,				'".$targetFinish."',	'',				0,			'',				5,					1)
						";
					//~ echo $sql."<br>";
					if($displayFlag == 0)
					{
						$queryInsert = $db->query($sql);
						if($queryInsert)
						{
							$sql = "
								INSERT INTO view_workschedule
										(	`id`,	`lotNumber`,	`processCode`,	`processOrder`,	`targetFinish`,	`status`,	`processSection`,	`availability`)
								SELECT 		`id`,	`lotNumber`,	`processCode`,	`processOrder`,	`targetFinish`,	`status`,	`processSection`,	`availability`
								FROM	ppic_workschedule
								WHERE 	lotNumber LIKE '".$lot."' ORDER BY processOrder
								";
							$queryInsert = $db->query($sql);
						}
					}
				}
				return $lot;
			}
		//~ }
		//~ else
		//~ {
			//~ return "error1";
		//~ }
	}
	
	function convertSeconds($inputValue)
	{
		$leadingZero = "";
		$numbersOfHours = ($inputValue / 3600);
		$remainingValue = $inputValue % 3600;	
		$numberOfMinutes = $remainingValue / 60;
		$numberOfSeconds = $remainingValue % 60;
		if($numberOfMinutes < 10)
		{
			$minutesLeadingZero = "0";
		}
		if($numberOfSeconds < 10)
		{
			$secondsLeadingZero = "0";
		}
		$displayText = floor($numbersOfHours)."H ".$minutesLeadingZero."".floor($numberOfMinutes)."M ".$secondsLeadingZero."".floor($numberOfSeconds)."S";
		
		return $displayText;
	}
	
	
	function computeQuantity($bookingId,$materialLength,$lotNo = '')
	{
		include('PHP Modules/mysqliConnection.php');
		
		$lotNoArray = $lotNumberArray = $itemXArray = $returnArray = $bookedArray = array();
		$computeQuantity = 1;
		$count = 0;
		$returnArray[$computeQuantity] = $materialLength;
		$sql = "SELECT lotNumber FROM `engineering_bookingdetails` WHERE bookingId = ".$bookingId."";
		$queryBookingDetails = $db->query($sql);
		if($queryBookingDetails->num_rows > 0)
		{
			while($resultBookingDetails = $queryBookingDetails->fetch_array())
			{
				$lotNoArray[] = "'".$resultBookingDetails['lotNumber']."'";
			}
		}
		
		if(is_array($lotNo))
		{
			if(count($lotNo) > 0)
			{
				foreach($lotNo as $lot)
				{
					$lotNoArray[] = "'".$lot."'";
				}
			}
		}
		else
		{
			if($lotNo != '') $lotNoArray[] = "'".$lotNo."'";
		}
		
		$partId = '';
		$lotQty = 0;
		$sql = "SELECT lotNumber, partId, workingQuantity FROM ppic_lotlist WHERE lotNumber IN(".implode(",",$lotNoArray).") AND identifier = 1";
		$queryLotList = $db->query($sql);
		if($queryLotList->num_rows > 0)
		{
			while($resultLotList = $queryLotList->fetch_array())
			{
				$lotNumber = $resultLotList['lotNumber'];
				$partId = $resultLotList['partId'];
				$lotQty = $resultLotList['workingQuantity'];
				
				$itemX = 0;
				$sql = "SELECT x FROM cadcam_parts WHERE partId = ".$partId." LIMIT 1";
				$queryParts = $db->query($sql);
				if($queryParts->num_rows > 0)
				{
					$resultParts = $queryParts->fetch_array();
					$itemX = $resultParts['x'] + 10;
				}
				
				if(!in_array($itemX,$itemXArray))
				{
					$itemXArray[] = $itemX;
				}
				
				//~ $lotNumberArray[$itemX][] = $lotNumber."|".$lotQty;
				
				$lotNumberArray[$lotNumber."|".$lotQty] = $itemX;
			}
		}

		arsort($lotNumberArray);
		
		foreach($lotNumberArray as $keys => $itemX)
		{
			$arrayParts = explode("|",$keys);
			$lotNumber = $arrayParts[0];
			$lotQty = $arrayParts[1];
			
			$index = 0;
			while($index < $lotQty)
			{
				asort($returnArray);
				
				foreach($returnArray as $key => $returnLength)
				{
					$addQuantityFlag = 1;
					if($returnLength >= $itemX)
					{
						$returnArray[$key] -= $itemX;
						$bookedArray[$key][] = $lotNumber."|".$itemX;
						$addQuantityFlag = 0;
						break;
					}
				}
				
				ksort($returnArray);
				
				if($addQuantityFlag == 1)
				{
					$returnArray[] = ($materialLength - $itemX);
					$bookedArray[count($returnArray)][] = $lotNumber."|".$itemX;
				}
				
				$index++;
			}
		}

		//~ rsort($itemXArray);
		//~ 
		//~ foreach($itemXArray as $itemX)
		//~ {
			//~ foreach($lotNumberArray[$itemX] as $value)
			//~ {
				//~ $arrayParts = explode("|",$value);
				//~ $lotNumber = $arrayParts[0];
				//~ $lotQty = $arrayParts[1];
				//~ 
				//~ $index = 0;
				//~ while($index < $lotQty)
				//~ {
					//~ asort($returnArray);
					//~ 
					//~ foreach($returnArray as $key => $returnLength)
					//~ {
						//~ $addQuantityFlag = 1;
						//~ if($returnLength >= $itemX)
						//~ {
							//~ $returnArray[$key] -= $itemX;
							//~ $bookedArray[$key][] = $lotNumber."|".$itemX;
							//~ $addQuantityFlag = 0;
							//~ break;
						//~ }
					//~ }
					//~ 
					//~ ksort($returnArray);
					//~ 
					//~ if($addQuantityFlag == 1)
					//~ {
						//~ $returnArray[] = ($materialLength - $itemX);
						//~ $bookedArray[count($returnArray)][] = $lotNumber."|".$itemX;
					//~ }
					//~ 
					//~ $index++;
				//~ }
			//~ }
		//~ }
		
		$computeQuantity = count($returnArray);
		
		return array($computeQuantity,$bookedArray,$returnArray);
	}
	
	function checkBooking($inventoryId,$stock,$materialLength,$lotNumber)
	{
		include('PHP Modules/mysqliConnection.php');
		
		$chosenBookingId = '';
		$sql = "SELECT bookingId, bookingQuantity FROM `engineering_booking` WHERE inventoryId LIKE '".$inventoryId."' AND bookingStatus = 0 ORDER BY bookingDate";
		$queryBooking = $db->query($sql);
		if($queryBooking->num_rows > 0)
		{
			while($resultBooking = $queryBooking->fetch_array())
			{
				$bookingId = $resultBooking['bookingId'];
				$bookingQuantity = $resultBooking['bookingQuantity'];
				
				$arrayParts = computeQuantity($bookingId,$materialLength,$lotNumber);
				$computeQuantity = $arrayParts[0];
				$bookedArray = $arrayParts[1];
				
				if($computeQuantity == 0)	$computeQuantity = 1;
				
				if(($stock + $bookingQuantity) >= $computeQuantity)
				{
					$chosenBookingId = $bookingId;
					break;					
				}
			}
		}
		return array($chosenBookingId,$computeQuantity);
	}
	
	function computeQtyPerSheet($itemX,$itemY,$materialX,$materialY,$blanking,$customerId = '',$partId = 0)
	{
		/*	Used in the following files/directories:
		 *	1.	/Automated Material Computation/gerald_materialNoStock.php
		 *	2.	/Automated Material Computation/gerald_materialTemporaryBooking.php
		 *	3.	/Engineering Data Management Software/Material Booking/gerald_materialDetails.php
		 *	4.	/Engineering Data Management Software/Material Booking/gerald_bookingLotDetails.php
		 */
		 
		include('PHP Modules/mysqliConnection.php');
		
		$qtyPerSheet = 0;
		if($blanking=='TPP')
		{
			//~ $clearance = 10;//Old Clearance 15 (2016-10-18)
			
			if(in_array($customerId,array(28,37)))
			{
				$itemGap = 17;
				$clamp = 60;
				$clearanceLeft = 30;
				$clearanceRight = 30;
				$clearanceTop = 20;
				
				if($partId==20968)
				{
					$clearanceLeft = 28;
					$clearanceRight = 28;
					$itemGap = 0;
				}
			}
			else if(in_array($customerId,array(45,49)))
			{
				$itemGap = 17;
				$clamp = 60;
				$clearanceLeft = 20;
				$clearanceRight = 20;
				$clearanceTop = 20;
			}
			else
			{
				$itemGap = 17;
				$clamp = 60;
				$clearanceLeft = 15;
				$clearanceRight = 15;
				$clearanceTop = 15;
				
				if($partId==12359)
				{
					$clearanceLeft = 14;
					$clearanceRight = 14;
				}
				
				if(in_array($partId,array(2364,2378,2394,2416,57111,57093,57081)))
				{
					$itemGap = 0;
					$clamp = 0;
					$clearanceLeft = 10;
					$clearanceRight = 10;
					$clearanceTop = 10;			
				}
				
				//~ if(in_array($partId,array(40745)))
				//~ {
					//~ $clearanceLeft = 0;
					//~ $clearanceRight = 0;
				//~ }
				
				if($partId==2726)
				{
					$clearanceLeft = 10;
					$clearanceRight = 10;
					$itemGap = 0;
				}
				
				if($partId==2363)
				{
					$clearanceLeft = 0;
					$clearanceRight = 0;
					$itemGap = 0;
				}
				
				if($partId==57272 OR $partId==57271)//fixed qty 4 pcs per sheet sir roldan 2021-10-04
				{
					//~ $clearanceLeft = 20;
					//~ $clearanceRight = 20;
					//~ $itemGap = 0;
					//~ $clamp = 0;
				}
			}
		}
		else if($blanking=='Laser')
		{
			$itemGap = 5;
			//~ $clamp = 45;//No clamp for laser sir mar 2019-10-28
			$clamp = 10;
			
			//change from 10 to 5 mizuno 2020-09-28
			//~ $clearanceLeft = 10;
			//~ $clearanceRight = 10;
			//~ $clearanceTop = 10;
			$clearanceLeft = 5;
			$clearanceRight = 5;
			$clearanceTop = 5;
		}
		else
		{
			$itemGap = 0;
			$clamp = 0;
			$clearanceLeft = 0;
			$clearanceRight = 0;
			$clearanceTop = 0;
		}
		
		if(in_array($partId,array(40745,40746,40747, 15061, 33751, 50276,50277,30845)))
		{
			$itemGap = 0;
			$clamp = 0;
			$clearanceLeft = 0;
			$clearanceRight = 0;
			$clearanceTop = 0;			
		}
		
		if(in_array($partId,array(30845)))
		{
			$clamp = 0;
		}
		
		if(in_array($partId,array(33374)))//by sir mar and batman 2020-08-22
		{
			$clearanceLeft = 4;
			$clearanceRight = 4;
			$clearanceTop = 4;	
			$clamp = 4;
		}
		
		$itemArea = ($itemX + $itemGap) * ($itemY + $itemGap);
	
		//~ $materialArea = ($materialX - ($clamp + $clearance)) * ($materialY - ($clearance * 2));
		//~ $materialArea = ($materialX - ($clearance * 2)) * ($materialY - ($clamp +  $clearance));
		$materialArea = ($materialX - ($clearanceLeft + $clearanceRight)) * ($materialY - ($clamp +  $clearanceTop));
		
		$qtyPerSheet = 0;
		if($materialArea >= $itemArea)
		{
			$usedX = $usedY = 0;
			if($itemX > 0 AND $itemY > 0)
			{
				//~ $usedX = ($materialX - ($clamp + $clearance)) / ($itemX + $itemGap);
				//~ $usedY = ($materialY - ($clearance * 2)) / ($itemY + $itemGap);
				
				//~ $usedX = ($materialX - ($clearance * 2)) / ($itemX + $itemGap);
				//~ $usedY = ($materialY - ($clamp + $clearance)) / ($itemY + $itemGap);
				
				$usedX = ($materialX - ($clearanceLeft + $clearanceRight)) / ($itemX + $itemGap);
				//~ echo "($materialX - ($clearanceLeft + $clearanceRight)) / ($itemX + $itemGap)";
				$usedY = ($materialY - ($clamp + $clearanceTop)) / ($itemY + $itemGap);
				//~ echo "<br>($materialY - ($clamp + $clearanceTop)) / ($itemY + $itemGap)";
			}
			
			$qtyPerSheet = floor($usedX) * floor($usedY);
			
			$leftMaterialArea = $materialArea - ($itemArea * $qtyPerSheet);

			//~ echo "<hr>".$materialX." = ".$itemX; 
			//~ echo "<br>".$materialY." = ".$itemY; 
			//~ echo "<br>".$qtyPerSheet; 
			//~ echo "<br>".$leftMaterialArea; 
			
			if($leftMaterialArea >= $itemArea AND $qtyPerSheet > 0)
			{
				//~ $leftX = $materialX - (floor($usedX) * ($itemX + $itemGap)) + $clearance;
				$leftX = $materialX - (floor($usedX) * ($itemX + $itemGap)) + ($clearanceLeft + $clearanceRight);
				$materialX = $leftX;
				//~ if(($itemX + $itemGap) > ($leftX - ($clamp + $clearance)))
				if(($itemX + $itemGap) > ($leftX - ($clamp + $clearanceTop)))
				{
					$materialX = $materialY;
					$materialY = $leftX;
					
					$qtyPerSheet += computeQtyPerSheet($itemX,$itemY,$materialX,$materialY,$blanking);
				}
				
			}
		}
		
		if($partId > 0)
		{
			$processCode = 0;
			if($blanking=='TPP')		$processCode = 86;
			else if($blanking=='Laser')	$processCode = 381;
			
			$sql = "SELECT quantityPerSheet FROM engineering_quantitypersheet WHERE partId = ".$partId." AND blankingProcess = ".$processCode." LIMIT 1";
			$queryQuantityPerSheet = $db->query($sql);
			if($queryQuantityPerSheet AND $queryQuantityPerSheet->num_rows > 0)
			{
				$resultQuantityPerSheet = $queryQuantityPerSheet->fetch_assoc();
				$qtyPerSheet = $resultQuantityPerSheet['quantityPerSheet'];
			}
		}
		
		return $qtyPerSheet;
	}
	
	// ------------------------------------- For Deletion --------------------------------------------------
	function checkMaterial($lotNumber,$partId,$workingQuantity)
	{
		include('PHP Modules/mysqliConnection.php');
		
		global $inventoryUsage, $poContentUsage;
		
		$arrayResult = array();
		
		$lotNumberForPO = "";
		
		$materialTable = "";
		
		$blankingProcess = '';
		$processCode = '';
		$sql = "SELECT processCode FROM ppic_workschedule WHERE partId = ".$partId." AND processCode IN(86,52,381,98,392) LIMIT 1";
		$queryPartProcess = $db->query($sql);
		if($queryPartProcess->num_rows > 0)
		{
			$resultPartProcess = $queryPartProcess->fetch_array();
			$processCode = $resultPartProcess['processCode'];
		}
		else
		{
			$sql = "SELECT processCode FROM cadcam_partprocess WHERE partId = ".$partId." AND processCode IN(86,52,381,98,392) LIMIT 1";
			$queryPartProcess = $db->query($sql);
			if($queryPartProcess->num_rows > 0)
			{
				$resultPartProcess = $queryPartProcess->fetch_array();
				$processCode = $resultPartProcess['processCode'];
			}
		}
		
		if($processCode==86)
		{
			$blankingProcess = 'TPP';
			$itemGap = 17;
			$clamp = 65;
			$clearance = 15;
		}
		else if($processCode==381)
		{
			$blankingProcess = 'Laser';
			$itemGap = 5;
			//~ $clamp = 45;//No clamp for laser sir mar 2019-10-28
			$clamp = 10;
			$clearance = 10;
		}
		else if($processCode==52)
		{
			$blankingProcess = 'Press';
		}
		else if(in_array($processCode,array(328,98,392)))
		{
			$blankingProcess = 'Cutting';
		}			
		
		if($blankingProcess=="") return "";
		
		$partNumber = $revisionId = $customerId = $materialSpecId = $PVC = $x = $y = $treatmentId = '';
		$sql = "SELECT partNumber, revisionId, customerId, materialSpecId, PVC, x, y, treatmentId FROM cadcam_parts WHERE partId = ".$partId." LIMIT 1";
		$queryParts = $db->query($sql);
		if($queryParts->num_rows > 0)
		{
			$resultParts = $queryParts->fetch_array();
			$partNumber = $resultParts['partNumber'];
			$revisionId = $resultParts['revisionId'];
			$customerId = $resultParts['customerId'];
			$materialSpecId = $resultParts['materialSpecId'];
			$PVC = $resultParts['PVC'];
			$x = $resultParts['x'];
			$y = $resultParts['y'];
			$treatmentId = $resultParts['treatmentId'];
		}
		else
		{
			return "";
		}
		
		$itemX = $x+$itemGap;
		$itemY = $y+$itemGap;
		
		//~ $metalType = $metalThickness = '';
		//~ $sql = "SELECT metalType, metalThickness FROM cadcam_materialspecs WHERE materialSpecId = ".$materialSpecId." AND metalType != '' LIMIT 1";
		//~ $queryMaterialSpecs = $db->query($sql);
		//~ if($queryMaterialSpecs->num_rows > 0)
		//~ {
			//~ $resultMaterialSpecs = $queryMaterialSpecs->fetch_array();
			//~ $metalType = $resultMaterialSpecs['metalType'];
			//~ $metalThickness = $resultMaterialSpecs['metalThickness'];
		//~ }
		//~ else
		//~ {
			//~ return "";
		//~ }
		
		//;cadcam_materialspecs;
		$metalType = $metalThickness = '';
		$sql = "SELECT materialTypeId, metalThickness FROM cadcam_materialspecs WHERE materialSpecId = ".$materialSpecId." AND metalType != '' LIMIT 1";
		$queryMaterialSpecs = $db->query($sql);
		if($queryMaterialSpecs->num_rows > 0)
		{
			$resultMaterialSpecs = $queryMaterialSpecs->fetch_array();
			$materialTypeId = $resultMaterialSpecs['materialTypeId'];
			$metalThickness = $resultMaterialSpecs['metalThickness'];
				
			$sql = "SELECT materialType FROM engineering_materialtype WHERE materialTypeId = ".$materialTypeId." LIMIT 1";
			$queryMaterialType = $db->query($sql);
			if($queryMaterialType AND $queryMaterialType->num_rows > 0)
			{
				$resultMaterialType = $queryMaterialType->fetch_assoc();
				$metalType = $resultMaterialType['materialType'];
			}
		}
		else
		{
			return "";
		}
		//;cadcam_materialspecs;
		
		$materialSpecIdArray = array();
		$materialSpecIdArray[] = $materialSpecId;
		$sql = "SELECT materialSpecId FROM engineering_alternatematerial WHERE partId = ".$partId."";
		$queryAlternateMaterial = $db->query($sql);
		if($queryAlternateMaterial->num_rows > 0)
		{
			while($resultAlternateMaterial = $queryAlternateMaterial->fetch_array())
			{
				$materialSpecIdArray[] = $resultAlternateMaterial['materialSpecId'];
			}
		}
		
		//~ $metalTypeArray = array();
		//~ $metalTypeArray[] = "'".$metalType."'";
		//~ $sql = "SELECT metalType FROM cadcam_materialspecs WHERE materialSpecId IN(".implode(",",$materialSpecIdArray).")";
		//~ $queryMaterialSpecs = $db->query($sql);
		//~ if($queryMaterialSpecs->num_rows > 0)
		//~ {
			//~ while($resultMaterialSpecs = $queryMaterialSpecs->fetch_array())
			//~ {
				//~ $metalTypeArray[] = "'".$resultMaterialSpecs['metalType']."'";
			//~ }
		//~ }
		
		//;cadcam_materialspecs;
		$materialTypeIdArray = array();
		$sql = "SELECT materialTypeId FROM cadcam_materialspecs WHERE materialSpecId IN(".implode(",",$materialSpecIdArray).")";
		$queryMaterialSpecs = $db->query($sql);
		if($queryMaterialSpecs->num_rows > 0)
		{
			while($resultMaterialSpecs = $queryMaterialSpecs->fetch_array())
			{
				$materialTypeIdArray[] = $resultMaterialSpecs['materialTypeId'];
			}
		}
		
		$metalTypeArray = array();
		$metalTypeArray[] = "'".$metalType."'";
		$sql = "SELECT materialType FROM engineering_materialtype WHERE materialTypeId IN(".implode(",",$materialTypeIdArray).")";
		$queryMaterialType = $db->query($sql);
		if($queryMaterialType->num_rows > 0)
		{
			while($resultMaterialType = $queryMaterialType->fetch_array())
			{
				$metalTypeArray[] = "'".$resultMaterialType['materialType']."'";
			}
		}
		//;cadcam_materialspecs;
		
		$treatmentName = 'Raw';
		$sql = "SELECT treatmentName FROM cadcam_treatmentprocess WHERE treatmentId = ".$treatmentId." LIMIT 1";
		$queryTreatment = $db->query($sql);
		if($queryTreatment->num_rows > 0)
		{
			$resultTreatment = $queryTreatment->fetch_array();
			$treatmentName = $resultTreatment['treatmentName'];
		}
		
		if(in_array('2024T3',$metalTypeArray) OR in_array('2024-T3',$metalTypeArray))
		{
			$dataOneWhere = "dataOne IN('2024T3','2024-T3',".implode(",",$metalTypeArray).")";
		}
		else if(in_array("'MS2007'",$metalTypeArray) OR in_array("'MS2009'",$metalTypeArray))
		{
			//~ $dataOneWhere = "dataOne IN('SS Wire Cloth 316',".implode(",",$metalTypeArray).")";
		}
		else
		{
			$dataOneWhere = "dataOne IN(".implode(",",$metalTypeArray).")";
		}
		
		if($customerId == '45')//Jamco
		{
			$filterSupplier = "AND supplierAlias IN('Jamco','KAPCO','Kapco Manufacturing Inc.')";
		}
		else if($customerId == '28' OR $customerId == '37')//BE
		{
			$filterSupplier = "AND supplierAlias IN('Metalweb Ltd.','KAPCO','Kapco Manufacturing Inc.','Shs Perforated Materials Inc.','B/e Aerospace')";
		}
		else
		{
			$filterSupplier = "AND supplierAlias != 'Jamco'";
		}
		
		//~ $sqlBendProcess = "SELECT processCode FROM cadcam_process WHERE processName LIKE '%Bending%'";
		//~ $sql = "SELECT processCode FROM cadcam_partprocess WHERE partId = ".$partId." AND processCode IN(".$sqlBendProcess.") LIMIT 1";
		//~ $queryBendProcess = $db->query($sql);
		//~ $filterBend = ($queryBendProcess->num_rows > 0) ? 'AND bendStatus = 1' : '';
		
		$purchaseAlarmFlag = 1;
		
		$inventoryIdArray = array();
		$sql = "SELECT inventoryId, supplierAlias, dataOne, dataTwo, dataThree, dataFour, dataFive, inventoryQuantity FROM warehouse_inventory WHERE type = 1 AND ".$dataOneWhere." AND dataTwo = ".$metalThickness." AND dataThree >= ".$itemX." AND dataFour >= ".$itemY." AND dataFive LIKE '".$treatmentName."' ".$filterSupplier." ".$filterBend." ORDER BY dataThree, dataFour, stockDate";
		$sql = "SELECT inventoryId, supplierAlias, dataOne, dataTwo, dataThree, dataFour, dataFive, inventoryQuantity FROM warehouse_inventory WHERE type = 1 AND ".$dataOneWhere." AND dataTwo = ".$metalThickness." AND dataThree >= ".$itemX." AND dataFour >= ".$itemY." AND dataFive LIKE '".$treatmentName."' ".$filterSupplier." ".$filterBend." ORDER BY (dataThree * dataFour), stockDate";
		$sqlasd = $sql;
		
		$queryInventory = $db->query($sql);
		if($queryInventory->num_rows > 0)
		{
			while($resultInventory = $queryInventory->fetch_array())
			{
				$inventoryId = $resultInventory['inventoryId'];
				$supplierAlias = $resultInventory['supplierAlias'];
				$dataOne = $resultInventory['dataOne'];
				$dataTwo = $resultInventory['dataTwo'];
				$dataThree = $resultInventory['dataThree'];
				$dataFour = $resultInventory['dataFour'];
				$dataFive = $resultInventory['dataFive'];
				$inventoryQuantity = $resultInventory['inventoryQuantity'];
				$area = $resultInventory['area'];
				
				$totalBookingQty = 0;
				$sql = "SELECT SUM(bookingQuantity) as totalBookingQty FROM engineering_booking WHERE inventoryId LIKE '".$inventoryId."' AND bookingStatus = 0";
				$queryBooking = $db->query($sql);
				if($queryBooking->num_rows > 0)
				{
					$resultBooking = $queryBooking->fetch_array();
					$totalBookingQty = $resultBooking['totalBookingQty'];
					if($totalBookingQty==NULL) $totalBookingQty = 0;
				}
				
				$totalWithdrawalQty = 0;
				$sql = "SELECT SUM(withdrawMaterialQuantity) as totalWithdrawalQty FROM warehouse_materialwithdrawal WHERE withdrawMaterialId LIKE '".$inventoryId."'";
				$queryMaterialWithdrawal = $db->query($sql);
				if($queryMaterialWithdrawal->num_rows > 0)
				{
					$resultMaterialWithdrawal = $queryMaterialWithdrawal->fetch_array();
					$totalWithdrawalQty = $resultMaterialWithdrawal['totalWithdrawalQty'];
					if($totalWithdrawalQty==NULL) $totalWithdrawalQty = 0;
				}
				
				if(!isset($inventoryUsage[$inventoryId]))	$inventoryUsage[$inventoryId] = 0;
				
				$stock = $inventoryQuantity - ($totalBookingQty + $totalWithdrawalQty);
				
				$stock -= ceil($inventoryUsage[$inventoryId]);
				
				$qtyPerSheet = computeQtyPerSheet($x,$y,$dataThree,$dataFour,$blankingProcess);
				$requirement = ($qtyPerSheet > 0) ? $workingQuantity / $qtyPerSheet : 0; 
				
				if(($stock > $requirement) AND $requirement > 0)
				{
					$inventoryUsage[$inventoryId] += $requirement;
					
					$materialTable = "
							<td>".$inventoryId."</td>
							<td>".$supplierAlias."</td>
							<td>".$dataOne."</td>
							<td>".$dataThree."</td>
							<td>".$dataFour."</td>
							<td>".$dataFive."</td>
							<td>".$stock."</td>
							<td>".$qtyPerSheet."</td>
							<td>".$requirement."</td>
							<td>".$blankingProcess."</td>
					";
					
					$purchaseAlarmFlag = 0;
					
					break;
				}
			}
		}
		
		if($purchaseAlarmFlag == 1)
		{
			$sql = "SELECT poContentId, poNumber, dataOne, dataTwo, dataThree, dataFour, dataFive, itemQuantity FROM purchasing_pocontents WHERE itemStatus = 0 AND ".$dataOneWhere." AND dataTwo = ".$metalThickness." AND dataThree >= ".$itemX." AND dataFour >= ".$itemY." AND dataFive LIKE '".$treatmentName."' ORDER BY (dataThree * dataFour)";
			$queryOpenPoList = $db->query($sql);
			if($queryOpenPoList->num_rows > 0)
			{
				while($resultOpenPoList = $queryOpenPoList->fetch_array())
				{
					$poContentId = $resultOpenPoList['poContentId'];
					//~ $supplierAlias = $resultOpenPoList['supplierAlias'];
					$poNumber = $resultOpenPoList['poNumber'];
					$dataOne = $resultOpenPoList['dataOne'];
					$dataTwo = $resultOpenPoList['dataTwo'];
					$dataThree = $resultOpenPoList['dataThree'];
					$dataFour = $resultOpenPoList['dataFour'];
					$dataFive = $resultOpenPoList['dataFive'];
					$poQuantity = $resultOpenPoList['itemQuantity'];
					
					if(!isset($poContentUsage[$poContentId]))	$poContentUsage[$poContentId] = 0;
				
					$stock = $poQuantity;
					
					$stock -= ceil($poContentUsage[$poContentId]);
					
					$qtyPerSheet = computeQtyPerSheet($x,$y,$dataThree,$dataFour,$blankingProcess);
					$requirement = ($qtyPerSheet > 0) ? $workingQuantity / $qtyPerSheet : 0; 
					
					if($stock > $requirement)
					{
						$poContentUsage[$poContentId] += $requirement;
						
						$materialTable = "
								<td>".$poContentId."-".$poNumber."</td>
								<td>".$supplierAlias."</td>
								<td>".$dataOne."</td>
								<td>".$dataThree."</td>
								<td>".$dataFour."</td>
								<td>".$dataFive."</td>
								<td>".$stock."</td>
								<td>".$qtyPerSheet."</td>
								<td>".$requirement."</td>
								<td>".$blankingProcess."</td>
						";						
						
						$purchaseAlarmFlag = 0;
						
						break;
					}
				}
			}
		}
		
		if($purchaseAlarmFlag == 1)
		{
			$materialTable = "";
			if($treatmentId == 0)
			{
				$requirement = 0;
				$lotNumberForPO = "'".$lotNumber."'";
				
				if(in_array('2024T3',$metalTypeArray) OR in_array('2024-T3',$metalTypeArray))
				{
					$materialTypeWhere = "materialType IN('2024T3','2024-T3',".implode(",",$metalTypeArray).")";
				}
				else if(in_array("'MS2007'",$metalTypeArray) OR in_array("'MS2009'",$metalTypeArray))
				{
					//~ $materialTypeWhere = "materialType IN('SS Wire Cloth 316',".implode(",",$metalTypeArray).")";
				}
				else
				{
					$materialTypeWhere = "materialType IN(".implode(",",$metalTypeArray).")";
				}
				
				$length = $width = 0;
				
				$suppliermaterialIDArray = array();
				//~ $sql = "SELECT suppliermaterialID FROM purchasing_materialtype WHERE ".$materialTypeWhere."";
				$sql = "SELECT suppliermaterialID FROM purchasing_materialtype WHERE materialType LIKE '".$metalType."'";
				$queryMaterialType = $db->query($sql);
				if($queryMaterialType->num_rows > 0)
				{
					while($resultMaterialType = $queryMaterialType->fetch_array())
					{
						$suppliermaterialIDArray[] = $resultMaterialType['suppliermaterialID'];
					}
					
					$sql = "SELECT materialId, length, width FROM purchasing_material WHERE materialTypeId IN(".implode(",",$suppliermaterialIDArray).") AND thickness = ".$metalThickness." ORDER BY (length * width)";
					$queryMaterial = $db->query($sql);
					if($queryMaterial->num_rows > 0)
					{
						while($resultMaterial = $queryMaterial->fetch_array())
						{
							$materialId = $resultMaterial['materialId'];
							$length = $resultMaterial['length'];
							$width = $resultMaterial['width'];
							
							$status = 2;//No Price List Data Or No Default
							
							$supplyId = '';
							$supplyMOQ = 0;
							$sql = "SELECT listId, supplyId, supplyMOQ FROM purchasing_supplies WHERE supplyId = ".$materialId." AND supplyType = 1 AND defaultFlag = 1";
							$querySupplies = $db->query($sql);
							if($querySupplies->num_rows > 0)
							{
								$resultSupplies = $querySupplies->fetch_array();
								$supplyId = $resultSupplies['supplyId'];
								$listId = $resultSupplies['listId'];
								$supplyMOQ = $resultSupplies['supplyMOQ'];
								
								$qtyPerSheet = computeQtyPerSheet($x,$y,$length,$width,$blankingProcess);
								$requirement = ($qtyPerSheet > 0) ? $workingQuantity / $qtyPerSheet : 0;
								
								$forPOLot = "";
								
								if($requirement==0) continue;
								
								$sql = "SELECT lotNumber, workingQuantity FROM ppic_lotlist WHERE poId = 0 AND partId = ".$supplyId." AND identifier = 4 AND status = 1";
								$queryLotForPO = $db->query($sql);
								if($queryLotForPO->num_rows > 0)
								{
									while($resultLotForPO = $queryLotForPO->fetch_array())
									{
										$forPOLot = $resultLotForPO['lotNumber'];
										$forPOQty = $resultLotForPO['workingQuantity'];
										
										$processRemarksWhere = ($PVC==1) ? "AND processRemarks LIKE 'w/PVC'" : "AND processRemarks NOT LIKE 'w/PVC'";
										
										$sql = "SELECT lotNumber FROM ppic_workschedule WHERE lotNumber LIKE '".$forPOLot."' AND processCode = 403 ".$processRemarksWhere." LIMIT 1";
										$queryWorkSchedule = $db->query($sql);
										if($queryWorkSchedule->num_rows > 0)
										{
											break;
										}
									}
								}
								else
								{
									if($supplyId!='')
									{
										$forPOLot = getLotNumber();
										$repeatFlag = 1;
										while($repeatFlag == 1)
										{
											$repeatFlag = 0;
											$sql = "INSERT INTO	ppic_lotlist
															(	lotNumber,			poId,	partId, 			parentLot,	partLevel,	workingQuantity,	identifier, dateGenerated,	status,		bookingStatus)
													VALUES	(	'".$forPOLot."',	0,		'".$supplyId."',	'',			0,			'0',				4,			now(),			1,			0)";
											$queryInsert = $db->query($sql);
											if(!$queryInsert)
											{
												$mysqliError = $db->error;
												if(strstr($mysqliError,'Duplicate entry'))
												{
													$lot = getLotNumber();
													$repeatFlag = 1;
												}
											}
										}
									}
								}
								
								if($forPOLot!="")
								{
									$requireQuantity = 0;
									$sql = "SELECT SUM(requirement) as requireQuantity FROM system_formaterialpo WHERE itemLotNumber LIKE '".$forPOLot."'";
									$queryForMaterialPo = $db->query($sql);
									if($queryForMaterialPo->num_rows > 0)
									{
										$resultForMaterialPo = $queryForMaterialPo->fetch_array();
										$requireQuantity = ceil($resultForMaterialPo['requireQuantity']);
									}
									
									if($supplyMOQ >= $requireQuantity) $requireQuantity = $supplyMOQ;
									
									$sql = "UPDATE ppic_lotlist SET workingQuantity = '".$requireQuantity."' WHERE lotNumber LIKE '".$forPOLot."' LIMIT 1";
									$queryUpdate = $db->query($sql);
									
									$materialTable = "
											<td>".$forPOLot."</td>
											<td></td>
											<td></td>
											<td>".$length."</td>
											<td>".$width."</td>
											<td></td>
											<td></td>
											<td>".$qtyPerSheet."</td>
											<td>".$requirement."</td>
											<td>".$blankingProcess."</td>
									";
									
									$status = 3;//No Price;
									$sql = "SELECT priceId FROM purchasing_price WHERE listId = ".$listId." AND status = 2 LIMIT 1";
									$queryPrice = $db->query($sql);
									if($queryPrice->num_rows > 0)
									{
										$status = 4;//For PO
										
										$targetFinish = addDays(2);
										$processRemarks = ($PVC==1) ? "w/PVC" : "";
										
										$sql = "SELECT id FROM ppic_workschedule WHERE lotNumber LIKE '".$forPOLot."' AND processCode = 403 LIMIT 1";
										$queryWorkSchedule = $db->query($sql);
										if($queryWorkSchedule->num_rows == 0)
										{
											$sql = "INSERT INTO `ppic_workschedule`
															(	`lotNumber`,		`processCode`,		`processOrder`,	`targetFinish`,			`processRemarks`,		`actualFinish`,	`status`,	`employeeId`,	`processSection`,	`availability`)
													VALUES	(	'".$forPOLot."',	403,				1,				'".$targetFinish."',	'".$processRemarks."',	'',				0,			'',				5,					1)
												";
											$queryInsert = $db->query($sql);									
										}
									}
									break;
								}
							}
						}
						//~ if($requirement==0)	$status = 1;//No Material Dimension
					}
					else
					{
						$status = 1;//No Material Dimension
					}
				}
				else
				{
					$status = 0;//No Material Type
				}
				
				$sql = "SELECT lotNumber FROM system_formaterialpo WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
				$queryForMaterialPo = $db->query($sql);
				if($queryForMaterialPo->num_rows > 0)
				{
					$sql = "UPDATE	`system_formaterialpo`
							SET		`metalType`='".$metalType."',
									`metalThickness`='".$metalThickness."',
									`metalLength`='".$length."',
									`metalWidth`='".$width."',
									`itemLotNumber`='".$forPOLot."',
									`requirement`='".$requirement."',
									`status`='".$status."'
							WHERE	`lotNumber` LIKE '".$lotNumber."' LIMIT 1";
					$queryUpdate = $db->query($sql);
				}
				else
				{
					$sql = "INSERT INTO `system_formaterialpo`
									(	`lotNumber`,		`metalType`,		`metalThickness`,		`metalLength`,	`metalWidth`,	`itemLotNumber`,	`requirement`,		`status`)
							VALUES	(	'".$lotNumber."',	'".$metalType."',	'".$metalThickness."',	'".$length."',	'".$width."',	'".$forPOLot."',	'".$requirement."',	'".$status."')";
					$queryInsert = $db->query($sql);
				}
				
				$listIdArray = array();
				$sql = "SELECT listId FROM system_formaterialpo WHERE metalType LIKE '".$metalType."' AND metalThickness LIKE '".$metalThickness."' AND metalLength = ".$length." AND metalWidth = ".$width." AND status < 4 ORDER BY listId";
				$queryForMaterialPo = $db->query($sql);
				if($queryForMaterialPo->num_rows > 0)
				{
					while($resultForMaterialPo = $queryForMaterialPo->fetch_array())
					{
						$listIdArray[] = $resultForMaterialPo['listId'];
					}
					
					$notificationIdArray = array();
					$sql = "SELECT notificationId FROM system_notificationdetails WHERE notificationKey IN(".implode(",",$listIdArray).") AND notificationType = 2";
					$queryNotificationDetails = $db->query($sql);
					if($queryNotificationDetails->num_rows > 0)
					{
						while($resultNotificationDetails = $queryNotificationDetails->fetch_array())
						{
							$notificationIdArray[] = $resultNotificationDetails['notificationId'];
						}
					}
					
					$notificationDetail = "For Material PO Pending (".$metalType." t.".$metalThickness.")";
					$notificationLink = "/Purchasing Management System/Material PO Management Software/gerald_forMaterialPoList.php?listId=".$listIdArray[0];
					
					$sql = "SELECT listId FROM system_notification WHERE notificationId IN(".implode(",",$notificationIdArray).") AND notificationStatus = 0 LIMIT 1";
					$queryNotification = $db->query($sql);
					if($queryNotification->num_rows == 0)
					{
						$sql = "INSERT INTO `system_notificationdetails`
										(	`notificationDetail`,		`notificationKey`,		`notificationLink`,			`notificationType`)
								VALUES	(	'".$notificationDetail."',	'".$listIdArray[0]."',	'".$notificationLink."',	'2')";
						$queryInsert = $db->query($sql);
						
						$sql = "SELECT max(notificationId) AS max FROM system_notificationdetails";
						$query = $db->query($sql);
						$result = $query->fetch_array();
						$notificationId = $result['max'];
						
						$sql = "INSERT INTO `system_notification`
										(	`notificationId`,		`notificationTarget`,	`notificationStatus`,	`targetType`)
								VALUES	(	'".$notificationId."',	'5',					'0',					'0')";
						$queryInsert = $db->query($sql);
					}
				}
				
				$listIdArray = array();
				$sql = "SELECT listId FROM system_formaterialpo WHERE metalType LIKE '".$metalType."' AND metalThickness LIKE '".$metalThickness."' AND metalLength = ".$length." AND metalWidth = ".$width." AND status = 4 ORDER BY listId";
				$queryForMaterialPo = $db->query($sql);
				if($queryForMaterialPo->num_rows > 0)
				{
					while($resultForMaterialPo = $queryForMaterialPo->fetch_array())
					{
						$listIdArray[] = $resultForMaterialPo['listId'];
					}
					
					$notificationIdArray = array();
					$sql = "SELECT notificationId FROM system_notificationdetails WHERE notificationKey IN(".implode(",",$listIdArray).") AND notificationType = 2";
					$queryNotificationDetails = $db->query($sql);
					if($queryNotificationDetails->num_rows > 0)
					{
						while($resultNotificationDetails = $queryNotificationDetails->fetch_array())
						{
							$notificationIdArray[] = $resultNotificationDetails['notificationId'];
						}
					}
					
					$sql = "UPDATE system_notification SET notificationStatus = 1 WHERE notificationId IN(".implode(",",$notificationIdArray).")";
					$queryUpdate = $db->query($sql);					
				}				
			}
			
			if($materialTable == "")
			{
				$materialTable = "<th colspan='9'>Purchase Alarm ".$treatmentName."</th>";
			}
		}
		
		//~ echo "
			//~ <tr>
				//~ <td>".$lotNumber."</td>
				//~ <td>".$workingQuantity."</td>
				//~ <td>".$metalType."</td>
				//~ <td>".$metalThickness."</td>
				//~ <td>".$treatmentName."</td>
				//~ <td>".$x."</td>
				//~ <td>".$y."</td>
				//~ ".$materialTable."
			//~ </tr>
		//~ ";
		
		return $lotNumberForPO;
	}		

	function getSetUpTime($partId,$processCode,$sectionId,$lotNumber)
	{
		include('PHP Modules/mysqliConnection.php');

		$customerId = "";
		$partNumber = "";
		$x = $y = 0;
		$sql = "SELECT partNumber, customerId, x, y FROM cadcam_parts WHERE partId = ".$partId." LIMIT 1";
		$queryParts = $db->query($sql);
		if($queryParts->num_rows > 0)
		{
			$resultParts = $queryParts->fetch_array();
			$partNumber = $resultParts['partNumber'];
			$customerId = $resultParts['customerId'];
			$x = $resultParts['x'];
			$y = $resultParts['y'];
		}	
		
		// ----------------------------------------- Anthony Working Time ---------------------------------------
		$standardTime = $stSetup = 0;
		$sql = "SELECT processUnitId, unitId FROM cadcam_processUnits WHERE processCode = ".$processCode." ";
		$getProcessUnit = $db->query($sql);
		if($getProcessUnit->num_rows > 0)
		{
			while($getProcessUnitResult = $getProcessUnit->fetch_array())
			{
				$unitName = $unitValue = '';
				$sql = "SELECT unitName, unitValue FROM cadcam_units WHERE unitID = ".$getProcessUnitResult['unitId']." ";
				$getUnits = $db->query($sql);
				if($getUnits->num_rows > 0)
				{
					$getUnitsResult = $getUnits->fetch_array();
					$unitName = $getUnitsResult['unitName'];
					$unitValue = $getUnitsResult['unitValue'];
				}
				
				$unitCount = 0;
				$sql = "SELECT unitCount FROM cadcam_standardtime WHERE partID = ".$partId." AND processUnitId = ".$getProcessUnitResult['processUnitId']." ";
				$getUnitCount = $db->query($sql);
				if($getUnitCount->num_rows > 0)
				{
					$getUnitCountResult = $getUnitCount->fetch_array();
					$unitCount = $getUnitCountResult['unitCount'];
				}
				
				if(preg_match("/(Set-up|Program Loading|Tools)/",$unitName))
				{
					$stSetup += ($unitCount * $unitValue);
				}
			}
		}

		return $stSetup;
	}

	function getStandardTime2($partId,$processCode,$sectionId,$lotNumber)
	{
		include('PHP Modules/mysqliConnection.php');

		$customerId = "";
		$partNumber = "";
		$x = $y = 0;
		$sql = "SELECT partNumber, customerId, x, y FROM cadcam_parts WHERE partId = ".$partId." LIMIT 1";
		$queryParts = $db->query($sql);
		if($queryParts->num_rows > 0)
		{
			$resultParts = $queryParts->fetch_array();
			$partNumber = $resultParts['partNumber'];
			$customerId = $resultParts['customerId'];
			$x = $resultParts['x'];
			$y = $resultParts['y'];
		}	
		
		// ----------------------------------------- Anthony Working Time ---------------------------------------
		$standardTime = $stSetup = 0;
		$sql = "SELECT processUnitId, unitId FROM cadcam_processUnits WHERE processCode = ".$processCode." ";
		$getProcessUnit = $db->query($sql);
		if($getProcessUnit->num_rows > 0)
		{
			while($getProcessUnitResult = $getProcessUnit->fetch_array())
			{
				$unitName = $unitValue = '';
				$sql = "SELECT unitName, unitValue FROM cadcam_units WHERE unitID = ".$getProcessUnitResult['unitId']." ";
				$getUnits = $db->query($sql);
				if($getUnits->num_rows > 0)
				{
					$getUnitsResult = $getUnits->fetch_array();
					$unitName = $getUnitsResult['unitName'];
					$unitValue = $getUnitsResult['unitValue'];
				}
				
				$unitCount = 0;
				$sql = "SELECT unitCount FROM cadcam_standardtime WHERE partID = ".$partId." AND processUnitId = ".$getProcessUnitResult['processUnitId']." ";
				$getUnitCount = $db->query($sql);
				if($getUnitCount->num_rows > 0)
				{
					$getUnitCountResult = $getUnitCount->fetch_array();
					$unitCount = $getUnitCountResult['unitCount'];
				}

				
				if(preg_match("/(Set-up|Program Loading|Tools)/",$unitName))
				{
					$standardTime += ($unitCount * $unitValue);
					$stSetup += ($unitCount * $unitValue);
				}
				else
				{
					$standardTime += ($unitCount * $unitValue);
				}
			}
		}

		if($sectionId==42)
		{
			if($customerId == 45 OR $customerId == 28)
			{
				$standardTime = $standardTime * 1.5;
			}
		}

		if(in_array($processCode,array(312,430,431,432)))
		{				
			if($processCode == 312)
			{					
				if(in_array($customerId,array(45,49)))
				{
					$standardTime = 240;
				}
				else if(in_array($customerId,array(28,37)))
				{
					$standardTime = 360;
				}
				else
				{
					$standardTime = 600;
				}				
			}
			else if($processCode == 430)
			{					
				$excelPreparation = 10;
				$csv = 5;
				$materialPreparation = 10;
				$makeSheet = 5;
				$nesting = 10;
				$sequence = 5;
				
				$outputReport = 10;
				$saveAsSDD = 10;
				$programNumber = 5;
				
				//~ $checkingQty = 5;	
				$checkingQty = 0;	
				
				if(in_array($customerId,array(28,37)))
				{
					// $nesting = ($nesting * $workingQuantity);
					
					$sql = "SELECT materialSpecId FROM cadcam_materialspecs WHERE materialTypeId IN(SELECT materialTypeId FROM engineering_materialtype WHERE materialType LIKE '1050A%') LIMIT 1";//;cadcam_materialspecs;
					$queryMaterialSpecs = $db->query($sql);
					if($queryMaterialSpecs->num_rows > 0)//1050A Material
					{
						$nesting += 10;
					}
				}
				
				// $sequence = ($sequence * $workingQuantity);
				
				$standardTime = $excelPreparation + $csv + $materialPreparation + $makeSheet + $nesting + $sequence + $outputReport + $saveAsSDD + $programNumber + $checkingQty;
			}
			else
			{
				$standardTime = 60;//1
			}
			
		}
		else if($processCode==136)
		{
			$standardTime = 120;
		}
		// ------------------------------------------ Material Withdrawal -----------------------------------
		
		// ------------------------------------------ Blanking ----------------------------------------------
		if($sectionId==28)
		{
			$excelPreparation = 10;
			$toolChecking = 20;
			$materialPreparation = 20;
			$nesting = 40;
			$searchByLot = 10;
			$searchForMaterialId = 10;
			$programPreparation = 10;
			
			if(in_array($customerId,array(28,37)))
			{
				// $nesting = ($nesting * $workingQuantity);
				$standardTime = $excelPreparation + $materialPreparation + $nesting + $searchByLot + $searchForMaterialId + $programPreparation;
			}
			else if(in_array($customerId,array(45,49)))
			{
				if($partNumber[0]=='P')
				{
					// $nesting = ($nesting * $workingQuantity);
					$standardTime = $excelPreparation + $toolChecking + $materialPreparation + $nesting + $searchByLot + $searchForMaterialId + $programPreparation;
				}
				else if($partNumber[0]=='7' OR $customerId==49)
				{
					$nesting += 35;
					// $nesting = ($nesting * $workingQuantity);
					$standardTime = $excelPreparation + $toolChecking + $materialPreparation + $nesting + $searchByLot + $searchForMaterialId;
				}
			}
			else
			{
				$nesting += 200;
				//~ $nesting = ($nesting * $workingQuantity);
				$programPreparation += 20;
				$standardTime = $excelPreparation + $materialPreparation + $nesting + $searchByLot + $searchForMaterialId + $programPreparation;
			}			
		}			
		
		// Ace Sandoval : Execute To Adjust ST		
		// ------------------ Welding Assembly ---------------------
		if($sectionId == 6)
		{
			//$standardTime = $standardTime * (2.5);
		}
		// ------------------ End of Welding Assembly ---------------------
		
		// ------------------ Painting ----------------------------------
		if($sectionId == 12)
		{
			if($customerId != 9 AND $processCode == 218)
			{
				$standardTime = $standardTime * (3);
			}
		}
		// ----------------- End Of Painting -------------------
		
		// ----------------- Ace : Compute ST For Sanding (Repair)
		if($processCode==117 AND $standardTime==0)
		{
			$standardTime = 300;
		}

		return $standardTime;
	}
	
	// ------------------------------------------------- Compute Standard Time --------------------------------------------------------------------
	function getStandardTime($partId,$processCode,$workingQuantity,$sectionId,$lotNumber,$returnType = 0)
	{
		/*	Used in the following files/directories:
		 *	1.	/Employee Performance Software/
		 *	2.	/Section Work Schedule Graph/
		 */
		 
		include('PHP Modules/mysqliConnection.php');
		
		$sqlCadcamPartProcess = "SELECT setupTime, cycleTime FROM cadcam_partprocess WHERE partId=".$partId." AND processCode = ".$processCode." AND cycleTime > 0";
		$queryPartProcess = $db->query($sqlCadcamPartProcess);
		if(($_GET['country']=='2' OR $_GET['country']=='1') AND $queryPartProcess AND $queryPartProcess->num_rows>0)
		{
			$resultPartProcess = $queryPartProcess->fetch_assoc();
			$cycleTime = $resultPartProcess['cycleTime'];
			$setupTime = $resultPartProcess['setupTime'];

			if((($sectionId == 4 OR $sectionId == 4) and $workingQuantity > 5) AND $processCode != 352)
			{					
				$workingQuantity = 5;
			}
			
			$standardTime = ($cycleTime * $workingQuantity)+$setupTime;

			if($sectionId==42)
			{
				if($customerId == 45 OR $customerId == 28)
				{
					$standardTime = $standardTime * 1.5;
				}
			}

			// --------------------- CHICHA 06-04-18 INCOMING INSPECTION ---------------------
			if($processCode == 163)
			{
				$standardTime = ($standardTime + 30) * $workingQuantity;
			}
			// --------------------- END INCOMING INSPECTION ---------------------
			
			// ------------------ Painting ----------------------------------
			if($sectionId == 12)
			{
				if($customerId != 9 AND $processCode == 218)
				{
					$standardTime = $standardTime * (3);
				}
			}
            // ----------------- End Of Painting -------------------
            
            // NG VERIFICATION
            if($processCode == 368)
			{
                $standardTime = 120;
            }
		}
		else
		{
			$customerId = "";
			$partNumber = "";
			$x = $y = 0;
			$sql = "SELECT partNumber, customerId, x, y FROM cadcam_parts WHERE partId = ".$partId." LIMIT 1";
			$queryParts = $db->query($sql);
			if($queryParts->num_rows > 0)
			{
				$resultParts = $queryParts->fetch_array();
				$partNumber = $resultParts['partNumber'];
				$customerId = $resultParts['customerId'];
				$x = $resultParts['x'];
				$y = $resultParts['y'];
			}	
			
			// ----------------------------------------- Anthony Working Time ---------------------------------------
			$standardTime = $stSetup = 0;
			$sql = "SELECT processUnitId, unitId FROM cadcam_processUnits WHERE processCode = ".$processCode." ";
			$getProcessUnit = $db->query($sql);
			if($getProcessUnit->num_rows > 0)
			{
				while($getProcessUnitResult = $getProcessUnit->fetch_array())
				{
					$unitName = $unitValue = '';
					$sql = "SELECT unitName, unitValue FROM cadcam_units WHERE unitID = ".$getProcessUnitResult['unitId']." ";
					$getUnits = $db->query($sql);
					if($getUnits->num_rows > 0)
					{
						$getUnitsResult = $getUnits->fetch_array();
						$unitName = $getUnitsResult['unitName'];
						$unitValue = $getUnitsResult['unitValue'];
					}
					
					$unitCount = 0;
					$sql = "SELECT unitCount FROM cadcam_standardtime WHERE partID = ".$partId." AND processUnitId = ".$getProcessUnitResult['processUnitId']." ";
					$getUnitCount = $db->query($sql);
					if($getUnitCount->num_rows > 0)
					{
						$getUnitCountResult = $getUnitCount->fetch_array();
						$unitCount = $getUnitCountResult['unitCount'];
					}
										// ----------------- Rose : Compute ST For Engineering (drawing,DMS,program) --------------
					//~ if($_GET['country']=='1' and ($processCode==298 OR $processCode==462 OR $processCode==506 OR $processCode==508))
					//~ if($_GET['country']=='1' and in_array($processCode,array(298,462,506,508,509,529)))//2018-05-18 add 509,529
					//~ if($_GET['country']=='1' and $sectionId==40)//2018-05-23 change to engineering section only
					if($sectionId==40)//2018-06-25 for PH and JP
					{
						if($processCode==462)
						{
						// 	$sumProcess = 1;
						// 	$sqlCadcamPartProcess = "SELECT count(partId) as sumProcess FROM cadcam_partprocess WHERE partId=".$partId." group by patternId";
						// 	$queryPartProcess = $db->query($sqlCadcamPartProcess);
						// 	if($_GET['country']=='1' AND $queryPartProcess AND $queryPartProcess->num_rows>0)
						// 	{
						// 		$resultPartProcess = $queryPartProcess->fetch_assoc();
						// 		$sumProcess = $resultPartProcess['sumProcess'];
						// 	}
						// 	$standardTime += ($unitValue*$sumProcess);
							$standardTime = $unitValue;
						}
						else
						{
							$standardTime += $unitValue;
						}
					}
					// ----------------- Rose : END Compute ST For Engineering (drawing,DMS,program) --------------
					else
					{
						// --------------------- Ace Sandoval: Special Case For QC ----------------------------------
						if((($sectionId == 4 OR $sectionId == 4) and $workingQuantity > 5) AND $processCode != 352)
						{					
							$workingQuantity = 5;
						}
						
						if(preg_match("/(Set-up|Program Loading|Tools)/",$unitName))
						{
							$standardTime += ($unitCount * $unitValue);
							$stSetup += ($unitCount * $unitValue);
						}
						else
						{
							$standardTime += ($unitCount * $unitValue) * $workingQuantity;// - OLD
							//~ $standardTime += ($unitCount * $unitValue);
						}
					}
				}
			}
            // ----------------------------------------- End of Anthony Working Time ---------------------------------------	
            //~ if($sectionId == 40)
            //~ {
                //~ if($processCode==462)
                //~ {	
                    //~ $standardTime = $standardTime;
                //~ }
            //~ }
            //~ else
            //~ {
                //~ $standardTime = $standardTime * $workingQuantity;
            //~ }
            
            if($sectionId==42)
			{
				if($customerId == 45 OR $customerId == 28)
				{
					$standardTime = $standardTime * 1.5;
				}
			}
			
			// ------------------------------------------ Material Booking ---------------------------------------
			if(in_array($processCode,array(312,430,431,432)))
			{				
				if($processCode == 312)
				{
					if(in_array($customerId,array(45,49)))
					{
						$standardTime = 240;
					}
					else if(in_array($customerId,array(28,37)))
					{
						$standardTime = 360;
					}
					else
					{
						$standardTime = 600;
					}
				}
				else if($processCode == 430)
				{
					if($_GET['country']==1)
					{
						//~ $excelPreparation = 10; //Remove 2018-05-23
						$csv = 5;
						$materialPreparation = 5;// 2018-05-23
						$makeSheet = 5;
						$nesting = 15;//10 change(2017-10-09)
						$sequence = 5;// 2018-05-23
						$outputReport = 5;// 2018-05-23
						$saveAsSDD = 5;// 2018-05-23
						$nestingCheckList = 15;//2018-05-18
						$programNumber = 5;				
					}
					else
					{
						//~ $excelPreparation = 10; //Remove 2018-05-23
						$csv = 5;
						$materialPreparation = 5;
						$makeSheet = 5;
						$nesting = 10;
						$sequence = 10;
						$outputReport = 5;
						$saveAsSDD = 15;
						$nestingchecklist = 15;
						$programNumber = 5;
					}
					
					//~ $checkingQty = 5;	
					$checkingQty = 0;	
					
					if($workingQuantity >= 100)	$nesting = 40;//add(2017-10-09)
					
					if(in_array($customerId,array(28,37)))
					{
						//~ $nesting = ($nesting * $workingQuantity);// remove (2017-10-09)
						
						$sql = "SELECT materialSpecId FROM cadcam_materialspecs WHERE materialTypeId IN(SELECT materialTypeId FROM engineering_materialtype WHERE materialType LIKE '1050A%') LIMIT 1";//;cadcam_materialspecs;
						$queryMaterialSpecs = $db->query($sql);
						if($queryMaterialSpecs->num_rows > 0)//1050A Material
						{
							$nesting += 10;
						}
					}
					
					$sequence = ($sequence * $workingQuantity);
					
					$standardTime = $excelPreparation + $csv + $materialPreparation + $makeSheet + $nesting + $sequence + $outputReport + $saveAsSDD + $nestingCheckList + $programNumber + $checkingQty;
				}
				else
				{
					$standardTime = 60;//1
				}
				
				if($workingQuantity >= 10)
				{
					$standardTime += ($standardTime * 0.2);
				}
			}
			// ------------------------------------------ Material Booking ---------------------------------------
			// ------------------------------------------ Material Withdrawal -----------------------------------
			else if($processCode==136)
			{
				$standardTime = 120 * $workingQuantity;
			}
			
			// ------------------------------------------ Material Withdrawal -----------------------------------
			
			// ------------------------------------------ Blanking ----------------------------------------------
			if($sectionId==28)
			{
				$excelPreparation = 10;
				$toolChecking = 20;
				$materialPreparation = 20;
				$nesting = 40;
				$searchByLot = 10;
				$searchForMaterialId = 10;
				$programPreparation = 10;
				$nestingCheckList = 30;//2018-05-18
				
				//rose feb 20,2018
				if($workingQuantity==0){$workingQuantity=1;}
				//rose feb 20,2018
				
				if(in_array($customerId,array(28,37)))
				{
					$nesting = ($nesting * $workingQuantity);
					$standardTime = $excelPreparation + $materialPreparation + $nesting + $searchByLot + $searchForMaterialId + $programPreparation + $nestingCheckList;
				}
				else if(in_array($customerId,array(45,49)))
				{
					if($partNumber[0]=='P')
					{
						$nesting = ($nesting * $workingQuantity);
						$standardTime = $excelPreparation + $toolChecking + $materialPreparation + $nesting + $searchByLot + $searchForMaterialId + $programPreparation + $nestingCheckList;
					}
					else if($partNumber[0]=='7' OR $customerId==49)
					{
						$nesting += 35;
						$nesting = ($nesting * $workingQuantity);
						$standardTime = $excelPreparation + $toolChecking + $materialPreparation + $nesting + $searchByLot + $searchForMaterialId + $nestingCheckList;
					}
				}
				else
				{
					$nesting += 230;
					//~ $nesting = ($nesting * $workingQuantity);
					$programPreparation += 20;
					$standardTime = $excelPreparation + $materialPreparation + $nesting + $searchByLot + $searchForMaterialId + $programPreparation + $nestingCheckList;
				}			
			}			
			
			// Ace Sandoval : Execute To Adjust ST		
			// ------------------ Welding Assembly ---------------------
			if($sectionId == 6)
			{
				//$standardTime = $standardTime * (2.5);
			}
			// ------------------ End of Welding Assembly ---------------------
			
			// --------------------- CHICHA 06-04-18 INCOMING INSPECTION ---------------------
			if($processCode == 163)
			{
				$standardTime = ($standardTime + 30) * $workingQuantity;
			}
			// --------------------- END INCOMING INSPECTION ---------------------
			
			// ------------------ Painting ----------------------------------
			if($sectionId == 12)
			{
				if($customerId != 9 AND $processCode == 218)
				{
					$standardTime = $standardTime * (3);
				}
			}
			// ----------------- End Of Painting -------------------
			
			// ----------------- Ace : Compute ST For Sanding (Repair)
			if($processCode==117 AND $standardTime==0)
			{
				$standardTime = 300;
			}
			
			if($processCode==496 AND $standardTime==0)//Item Handling
			{
				//$standardTime = 300;//5mins // by ace 2019-04-22
				$standardTime = 5;//5mins
			}
			
			if($processCode==364 AND $standardTime==0)//Inspection Data Input
			{
				//~ $standardTime = 120;//2mins
				$standardTime = 750;//12.5mins
			}
			
			if($processCode==358 AND $standardTime==0)//QA Documentation Checking
			{
				$standardTime = 120;//2mins
			}

			if($processCode==352)
			{
				$standardTime = 585;
            }
            
            // NG VERIFICATION
			if($processCode==368)
			{
				$standardTime = 120;
			}
			//  ---------------------------- Ace Sandoval : Display Data For Debugging -------------------------------
			/*
			echo "<table border = 1><tr>";
				echo "<td width=50>".$partId."</td>";
				echo "<td width=50>".$lotNumber."</td>";
				echo "<td width=50>".$processCode."</td>";
				echo "<td width=50>".$workingQuantity."</td>";
				echo "<td width=50>".$sectionId."</td>";
				echo "<td width=50>".$standardTime."</td>";
				echo "<td width=50>".($standardTime/60)."</td>";
			echo "</tr></table>";
			*/
			//  ---------------------------- End of Ace Sandoval : Display Data For Debugging -------------------------------

			if($processCode == 86)
			{
				$sql = "SELECT bookingId FROM engineering_bookingdetails WHERE lotNumber = '".$lotNumber."' LIMIT 1";
				$queryBooking = $db->query($sql);
				if($queryBooking AND $queryBooking->num_rows > 0)
				{
					$resultBooking = $queryBooking->fetch_assoc();
					$bookingId = $resultBooking['bookingId'];

					$addedSeconds = 0;
					$sql = "SELECT bookingQuantity FROM engineering_booking WHERE bookingId = ".$bookingId;
					$queryBookingId = $db->query($sql);
					if($queryBookingId AND $queryBookingId->num_rows > 0)
					{
						$resultBookingId = $queryBookingId->fetch_assoc();
						$bookingQuantity = $resultBookingId['bookingQuantity'];
						$addedSeconds = $bookingQuantity * 60;
					}

					$sql = "SELECT lotNumber FROM engineering_bookingdetails WHERE quantity != 0 AND status != 0 AND bookingId = ".$bookingId;
					$queryCountLot = $db->query($sql);
					$lotCount = $queryCountLot->num_rows;

					if($lotCount > 0)
					{
						$stAdd = round(($addedSeconds / $lotCount), 2);
						$standardTime = $standardTime + $stAdd;
					}

				}
			}
		}
		
		if($returnType==0)
		{
			return $standardTime;
		}
		else if($returnType==1)
		{
			return $stSetup;
		}
		else if($returnType==2)
		{
			return array($standardTime,$stSetup);
		}
	}
	// ------------------------------------------------- End of Compute Standard Time --------------------------------------------------------------------		
	
	function generateCode($textValue,$prefix,$textLength)
	{
		$zeroCount = $textLength - strlen($textValue);
		$text = str_replace(" ","_",$prefix);
		while($zeroCount > 0)
		{
			$text .= "0";
			$zeroCount--;
		}
		$text .= $textValue;
		
		return $text;
	}
	
	function toolTip($toolTipId)
	{
		include('PHP Modules/mysqliConnection.php');
		
		$switch = ($_SESSION['switcher'] == 'on') ? 1 : 0;
		
		$languageFlag = (isset($_SESSION['language'])) ? $_SESSION['language'] : 1;
		$sql = "SELECT languageFlag FROM hr_employee WHERE idNumber LIKE '".$_SESSION['idNumber']."' AND status = 1 LIMIT 1";
		$queryEmployee = $db->query($sql);
		if($queryEmployee AND $queryEmployee->num_rows > 0)
		{
			$resultEmployee = $queryEmployee->fetch_assoc();
			$languageFlag = $resultEmployee['languageFlag'];
		}
		
		$textTableField = ($languageFlag==1) ? 'toolTipTextOne' : 'toolTipTextTwo';
		
		$textOne = '';
		$sql = "SELECT ".$textTableField." FROM system_software WHERE displayId LIKE '".$toolTipId."' LIMIT 1";
		$queryTextOne = $db->query($sql);
		if($queryTextOne AND $queryTextOne->num_rows > 0)
		{
			$resultTextOne = $queryTextOne->fetch_assoc();
			$textOne = $resultTextOne[$textTableField];
		}
		
		$editTable = ($switch==1) ? "data-tooltip-editable data-tooltip-id='".$toolTipId."' data-tooltip-field='".$textTableField."'" : "";
		
		echo "data-tooltip data-tooltip-content='".$textOne."' ".$editTable;
	}
	
	// -------------------------------------------------- Partial Lot Number Detail Tree Functions -------------------------------------------------- //
	function lotDescendantsCount(&$descendantsCount,$recursiveLot)
	{
		include('PHP Modules/mysqliConnection.php');
		
		$sql = "SELECT lotNumber FROM ppic_prslog WHERE sourceLotNumber LIKE '".$recursiveLot."' AND type != 7";
		$queryPrsLog = $db->query($sql);
		if($queryPrsLog AND $queryPrsLog->num_rows > 0)
		{
			while($resultPrsLog = $queryPrsLog->fetch_assoc())
			{
				$lotNumber = $resultPrsLog['lotNumber'];
				$descendantsCount++;
				lotDescendantsCount($descendantsCount,$lotNumber);
			}
		}
	}
	
	function partialLotNumberDepth($recursiveLot,$depth)
	{
		include('PHP Modules/mysqliConnection.php');
		
		$depthTemp = $depth;
		$sql = "SELECT lotNumber FROM ppic_prslog WHERE sourceLotNumber LIKE '".$recursiveLot."' AND type != 7";
		$queryPrsLog = $db->query($sql);
		if($queryPrsLog AND $queryPrsLog->num_rows > 0)
		{
			$descendantsCount += $queryPrsLog->num_rows;
			$depth++;
			while($resultPrsLog = $queryPrsLog->fetch_assoc())
			{
				$lotNumber = $resultPrsLog['lotNumber'];
				$depthTemp1 = partialLotNumberDepth($lotNumber,$depth);
				if($depthTemp1 > $depthTemp)
				{
					$depthTemp = $depthTemp1;
				}
			}
		}
		
		return $depthTemp;
	}
	
	function partialLotNumberTree($poId)
	{
		include('PHP Modules/mysqliConnection.php');
		
		if($poId!='')
		{
			$sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId = ".$poId." AND identifier = 1 AND partLevel = 1 LIMIT 1";
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				$resultLotList = $queryLotList->fetch_assoc();
				$lotNumber = $resultLotList['lotNumber'];
			
				$poQuantity = 0;
				$sql = "SELECT poQuantity FROM sales_polist WHERE poId = ".$poId." LIMIT 1";
				$queryPoList = $db->query($sql);
				if($queryPoList AND $queryPoList->num_rows > 0)
				{
					$resultPoList = $queryPoList->fetch_assoc();
					$poQuantity = $resultPoList['poQuantity'];
				}			
				
				$lotNumberArray[0][] = $lotNumber;
				$recursiveLot = $lotNumber;
				
				$usedLotNumberArray = array();
				
				//~ $depth = partialLotNumberDepth($lotNumber,$depth);;
				$repeatFlag = 1;
				$level = 0;
				//~ while($level < $depth)
				while($repeatFlag == 1)
				{
					$repeatFlag = 0;
					$depth = $index = $level;
					$level++;
					$lotNumberArray[$level] = array();
					foreach($lotNumberArray[$index] as $recursiveLot)
					{
						$sqlFilter = (count($usedLotNumberArray) > 0) ? "AND lotNumber NOT IN('".implode("','",$usedLotNumberArray)."')" : "";
						
						$sql = "SELECT lotNumber FROM ppic_prslog WHERE sourceLotNumber LIKE '".$recursiveLot."' AND sourceLotNumber != '' AND type != 7 ".$sqlFilter." LIMIT 1";
						$queryPrsLog = $db->query($sql);
						if($queryPrsLog AND $queryPrsLog->num_rows > 0)
						{
							$resultPrsLog = $queryPrsLog->fetch_assoc();
							$lote = $resultPrsLog['lotNumber'];
							
							$lotNumberArray[$level][] = $lote;
							$lotNumberArray[$level][] = $recursiveLot;
							
							$usedLotNumberArray[] = $lote;
							$repeatFlag = 1;
						}
						else
						{
							//~ $lotNumberArray[$level][] = "";
							$lotNumberArray[$level][] = $recursiveLot.".";
						}
					}
				}
				
				$partialQuantityArray[$lotNumber] = $poQuantity;
				
				$colspanMax = 1;
				lotDescendantsCount($colspanMax,$lotNumber);
				$colspanCountArray[$lotNumber] = $colspanMax;
				$sql = "SELECT lotNumber, partialQuantity FROM ppic_prslog WHERE lotNumber LIKE '".$lotNumber."-%' AND type != 7 AND sourceLotNumber!=''";
				$queryPrsLog = $db->query($sql);
				if($queryPrsLog AND $queryPrsLog->num_rows > 0)
				{
					while($resultPrsLog = $queryPrsLog->fetch_assoc())
					{
						$lote = $resultPrsLog['lotNumber'];
						$partialQuantityArray[$lote] = $resultPrsLog['partialQuantity'];
						$colspanCount1 = 1;
						lotDescendantsCount($colspanCount1,$lote);
						$colspanCountArray[$lote] = $colspanCount1;
					}
				}
				
				$tempLotArray = $lotArray = array();
				
				echo "<table border='1' style='width:100%; font-family:Roboto;'>";
				if(count($lotNumberArray) > 0)
				{
					foreach($lotNumberArray as $level => $lotNoArray)
					{
						echo "<tr>";
						foreach($lotNoArray as $lot)
						{
							if(!in_array($lot,$tempLotArray))
							{
								$colspanCount = $colspanCountArray[$lot];
								$tempLotArray[] = $lot;
								$prevColspanCount = $colspanCount;
								
								$partialQuantity = $partialQuantityArray[$lot];
								$prevPartialQuantity = $partialQuantity;
								
								$lastColspanCount = $colspanCount;
							}
							else
							{
								$colspanCount = $colspanCountArray[$lot];
								$colspanCount -= $prevColspanCount;
								$colspanCountArray[$lot] = $colspanCount;
								
								$partialQuantity = $partialQuantityArray[$lot];
								$partialQuantity -= $prevPartialQuantity;
								$partialQuantityArray[$lot] = $partialQuantity;
							}
							$cellData = "";
							if($lot!='')
							{
								$gFlag = 0;
								if(strstr($lot,".")!==FALSE)
								{
									$lot = str_replace(".","",$lot);
									$partialQuantity = $partialQuantityArray[$lot];
									$gFlag = 1;
								}
								else
								{
									$cellData = $lot."<br>(".$partialQuantity.")";
								}
								if(($colspanCount==1 OR $gFlag==1) AND $level==$depth)
								{
									//~ $lotArray[] = $lot;
									$lotArray[] = $lot."<br>(".$partialQuantity.")";
								}
							}
							
							echo "<th colspan='".$colspanCount."' style='font-size:13px;'>".$cellData."</th>";
							//~ echo "<th colspan='".$colspanCount."'>".$cellData." ".$colspanCount." ".$level." ".$depth."</th>";
						}
						echo "</tr>";
					}
					echo "<tr><th colspan='".$colspanMax."' style='//height:50px;'></th></tr>";
					echo "<tr><th style='min-width:100px; font-size:13px;'>".implode("</th><th style='min-width:100px; font-size:13px;'>",$lotArray)."</th></tr>";
				}
				echo "</table>";
			}
		}
	}
	
	function generateDescendantsLotTree(&$tempArray,$rootQuantity,$recursiveLot,&$lastPartialLotArray)
	{
		include('PHP Modules/mysqliConnection.php');
		
		$sqlFilter = (count($tempArray) > 0) ? "AND lotNumber NOT IN('".implode("','",$tempArray)."')" : "";
		
		//~ echo "<br>".$sql = "SELECT lotNumber, partialQuantity FROM ppic_prslog WHERE sourceLotNumber LIKE '".$recursiveLot."' AND type != 7 ".$sqlFilter." LIMIT 1";
		//~ $sql = "SELECT lotNumber, partialQuantity, SUBSTRING_INDEX(sourceLotNumber,'-',-1) as leftLot, SUBSTRING_INDEX(lotNumber,'-',-1) as rightLot FROM ppic_prslog WHERE sourceLotNumber LIKE '".$recursiveLot."' AND type != 7 ".$sqlFilter." LIMIT 1";
		$counterX = 0;
		$sql = "SELECT lotNumber, partialQuantity, type, SUBSTRING_INDEX(sourceLotNumber,'-',-2) as leftLot, SUBSTRING_INDEX(lotNumber,'-',-2) as rightLot FROM ppic_prslog WHERE sourceLotNumber LIKE '".$recursiveLot."' AND type != 7 ".$sqlFilter." LIMIT 1";
		$queryPrsLog = $db->query($sql);
		if($queryPrsLog AND $queryPrsLog->num_rows > 0)
		{
			echo "<ul>";
			while($resultPrsLog = $queryPrsLog->fetch_assoc())
			{
				$lotNumber = $resultPrsLog['lotNumber'];
				$partialQuantity = $resultPrsLog['partialQuantity'];
				$type = $resultPrsLog['type'];
				$quantity = $rootQuantity - $partialQuantity; 
				$tempArray[] = $lotNumber;
				
				$leftLot = $recursiveLot;
				$rightLot = $lotNumber;
				
				$leftLot = $resultPrsLog['leftLot'];
				$rightLot = $resultPrsLog['rightLot'];
				
				if(substr_count($recursiveLot,'-') < 3)
				{
					$leftLotArray = explode("-",$recursiveLot);
					$leftLot = $leftLotArray[3];
				}
				else
				{
					$leftLotEx = explode("-",$leftLot);
					$leftLot = $leftLotEx[1];
				}

				$rightLotArray = explode('-', $rightLot);
				$rightLot = $rightLotArray[1];
				if($leftLot == "") $leftLot = 0;
				if($rightLot == "") $rightLot = 0;

				$leftColor = "background-color:lightgray;";
				$sql = "SELECT id FROM view_workschedule WHERE lotNumber LIKE '".$recursiveLot."' LIMIT 1";
				$queryViewWorkSched = $db->query($sql);
				if($queryViewWorkSched AND $queryViewWorkSched->num_rows > 0)
				{
					//~ $leftColor = "background-color:green;";
					$leftColor = "background-color:#77DD77;";
				}
				
				$borderColorLeft = ($type==5) ? "border:2px solid #FF6961;" : "border:2px solid orange;";
				
				echo "<li>";
				echo "<a data-value='".$recursiveLot."' style='cursor:pointer; ".$leftColor.$borderColorLeft."'>".$leftLot."<br>(".$quantity.")</a>";
				//~ echo "<a href='#' bgcolor='red'>".$leftLot."<br>(".$quantity.")</a>";
				generateDescendantsLotTree($tempArray,$quantity,$recursiveLot,$lastPartialLotArray);
				echo "</li>";
				
				$rightColor = "background-color:lightgray;";
				$sql = "SELECT id FROM view_workschedule WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
				$queryViewWorkSched = $db->query($sql);
				if($queryViewWorkSched AND $queryViewWorkSched->num_rows > 0)
				{
					//~ $rightColor = "background-color:yellowgreen;";
					$rightColor = "background-color:#77DD77;";
				}
				
				$borderColorRight = ($type==5) ? "border:2px solid #FF6961;" : "border:2px solid orange;";
				
				echo "<li>";
				echo "<a style='cursor:pointer; ".$rightColor.$borderColorRight."' data-value='".$lotNumber."'>".$rightLot."<br>(".$partialQuantity.")</a>";
				// echo "<a href='#' onclick=\"window.open('raymond_treeDiagramDatav2.php?lotNumber=".$lotNumber."', '".$lotNumber."','left=1200,top=10,width=450,height=500,toolbar=1,resizable=0')\" style='".$rightColor.$borderColorRight."' data-value='".$rightLot."'>".$rightLot."<br>(".$partialQuantity.")</a>";
				generateDescendantsLotTree($tempArray,$partialQuantity,$lotNumber,$lastPartialLotArray);
				echo "</li>";
				
				//~ echo "<li>";
				//~ echo "<a href='#'>".$leftLot."<br>(".$quantity.")</a>";
				//~ partialLotNumberTree2($tempArray,$quantity,$recursiveLot);
				//~ echo "</li>";
			}
			echo "</ul>";
		}
		else
		{
			$lastPartialLotArray[] = $recursiveLot;
		}		
	}
	
	function partialLotNumberTree2($poId)	
	{
		include('PHP Modules/mysqliConnection.php');
		
		if($poId!='')
		{
			$sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId = ".$poId." AND identifier = 1 AND partLevel = 1 LIMIT 1";
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				$resultLotList = $queryLotList->fetch_assoc();
				$lotNumber = $resultLotList['lotNumber'];
				$explodedLot = explode('-', $lotNumber);
				$lotNumberEx = $explodedLot[3];
				if($lotNumberEx == "") $lotNumberEx = 0;

				$poQuantity = 0;
				$sql = "SELECT poQuantity FROM sales_polist WHERE poId = ".$poId." LIMIT 1";
				$queryPoList = $db->query($sql);
				if($queryPoList AND $queryPoList->num_rows > 0)
				{
					$resultPoList = $queryPoList->fetch_assoc();
					$poQuantity = $resultPoList['poQuantity'];
				}
				
				$url = "raymond_treeDiagramDatav2.php?lotNumber=".$lotNumber;
				$name = $lotNumber;
				$param = "left=1200,top=0,width=450,height=500,toolbar=1,resizable=0";
				$tempArray = $lastPartialLotArray = array();
				echo "<div id='divDiv' float:left;width:100%;'>";
				echo "<center><table border='0' style='border-collapse:collapse;'>";
				echo "<tr><td align='center'>";
				
				echo "
					<div id='divDivTable' class='tree' style='float:left;width:100%;'>
						<ul>
							<li>
								<a style='cursor:pointer;' id='gitna' data-value='".$lotNumber."'>".$lotNumberEx."<br>(".$poQuantity.")</a>";
								// <a style='cursor:pointer;' id='gitna' onclick=\"window.open('".$url."', '".$name."', '".$param."')\">".$lotNumber."<br>(".$poQuantity.")</a>";
								// <a style='cursor:pointer;' id='gitna' onclick=\"window.open('raymond_treeDiagramDatav2.php?lotNumber=".$lotNumber."',  '".$lotNumber."','left=1200,top=0,width=450,height=500,toolbar=1,resizable=0')\">".$lotNumber."<br>(".$poQuantity.")</a>";
								generateDescendantsLotTree($tempArray,$poQuantity,$lotNumber,$lastPartialLotArray);

				echo "
					</div>
				";
				echo "</td></tr>";
				echo "</table></center>";
				echo "</div>";
				$levelGap = '5px';
				?>
				<link rel="stylesheet" href="/Common Data/Templates/Bootstrap/Font Awesome/css/font-awesome.css">
  				<link rel="stylesheet" href="/Common Data/Templates/Bootstrap/Bootstrap 3.3.7/Roboto Font/roboto.css">
				<style>
				div {
			      font-family: Roboto;
			      font-size: 10px;
			      font-weight: bold;
			    }

			    .tree ul {
			      position: relative;
			      padding: 1em 0;
			      white-space: nowrap;
			      margin: 0 auto;
			      text-align: center;
			    }
			    .tree ul::after {
			      content: '';
			      display: table;
			      clear: both;
			    }

			    .tree li {
			      display: inline-block;
			      vertical-align: top;
			      text-align: center;
			      list-style-type: none;
			      position: relative;
			      padding: 1em .5em 0 .5em;
			    }
			    .tree li::before, .tree li::after {
			      content: '';
			      position: absolute;
			      top: 0;
			      right: 50%;
			      border-top: 1px solid #ccc;
			      width: 50%;
			      height: 1em;
			    }
			    .tree li::after {
			      right: auto;
			      left: 50%;
			      border-left: 1px solid #ccc;
			    }
			    .tree li:only-child::after, .tree li:only-child::before {
			      display: none;
			    }
			    .tree li:only-child {
			      padding-top: 0;
			    }
			    .tree li:first-child::before, .tree li:last-child::after {
			      border: 0 none;
			    }
			    .tree li:last-child::before {
			      border-right: 1px solid #ccc;
			      border-radius: 0 5px 0 0;
			    }
			    .tree li:first-child::after {
			      border-radius: 5px 0 0 0;
			    }

			    .tree ul ul::before {
			      content: '';
			      position: absolute;
			      top: 0;
			      left: 50%;
			      border-left: 1px solid #ccc;
			      width: 0;
			      height: 1em;
			    }

			    .tree li a {
			      border: 1px solid #ccc;
			      padding: .5em .75em;
			      text-decoration: none;
			      display: inline-block;
			      border-radius: 5px;
			      color: #333;
			      position: relative;
			      top: 1px;
			    }

			    .tree li a:hover,
			    /*.tree li a:hover + ul li a */
			    {
			      background: #e9453f;
			      color: #fff;
			      border: 1px solid #e9453f;
			    }

			    .tree li a:hover + ul li::after,
			    .tree li a:hover + ul li::before,
			    .tree li a:hover + ul::before,
			    .tree li a:hover + ul ul::before {
			      border-color: #e9453f;
			    }

				.hoverClass {
					background-color: #c8e4f8!important;
					color: #000;
					border:1px solid #94a0b4;
				}

				/*Thats all. I hope you enjoyed it.
				Thanks :)*/	
				</style>			
				<script src='/Common Data/Libraries/Javascript/jQuery 3.1.1/jquery-3.1.1.js'></script>
				<link rel="stylesheet" href="/Common Data/Libraries/Javascript/iziToast-master/dist/css/iziToast.css">
				<script src='/Common Data/Libraries/Javascript/iziToast-master/dist/js/iziToast.js'></script>
				<script>
					$(function(){
						var childwindows = new Array();
						var nameArray = [];
						function openPopup(url, name)
						{
							try
							{
								nameArray.push(name);
								var uniqueNames = [];
								$.each(nameArray, function(i, el){
								    if($.inArray(el, uniqueNames) === -1) uniqueNames.push(el);
								});
								
								var addTop = 0;
								var frameCount = uniqueNames.length;
								if($.inArray(name, uniqueNames) !== -1 && frameCount > 1)
								{
									addTop = (90 * (frameCount - 1));
								}

								console.log(uniqueNames)
								var win = window.open(url, name, "left=1200,top="+addTop+",width=450,height=290,toolbar=1,resizable=0");
								childwindows[childwindows.length] = win;
								
						  		win.focus();
							}
							catch(e)
							{
								alert(e);
						 	}
						}
						 
						window.onunload = function closeChildWin()
						{
							for(var i=0; i<childwindows.length; i++)
							{
								try
								{
						     		childwindows[i].close()
						  		}
						  		catch(e)
						  		{
						   			alert(e);
								}
							}
						}

						$(".tree li a").hover(
							function() {
								var thisObj = $(this);
								var i = 0;
								thisObj.parents("li").each(function() {
									$(this).children("a").prop('class','hoverClass');
									var lotNumber = $(this).children("a").attr("data-value");
									$(this).click(function(event) {
										if(lotNumber != undefined)
										{
											openPopup('raymond_treeDiagramDatav2.php?lotNumber='+lotNumber, lotNumber);
										}
										return false;
									});
									i++;
								});
							}, function() {
								var thisObj = $(this);
								thisObj.parents("li").each(function() {
									$(this).children("a").removeClass("hoverClass");
								});
							}
						);

						var showTree = window.parent.document.getElementById('showTree');
						var counterXZ = window.parent.document.getElementsByClassName('counterXZ');
						
						$(counterXZ).each(function(index, el) {
							var index = $(this).prop("id");

							var lotHover = window.parent.document.getElementById('lotHover'+index);
							$(lotHover).mouseover(function(event) {
								var lot = $(this).prop("class");
							});
						});
					});
				</script>
				<?php
			}
		}
	}
	// ------------------------------------------------ END Partial Lot Number Detail Tree Functions ------------------------------------------------ //
	
//----------------------------------------------------- Gusteng's Functions ---------------------------------------------------------	

	//-------------------------------------------------- RECEIVE ORDER LIST ------------------------------------------------------------------------
	
	function updateProirity()
	{
		include('PHP Modules/mysqliConnection.php');
		
		/*$today 			= date("Y-m-d");
		$tomorrow 		= date("Y-m-d", strtotime("+1 day"));
		$afterTomorrow 	= date("Y-m-d", strtotime("+2 day"));
		$fourthStart 	= date("Y-m-d", strtotime("+3 day"));
		$fourthEnd 		= date("Y-m-d", strtotime("+7 day"));
		$fifthStart 	= date("Y-m-d", strtotime("+8 day"));
		$fifthEnd 		= date("Y-m-d", strtotime("+12 day"));
		
		//$prioDates = array();
		$prioDates[0] 	= "deliveryDate <= '".$today."'";
		$prioDates[1] 	= "deliveryDate = '".$tomorrow."'";
		$prioDates[2]	= "deliveryDate = '".$afterTomorrow."'";
		$prioDates[3]	= "deliveryDate >= '".$fourthStart."' AND deliveryDate <= '".$fourthEnd."'";
		$prioDates[4]	= "deliveryDate >= '".$fifthStart."' AND deliveryDate <= '".$fifthEnd."'";
		
		$urgentFlag = array(5,6,7,8,10);
		$remarks = array('1st Priority','2nd Priority','3rd Priority','4th Priority','5th Priority');
		
		//$tableName = array();
		$tableName[0] = 'view_bendinggroupschedule';
		$tableName[1] = 'view_filinggroupschedule';
		$tableName[2] = 'view_qcgroupschedule';
		$tableName[3] = 'view_engineeringgroupschedule';
		$tableName[4] = 'view_packaginggroupschedule';
		$tableName[5] = 'view_poschedulinggroupschedule';
		$tableName[6] = 'view_wetpaintinggroupschedule';
		$tableName[7] = 'view_powderpaintinggroupschedule';
		$tableName[8] = 'view_lasernestinggroupschedule';						

		for($i=0;$i<COUNT($prioDates);$i++)
		{
			
			$sql = "SELECT DISTINCT poId FROM system_lotlist WHERE ".$prioDates[$i]."";
			// echo $sql."<br><br>";
			$query = $db->query($sql);
			if($query AND $query->num_rows > 0)
			{
				WHILE($result = $query->fetch_assoc())
				{
					$poId = $result['poId'];
					
					$sqlHotList = "SELECT status FROM system_hotlist WHERE poId = ".$poId." ORDER BY inputDate DESC, inputTime DESC LIMIT 1";
					// echo "<br>".$sqlHotList."<br>";
					$queryHotList = $db->query($sqlHotList);
					if($queryHotList AND $queryHotList->num_rows>0)
					{
						$resultHotList = $queryHotList->fetch_assoc();			
						$status = $resultHotList['status'];
						if($status != $urgentFlag[$i])
						{
							$insert = "INSERT INTO system_hotlist (poId, inputDate, inputTime, incharge, status)
										VALUES (".$poId.", NOW(), NOW(), 'system', ".$urgentFlag[$i].")";
							$queryInsert = $db->query($insert);
							
							//rose 2017-08-26
							$updatePriority = "UPDATE system_lotlist SET priorityFlag= ".$urgentFlag[$i]." WHERE poId = ".$poId."";
							$queryUpdatePriority = $db->query($updatePriority);
							
							for($x=0;$x<COUNT($tableName);$x++)
							{
								$sqlPONum = "SELECT lotNumber FROM ppic_lotlist WHERE poId = ".$poId." AND identifier IN(1,2)";
								// echo "<br>".$sqlPONum."<br>";
								$queryPONum = $db->query($sqlPONum);
								if($queryPONum AND $queryPONum->num_rows > 0)
								{
									WHILE($resultPONum = $queryPONum->fetch_assoc())
									{
										$update = "UPDATE ".$tableName[$x]." SET remarks= '".$remarks[$i]."' WHERE lotNumber LIKE '".$resultPONum['lotNumber']."'";
										// echo "<br>".$update."<br>";
										$queryUpdate = $db->query($update);
									}
								}
							}
						}
					}
					else
					{
						$insert = "INSERT INTO system_hotlist (poId, inputDate, inputTime, incharge, status)
									VALUES (".$poId.", NOW(), NOW(), 'system', ".$urgentFlag[$i].")";
						$queryInsert = $db->query($insert);
						
						//rose 2017-08-26
						$updatePriority = "UPDATE system_lotlist SET priorityFlag= ".$urgentFlag[$i]." WHERE poId = ".$poId."";
						$queryUpdatePriority = $db->query($updatePriority);
						
						for($x=0;$x<COUNT($tableName);$x++)
						{
							$sqlPONum = "SELECT lotNumber FROM ppic_lotlist WHERE poId = ".$poId." AND identifier IN(1,2)";
							// echo "<br>".$sqlPONum."<br>";
							$queryPONum = $db->query($sqlPONum);
							if($queryPONum AND $queryPONum->num_rows > 0)
							{
								WHILE($resultPONum = $queryPONum->fetch_assoc())
								{
									$update = "UPDATE ".$tableName[$x]." SET remarks= '".$remarks[$i]."' WHERE lotNumber LIKE '".$resultPONum['lotNumber']."'";
									// echo "<br>".$update."<br>";
									$queryUpdate = $db->query($update);
								}
							}
						}
					}
				}
			}
		}*/
	}
		// --------------------------------------------- End of Third Priority -----------------------------------------------------------
		
	//------------------------------------------------ END OF RECEIVE ORDER LIST ------------------------------------------------------------------------
//------------------------------------------------------ Gusteng's Functions ---------------------------------------------------------		

	function sendArktechMail($account,$password,$from,$fromName,$subject,$bodyText,$destinationAddressArray = array(),$attachPathFile = '',$attachFile = '',$ccAddressArray = array())
	{
		require_once("PHP Modules/phpmailer/class.phpmailer.php");
		
		$email = new PHPMailer();

		//~ $account = "purchasing2@arktech.co.jp";
		//~ $password = "p6183819";
		
		$email->IsSMTP();
		$email->CharSet = 'UTF-8';
		$email->Host = gethostbyname('arktech.co.jp');
		$email->SMTPDebug = 2;
		$email->SMTPAuth= true;
		$email->Port = 587;
		$email->Username= $account;
		$email->Password= $password;
		$email->SMTPSecure = 'tls';
		
		$email->From      = $from;//'you@example.com'
		$email->FromName  = $fromName;//'Your Name'
		$email->Subject   = $subject;//'Message Subject'
		$email->isHTML(true);
		$email->Body      = $bodyText;
		
		if(count($destinationAddressArray) > 0)
		{
			foreach($destinationAddressArray as $eAddress)
			{
				$email->AddAddress($eAddress);
			}
			
			if($attachPathFile!='' AND $attachFile!='')
			{
				$email->AddAttachment($attachPathFile,$attachFile);
			}
			
			if(count($ccAddressArray) > 0)
			{
				foreach($ccAddressArray as $ccEAddress)
				{
					$email->AddCC($ccEAddress);
				}
			}
			
			if(!$email->Send())
			{
				return "Error sending: " . $mail->ErrorInfo;
			}
		}
		else
		{
			return "Error!";
		}
	}
	
	//************************************************** Functions for scheduling/rescheduling (2018-06-04) **************************************************//
	function buildLotNumberTree(&$lotNumberArray,$recursiveLot)
	{
		include('PHP Modules/mysqliConnection.php');
		
		$sql = "SELECT lotNumber FROM ppic_lotlist WHERE parentLot LIKE '".$recursiveLot."'";
		$queryLotList = $db->query($sql);
		if($queryLotList AND $queryLotList->num_rows > 0)
		{
			while($resultLotList = $queryLotList->fetch_assoc())
			{
				$lotNumber = $resultLotList['lotNumber'];
				
				$lotNumberArray[] = $lotNumber;
				
				buildLotNumberTree($lotNumberArray,$lotNumber);
			}
		}
	}
	
	function insertItemHandlingProcess($lotNumber)
	{
		include('PHP Modules/mysqliConnection.php');
		
		$notInProcess = ($_GET['country']==2) ? "459,324" : "460,459,324";
		
		$workScheduleIdArray = array();
		$processCount = 0;
		$sql = "SELECT id, processCode, processSection, status FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode NOT IN(".$notInProcess.") AND processOrder > 0 ORDER BY processOrder DESC";
		$queryWorkSchedule = $db->query($sql);
		if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
		{
			while($resultWorkSchedule = $queryWorkSchedule->fetch_row())
			{
				$id = $resultWorkSchedule[0];
				$processCode = $resultWorkSchedule[1];
				$processSection = $resultWorkSchedule[2];
				$status = $resultWorkSchedule[3];
				
				if($status==0)
				{
					if($processCount==0 AND $processCode==496)
					{
						$sql = "DELETE FROM ppic_workschedule WHERE id = ".$id." LIMIT 1";
						$queryDelete = $db->query($sql);
						continue;
					}
					
					if($recentProcessSection!=$processSection AND $recentProcessSection!=50)
					{
						$notIHProcessArray = ($_GET['country']==2) ? array(144,518,94,96,86,314,328,378,381,382,383,385,401,403,478,479,372,499,353,496,136,312,430,431,432,137,162,358,461,192,437,539,540,553,597,598,599,600,601,602,603,561) : array(144,518,94,96,86,381,353,496,136,312,430,431,432,137,162,358,461,192,437,539,540,553,597,598,599,600,601,602,603);
						
						if(!in_array($processCode,$notIHProcessArray))
						{
							//~ if($processSection!=11 OR $processSection!=8)
							
							$notIHSectionArray = ($_GET['country']==2) ? array(11,8,40,34,49,3,5,23,28,44,45,10,15,4) : array(11,8,40,34,49,3,5,23,28,44,45,10);
							
							if(!in_array($processSection,$notIHSectionArray))
							{
								$sql = "
										INSERT INTO `ppic_workschedule`
												(	`poId`, `customerId`, `poNumber`, `lotNumber`, `partNumber`, `revisionId`,	`processCode`,	`processOrder`, `processSection`, 	`processRemarks`, `targetStart`, `targetFinish`, `standardTime`,	`receiveDate`, `deliveryDate`, `recoveryDate`, `availability`, `urgentFlag`, `subconFlag`, `partLevelFlag`, `status`)
										SELECT		`poId`, `customerId`, `poNumber`, `lotNumber`, `partNumber`, `revisionId`,	'496',			`processOrder`, '50', 				`processRemarks`, `targetStart`, `targetFinish`, '0', 			`receiveDate`, `deliveryDate`, `recoveryDate`, `availability`, `urgentFlag`, `subconFlag`, `partLevelFlag`, `status`
										FROM	`ppic_workschedule` WHERE id = ".$id." LIMIT 1";
								$queryInsert = $db->query($sql);
								if($queryInsert)
								{
									$workScheduleId = $db->insert_id;
									$workScheduleIdArray[] = $workScheduleId;
								}
							}
						}
					}
				}
					
				$recentProcessSection = $processSection;
				
				$workScheduleIdArray[] = $id;
				
				$processCount++;
			}
			
			if(count($workScheduleIdArray) > 0)
			{
				$sql = "SET @newProcessOrder = ".(count($workScheduleIdArray)+1);
				$query = $db->query($sql);
				
				$sql = "UPDATE `ppic_workschedule` SET processOrder = @newProcessOrder := ( @newProcessOrder -1 ) WHERE id IN(".implode(",",$workScheduleIdArray).") AND processOrder > 0 ORDER BY FIELD(id,'".implode("','",$workScheduleIdArray)."')";
				$queryUpdate = $db->query($sql);
			}
		}
	}
	
	function insertLotProcess($poId)
	{
		include('PHP Modules/mysqliConnection.php');
		
		$sqlMain = "INSERT INTO `system_temporaryworkschedule`(`idNumber`, `destination`, `poId`, `customerId`, `poNumber`, `lotNumber`, `partNumber`, `revisionId`, `processCode`, `processOrder`, `processSection`, `processRemarks`, `targetStart`, `targetFinish`, `standardTime`, `receiveDate`, `deliveryDate`, `recoveryDate`, `availability`, `urgentFlag`, `subconFlag`, `partLevelFlag`) VALUES";	
		$sqlValuesArray = array();
		$counter = 0;
		
		$poNumber = $deliveryDate = $customerDeliveryDate = '';
		$rtvFlag = 0;
		$sql = "SELECT poNumber, customerId, customerDeliveryDate, receiveDate, rtvFlag FROM sales_polist WHERE poId = ".$poId." LIMIT 1";
		$queryPoList = $db->query($sql);
		if($queryPoList AND $queryPoList->num_rows > 0)
		{
			$resultPoList = $queryPoList->fetch_assoc();
			$poNumber = $resultPoList['poNumber'];
			$customerId = $resultPoList['customerId'];
			$customerDeliveryDate = $resultPoList['customerDeliveryDate'];
			$receiveDate = $resultPoList['receiveDate'];
			$rtvFlag = $resultPoList['rtvFlag'];
			//~ $customerDeliveryDate = '2018-03-28';
		}
		
		$deliveryDate = $customerDeliveryDate;
		
		$sql = "SELECT listId FROM system_hotlist WHERE poId = ".$poId." LIMIT 1";
		$queryCheckUrgent = $db->query($sql);
		$urgentFlag = ($queryCheckUrgent->num_rows > 0) ? '1' : '0';
		
		$mainLotNumber = '';
		$sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId = ".$poId." AND parentLot = '' AND partLevel = 1 AND identifier = 1 LIMIT 1";
		$queryLotList = $db->query($sql);
		if($queryLotList AND $queryLotList->num_rows > 0)
		{
			$resultLotList = $queryLotList->fetch_assoc();
			$mainLotNumber = $resultLotList['lotNumber'];
			
			$lotNumberArray = array();
			$lotNumberArray[] = $mainLotNumber;
			$lotNoArray[] = $mainLotNumber;
			
			buildLotNumberTree($lotNumberArray,$mainLotNumber);
			
			$lotCount = count($lotNumberArray);
		}
		//~ else
		//~ {
			//~ continue;
		//~ }
		
		$dataArray = $lotDataArray = $sectionPerLevel = $lastTargetFinishArray = array();
		
		$goodsIssueFlag = 0;
		
		if($lotCount > 0)
		{
			//~ if($_SESSION['idNumber']!='0346')	$sqlFilter = "AND ROUND((LENGTH(lotNumber)-LENGTH(REPLACE(lotNumber,'-','')))/LENGTH('-')) = 2";
			$sql = "SELECT lotNumber, partId, parentLot, partLevel, workingQuantity, identifier, patternId FROM ppic_lotlist WHERE poId = ".$poId." AND identifier IN(1,2) ".$sqlFilter." ORDER BY FIELD(lotNumber,'".implode("','",$lotNumberArray)."')";
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				while($resultLotList = $queryLotList->fetch_assoc())
				{
					$lotNumber = $resultLotList['lotNumber'];
					$partId = $resultLotList['partId'];
					$parentLot = $resultLotList['parentLot'];
					$partLevel = $resultLotList['partLevel'];
					$workingQuantity = $resultLotList['workingQuantity'];
					$identifier = $resultLotList['identifier'];
					$patternId = $resultLotList['patternId'];
					
					$lotCounter++;
					
					if($partLevel == 1)
					{
						$sql = "SELECT lotNumber FROM ppic_lotlist WHERE parentLot LIKE '".$lotNumber."' LIMIT 1";
						$queryCheckChildLot = $db->query($sql);
						$partLevelFlag = ($queryCheckChildLot->num_rows > 0) ? 1 : 2;
					}
					else if($partLevel > 1)
					{
						if($identifier==1)
						{
							if($patternId != -1)
							{
								if($_GET['country']==2)//2021-06-07
								{
									$sql = "SELECT patternId FROM ppic_lotlist WHERE poId = ".$poId." AND partLevel = 1 AND identifier = 1 LIMIT 1";
									$queryParentPattern = $db->query($sql);
									if($queryParentPattern AND $queryParentPattern->num_rows > 0)
									{
										$resultParentPattern = $queryParentPattern->fetch_assoc();
										$patternId = $resultParentPattern['patternId'];
									}
									
									$sql = "UPDATE ppic_lotlist SET patternId = ".$patternId." WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
									$queryUpdate = $db->query($sql);
									
									$sql = "SELECT partId FROM cadcam_partprocess WHERE partId = ".$partId." AND patternId = ".$patternId." AND processCode = 144 LIMIT 1";
									$queryCheckDeliveryProcess = $db->query($sql);
									if($queryCheckDeliveryProcess->num_rows > 0)
									{
										$sql = "SELECT DISTINCT patternId FROM cadcam_partprocess WHERE partId = ".$partId." AND patternId != ".$patternId."";
										$queryPartProcess = $db->query($sql);
										if($queryPartProcess->num_rows > 0)
										{
											while($resultPartProcess = $queryPartProcess->fetch_assoc())
											{
												$sql = "SELECT partId FROM cadcam_partprocess WHERE partId = ".$partId." AND patternId = ".$resultPartProcess['patternId']." AND processCode = 144 LIMIT 1";
												$queryCheckDeliveryProcess = $db->query($sql);
												if($queryCheckDeliveryProcess->num_rows == 0)
												{
													$patternId = $resultPartProcess['patternId'];
													$sql = "UPDATE ppic_lotlist SET patternId = ".$patternId." WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
													$queryUpdate = $db->query($sql);
													break;
												}
											}
										}
									}
								}
								else
								{
									$sql = "SELECT partId FROM cadcam_partprocess WHERE partId = ".$partId." AND patternId = ".$patternId." AND processCode = 144 LIMIT 1";
									$queryCheckDeliveryProcess = $db->query($sql);
									if($queryCheckDeliveryProcess->num_rows > 0)
									{
										$sql = "SELECT DISTINCT patternId FROM cadcam_partprocess WHERE partId = ".$partId." AND patternId != ".$patternId."";
										$queryPartProcess = $db->query($sql);
										if($queryPartProcess->num_rows > 0)
										{
											while($resultPartProcess = $queryPartProcess->fetch_assoc())
											{
												$sql = "SELECT partId FROM cadcam_partprocess WHERE partId = ".$partId." AND patternId = ".$resultPartProcess['patternId']." AND processCode = 144 LIMIT 1";
												$queryCheckDeliveryProcess = $db->query($sql);
												if($queryCheckDeliveryProcess->num_rows == 0)
												{
													$patternId = $resultPartProcess['patternId'];
													$sql = "UPDATE ppic_lotlist SET patternId = ".$patternId." WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
													$queryUpdate = $db->query($sql);
													break;
												}
											}
										}
									}
								}
							}
						}
						
						$partLevelFlag = 0;
					}
					
					$scheduleDataArray = array();
					
					$subconFlag = 0;
					
					if($identifier==1)
					{
						if($patternId!=-1)
						{
							$sql = "SELECT partId FROM cadcam_partprocess WHERE partId = ".$partId." AND patternId = ".$patternId." LIMIT 1";
							$queryPartProcess = $db->query($sql);
							if($queryPartProcess->num_rows == 0)
							{
								$sql = "SELECT DISTINCT patternId FROM cadcam_partprocess WHERE partId = ".$partId." LIMIT 1";
								$queryPartProcess = $db->query($sql);
								if($queryPartProcess AND $queryPartProcess->num_rows > 0)
								{
									$resultPartProcess = $queryPartProcess->fetch_assoc();
									$patternId = $resultPartProcess['patternId'];
								}
							}
						}
						
						$PVC = 0;
						$partNumber = $revisionId = $customerId = '';
						$sql = "SELECT partNumber, revisionId, customerId, PVC FROM cadcam_parts WHERE partId = ".$partId." LIMIT 1";
						$queryParts = $db->query($sql);
						if($queryParts AND $queryParts->num_rows > 0)
						{
							$resultParts = $queryParts->fetch_assoc();
							$partNumber = $resultParts['partNumber'];
							$revisionId = $resultParts['revisionId'];
							$customerId = $resultParts['customerId'];
							$PVC = $resultParts['PVC'];
						}
						
						if($_GET['country']=='1')//Philippines
						{
							$blankingProcesses = array(86,52,381);//Blanking (TPP) AND Blanking (Press) 1,Blanking (Laser)
							$bookingProcess = "312,430,431";
							$FGProcess = 254;
							$qcProcess = "91,92,93,168,197,205,220,230,238,241,242,342,343,346,163,424,173";
						}
						else if($_GET['country']=='2')//Japan
						{
							//~ $blankingProcesses = array(86,314,328,378,381,382,383,385,401,403,478,479,372,499);//Blanking (TPP),Laser,Cutting,Machining
							$blankingProcesses = array(86,314,328,378,381,382,383,385,401,403,478,479);//Blanking (TPP),Laser,Cutting,Machining //2021-04-29 remove cutting(372) and machining(499) by mam rose
							$bookingProcess = "312,430,431";
							$FGProcess = 343;
							$qcProcess = "91,163,167,256,140,137,93,92,455,344,352,364,368,413,508,510";
							
							$firstBlankingProcessCode = '';
                          	$sql = "SELECT processCode FROM cadcam_partprocess WHERE partId = ".$partId." AND patternId = ".$patternId." ANd processCode IN(".implode(",",$blankingProcesses).") ORDER BY processOrder LIMIT 1";
                          	$queryBlankingProcess = $db->query($sql);
                          	if($queryBlankingProcess AND $queryBlankingProcess->num_rows > 0)
                          	{
								$resultBlankingProcess = $queryBlankingProcess->fetch_assoc();
								$firstBlankingProcessCode = $resultBlankingProcess['processCode'];
							}
						}
						
						if($_GET['country']==2)
						{
							$subconProcessFlag = 0;
							// $sql = "SELECT count FROM cadcam_partprocess WHERE partId = ".$partId." AND patternId = ".$patternId." AND processSection = 10 ORDER BY processOrder DESC";
							$sql = "SELECT count FROM cadcam_partprocess WHERE partId = ".$partId." AND patternId = ".$patternId." AND processCode IN(137,138,229) ORDER BY processOrder DESC";
							$queryPartProcess = $db->query($sql);
							if($queryPartProcess AND $queryPartProcess->num_rows > 0)
							{
								$subconFlag = $subconProcessFlag = 1;
							}
						}
						else
						{
							$subconProcessFlag = 0;
							$sql = "SELECT count FROM cadcam_partprocess WHERE partId = ".$partId." AND patternId = ".$patternId." AND processCode IN(145,148) ORDER BY processOrder DESC";
							$queryPartProcess = $db->query($sql);
							if($queryPartProcess AND $queryPartProcess->num_rows > 0)
							{
								$subconFlag = $subconProcessFlag = 1;
							}
						}
						
						$packagingProcessArray = array();
						$sql = "SELECT processCode FROM cadcam_process WHERE processName LIKE '%packaging%' AND status = 0";
						$queryProcess = $db->query($sql);
						if($queryProcess AND $queryProcess->num_rows > 0)
						{
							while($resultProcess = $queryProcess->fetch_assoc())
							{
								$packagingProcessArray[] = $resultProcess['processCode'];
							}
						}
						
						$dueDateFlag = 0;
						$sql = "SELECT count FROM cadcam_partprocess WHERE partId = ".$partId." AND patternId = ".$patternId." AND processCode = 518 ORDER BY processOrder DESC LIMIT 1";
						$queryPartProcess = $db->query($sql);
						if($queryPartProcess AND $queryPartProcess->num_rows > 0)
						{
							$dueDateFlag = 1;
						}
						
						$itemRemovalFlag = 0;
						$sql = "SELECT count FROM cadcam_partprocess WHERE partId = ".$partId." AND patternId = ".$patternId." AND processCode = 184 ORDER BY processOrder DESC LIMIT 1";
						$queryPartProcess = $db->query($sql);
						if($queryPartProcess AND $queryPartProcess->num_rows > 0)
						{
							$itemRemovalFlag = 1;
						}
						
						$itemProtectionFlag = 0;
						$sql = "SELECT count FROM cadcam_partprocess WHERE partId = ".$partId." AND patternId = ".$patternId." AND processCode = 533 ORDER BY processOrder DESC LIMIT 1";
						$queryPartProcess = $db->query($sql);
						if($queryPartProcess AND $queryPartProcess->num_rows > 0)
						{
							$itemProtectionFlag = 1;
						}
						
						$excludeProcessArray = array();
						$sql = "SELECT processCode FROM cadcam_partprocess WHERE partId = ".$partId." AND patternId = ".$patternId." AND processCode IN(".$bookingProcess.") ORDER BY processOrder DESC";
						$queryPartProcess = $db->query($sql);
						if($queryPartProcess AND $queryPartProcess->num_rows > 0)
						{
							$excludeProcessArray[] = $bookingProcess;
						}
						
						$sql = "SELECT processCode FROM cadcam_partprocess WHERE partId = ".$partId." AND patternId = ".$patternId." AND processCode = 136 ORDER BY processOrder DESC";
						$queryPartProcess = $db->query($sql);
						if($queryPartProcess AND $queryPartProcess->num_rows > 0)
						{
							$excludeProcessArray[] = 136;
						}
						
						$excludeProcessSql = (count($excludeProcessArray) > 0) ? "AND processCode NOT IN(".implode(",",$excludeProcessArray).")" : "";
						
						$totalSt = 0;
						$sql = "SELECT processOrder, processCode, processSection, itemHandlingFlag FROM cadcam_partprocess WHERE partId = ".$partId." AND patternId = ".$patternId." ".$excludeProcessSql." ORDER BY processOrder DESC";
						
						if($patternId==-1 OR $rtvFlag==1)
						{
							$patId = '';
							$sql = "SELECT patternId FROM cadcam_partprocess WHERE partId = ".$partId." AND processCode = 144 LIMIT 1";
							$queryPartProcess = $db->query($sql);
							if($queryPartProcess->num_rows > 0)
							{
								$resultPartProcess = $queryPartProcess->fetch_assoc();
								$patId = $resultPartProcess['patternId'];
							}
							
							$dueDateFlag = 0;
							$sql = "SELECT count FROM cadcam_partprocess WHERE partId = ".$partId." AND patternId = ".$patId." AND processCode = 518 ORDER BY processOrder DESC LIMIT 1";
							$queryPartProcess = $db->query($sql);
							if($queryPartProcess AND $queryPartProcess->num_rows > 0)
							{
								$dueDateFlag = 1;
							}
							
							$itemRemovalFlag = 0;
							$sql = "SELECT count FROM cadcam_partprocess WHERE partId = ".$partId." AND patternId = ".$patId." AND processCode = 184 ORDER BY processOrder DESC LIMIT 1";
							$queryPartProcess = $db->query($sql);
							if($queryPartProcess AND $queryPartProcess->num_rows > 0)
							{
								$itemRemovalFlag = 1;
							}
							
							$itemProtectionFlag = 0;
							$sql = "SELECT count FROM cadcam_partprocess WHERE partId = ".$partId." AND patternId = ".$patternId." AND processCode = 533 ORDER BY processOrder DESC LIMIT 1";
							$queryPartProcess = $db->query($sql);
							if($queryPartProcess AND $queryPartProcess->num_rows > 0)
							{
								$itemProtectionFlag = 1;
							}
							
							$firstProcessOrder = '';
							$sql = "SELECT processOrder FROM cadcam_partprocess WHERE partId = ".$partId." AND patternId = ".$patId." AND processCode IN(".$qcProcess.") ORDER BY processOrder DESC LIMIT 1";
							$queryPartProcess = $db->query($sql);
							if($queryPartProcess->num_rows > 0)
							{
								$resultPartProcess = $queryPartProcess->fetch_assoc();
								$firstProcessOrder = $resultPartProcess['processOrder'];
							}
							$sql = "SELECT processOrder, processCode, processSection, itemHandlingFlag FROM cadcam_partprocess WHERE partId = ".$partId." AND patternId = ".$patId." AND processOrder >= ".$firstProcessOrder." ORDER BY processOrder DESC";
						}
						
						$qaDocsFlag = 0;
						
						$processOrder = 0;
						$queryWorkSchedule = $db->query($sql);
						if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
						{
							$processCount = $queryWorkSchedule->num_rows;
							while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
							{
								//~ $processOrder = $resultWorkSchedule['processOrder'];
								$processOrder++;
								$processCode = $resultWorkSchedule['processCode'];
								$processSection = $resultWorkSchedule['processSection'];
								$itemHandlingFlag = $resultWorkSchedule['itemHandlingFlag'];
								
								if($_GET['country']==2 AND $processCode==343 AND $partLevel==1)	$goodsIssueFlag = 1;
								
								if($recentProcessSection!=$processSection)
								{
									$notIHProcessArray = ($_GET['country']==2) ? array(144,518,94,96,86,314,328,378,381,382,383,385,401,403,478,479,372,499,353,496,136,312,430,431,432,137,162,358,461,192,437,539,540,553,597,598,599,600,601,602,603,561) : array(144,518,94,96,86,381,353,496,136,312,430,431,432,137,162,358,461,192,437,539,540,553,597,598,599,600,601,602,603);
									
									if(!in_array($processCode,$notIHProcessArray))
									{
										//~ if($processSection!=11)
										
										$notIHSectionArray = ($_GET['country']==2) ? array(11,8,40,34,49,3,5,23,28,44,45,10,15,4) : array(11,8,40,34,49,3,5,23,28,44,45,10);
										
										if(!in_array($processSection,$notIHSectionArray))
										{
											$processDataArray = array();
											$processDataArray['processCode'] = 496;
											$processDataArray['processSection'] = 50;
											
											$scheduleDataArray[] = $processDataArray;
										}
									}
								}
								
								if($_GET['country']=='2')//Japan
								{
									if($itemRemovalFlag==0)
									{
										if(in_array($processCode,$blankingProcesses) OR ($processOrder==$processCount AND in_array($processCode,array(98,328,392))))
										{
											if($firstBlankingProcessCode==$processCode)
											{
												if(in_array($processCode,array(314,378)))
												{
													$processDataArray = array();
													$processDataArray['processCode'] = 184;
													$processDataArray['processSection'] = 0;
													
													$scheduleDataArray[] = $processDataArray;
												}
											}
										}
									}
								}
								
								//~ if($processCode==184 AND $PVC==1)
								if($processCode==184)
								{
									if(in_array($customerId,array(28,37,45,49)))
									{
										if($itemProtectionFlag==0)
										{
											//2018-08-27 Item Protection for Blanking Laser Only
											$sql = "SELECT processCode FROM cadcam_partprocess WHERE partId = ".$partId." AND patternId = ".$patternId." ANd processCode IN(".implode(",",$blankingProcesses).") AND processOrder < ".$resultWorkSchedule['processOrder']." ORDER BY processOrder DESC LIMIT 1";
											$queryBlankingProcess = $db->query($sql);
											if($queryBlankingProcess AND $queryBlankingProcess->num_rows > 0)
											{
												$resultBlankingProcess = $queryBlankingProcess->fetch_assoc();
												if($resultBlankingProcess['processCode']==381)
												{
													$processDataArray = array();
													$processDataArray['processCode'] = 533;
													$processDataArray['processSection'] = 0;
													
													$scheduleDataArray[] = $processDataArray;
												}
											}
										}
									}
								}
								
								$processDataArray = array();
								$processDataArray['processCode'] = $processCode;
								$processDataArray['processSection'] = $processSection;
								
								$scheduleDataArray[] = $processDataArray;
								
								if($processCode==144 AND $dueDateFlag==0)
								{
									if($_GET['country']==2)
									{
										$scheduleDataArray[] = array('processCode'=>518,'processSection'=>8);//2018-12-07
									}
									else
									{
										$scheduleDataArray[] = array('processCode'=>518,'processSection'=>36);//2018-12-07
									}
								}
								
								if($processCode==94)
								{
									$qaDocsFlag = 1;
								}
								else if($qaDocsFlag==1 AND in_array($processCode,$packagingProcessArray))//2021-02-27
								{
									$qaDocsFlag = 0;
									
									$qaDocCheckingFlag = 0;
									if($_GET['country']=='1')//Philippines
									{
										//~ if(in_array($customerId,array(28,37)))//2018-06-20
										if(in_array($customerId,array(28,37,45,49)))
										{
											$qaDocCheckingFlag = 1;
										}
										else
										{
											$remarksofFAI="";
											$lotRose1 = explode("-",$lotNumber); $lotRose0= $lotRose1[0].'-'.$lotRose1[1].'-'.$lotRose1[2];
											
											$sql = "SELECT lotNumber, poId FROM ppic_lotlist where partId=".$partId." AND identifier = 1 ORDER BY dateGenerated ASC";
											$queryLotQuery = $db->query($sql);
											while($resultLotQuery = $queryLotQuery->fetch_assoc())
											{
											$lotNumberT2=$resultLotQuery['lotNumber'];	
											$poId2=$resultLotQuery['poId'];	
												//"SELECT customerId FROM sales_polist WHERE poId = ".$poId2." and customerId=".$resultCustomer['customerId']
												$sql2 = "SELECT customerId FROM sales_polist WHERE poId = ".$poId2." and customerId=".$customerId;
												$queryCustomer2 = $db->query($sql2);
												if($queryCustomer2->num_rows > 0)
												{
													$lotRose2 = explode("-",$lotNumberT2); $lotRose= $lotRose2[0].'-'.$lotRose2[1].'-'.$lotRose2[2];
													//"SELECT lotNumber, status FROM ppic_workschedule where (lotNumber like '".$lotRose."' or lotNumber like '".$lotRose."-%') and processCode=144"
													$sql3 = "SELECT lotNumber, status FROM ppic_workschedule where (lotNumber like '".$lotRose."' or lotNumber like '".$lotRose."-%') and processCode=144";
													$queryworkschedQuery = $db->query($sql3);
													while($resultworkschedQuery = $queryworkschedQuery->fetch_assoc())
													{
														$loopcount=1;
														$status=$resultworkschedQuery['status'];	
															if($status==1)
															{		
																if($lotRose0!=$lotRose)
																{
																	$remarksofFAI= $remarksofFAI."del";				
																}
																else
																{
																	$remarksofFAI= $remarksofFAI."same lot 1st del".$loopcount;
																}						
																break 2;
															}
															if($status==0)
															{
															$countnodel++;
															$remarksofFAI= $remarksofFAI."nodel";
															}
														$loopcount++;
													}
												}
											}
											if(trim($remarksofFAI)!="del")
											{
												$qaDocCheckingFlag = 1;
											}
										}
										
										if($qaDocCheckingFlag==1)
										{
											if(in_array($customerId,array(28,37,45,49)) AND $_SESSION['idNumber']=='*0346')
											{
												$scheduleDataArray[(count($scheduleDataArray)-1)]  = array('processCode'=>358,'processSection'=>34);//Change Section 2018-08-10
												$scheduleDataArray[] = $processDataArray;
											}
											else
											{
												//~ $scheduleDataArray[] = array('processCode'=>358,'processSection'=>29);
												$scheduleDataArray[] = array('processCode'=>358,'processSection'=>34);//Change Section 2018-08-10
											}
											
											$processCode = $processDataArray['processCode'];
											$processSection = $processDataArray['processSection'];											
										}
									}
								}
								else if(in_array($processCode,$blankingProcesses) OR ($processOrder==$processCount AND in_array($processCode,array(98,328,392))))
								{
									$processDataArray = array();
									if($_GET['country']=='1')//Philippines
									{
										$scheduleDataArray[] = array('processCode'=>136,'processSection'=>23);
										
										if($processCode==86)
										{
											$nestingProcess = 312;
											$processDataArray['processCode'] = 312;//TPP Nesting
											//~ $processDataArray['processSection'] = 28;
											$processDataArray['processSection'] = 40;
										}
										else if($processCode==381)
										{
											$nestingProcess = 430;
											$processDataArray['processCode'] = 430;//Laser Nesting
											//~ $processDataArray['processSection'] = 43;
											$processDataArray['processSection'] = 40;
										}
										else if($processCode==52)
										{
											$nestingProcess = 431;
											$processDataArray['processCode'] = 431;//Press Nesting
											//~ $processDataArray['processSection'] = 44;
											$processDataArray['processSection'] = 40;
										}
										else if(in_array($processCode,array(98,328,392)))
										{
											$nestingProcess = 432;
											$processDataArray['processCode'] = 432;//Cutting Nesting
											//~ $processDataArray['processSection'] = 45;
											$processDataArray['processSection'] = 40;
										}
									}
									else if($_GET['country']=='2')//Japan
									{
										if($firstBlankingProcessCode==$processCode)
										{
											$targetFinish = addDays(-1,$targetFinish);
											
											//~ $scheduleDataArray[] = array('processCode'=>136,'processSection'=>23);//2019-07-29 by sir Ace
											
											//~ if(in_array($processCode,array(372,381,382,401,403,499)))
											if(in_array($processCode,array(381,382,401,403)))//2021-04-29 remove cutting(372) and machining(499) by mam rose
											{
												/* remove TPP Nesting process 2019-07-29 by sir Ace*/
												/* activate TPP Nesting process 2021-04-13 by sir Ace*/
												
												$scheduleDataArray[] = array('processCode'=>136,'processSection'=>23);
												
												$nestingProcess = 312;
												$processDataArray['processCode'] = 312;//TPP Nesting
												//~ $processDataArray['processSection'] = 28;
												$processDataArray['processSection'] = 40;
											}
											else if(in_array($processCode,array(314,378)))
											{
												$scheduleDataArray[] = array('processCode'=>136,'processSection'=>23);//insert material withdrawal if laser 2019-07-29 by sir Ace
												
												$nestingProcess = 430;
												$processDataArray['processCode'] = 430;//Laser Nesting
												//~ $processDataArray['processSection'] = 43;
												$processDataArray['processSection'] = 40;
											}
										}
									}
									
									if(count($processDataArray) > 0)
									{
										$scheduleDataArray[] = $processDataArray;
									}
									
									$processCode = $processDataArray['processCode'];
									$processSection = $processDataArray['processSection'];
								}
								
								$recentProcessSection = $processSection;
								$recentProcessCode = $processCode;
							}
							
							if($patternId==-1 OR $rtvFlag==1)
							{
								$processCodeArray = array();
								if($_GET['country']=='1')
								{
									$scheduleDataArray[] = array('processCode'=>254,'processSection'=>36);//30
								}
								else if($_GET['country']=='2')
								{
									$scheduleDataArray[] = array('processCode'=>343,'processSection'=>52);
								}
							}
							
							if($subconProcessFlag==1)
							{
								$sql = "SELECT processCode, subconOrder FROM cadcam_subconlist WHERE partId = ".$partId." AND active = 0 ORDER BY subconOrder DESC";
								$querySubconlist = $db->query($sql);
								if($querySubconlist AND $querySubconlist->num_rows > 0)
								{
									//~ if($_GET['country']==2 OR $_SESSION['idNumber']=='0346')
									if($_SESSION['idNumber']==true)
									{
										$subconProcessArray = array();
										while($resultSubconlist = $querySubconlist->fetch_assoc())
										{
											$sql = "SELECT treatmentName FROM engineering_treatment WHERE treatmentId = ".$resultSubconlist['processCode']." LIMIT 1";
											$queryTreatmentProcess = $db->query($sql);
											if($queryTreatmentProcess AND $queryTreatmentProcess->num_rows > 0)
											{
												$resultTreatmentProcess = $queryTreatmentProcess->fetch_assoc();
												$subconProcessArray[] = $resultTreatmentProcess['treatmentName'];
											}
										}
										
										if(count($subconProcessArray) > 0)
										{
											foreach($subconProcessArray as $subconProcess)
											{
												$scheduleDataArray[] = array('processCode'=>598,'processSection'=>5,'processRemarks'=>$subconProcess);
											}
											foreach($subconProcessArray as $subconProcess)
											{
												$scheduleDataArray[] = array('processCode'=>597,'processSection'=>5,'processRemarks'=>$subconProcess);
											}
										}
									}
									else
									{
										$scheduleDataArray[] = array('processCode'=>461,'processSection'=>5);
									}									
								}
							}
						}
					}
					else if($identifier==2)
					{
						
						
						$partNumber = '';
						$sql = "SELECT accessoryNumber FROM cadcam_accessories WHERE accessoryId = ".$partId." LIMIT 1";
						$queryAccessory = $db->query($sql);
						if($queryAccessory->num_rows > 0)
						{
							$resultAccessory = $queryAccessory->fetch_assoc();
							$partNumber = $resultAccessory['accessoryNumber'];
							$revisionId = 'N/A';
						}
						
						if($_GET['country']=='2')//Japan
						{
							$scheduleDataArray[] = array('processCode'=>344,'processSection'=>4);
							$scheduleDataArray[] = array('processCode'=>343,'processSection'=>52);
						}
						else
						{
							if(in_array($partId,array(1577,1631)))
							{
								$scheduleDataArray[] = array('processCode'=>87,'processSection'=>12);
								$scheduleDataArray[] = array('processCode'=>117,'processSection'=>6);
								$scheduleDataArray[] = array('processCode'=>91,'processSection'=>4);
								$scheduleDataArray[] = array('processCode'=>254,'processSection'=>36);
							}
							else
							{
								$sql = "SELECT accessoryId FROM `cadcam_accessories` WHERE accessoryId = ".$partId." AND `accessoryName` LIKE '%packaging%' LIMIT 1";
								$queryPackagingMaterial = $db->query($sql);
								if($queryPackagingMaterial AND $queryPackagingMaterial->num_rows > 0 OR in_array($partId,array(1646,1818,1647)))
								{
									$scheduleDataArray[] = array('processCode'=>254,'processSection'=>36);								
								}
								else
								{
									$scheduleDataArray[] = array('processCode'=>192,'processSection'=>30);
									$scheduleDataArray[] = array('processCode'=>91,'processSection'=>4);
									$scheduleDataArray[] = array('processCode'=>254,'processSection'=>36);
								}
							}
						}
					}
					
					$countCount = 0;
					$processCount = $processOrder = count($scheduleDataArray);
					if($processOrder > 0)
					{
						foreach($scheduleDataArray as $key => $dataArray)
						{
							$countCount++;
							$currentIndex = $key;
							
							$processCode = $dataArray['processCode'];
							$processSection = $dataArray['processSection'];
							$processRemarks = (isset($dataArray['processRemarks'])) ? $dataArray['processRemarks'] : '';
							
							$availability = 0;
							//~ if($processCode == 461 OR $processCode == $nestingProcess) //PHILS
							//~ if((($_GET['country']==1 AND $processCode == 461) OR ($_GET['country']==2 AND $processCode == 597)) OR $processCode == $nestingProcess) //PHILS
							if($_GET['country']==2 AND $processCode == 597 OR $processCode == $nestingProcess) //PHILS
							{
								//~ $availability = 1;
								$availability = 0;
							}
							else
							{
								$sql = "SELECT lotNumber FROM ppic_lotlist WHERE parentLot LIKE '".$lotNumber."' LIMIT 1";
								$queryCheckChildLot = $db->query($sql);
								if($queryCheckChildLot AND $queryCheckChildLot->num_rows == 0)
								{
									if($processCount==$countCount AND $identifier==1)	$availability = 1;
								}
							}
							
							$st = 0;
							if($identifier==1)
							{
								$st = getStandardTime($partId,$processCode,$workingQuantity,$processSection,$lotNumber);
							}
							
							$destination = 0;
							$sqlValues = "('".$_SESSION['idNumber']."', '".$destination."', '".$poId."', '".$customerId."', '".$poNumber."', '".$lotNumber."', '".$partNumber."', '".$revisionId."', '".$processCode."', '".$processOrder."', '".$processSection."', '".$processRemarks."', '".$targetStart."', '".$targetFinish."', '".$st."', '".$receiveDate."', '".$customerDeliveryDate."', '".$deliveryDate."', '".$availability."', '".$urgentFlag."', '".$subconFlag."', '".$partLevelFlag."')";
							
							$sqlValuesArray[] = $sqlValues;
							$counter++;
							if($counter == 50)
							{
								$sqlInsert = $sqlMain." ".implode(",",$sqlValuesArray);
								$queryInsert = $db->query($sqlInsert);
								$sqlValuesArray = array();
								$counter = 0;
							}
							
							$processOrder--;
						}
					}
					
					if($partLevel==1 AND ($patternId==-1 OR $rtvFlag==1))
					{
						break;
					}
					
					if($goodsIssueFlag==1)	break;
				}
				if($counter > 0)
				{
					$sqlInsert = $sqlMain." ".implode(",",$sqlValuesArray);
					$queryInsert = $db->query($sqlInsert);
				}
			}
		}
	}
	
	function getTargetFinishDate($targetFinish,$st,$workingTimeLimit,&$workingTimeLeft,$startToLastFlag = 0)
	{
		$stTemp = $st;
		$daysIncrement = 0;
		while($stTemp > $workingTimeLimit)
		{
			$daysIncrement++;
			$stTemp -= $workingTimeLimit;
		}
		
		if($stTemp > $workingTimeLeft)
		{
			$stTemp -= $workingTimeLeft;
			$workingTimeLeft = $workingTimeLimit;
			$daysIncrement += 1;
		}
		
		$workingTimeLeft -= $stTemp;
		
		if($daysIncrement>0)
		{
			if($startToLastFlag==1)
			{
				$targetFinish = addDays(+$daysIncrement,$targetFinish);
			}
			else
			{
				$targetFinish = addDays(-$daysIncrement,$targetFinish);
			}
		}
		
		return $targetFinish;
	}
	
	function generateScheduleSingleItems($itemScheduleDataArray,$rescheduleFlag = 0,$viewDetailFlag = 1,$remarksLog='')
	{
		include('PHP Modules/mysqliConnection.php');
		
		$levelEndDatesArray = array();
		
		$workingTimeLeft = $workingTimeLimit = 30600;//8.5 hours
		//~ if($_SESSION['idNumber']=='0346') $workingTimeLeft = $workingTimeLimit = 57600;//16 hours
		$resetFlag = 0;
		
		foreach($itemScheduleDataArray as $data)
		{
			$lotNumber = $data['lotNumber'];
			$partId = $data['partId'];
			$identifier = $data['identifier'];
			$partLevel = $data['partLevel'];
			$workingQuantity = $data['workingQuantity'];
			$scheduleDataArray = $data['scheduleDataArray'];
			$highestSTDataArray = $data['highestSTDataArray'];
			$workingDays = $data['workingDays'];
			$startDate = $data['startDate'];
			$endDate = $data['endDate'];
			$dueDate = $data['dueDate'];
			
			$targetFinish = $endDate;
			
			$partialBatchIdFlag = 0;
			
			if(isset($data['totalStMix']))
			{
				$totalSt = $data['totalStMix'];
			}
			else
			{
				$totalSt = $highestSTDataArray[$partLevel];
			}
			
			$consumeDayST = ceil($totalSt / $workingTimeLimit);
			//~ e($totalSt / $workingTimeLimit);
			
			//~ $stTemp = $totalSt;
			//~ $consumeDayST = 0;
			//~ while($stTemp > $workingTimeLimit)
			//~ {
				//~ $consumeDayST++;
				//~ $stTemp -= $workingTimeLimit;
			//~ }
			
			if($consumeDayST==0)	$consumeDayST = 1;
			
			//~ $leadTimeLeft = $leadTimeLeftTemp = ($workingDays - $consumeDayST) - 1;
			$leadTimeLeft = $leadTimeLeftTemp = ($workingDays - $consumeDayST);
			//~ $leadTimeLeft = $workingDays;
			
			$fQCInspectionFlag = 0;
			
			$incrementDay = 0;
			
			if($identifier==1)
			{
				$dueDateCount = $itemHandlingCount = $materialWithdrawalCount = $deliveryToSubconCount = $nestingCount = $fQCInspectionCount = 0;
				foreach($scheduleDataArray as $scheduleData)
				{
					if($scheduleData['processCode']==518) $dueDateCount++;
					if($scheduleData['processCode']==496) $itemHandlingCount++;
					if($scheduleData['processCode']==136) $materialWithdrawalCount++;
					if($_GET['country']==1)
					{
						if(in_array($scheduleData['processCode'],array(145,172,228))) $deliveryToSubconCount++;
					}
					else if($_GET['country']==2)
					{
						if(in_array($scheduleData['processCode'],array(145)) OR in_array($scheduleData['processSection'],array(10,12))) $deliveryToSubconCount++;
					}
					if(in_array($scheduleData['processCode'],array(312,430,431,432))) $nestingCount++;
					
					if(in_array($scheduleData['processCode'],array(91))) $fQCInspectionCount = 1;
					
					//~ if($_SESSION['idNumber']=='0412')
					//~ {
						if($scheduleData['processCode']==218 AND in_array($scheduleData['processSection'],array(12,48))) $coatingCount = 1;//coating
						if(in_array($scheduleData['processCode'],array(160,383)) AND in_array($scheduleData['processSection'],array(12,48))) $printingCount = 1;//printing
						//~ if($scheduleData['processCode']==136) $materialWithdrawalCount++;
					//~ }
				}
				
				//~ if($_SESSION['idNumber']=='0346') $dueDateCount = 0;
				if($dueDate!='') $dueDateCount = 0;
				//~ $dueDateCount = 0;
				
				$dueDateGap = 0;
				if($dueDateCount > 0)
				{
					if($_GET['country']=='1')
					{
						$sql = "SELECT value FROM system_duedateparameter WHERE min <= ".$leadTimeLeftTemp." AND max >= ".$leadTimeLeftTemp." LIMIT 1";
						$queryDueDateParameter = $db->query($sql);
						if($queryDueDateParameter AND $queryDueDateParameter->num_rows > 0)
						{
							$resultDueDateParameter = $queryDueDateParameter->fetch_assoc();
							$dueDateGap = $resultDueDateParameter['value'];
						}
						else
						{
							$sql = "SELECT value FROM system_duedateparameter WHERE min <= ".$leadTimeLeftTemp." AND max = 0 LIMIT 1";
							$queryDueDateParameter = $db->query($sql);
							if($queryDueDateParameter AND $queryDueDateParameter->num_rows > 0)
							{
								$resultDueDateParameter = $queryDueDateParameter->fetch_assoc();
								$dueDateGap = $resultDueDateParameter['value'];
							}
						}
					}
					else
					{
						$dueDateGap = 1;
					}
				}
				
				//~ echo "<br>".$leadTimeLeftTemp;
				//~ if($leadTimeLeftTemp > 0)	$leadTimeLeftTemp -= $dueDateGap;
				//~ echo "<br>".$leadTimeLeftTemp;
				
				$initialSubconGap = ($_GET['country']==2) ? 6 : 7 ;///change subcon interval from 3 day to 7 days 2019-08-02 sir roldan M, ma'am rose
				
				$delToSubGap = $itemHandlingGap = 0;
				
				$itemHandlingGapLeft = 0;
				
				if($leadTimeLeftTemp > 0)
				{
					if($deliveryToSubconCount > 0)
					{
						$delToSubGap = floor($leadTimeLeftTemp/$deliveryToSubconCount);
						
						if($delToSubGap > $initialSubconGap)	$delToSubGap = $initialSubconGap;
						
						$leadTimeLeftTemp = ($leadTimeLeftTemp-($deliveryToSubconCount*$delToSubGap));
					}
					//~ echo "<br>".$leadTimeLeftTemp;
					
					if($leadTimeLeftTemp <= 2 AND $fQCInspectionFlag==1)
					{
						if($leadTimeLeftTemp > 0)
						{
							$fQCInspectionFlag = $fQCInspectionCount;
							$leadTimeLeftTemp -= $fQCInspectionFlag;
						}
					}
					
					//~ if($_SESSION['idNumber']=='0412')
					//~ {
						if($leadTimeLeftTemp > 0)	$leadTimeLeftTemp -= $coatingCount;
						if($leadTimeLeftTemp > 0)	$leadTimeLeftTemp -= $printingCount;
					//~ }
					
					if($leadTimeLeftTemp > 0)	$leadTimeLeftTemp -= $materialWithdrawalCount;
					//~ echo "<br>".$leadTimeLeftTemp;
					
					if($leadTimeLeftTemp > 0)	$leadTimeLeftTemp -= $nestingCount;
					//~ echo "<br>".$leadTimeLeftTemp;
					
					if(($deliveryToSubconCount > 0 AND $delToSubGap==$initialSubconGap) OR $deliveryToSubconCount == 0)
					{
						if($leadTimeLeftTemp > 0)
						{
							if($itemHandlingCount > 0)
							{
								if($leadTimeLeftTemp >= $itemHandlingCount)
								{
									//~ $itemHandlingGap = floor($leadTimeLeftTemp/$itemHandlingCount);
									$itemHandlingGap = 1;
									if($itemHandlingGap > 2)	$itemHandlingGap = 2;
									
									//~ if($itemHandlingGap==1)
									//~ {
										//~ $itemHandlingGapLeft = $leadTimeLeftTemp%$itemHandlingCount;
									//~ }
									
									//~ if($_SESSION['idNumber']=='0613')
									//~ {
										//~ $itemHandlingGap = 1;
										//~ $itemHandlingGapLeft = 0;
									//~ }
									
									$leadTimeLeftTemp = ($leadTimeLeftTemp-($itemHandlingCount*$itemHandlingGap));
								}
								else
								{
									$itemHandlingGapLeft = $leadTimeLeftTemp;
									$leadTimeLeftTemp = 0;
								}
							}
						}
					}
					
					//~ //special code to be deleted
					//~ if($leadTimeLeftTemp > 0)
					//~ {
						//~ $dueDateGap = ($leadTimeLeftTemp >= 5) ? 5 : $leadTimeLeftTemp;
					//~ }
				}
			}
			
			if($viewDetailFlag == 1)
			{
				echo "<table border='1'>";
				echo "
					<tr>
						<td colspan='10'>
							<table style='float:left;'>
								<tr>
									<td align='right'>Working Time : </td>
									<td>".$workingTimeLimit." seconds (".convertSeconds($workingTimeLimit).")</td>
								</tr>
								<tr>
									<td align='right'>Total ST : </td>
									<td>".$totalSt." seconds (".convertSeconds($totalSt).")</td>
								</tr>
								<tr>
									<td align='right'>Consumed ST : </td>
									<td>".$consumeDayST." day(s) [Total ST / Working Time]</td>
								</tr>
							</table>
							<table style='float:left;'>
								<tr>
									<td align='right'>Working Days : </td>
									<td align='right'>".$workingDays."</td>
								</tr>
								<tr>
									<td align='right'>Lead Time Left : </td>
									<td align='right'>".$leadTimeLeft."</td>
								</tr>
							</table>
							<table style='float:right;' border='1'>
								<tr>
									<th>Process</th>
									<th>Lead Time</th>
									<th>Count</th>
									<th>Day</th>
								</tr>
								<tr>
									<td>Due Date</td>
									<td align='center'>".$dueDateGap."</td>
									<td align='center'>".$dueDateCount."</td>
									<td align='center'>".($dueDateGap*$dueDateCount)."</td>
								</tr>
								<tr>
									<td>Delivery to Subcon</td>
									<td align='center'>".$delToSubGap."</td>
									<td align='center'>".$deliveryToSubconCount."</td>
									<td align='center'>".($delToSubGap*$deliveryToSubconCount)."</td>
								</tr>
								<tr>
									<td>Material Withdrawal</td>
									<td align='center'>1</td>
									<td align='center'>".$materialWithdrawalCount."</td>
									<td align='center'>".(1*$materialWithdrawalCount)."</td>
								</tr>
								<tr>
									<td>Nesting</td>
									<td align='center'>1</td>
									<td align='center'>".$nestingCount."</td>
									<td align='center'>".(1*$nestingCount)."</td>
								</tr>
								<tr>
									<td>Item Handling</td>
									<td align='center'>".$itemHandlingGap."</td>
									<td align='center'>".$itemHandlingCount."</td>
									<td align='center'>".($itemHandlingGap*$itemHandlingCount)."</td>
								</tr>
								<tr>
									<th align='right'>Remaining Days</th>
									<td colspan='2' align='center'>".$leadTimeLeftTemp."</td>
									<td align='center'>".($leadTimeLeft-$leadTimeLeftTemp)."</td>
								</tr>
							</table>
						</td>
					</tr>
				";
			}
			
			$proceedToAssemblyFlag = 0;
			$minusFlag = 0;
			$totalSTSeconds = 0;
			$totalSt = 0;
			$previousDate = '';
			$recentProcessCode = '';
			foreach($scheduleDataArray as $scheduleData)
			{
				$id = $scheduleData['id'];
				$processCode = $scheduleData['processCode'];
				$st = $scheduleData['st'];
				if(isset($scheduleData['partLevel']))	$partLevel = $scheduleData['partLevel'];
				
				$st = round($st);
				
				$incrementDay = 0;
				
				if($nestingCount > 0)
				{
					if(in_array($processCode,array(312,430,431,432)))
					{
						if($leadTimeLeft > 0)
						{
							$incrementDay++;
							$leadTimeLeft--;
						}
					}
				}
				
				if($materialWithdrawalCount > 0)
				{
					if($processCode==136)
					{
						if($leadTimeLeft > 0)
						{
							$incrementDay++;
							$leadTimeLeft--;
						}
					}
				}
				
				if($coatingCount > 0)
				{
					if($recentProcessCode==218)
					{
						if($leadTimeLeft > 0)
						{
							$incrementDay++;
							$leadTimeLeft--;
						}
					}
				}
				
				if($printingCount > 0)
				{
					if(in_array($recentProcessCode,array(160,383)))
					{
						if($leadTimeLeft > 0)
						{
							$incrementDay++;
							$leadTimeLeft--;
						}
					}
				}
				
				if($delToSubGap > 0)
				{
					//~ if(in_array($processCode,array(145,172,228)))
					if(($_GET['country']==1 AND in_array($processCode,array(145,172,228))) OR ($_GET['country']==2 AND (in_array($scheduleData['processCode'],array(145)) OR in_array($scheduleData['processSection'],array(10,12)))))
					{
						if($leadTimeLeft > 0)
						{
							$incrementDay += $delToSubGap;
							$leadTimeLeft -= $delToSubGap;
							
							if($delToSubGap < $initialSubconGap)
							{
								if($leadTimeLeftTemp > 0)
								{
									if($deliveryToSubconCount == $leadTimeLeftTemp)
									{
										$incrementDay++;
										$leadTimeLeft--;
										$leadTimeLeftTemp--;
									}
									//~ $itemHandlingCount--;
								}
							}
						}
					}
				}
				else
				{
					//~ if(in_array($processCode,array(145,172,228)))
					if(($_GET['country']==1 AND in_array($processCode,array(145,172,228))) OR ($_GET['country']==2 AND (in_array($scheduleData['processCode'],array(145)) OR in_array($scheduleData['processSection'],array(10,12)))))
					{
						if($leadTimeLeftTemp > 0)
						{
							if($deliveryToSubconCount == $leadTimeLeftTemp)
							{
								$incrementDay++;
								$leadTimeLeft--;
								$leadTimeLeftTemp--;
							}
							//~ $itemHandlingCount--;
						}
					}
				}
				
				if($proceedToAssemblyFlag==0)
				{
					if($itemHandlingGap > 0)
					{
						if($processCode==496 OR ($processCode==162 AND isset($scheduleData['partLevel'])))
						{
							if($leadTimeLeft > 0)
							{
								$incrementDay += $itemHandlingGap;
								$leadTimeLeft -= $itemHandlingGap;
								
								if($itemHandlingGapLeft > 0)
								{
									if($itemHandlingCount == $itemHandlingGapLeft)
									{
										$incrementDay++;
										$leadTimeLeft--;
										$itemHandlingGapLeft--;
									}
								}
								
								$itemHandlingCount--;
							}
						}
					}
					else
					{
						if($processCode==496 OR ($processCode==162 AND isset($scheduleData['partLevel'])))
						{
							if($itemHandlingGapLeft > 0)
							{
								if($itemHandlingCount == $itemHandlingGapLeft)
								{
									$incrementDay++;
									$leadTimeLeft--;
									$itemHandlingGapLeft--;
								}
								$itemHandlingCount--;
							}
						}
					}
				}
				
				if($itemHandlingGap > 0)
				{
					if($fQCInspectionFlag==1)
					{
						if($recentProcessCode==91)
						{
							$incrementDay += $fQCInspectionFlag;
							$leadTimeLeft -= $fQCInspectionFlag;
						}
					}
				}
				
				if($dueDateGap > 0)
				{
					if($processCode==518)
					{
						$incrementDay += $dueDateGap;
						$leadTimeLeft -= $dueDateGap;
					}
				}
				
				//~ if($processCode==114)	echo "<br>a".$workingTimeLimit;
				if($incrementDay > 0)
				{
					$targetFinish = addDays(-$incrementDay,$targetFinish);
					$workingTimeLeft = $workingTimeLimit;
					$resetFlag = 0;
				}
				
				//~ if($count==6 AND $processCode==496)	echo "<br>a".$targetFinish;
				//~ if($processCode==114)	echo "<br>a".$workingTimeLimit;
				//~ if($_SESSION['idNumber']=='0346')
				if($_SESSION['idNumber']=='0412*')
				{
					//~ echo "<br>".$resetFlag;
					if($resetFlag==0 AND $processCode!=496)
					{
						$processSectionTemp = $scheduleData['processSection'];
						if(in_array($processSectionTemp,array(8,34)))	$processSectionTemp = 11;
						$repeatFlag = 1;
						while($repeatFlag==1)
						{
							$repeatFlag = 0;
							
							$idNumberArray = array();
							$employeeCount = 0;
							$sql = "SELECT idNumber FROM hr_employee WHERE sectionId = ".$processSectionTemp." AND status = 1 ";
							$queryEmployeee = $db->query($sql);
							if($queryEmployeee AND $queryEmployeee->num_rows > 0)
							{
								$employeeCount = $queryEmployeee->num_rows;
								while($resultEmployeee = $queryEmployeee->fetch_assoc())
								{
									$idNumberArray[] = $resultEmployeee['idNumber'];
								}
							}
							
							$sql = "SELECT DISTINCT employeeId FROM hr_leave WHERE employeeId IN('".implode("','",$idNumberArray)."') AND leaveDate >= '".$targetFinish."' AND <= '".$targetFinish."' AND employeeId IN('".implode("','",$idNumberArray)."')";
							$queryLeave = $db->query($sql);
							if($queryLeave AND $queryLeave->num_rows > 0)
							{
								$employeeCount -= $queryLeave->num_rows;
							}
							
							$capacity = (7 * $employeeCount) * 3600;
							
							$totalLoad = 0;
							$sql = "SELECT SUM(standardTime) as totalLoad FROM `view_workschedule` WHERE `processSection` = ".$processSectionTemp." AND `targetFinish` = '".$targetFinish."'";
							$queryWorkschedule = $db->query($sql);
							if($queryWorkschedule AND $queryWorkschedule->num_rows > 0)
							{
								$resultWorkschedule = $queryWorkschedule->fetch_assoc();
								$totalLoad = $resultWorkschedule['totalLoad'];
							}
							
							if($capacity > 0 AND $totalLoad > 0)
							{
								if($capacity < $totalLoad)
								{
									$targetFinish = addDays(-1,$targetFinish);
									$repeatFlag = 1;
								}
								else
								{
									$workingTimeLeft = $workingTimeLimit = ($capacity - $totalLoad);
								}
							}
						}
					}
				}
				
				//~ if($processCode==224)	echo "<br>".$targetFinish;
				//~ if($count==6 AND $processCode==496)	echo "<br>b".$targetFinish;
				//~ if($processCode==114)	echo "<br>b".$workingTimeLimit." ".$targetFinish;
				
				/*
				if($partialBatchIdFlag==0 AND $processCode!=144)
				{
					if($_SESSION['idNumber']=='0346')
					{
						if(substr_count($lotNumber, '-')==3)
						{
							$sql = "SELECT partialBatchId FROM ppic_lotlist WHERE lotNumber LIKE '".$lotNumber."' AND partialBatchId > 0 LIMIT 1";
							$queryLotList = $db->query($sql);
							if($queryLotList AND $queryLotList->num_rows > 0)
							{
								$resultLotList = $queryLotList->fetch_assoc();
								$partialBatchId = $resultLotList['partialBatchId'];
								
								//~ echo "<br>gerald ".$lotNumber." => ".$processCode." ".$targetFinish." ".$partialBatchId." ".$endDate." ".$scheduleData['partLevel'];
								//~ print_r($scheduleData);
								
								$targetFinish = addDays(-$partialBatchId,$targetFinish);
							}
						}
					}	
					
					$partialBatchIdFlag = 1;				
				}*/
				
				$targetDateEnd = $targetFinish;
				
				$targetDateStart = $targetFinish = getTargetFinishDate($targetFinish,$st,$workingTimeLimit,$workingTimeLeft);
				//~ if($count==6 AND $processCode==496)	echo "<br>".$targetFinish;
				if($processCode==496)
				{
					$targetDateStart = $targetDateEnd;
				}
				
				$proceedToAssemblyFlag = ($processCode==162) ? 1 : 0;
				
				//~ if(($processCode==461) OR ($identifier==2 AND $processCode!=192))//2021-08-16 by sir ace
				if((in_array($processCode,array(461,597,598))))//PO making is same to the first process 2021-08-16 by sir ace
				{
					//~ if(strtotime($targetDateStart) > strtotime($startDate)) $targetDateStart = $targetDateEnd;
					if(strtotime($targetDateStart) > strtotime($startDate)) $targetDateStart = $targetDateEnd = addDays(1,$startDate);//back to receive date 2021-10-23 by sir ace
				}
				else if(($identifier==2 AND $processCode!=192))
				{
					if(strtotime($targetDateStart) > strtotime($startDate)) $targetDateStart = $targetDateEnd = addDays(1,$startDate);
				}
				else
				{
					if(strtotime($targetDateStart) < strtotime($startDate))
					{
						if(strtotime($targetDateStart) < strtotime($startDate)) $targetFinish = $targetDateStart = $startDate;
						if(strtotime($targetDateEnd) < strtotime($startDate)) $targetDateEnd = $startDate;
					}
				}
				
				//~ if($processCode==144)
				//~ {
					//~ $sql = "SELECT customerDeliveryDate FROM sales_polist WHERE poId = """;
				//~ }
				
				if($viewDetailFlag == 1)
				{
					//~ if($_SESSION['idNumber']!='0346')
					if(!in_array($_SESSION['idNumber'],array('*0346','*0280')))
					{
						$processSection = $scheduleData['processSection'];
						if(isset($scheduleData['lotNumber']))	$lotNumber = $scheduleData['lotNumber'];
						$inputST = $scheduleData['inputST'];
						
						$processName = $section = '';
						$sql = "SELECT processName, processSection FROM cadcam_process WHERE processCode = ".$processCode." LIMIT 1";
						$queryProcess = $db->query($sql);
						if($queryProcess AND $queryProcess->num_rows > 0)
						{
							$resultProcess = $queryProcess->fetch_assoc();
							$processName = $resultProcess['processName'];
							$section = $resultProcess['processSection'];
						}
						
						$sectionName = '';
						$sql = "SELECT sectionName, motherSectionId, departmentId FROM ppic_section WHERE sectionId = ".$processSection." LIMIT 1";
						$querySection = $db->query($sql);
						if($querySection AND $querySection->num_rows > 0)
						{
							$resultSection = $querySection->fetch_assoc();
							$sectionName = $resultSection['sectionName'];
						}
						
						$totalStTD = "";
						if($previousDate!=$targetDateEnd)
						{
							if($totalSt > 0)
							{
								echo "
									<tr>
										<td></td>
										<td colspan='8' align='right'>Total ST</td>
										<td>".convertSeconds($totalSt)."</td>
									</tr>
								";
							}
							
							$totalSt = 0;
						}
						
						$totalSt += $st;
						
						if($previousDate!='')
						{
							$current = strtotime($previousDate);
							$last = strtotime($targetDateEnd);
							$dates = array();
							while($current >= $last)
							{
								$dateDate = date('Y-m-d',$current);
								
								$sql = "SELECT holidayName FROM hr_holiday WHERE holidayDate = '".$dateDate."' AND holidayType < 6 LIMIT 1";
								$queryHoliday = $db->query($sql);
								if($queryHoliday AND $queryHoliday->num_rows > 0)
								{
									echo "
										<tr bgcolor='pink'>
											<td></td>
											<td colspan='8' align='center'>Holiday (".$dateDate.")</td>
											<td></td>
										</tr>
									";
								}
								else if(date('w',$current)==0)
								{
									echo "
										<tr bgcolor='pink'>
											<td></td>
											<td colspan='8' align='center'>Sunday (".$dateDate.")</td>
											<td></td>
										</tr>
									";
								}
								
								$current = strtotime('-1 day', $current);
							}
						}
						
						$stInput = "";
						if($identifier==1 AND $processCode!=496)
						{
							//~ $stInput = "<input type='number' name='stPostArray[".$lotNumber."|".$processCode."]' value='".$st."' form='inputForm'>";
							$stInput = "<input type='number' name='stPostArray[".$lotNumber."|".$processCode."]' style='background-color:#FFFF99;' value='".$inputST."' form='inputForm'>";
						}
						
						echo "
							<tr ".$bgcolor.">
								<td>".++$count."</td>
								<td>".$lotNumber."</td>
								<td>".$processCode."</td>
								<td>".$processName."</td>
								<td>".$sectionName."</td>
								<td>".$stInput."</td>
								<td>".$st."</td>
								<td>".$targetDateStart."</td>
								<td>".$targetDateEnd."</td>
								<td>".convertSeconds($st)."</td>
								<td>".convertSeconds($workingTimeLeft)."</td>
								<td>".convertSeconds($workingTimeLimit)."</td>
								<td>".convertSeconds($capacity)."</td>
								<td>".convertSeconds($totalLoad)."</td>
							</tr>
						";
					}
					
					$sql = "UPDATE system_temporaryworkschedule SET targetStart = '".$targetDateStart."', targetFinish = '".$targetDateEnd."', standardTime = '".$st."' , inputST = '".$inputST."' WHERE listId = ".$id." LIMIT 1";
					$queryUpdate = $db->query($sql);					
				}
				else
				{
					if($rescheduleFlag==0)
					{
						$sql = "UPDATE system_temporaryworkschedule SET targetStart = '".$targetDateStart."', targetFinish = '".$targetDateEnd."' WHERE listId = ".$id." LIMIT 1";
						$queryUpdate = $db->query($sql);
					}
					else
					{
						//~ if($_SESSION['idNumber']=='0346')
						//~ {
							$sql = "SELECT lotNumber, targetStart, targetFinish FROM ppic_workschedule WHERE id = ".$id." LIMIT 1";
							$queryWorkSchedule = $db->query($sql);
							if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
							{
								$resultWorkSchedule = $queryWorkSchedule->fetch_assoc();
								$lotNumber = $resultWorkSchedule['lotNumber'];
								$oldTargetStart = $resultWorkSchedule['targetStart'];
								$oldTargetFinish = $resultWorkSchedule['targetFinish'];
								
								$sql = "UPDATE ppic_workschedule SET targetStart = '".$targetDateStart."', targetFinish = '".$targetDateEnd."' WHERE id = ".$id." LIMIT 1";
								$queryUpdate = $db->query($sql);
								if($queryUpdate)
								{
									if($oldTargetStart!=$targetDateStart)
									{
										$sql = "
											INSERT INTO `system_lotDetailsLog`
													(	`lotNumber`,		`action`,	`oldValue`,				`field`,							`employeeId`,				`logDate`,	`logTime`,	`remarks`)
											VALUES	(	'".$lotNumber."',	0,			'".$oldTargetStart."',	'Target Start = ".$processCode."',	'".$_SESSION['idNumber']."',NOW(),		NOW(),		'Auto Resched (changed to ".$targetDateStart.") ".$remarksLog."')
										";
										$queryInsert = $db->query($sql);
									}
									if($oldTargetFinish!=$targetDateEnd)
									{
										$sql = "
											INSERT INTO `system_lotDetailsLog`
													(	`lotNumber`,		`action`,	`oldValue`,				`field`,							`employeeId`,				`logDate`,	`logTime`,	`remarks`)
											VALUES	(	'".$lotNumber."',	0,			'".$oldTargetFinish."',	'Target Finish = ".$processCode."',	'".$_SESSION['idNumber']."',NOW(),		NOW(),		'Auto Resched (changed to ".$targetDateEnd.") ".$remarksLog."')
										";
										$queryInsert = $db->query($sql);
									}
								}
							}
						/*}
						else
						{
							$sql = "UPDATE ppic_workschedule SET targetStart = '".$targetDateStart."', targetFinish = '".$targetDateEnd."' WHERE id = ".$id." LIMIT 1";
							$queryUpdate = $db->query($sql);
						}*/
					}
				}
				
				if($processCode==144 AND $dueDate!='')
				{
					$targetDateStart = $targetDateEnd = $targetFinish = $dueDate;
				}
				
				if(isset($scheduleData['partLevel']))
				{
					if(!isset($levelEndDatesArray[$scheduleData['partLevel']]))
					{
						$levelEndDatesArray[$scheduleData['partLevel']] = $targetDateEnd;
						//~ echo "geraldo".$targetDateEnd." ".$lotNumber." ".$scheduleData['partLevel'];
						
						if($scheduleData['partLevel']==1)
						{
							$levelEndDatesArray[$scheduleData['partLevel']] = $endDate;
						}
					}
				}
				
				$previousDate = $targetDateStart;
				$recentProcessCode = $processCode;
				
				if($processCode!=496) $resetFlag++;
			}
		}
		
		return $levelEndDatesArray;
	}
	
	//~ function generateScheduleItems($poIdArray,$setStartDate='',$rescheduleFlag=0,$viewDetailFlag=1,$simulationData=array())
	function generateScheduleItems($poId,$setStartDate='',$rescheduleFlag=0,$viewDetailFlag=1,$simulationData=array())
	{
		/* $rescheduleFlag = 1 if reschedule only
		 * $viewDetailFlag = 1 View Table for testing and debugging
		 */
		include('PHP Modules/mysqliConnection.php');
		
		$newDueDate = $thisLotOnly = $startProcessId = $endProcessId = $dateEnd = $remarksLog = '';
		if(is_array($setStartDate))
		{
			$setStartDateArray = $setStartDate;
			$setStartDate = (isset($setStartDateArray['start'])) ? $setStartDateArray['start'] : '';
			$newDueDate = (isset($setStartDateArray['dueDate'])) ? $setStartDateArray['dueDate'] : '';
			$thisLotOnly = (isset($setStartDateArray['lotNumber'])) ? $setStartDateArray['lotNumber'] : '';
			$startProcessId = (isset($setStartDateArray['startProcessId'])) ? $setStartDateArray['startProcessId'] : '';
			$endProcessId = (isset($setStartDateArray['endProcessId'])) ? $setStartDateArray['endProcessId'] : '';
			$dateEnd = (isset($setStartDateArray['dateEnd'])) ? $setStartDateArray['dateEnd'] : '';
			$remarksLog = (isset($setStartDateArray['remarksLog'])) ? $setStartDateArray['remarksLog'] : '';
		}
		
		//~ if(count($simulationData) > 0 AND in_array($_SESSION['idNumber'],array(0346,0280)))
		if(count($simulationData) > 0)
		{
			$rescheduleFlag = 0;
			$viewDetailFlag = 1;
		}
		
		if($rescheduleFlag==0 AND $viewDetailFlag==1)
		{
			$sql = "DELETE FROM system_temporaryworkschedule WHERE idNumber LIKE '".$_SESSION['idNumber']."'";
			$queryDelete = $db->query($sql);
		}
		
		//~ foreach($poIdArray as $poId)
		//~ {
			if($rescheduleFlag==0)
			{
				insertLotProcess($poId);
			}
			
			$workingTimeLeft = $workingTimeLimit = 30600;//8.5 hours
			//~ if($_SESSION['idNumber']=='0346') $workingTimeLeft = $workingTimeLimit = 57600;//16 hours
			
			$poNumber = $customerDeliveryDate = $dueDate = '';
			$sql = "SELECT poNumber, customerId, customerDeliveryDate, receiveDate FROM sales_polist WHERE poId = ".$poId." LIMIT 1";
			$queryPoList = $db->query($sql);
			if($queryPoList AND $queryPoList->num_rows > 0)
			{
				$resultPoList = $queryPoList->fetch_assoc();
				$poNumber = $resultPoList['poNumber'];
				$customerId = $resultPoList['customerId'];
				$customerDeliveryDate = $resultPoList['customerDeliveryDate'];
				$receiveDate = $resultPoList['receiveDate'];
				
				//~ if($_GET['country']==2)
				//~ {
					$sql = "SELECT answerDate FROM system_lotlist WHERE poId = ".$poId." AND answerDate != '0000-00-00' LIMIT 1";
					$queryLotList = $db->query($sql);
					if($queryLotList AND $queryLotList->num_rows > 0)
					{
						$resultLotList = $queryLotList->fetch_assoc();
						$customerDeliveryDate = $resultLotList['answerDate'];
					}
				//~ }
              
				//~ if($_SESSION['idNumber']=='0346')
				//~ {
					$sql = "SELECT lotNumber, poId FROM ppic_lotlist WHERE poId = ".$poId." AND identifier = 1 AND partLevel = 1";
					$queryLotList = $db->query($sql);
					if($queryLotList AND $queryLotList->num_rows > 0)
					{
						$resultLotList = $queryLotList->fetch_assoc();
						$loteLote = $resultLotList['lotNumber'];
							
						$sql = "SELECT id FROM ppic_workschedule WHERE lotNumber LIKE '".$loteLote."' AND processCode = 324 AND status = 0 LIMIT 1";
						$queryWorkSchedule = $db->query($sql);
						if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
						{
				
							$sql = "SELECT dueDate FROM ppic_roreviewdatatemp where poId=".$poId." AND dueDate != '0000-00-00' LIMIT 1";
							$queryRoReviewDataTemp = $db->query($sql);
							if($queryRoReviewDataTemp AND $queryRoReviewDataTemp->num_rows > 0)
							{
								$resultRoReviewDataTemp = $queryRoReviewDataTemp->fetch_assoc();
								$dueDate = $resultRoReviewDataTemp['dueDate'];
							}
							else
							{
								$sql = "SELECT dueDate FROM ppic_roreviewdata where poId=".$poId." AND dueDate != '0000-00-00' LIMIT 1";
								$queryRoReviewDataTemp = $db->query($sql);
								if($queryRoReviewDataTemp AND $queryRoReviewDataTemp->num_rows > 0)
								{
									$resultRoReviewDataTemp = $queryRoReviewDataTemp->fetch_assoc();
									$dueDate = $resultRoReviewDataTemp['dueDate'];
								}
								else
								{
									//~ if($_SESSION['idNumber']=='0346')//Activated 2020-07-22
									//~ {
										if($dueDate=='')
										{
											$deliveryType = '';
											$sql = "SELECT deliveryType FROM sales_customer WHERE customerId = ".$customerId." LIMIT 1";
											$queryCustomer = $db->query($sql);
											if($queryCustomer AND $queryCustomer->num_rows > 0)
											{
												$resultCustomer = $queryCustomer->fetch_assoc();
												$deliveryType = $resultCustomer['deliveryType'];
											}
											
											$deliveryInterval = 1;
											if($deliveryType==1)
											{
												$deliveryInterval = 5;//change from 1 day to 5 days 2019-08-02 sir roldan M, ma'am rose
											}
											else if($deliveryType==2)
											{
												$deliveryInterval = 7;
											}
											else if($deliveryType==3)
											{
												$deliveryInterval = 30;
											}
											
											$dueDate = date("Y-m-d",strtotime($customerDeliveryDate."-".$deliveryInterval." Days"));
											
											$day =  date('l', strtotime($dueDate));
											
											// -------------------------- Check If Incremented / Decremented Date Is Holiday Or Sunday ----------------------
											if($_GET['country']=='1')//Philippines
											{
												$sql = "SELECT holidayId FROM hr_holiday WHERE holidayDate = '".$dueDate."' AND holidayType < 6 LIMIT 1";
											}
											else if($_GET['country']=='2')//Japan
											{
												$sql = "SELECT holidayId FROM hr_holiday WHERE holidayDate = '".$dueDate."' AND holidayType >= 6 LIMIT 1";
											}
											$dc = $db->query($sql);
											$dcnum = $dc->num_rows;
											// -------------------------- Increment / Decrement Date If Holiday Or Sunday ----------------------
											if($day=='Sunday' OR $dcnum > 0)
											{
												$dueDate = addDays(-1,$dueDate);
											}
										}
									//~ }
								}
							}
						}
					}
				//~ }
				
				if($setStartDate=='receiveDate')
				{
					$lotNumberArray = array();
					$sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId = ".$poId." AND identifier = 1 AND partLevel = 1";
					$queryLotList = $db->query($sql);
					if($queryLotList AND $queryLotList->num_rows > 0)
					{
						while($resultLotList = $queryLotList->fetch_assoc())
						{
							$lotNumberArray[] = $resultLotList['lotNumber'];
						}
					}
					
					$setStartDate = $receiveDate;
					$sql = "SELECT actualFinish FROM ppic_workschedule WHERE lotNumber IN('".implode("','",$lotNumberArray)."') AND processCode = 459 AND status = 1 LIMIT 1";
					$queryWorkSchedule = $db->query($sql);
					if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
					{
						$resultWorkSchedule = $queryWorkSchedule->fetch_assoc();
						$setStartDate = $resultWorkSchedule['actualFinish'];
					}
					
					$rescheduleAllFlag = 1;
				}
				
				if($newDueDate!='')	$dueDate = $newDueDate;
			}
			
			//~ if($poId==1452341)	$dueDate = '2020-03-18';
			//~ if($poId==1404039)	$dueDate = '2019-06-22';
			//~ if($poId==1404039)	$dueDate = '2019-06-22';
			//~ if($poId==1413357)	$dueDate = '2019-08-07';
			//~ if($poId==1413513)	$dueDate = '2019-09-06';
			//~ if($_SESSION['idNumber']=='0412')	$dueDate = '2019-08-07';
			//~ if($poId==1411222)
			//~ {
				//~ $customerDeliveryDate = $dueDate = '2019-07-30';
			//~ }
			if($poId==1470442 OR $poId==1470443)
			{
				$customerDeliveryDate = $dueDate;
			}
			
			if($setStartDate=='')
			{
				$startDate = (date('H') >= 15) ? addDays(1) : date('Y-m-d');
			}
			else
			{
				$startDate = $setStartDate;
			}
			
			//~ $startDate = date('Y-m-d');
			//~ $customerDeliveryDate = '2018-06-14';
			
			//~ if(count($simulationData) > 0 AND in_array($_SESSION['idNumber'],array('0346','0280')))
			if(count($simulationData) > 0)
			{
				$startDate = $simulationData['startDate'];
				$customerDeliveryDate = $simulationData['customerDeliveryDate'];
				$poQuantity = $simulationData['poQuantity'];
				//~ $sqlFilter = "AND ROUND((LENGTH(lotNumber)-LENGTH(REPLACE(lotNumber,'-','')))/LENGTH('-')) = 2";
				
				$stPostArray = $simulationData['stPostArray'];
			}
			
			if($dateEnd!='')	$customerDeliveryDate = $dateEnd;
			
			$lastDate = ($dueDate=='') ? $customerDeliveryDate : $dueDate;
			
			$workingDays = 0;
			$tempDate = $startDate;
			$tempDate = addDays(1,date('Y-m-d',strtotime($tempDate.'-1 days')));
			while(strtotime($tempDate) <= strtotime($lastDate))
			{
				$tempDate = addDays(1,$tempDate);
				$workingDays++;
			}
			//~ echo "<br>gerald ".$workingDays;
			$deepestPartLevel = 1;
			$lotNumber = '';
			$sql = "SELECT DISTINCT partLevel FROM ppic_lotlist WHERE poId = ".$poId." AND identifier = 1 ORDER BY partLevel DESC LIMIT 1";
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				$resultLotList = $queryLotList->fetch_assoc();
				$deepestPartLevel = $resultLotList['partLevel'];
			}
			
			$lotNumber = '';
			$sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId = ".$poId." AND identifier = 1 AND partLevel = ".$deepestPartLevel." AND workingQuantity > 0 ORDER BY partLevel DESC LIMIT 1";
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				$resultLotList = $queryLotList->fetch_assoc();
				$lotNumber = $resultLotList['lotNumber'];
				/* Commnet 2019-02-11
				if($rescheduleFlag==0)
				{
					$bookingId = '';
					$sql = "SELECT bookingId FROM engineering_bookingdetails WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
					$queryBookingDetails = $db->query($sql);
					if($queryBookingDetails AND $queryBookingDetails->num_rows > 0)
					{
						$resultBookingDetails = $queryBookingDetails->fetch_assoc();
						$bookingId = $resultBookingDetails['bookingId'];
						
						$inventoryId = '';
						$sql = "SELECT inventoryId FROM engineering_booking WHERE bookingId = ".$bookingId." LIMIT 1";
						$queryBooking = $db->query($sql);
						if($queryBooking AND $queryBooking->num_rows > 0)
						{
							$resultBooking = $queryBooking->fetch_assoc();
							$inventoryId = $resultBooking['inventoryId'];
						}
						
						if(strstr($inventoryId,'TMP')!==FALSE)
						{
							if($workingDays > 7)
							{
								$materialDeliverySchedule = addDays(7);
								
								if(strtotime($materialDeliverySchedule) > strtotime($startDate))
								{
									$startDate = $materialDeliverySchedule;
									
									$workingDays = 0;
									$tempDate = $startDate;
									$tempDate = addDays(1,date('Y-m-d',strtotime($tempDate.'-1 days')));
									while(strtotime($tempDate) <= strtotime($customerDeliveryDate))
									{
										$tempDate = addDays(1,$tempDate);
										$workingDays++;
									}
								}
							}
						}
					}
				}*/
			}
			//~ echo "<br>gerald ".$workingDays;
			$loteArray = array();
			
			$lote = $lotNumber;
			$loopFlag = 1;
			while($loopFlag==1)
			{
				$loopFlag = 0;
				
				if($rescheduleFlag==1 AND $_SESSION['idNumber']=='*0346')
				{
					$rootLot = '';
					$sql = "SELECT parentLot FROM ppic_lotlist WHERE lotNumber LIKE '".$lote."' AND identifier = 1 AND parentLot != '' LIMIT 1";
					$queryLotList = $db->query($sql);
					if($queryLotList AND $queryLotList->num_rows > 0)
					{
						$resultLotList = $queryLotList->fetch_assoc();
						$rootLot = $resultLotList['parentLot'];
						
						$sql = "SELECT lotNumber FROM ppic_lotlist WHERE parentLot LIKE '".$rootLot."' AND identifier = 1 AND workingQuantity > 0";
						$queryChildLot = $db->query($sql);
						if($queryChildLot AND $queryChildLot->num_rows > 0)
						{
							while($resuChildLot = $queryChildLot->fetch_assoc())
							{
								$lote = $resuChildLot['lotNumber'];
								
								$sql = "SELECT lotNumber FROM view_workschedule WHERE lotNumber LIKE '".$lote."' LIMIT 1";
								$queryCheckProcess = $db->query($sql);
								if($queryCheckProcess AND $queryCheckProcess->num_rows > 0)
								{
									$sql = "SELECT parentLot FROM ppic_lotlist WHERE lotNumber LIKE '".$lote."' AND identifier = 1 AND parentLot != '' LIMIT 1";
									$queryLotList = $db->query($sql);
									if($queryLotList AND $queryLotList->num_rows > 0)
									{
										$resultLotList = $queryLotList->fetch_assoc();
										$lote = $resultLotList['parentLot'];
										$loopFlag = 1;
									}
									
									$loteArray[] = $lote;
									
									continue;
								}
							}
						}
					}
					else
					{
						$loteArray[] = $lote;
						
						$sql = "SELECT parentLot FROM ppic_lotlist WHERE lotNumber LIKE '".$lote."' AND identifier = 1 AND parentLot != '' LIMIT 1";
						$queryLotList = $db->query($sql);
						if($queryLotList AND $queryLotList->num_rows > 0)
						{
							$resultLotList = $queryLotList->fetch_assoc();
							$lote = $resultLotList['parentLot'];
							$loopFlag = 1;
						}
					}
				}
				else
				{
					$loteArray[] = $lote;
					
					$sql = "SELECT parentLot FROM ppic_lotlist WHERE lotNumber LIKE '".$lote."' AND identifier = 1 AND parentLot != '' LIMIT 1";
					$queryLotList = $db->query($sql);
					if($queryLotList AND $queryLotList->num_rows > 0)
					{
						$resultLotList = $queryLotList->fetch_assoc();
						$lote = $resultLotList['parentLot'];
						$loopFlag = 1;
					}
				}
			}
			
			$notInProcess = ($_GET['country']==2) ? "459,324" : "460,459,324,493,298,299";
			
			if($thisLotOnly!='')
			{
				$loteArray = array($thisLotOnly);
			}
			
			krsort($loteArray);
			
			if(count($loteArray) > 0)
			{
				$itemScheduleDataArray = $scheduleDataArray = array();
				
				$endDate = $targetFinish = $customerDeliveryDate;
				
				$totalStMix = 0;
				$sql = "SELECT lotNumber, partId, parentLot, partLevel, workingQuantity, identifier, patternId FROM ppic_lotlist WHERE lotNumber IN('".implode("','",$loteArray)."') AND identifier = 1 AND workingQuantity > 0 ORDER BY FIELD(lotNumber,'".implode("','",$loteArray)."')";
				$queryLotList = $db->query($sql);
				if($queryLotList AND $queryLotList->num_rows > 0)
				{
					while($resultLotList = $queryLotList->fetch_assoc())
					{
						$lotNumber = $resultLotList['lotNumber'];
						$partId = $resultLotList['partId'];
						$parentLot = $resultLotList['parentLot'];
						$partLevel = $resultLotList['partLevel'];
						$workingQuantity = $resultLotList['workingQuantity'];
						$identifier = $resultLotList['identifier'];
						$patternId = $resultLotList['patternId'];
						
						//~ if(count($simulationData) > 0 AND in_array($_SESSION['idNumber'],array('0346','0280')))
						if(count($simulationData) > 0)
						{
							if($partLevel == 1)
							{
								$workingQuantity = $poQuantity;
							}
							else if($partLevel > 1)
							{
								$parentPartId = '';
								$sql = "SELECT partId FROM ppic_lotlist WHERE lotNumber LIKE '".$parentLot."' LIMIT 1";
								$queryParentPartId = $db->query($sql);
								if($queryParentPartId AND $queryParentPartId->num_rows > 0)
								{
									$resultParentPartId = $queryParentPartId->fetch_assoc();
									$parentPartId = $resultParentPartId['partId'];
								}
								
								$quantity = 0;
								$sql = "SELECT quantity FROM cadcam_subparts WHERE parentId = ".$parentPartId." AND childId = ".$partId." AND identifier = ".$identifier." LIMIT 1";
								$querySubparts = $db->query($sql);
								if($querySubparts AND $querySubparts->num_rows > 0)
								{
									$resultSubparts = $querySubparts->fetch_assoc();
									$quantity = $resultSubparts['quantity'];
								}
								
								$workingQuantity = $poQuantity * $quantity;
							}
						}
						
						$stTotal = 0;
						
						$tableTable = '';
						
						$continueFlag = 1;
						$prevProcessCode = '';
						
						if($rescheduleFlag==0)
						{
							$sql = "SELECT listId, processCode, processSection FROM system_temporaryworkschedule WHERE idNumber LIKE '".$_SESSION['idNumber']."' AND destination = 0 AND lotNumber LIKE '".$lotNumber."' AND processCode NOT IN(".$notInProcess.") AND status = 0 ORDER BY processOrder DESC";
						}
						else
						{
							if($identifier==1)	insertItemHandlingProcess($lotNumber);
							
							if($rescheduleAllFlag==1)
							{
								$sql = "SELECT id, processCode, processSection FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode NOT IN(".$notInProcess.") ORDER BY processOrder DESC";
							}
							else
							{
								$sql = "SELECT id, processCode, processSection FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode NOT IN(".$notInProcess.") AND status = 0 ORDER BY processOrder DESC";
							}
						}
						$queryWorkSchedule = $db->query($sql);
						if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
						{
							while($resultWorkSchedule = $queryWorkSchedule->fetch_row())
							{
								$id = $resultWorkSchedule[0];
								$processCode = $resultWorkSchedule[1];
								$processSection = $resultWorkSchedule[2];
								
								//$thisLotOnly = $startProcess = $endProcess
								
								if($endProcessId!='' AND $startProcessId!='')
								{
									if($endProcessId == $id)
									{
										$continueFlag = 0;
									}
									else if($startProcessId == $prevProcessId)
									{
										$continueFlag = 1;
									}
									
									if($continueFlag==1)	continue;
								}
								
								$st = 0;
								$inputST = '';
								if($identifier==1)
								{
									$st = getStandardTime($partId,$processCode,$workingQuantity,$processSection,$lotNumber);
									
									if($processCode==136) $st = 0;
									
									//~ if(count($simulationData) > 0 AND in_array($_SESSION['idNumber'],array('0346','0280')))
									if(count($simulationData) > 0)
									{
										if(isset($stPostArray[$lotNumber."|".$processCode]) AND $stPostArray[$lotNumber."|".$processCode]!='')
										{
											$inputST = $st = $stPostArray[$lotNumber."|".$processCode];
											$st = $st * $workingQuantity;
										}
									}
									if($_SESSION['idNumber']=='0412*') $st = 0;
								}
								
								$scheduleDataArray[] = array('id'=>$id,'lotNumber'=>$lotNumber,'partLevel'=>$partLevel,'processCode'=>$processCode,'processSection'=>$processSection,'st'=>$st,'inputST'=>$inputST);
								
								//~ $stTotal += $st;
								
								//~ if(!isset($highestSTDataArray[$partLevel])) $highestSTDataArray[$partLevel] = 0;
								//~ if($stTotal > $highestSTDataArray[$partLevel])
								//~ {
									//~ $highestSTDataArray[$partLevel] = $stTotal;
								//~ }
								
								$totalStMix += $st;
								
								$prevProcessId = $id;
							}
						}
					}
				}
				
				$itemScheduleDataArray[] = array('lotNumber'=>$lotNumber,'partId'=>$partId,'identifier'=>$identifier,'partLevel'=>$partLevel,'workingQuantity'=>$workingQuantity,'scheduleDataArray'=>$scheduleDataArray,'totalStMix'=>$totalStMix,'workingDays'=>$workingDays,'startDate'=>$startDate,'endDate'=>$endDate,'dueDate'=>$dueDate);		
				
				$levelEndDatesArray = generateScheduleSingleItems($itemScheduleDataArray,$rescheduleFlag,$viewDetailFlag,$remarksLog);
				
				//~ print_r($levelEndDatesArray);
				
				if($thisLotOnly=='')
				{
					$itemScheduleDataArray = array();
					
					$lotNumberPartialBatchArray = array();
				
					$sql = "SELECT lotNumber, partId, parentLot, partLevel, workingQuantity, identifier, patternId, partialBatchId FROM ppic_lotlist WHERE poId = ".$poId." AND identifier IN(1,2) AND workingQuantity > 0 AND lotNumber NOT IN('".implode("','",$loteArray)."')";
					$queryLotList = $db->query($sql);
					if($queryLotList AND $queryLotList->num_rows > 0)
					{
						while($resultLotList = $queryLotList->fetch_assoc())
						{
							$lotNumber = $resultLotList['lotNumber'];
							$partId = $resultLotList['partId'];
							$parentLot = $resultLotList['parentLot'];
							$partLevel = $resultLotList['partLevel'];
							$workingQuantity = $resultLotList['workingQuantity'];
							$identifier = $resultLotList['identifier'];
							$patternId = $resultLotList['patternId'];
							$partialBatchId = $resultLotList['partialBatchId'];
							
							if($partialBatchId > 0 AND $rescheduleFlag==0)
							{
								$lotNumberPartialBatchArray[] = array($lotNumber,$partialBatchId);
								continue;
							}
							
							$endDate = $targetFinish = $customerDeliveryDate;
							if(isset($levelEndDatesArray[$partLevel]))
							{
								$endDate = $targetFinish = $levelEndDatesArray[$partLevel];
							}
							
							if(!isset($levelEndDatesArray[$partLevel]) AND $identifier==2)
							{
								$endDate = $levelEndDatesArray[($partLevel-1)];
							}
							
							if($_SESSION['idNumber']=='0412*')
							{
								if($identifier==2)
								{
									$parentPartId = '';
									$sql = "SELECT partId FROM ppic_lotlist WHERE lotNumber LIKE '".$parentLot."' LIMIT 1";
									$queryParentPartId = $db->query($sql);
									if($queryParentPartId AND $queryParentPartId->num_rows > 0)
									{
										$resultParentPartId = $queryParentPartId->fetch_assoc();
										$parentPartId = $resultParentPartId['partId'];
									}
									
									$subpartProcessArray = array();
									$sql = "SELECT processCode FROM engineering_subpartprocesslink WHERE partId = ".$parentPartId." AND childId = ".$partId." AND patternId = ".$patternId."";
									$querySubpartProcessLink = $db->query($sql);
									if($querySubpartProcessLink AND $querySubpartProcessLink->num_rows > 0)
									{
										while($resultSubpartProcessLink = $querySubpartProcessLink->fetch_assoc())
										{
											$subpartProcessArray[] = $resultSubpartProcessLink['processCode'];
										}
									}
									
									if($rescheduleFlag==0)
									{
										$sql = "SELECT targetStart FROM system_temporaryworkschedule WHERE idNumber LIKE '".$_SESSION['idNumber']."' AND destination = 0 AND lotNumber LIKE '".$parentLot."' AND processCode IN(".implode(",",$subpartProcessArray).") ORDER BY processOrder LIMIT 1";
									}
									else
									{
										$sql = "SELECT targetStart FROM ppic_workschedule WHERE lotNumber LIKE '".$parentLot."' AND processCode IN(".implode(",",$subpartProcessArray).") ORDER BY processOrder LIMIT 1";
									}
									$queryWorkSchedule = $db->query($sql);
									if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
									{
										$resultWorkSchedule = $queryWorkSchedule->fetch_assoc();
										$endDate = $resultWorkSchedule['targetStart'];
									}
								}
							}
							
							$workingDays = 0;
							$tempDate = $startDate;
							$tempDate = addDays(1,date('Y-m-d',strtotime($tempDate.'-1 days')));
							while(strtotime($tempDate) <= strtotime($targetFinish))
							{
								$tempDate = addDays(1,$tempDate);
								$workingDays++;
							}						
							
							/* Commnet 2019-04-01
							if($rescheduleFlag==0)
							{
								$bookingId = '';
								$sql = "SELECT bookingId FROM engineering_bookingdetails WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
								$queryBookingDetails = $db->query($sql);
								if($queryBookingDetails AND $queryBookingDetails->num_rows > 0)
								{
									$resultBookingDetails = $queryBookingDetails->fetch_assoc();
									$bookingId = $resultBookingDetails['bookingId'];
									
									$inventoryId = '';
									$sql = "SELECT inventoryId FROM engineering_booking WHERE bookingId = ".$bookingId." LIMIT 1";
									$queryBooking = $db->query($sql);
									if($queryBooking AND $queryBooking->num_rows > 0)
									{
										$resultBooking = $queryBooking->fetch_assoc();
										$inventoryId = $resultBooking['inventoryId'];
									}
									
									if(strstr($inventoryId,'TMP')!==FALSE)
									{
										if($workingDays > 7)
										{
											$materialDeliverySchedule = addDays(7);
											
											if(strtotime($materialDeliverySchedule) > strtotime($startDate))
											{
												$startDate = $materialDeliverySchedule;
												
												$workingDays = 0;
												$tempDate = $startDate;
												$tempDate = addDays(1,date('Y-m-d',strtotime($tempDate.'-1 days')));
												while(strtotime($tempDate) <= strtotime($targetFinish))
												{
													$tempDate = addDays(1,$tempDate);
													$workingDays++;
												}
											}
										}
									}
								}
							*/
							
							$scheduleDataArray = array();
							
							$stTotal = 0;
							
							$tableTable = '';
							
							if($rescheduleFlag==0)
							{
								$sql = "SELECT listId, processCode, processSection FROM system_temporaryworkschedule WHERE idNumber LIKE '".$_SESSION['idNumber']."' AND destination = 0 AND lotNumber LIKE '".$lotNumber."' AND processCode NOT IN(".$notInProcess.") AND status = 0 ORDER BY processOrder DESC";
							}
							else
							{
								if($identifier==1)	insertItemHandlingProcess($lotNumber);
								
								$sql = "SELECT id, processCode, processSection FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode NOT IN(".$notInProcess.") AND status = 0 ORDER BY processOrder DESC";
							}
							$queryWorkSchedule = $db->query($sql);
							if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
							{
								while($resultWorkSchedule = $queryWorkSchedule->fetch_row())
								{
									$id = $resultWorkSchedule[0];
									$processCode = $resultWorkSchedule[1];
									$processSection = $resultWorkSchedule[2];
									
									$st = 0;
									$inputST = '';
									if($identifier==1)
									{
										$st = getStandardTime($partId,$processCode,$workingQuantity,$processSection,$lotNumber);
										
										if($processCode==136) $st = 0;
										
										//~ if(count($simulationData) > 0 AND in_array($_SESSION['idNumber'],array('0346','0280')))
										if(count($simulationData) > 0)
										{
											if(isset($stPostArray[$lotNumber."|".$processCode]) AND $stPostArray[$lotNumber."|".$processCode]!='')
											{
												$inputST = $st = $stPostArray[$lotNumber."|".$processCode];
												$st = $st * $workingQuantity;
											}
										}
										if($_SESSION['idNumber']=='0412*') $st = 0;
										//~ echo $_SESSION['idNumber'];
									}
									
									$scheduleDataArray[] = array('id'=>$id,'processCode'=>$processCode,'processSection'=>$processSection,'st'=>$st,'inputST'=>$inputST);
									
									$stTotal += $st;
									
									if(!isset($highestSTDataArray[$partLevel])) $highestSTDataArray[$partLevel] = 0;
									if($stTotal > $highestSTDataArray[$partLevel])
									{
										$highestSTDataArray[$partLevel] = $stTotal;
									}
								}
							}
							
							$itemScheduleDataArray[] = array('lotNumber'=>$lotNumber,'partId'=>$partId,'identifier'=>$identifier,'partLevel'=>$partLevel,'workingQuantity'=>$workingQuantity,'scheduleDataArray'=>$scheduleDataArray,'highestSTDataArray'=>$highestSTDataArray,'workingDays'=>$workingDays,'startDate'=>$startDate,'endDate'=>$endDate,'dueDate'=>$dueDate);
						}
						
						generateScheduleSingleItems($itemScheduleDataArray,$rescheduleFlag,$viewDetailFlag,$remarksLog);	
					}
					
					if(count($lotNumberPartialBatchArray) > 0)
					{
						foreach($lotNumberPartialBatchArray as $lotNumberPartialBatch)
						{
							$lotNumber = $lotNumberPartialBatch[0];
							$partialBatchId = $lotNumberPartialBatch[1];
							
							$sql = "SELECT listId, processCode, processOrder, targetStart, targetFinish FROM system_temporaryworkschedule WHERE idNumber LIKE '".$_SESSION['idNumber']."' AND destination = 0 AND lotNumber LIKE SUBSTRING_INDEX('".$lotNumber."','-',3) AND processCode NOT IN(".$notInProcess.") AND status = 0 ORDER BY processOrder DESC";
							//~ if($lotNumber=='20-07-3096-1') echo $sql;
							$queryWorkSchedule = $db->query($sql);
							if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
							{
								while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
								{
									$listId = $resultWorkSchedule['listId'];
									$processCode = $resultWorkSchedule['processCode'];
									$processOrder = $resultWorkSchedule['processOrder'];
									$oldSargetStart = $resultWorkSchedule['targetStart'];
									$oldTargetFinish = $resultWorkSchedule['targetFinish'];
									
									//~ if($lotNumber=='20-07-3096-1')
									//~ {
										//~ echo "<hr>".$sql;
										//~ echo "<br>".$oldSargetStart;
										//~ echo "<br>".$oldTargetFinish;
									//~ }
									
									if($processCode==144)
									{
										$newTargetStart = $oldSargetStart;
										$newTargetFinish = $oldTargetFinish;
									}
									else
									{
										$newTargetStart = addDays(-$partialBatchId,$oldSargetStart);
										$newTargetFinish = addDays(-$partialBatchId,$oldTargetFinish);
										
										//~ if($lotNumber=='20-07-3096-1')
										//~ {
											//~ echo "<br>oldSargetStart = ".$oldSargetStart." newTargetStart = ".$newTargetStart."";
											//~ echo "<br>oldTargetFinish = ".$oldTargetFinish." newTargetFinish = ".$newTargetFinish."";
										//~ }
										
										if(strtotime($newTargetStart) < strtotime($startDate))	$newTargetStart = $startDate;
										if(strtotime($newTargetFinish) < strtotime($startDate))	$newTargetFinish = $startDate;
									}
									
									$sql = "UPDATE system_temporaryworkschedule SET targetStart = '".$newTargetStart."', targetFinish = '".$newTargetFinish."' WHERE idNumber LIKE '".$_SESSION['idNumber']."' AND destination = 0 AND lotNumber LIKE '".$lotNumber."' AND processCode = ".$processCode." AND processOrder = ".$processOrder." AND status = 0 LIMIT 1";
									$queryUpdate = $db->query($sql);
								}
							}
							
							//~ if($lotNumber=='20-07-3096-1') break;							
						}
					}
				}
			}
			
			//~ /* Comment 2018-08-09 16:15:00	 Sir Mar, Sir Ace, Sir Ryan Conflict in no allowance algo
			// -------------------------------------------------- Nesting, Withdrawal, Blanking Process same target finish per Part Level -------------------------------------------------- //
			//2018-08-27 snow cover SCAD items only Sir Ryan //Activated 2018-08-31 04:25
			$partIdArray = array();
			$sql = "SELECT partId FROM cadcam_parts WHERE partName LIKE '%snow cover%' AND customerId = 9";
			$queryParts = $db->query($sql);
			if($queryParts AND $queryParts->num_rows > 0)
			{
				while($resultParts = $queryParts->fetch_assoc())
				{
					$partIdArray[] = $resultParts['partId'];
				}
			}
			
			$lotNumberArray = array();
			$sql = "SELECT lotNumber, partId, parentLot, partLevel, workingQuantity, identifier, patternId FROM ppic_lotlist WHERE poId = ".$poId." AND partId IN(".implode(",",$partIdArray).") AND identifier IN(1,2) AND workingQuantity > 0";
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				while($resultLotList = $queryLotList->fetch_assoc())
				{
					$lotNumberArray[] = $resultLotList['lotNumber'];
				}
				
				if($rescheduleFlag==0 OR $viewDetailFlag == 1)
				{
					$sql = "SELECT lotNumber FROM system_temporaryworkschedule WHERE lotNumber IN('".implode("','",$lotNumberArray)."') AND idNumber LIKE '".$_SESSION['idNumber']."' AND destination = 0 AND processCode IN(312,430) AND status = 0";
				}
				else
				{
					$sql = "SELECT lotNumber FROM ppic_workschedule WHERE lotNumber IN('".implode("','",$lotNumberArray)."') AND processCode IN(312,430) AND status = 0";
				}
				$queryWorkSchedule = $db->query($sql);
				if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
				{
					$lotNumberArray = array();
					while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
					{
						$lotNumberArray[] = $resultWorkSchedule['lotNumber'];
					}
				}
				
				$partLevelArray = $lotePerLevel = array();
				$sql = "SELECT lotNumber, partLevel FROM ppic_lotlist WHERE lotNumber IN('".implode("','",$lotNumberArray)."')";
				$queryLotList = $db->query($sql);
				if($queryLotList AND $queryLotList->num_rows > 0)
				{
					while($resultLotList = $queryLotList->fetch_assoc())
					{
						$lotNumber = $resultLotList['lotNumber'];
						$partLevel = $resultLotList['partLevel'];
						
						$lotePerLevel[$partLevel][] = $lotNumber;
						
						if(!in_array($partLevel,$partLevelArray))
						{
							$partLevelArray[] = $partLevel;
						}
					}
				}
				
				foreach($partLevelArray as $partLevel)
				{
					$lotNumbersArray = $lotePerLevel[$partLevel];
					
					if($rescheduleFlag==0 OR $viewDetailFlag == 1)
					{
						$sql = "SELECT lotNumber, processCode FROM system_temporaryworkschedule WHERE lotNumber IN('".implode("','",$lotNumbersArray)."') AND idNumber LIKE '".$_SESSION['idNumber']."' AND destination = 0 AND processCode IN(312,430) AND status = 0 ORDER BY targetFinish LIMIT 1";
					}
					else
					{
						$sql = "SELECT lotNumber, processCode FROM ppic_workschedule WHERE lotNumber IN('".implode("','",$lotNumbersArray)."') AND processCode IN(312,430) AND status = 0 ORDER BY targetFinish LIMIT 1";
					}
					$queryWorkSchedule = $db->query($sql);
					if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
					{
						$resultWorkSchedule = $queryWorkSchedule->fetch_assoc();
						$lotNumber = $resultWorkSchedule['lotNumber'];
						$processCode = $resultWorkSchedule['processCode'];
						
						$processSet = ($processCode==312) ? "312,136,86,184,533,496" : "430,136,381,184,533,496";
						
						if($rescheduleFlag==0 OR $viewDetailFlag == 1)
						{
							$sql = "SELECT lotNumber, processCode, targetStart, targetFinish FROM system_temporaryworkschedule WHERE lotNumber LIKE '".$lotNumber."' AND idNumber LIKE '".$_SESSION['idNumber']."' AND destination = 0 AND processCode IN(".$processSet.") AND status = 0 ORDER BY processOrder LIMIT 6";
						}
						else
						{
							if($rescheduleAllFlag==1)
							{
								$sql = "SELECT lotNumber, processCode, targetStart, targetFinish FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode IN(".$processSet.") ORDER BY processOrder LIMIT 6";
							}
							else
							{
								$sql = "SELECT lotNumber, processCode, targetStart, targetFinish FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode IN(".$processSet.") AND status = 0 ORDER BY processOrder LIMIT 6";
							}
						}
						$queryWorkSchedule = $db->query($sql);
						if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
						{
							while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
							{
								$lotNumber = $resultWorkSchedule['lotNumber'];
								$processCode = $resultWorkSchedule['processCode'];
								$targetStart = $resultWorkSchedule['targetStart'];
								$targetFinish = $resultWorkSchedule['targetFinish'];
								
								//~ if(in_array($processCode,$processSet))
								if(strstr($processSet,$processCode))
								{
									$processCodes = $processCode;
									
									if(in_array($processCode,array(312,430)))	$processCodes = "312,430";
									else if(in_array($processCode,array(86,381)))	$processCodes = "86,381";
									
									if($processCode==496)
									{
										foreach($lotNumbersArray as $lote)
										{
											if($rescheduleFlag==0 OR $viewDetailFlag == 1)
											{
												$sql = "UPDATE system_temporaryworkschedule SET targetStart = '".$targetStartPrev."', targetFinish = '".$targetFinishPrev."' WHERE lotNumber LIKE '".$lote."' AND idNumber LIKE '".$_SESSION['idNumber']."' AND destination = 0 AND processCode = 496 AND status = 0 ORDER BY processOrder LIMIT 1";
												$queryUpdate = $db->query($sql);
											}
											else
											{
												if($rescheduleAllFlag==1)
												{
													$sql = "UPDATE ppic_workschedule SET targetStart = '".$targetStartPrev."', targetFinish = '".$targetFinishPrev."' WHERE lotNumber LIKE '".$lote."' AND processCode = 496 ORDER BY processOrder LIMIT 1";
													$queryUpdate = $db->query($sql);
												}
												else
												{
													$sql = "UPDATE ppic_workschedule SET targetStart = '".$targetStartPrev."', targetFinish = '".$targetFinishPrev."' WHERE lotNumber LIKE '".$lote."' AND processCode = 496 AND status = 0 ORDER BY processOrder LIMIT 1";
													$queryUpdate = $db->query($sql);
													if($_SESSION['idNumber']=='0346')	echo "<hr>".$sql;
												}
											}
										}
									}
									else
									{
										if($rescheduleFlag==0 OR $viewDetailFlag == 1)
										{
											$sql = "UPDATE system_temporaryworkschedule SET targetStart = '".$targetStart."', targetFinish = '".$targetFinish."' WHERE lotNumber IN('".implode("','",$lotNumbersArray)."') AND idNumber LIKE '".$_SESSION['idNumber']."' AND destination = 0 AND processCode IN(".$processCodes.") AND status = 0 ORDER BY processOrder";
											$queryUpdate = $db->query($sql);
										}
										else
										{
											if($rescheduleAllFlag==1)
											{
												$sql = "UPDATE ppic_workschedule SET targetStart = '".$targetStart."', targetFinish = '".$targetFinish."' WHERE lotNumber IN('".implode("','",$lotNumbersArray)."') AND processCode IN(".$processCodes.") ORDER BY processOrder";
												$queryUpdate = $db->query($sql);
											}
											else
											{
												$sql = "UPDATE ppic_workschedule SET targetStart = '".$targetStart."', targetFinish = '".$targetFinish."' WHERE lotNumber IN('".implode("','",$lotNumbersArray)."') AND processCode IN(".$processCodes.") AND status = 0 ORDER BY processOrder";
												$queryUpdate = $db->query($sql);
												//~ if($_SESSION['idNumber']=='0346')	echo "<hr>".$sql;
											}
										}
										
										$targetStartPrev = $targetStart;
										$targetFinishPrev = $targetFinish;
									}
								}
							}
							
							
						}
					}
				}
			}
			// ------------------------------------------------ END Nesting, Withdrawal, Blanking Process same target finish per Part Level ------------------------------------------------ //
			//~ */
			
			/*
			$sql = "SELECT lotNumber, partialBatchId FROM ppic_lotlist WHERE poId = ".$poId." AND identifier IN(1,2) AND partialBatchId > 0";
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0 AND $_SESSION['idNumber']=='0346')
			{
				while($resultLotList = $queryLotList->fetch_assoc())
				{
					$lotNumber = $resultLotList['lotNumber'];
					$partialBatchId = $resultLotList['partialBatchId'];
					
					if($rescheduleFlag==0 OR $viewDetailFlag == 1)
					{
						$sql = "SELECT listId, processCode FROM system_temporaryworkschedule WHERE lotNumber LIKE '".$lotNumber."' AND idNumber LIKE '".$_SESSION['idNumber']."' AND destination = 0 AND status = 0 ORDER BY processOrder";
					}
					else
					{
						if($rescheduleAllFlag==1)
						{
							$sql = "SELECT id, processCode FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' ORDER BY processOrder";
						}
						else
						{
							$sql = "SELECT id, processCode FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND status = 0 ORDER BY processOrder";
						}
					}
					$queryWorkSchedule = $db->query($sql);
					if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
					{
						while($resultWorkSchedule = $queryWorkSchedule->fetch_row())
						{
							$id = $resultWorkSchedule[0];
							$processCode = $resultWorkSchedule[1];
							
							if($rescheduleFlag==0 OR $viewDetailFlag == 1)
							{
								$sql = "SELECT targetStart, targetFinish FROM system_temporaryworkschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode = ".$processCode." AND idNumber LIKE '".$_SESSION['idNumber']."' AND destination = 0 AND status = 0 ORDER BY processOrder";
							}
							else
							{
								if($rescheduleAllFlag==1)
								{
									$sql = "SELECT targetStart, targetFinish FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode = ".$processCode." ORDER BY processOrder";
								}
								else
								{
									$sql = "SELECT targetStart, targetFinish FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode = ".$processCode." AND status = 0 ORDER BY processOrder";
								}
							}
							
							//~ addDays(1,$targetStart);
						}
					}
				}
			}*/
			
			if($_SESSION['idNumber']=='0412*')
			{
				// -------------------------------------------------- Set Assembly same target finish per Part Level -------------------------------------------------- //
				$lotNumberArray = array();
				$sql = "SELECT lotNumber, partId, parentLot, partLevel, workingQuantity, identifier, patternId FROM ppic_lotlist WHERE poId = ".$poId." AND identifier IN(1,2) AND workingQuantity > 0";
				$queryLotList = $db->query($sql);
				if($queryLotList AND $queryLotList->num_rows > 0)
				{
					while($resultLotList = $queryLotList->fetch_assoc())
					{
						$lotNumberArray[] = $resultLotList['lotNumber'];
					}
				}
				
				if($rescheduleFlag==0 OR $viewDetailFlag == 1)
				{
					$sql = "SELECT lotNumber FROM system_temporaryworkschedule WHERE lotNumber IN('".implode("','",$lotNumberArray)."') AND idNumber LIKE '".$_SESSION['idNumber']."' AND destination = 0 AND processCode IN(535) AND status = 0";
				}
				else
				{
					if($rescheduleAllFlag==1)
					{
						$sql = "SELECT lotNumber FROM ppic_workschedule WHERE lotNumber IN('".implode("','",$lotNumberArray)."') AND processCode IN(535)";
					}
					else
					{
						$sql = "SELECT lotNumber FROM ppic_workschedule WHERE lotNumber IN('".implode("','",$lotNumberArray)."') AND processCode IN(535) AND status = 0";
					}
				}
				$queryWorkSchedule = $db->query($sql);
				if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
				{
					$lotNumberArray = array();
					while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
					{
						$lotNumberArray[] = $resultWorkSchedule['lotNumber'];
					}
				}
				
				$partLevelArray = $lotePerLevel = array();
				$sql = "SELECT lotNumber, partLevel FROM ppic_lotlist WHERE lotNumber IN('".implode("','",$lotNumberArray)."')";
				$queryLotList = $db->query($sql);
				if($queryLotList AND $queryLotList->num_rows > 0)
				{
					while($resultLotList = $queryLotList->fetch_assoc())
					{
						$lotNumber = $resultLotList['lotNumber'];
						$partLevel = $resultLotList['partLevel'];
						
						$lotePerLevel[$partLevel][] = $lotNumber;
						
						if(!in_array($partLevel,$partLevelArray))
						{
							$partLevelArray[] = $partLevel;
						}
					}
				}
				
				foreach($partLevelArray as $partLevel)
				{
					$lotNumbersArray = $lotePerLevel[$partLevel];
					
					if($rescheduleFlag==0 OR $viewDetailFlag == 1)
					{
						$sql = "SELECT lotNumber, processSection, targetFinish FROM system_temporaryworkschedule WHERE lotNumber IN('".implode("','",$lotNumbersArray)."') AND idNumber LIKE '".$_SESSION['idNumber']."' AND destination = 0 AND processCode IN(535) AND status = 0 ORDER BY targetFinish LIMIT 1";
					}
					else
					{
						if($rescheduleAllFlag==1)
						{
							$sql = "SELECT lotNumber, processSection, targetFinish FROM ppic_workschedule WHERE lotNumber IN('".implode("','",$lotNumbersArray)."') AND processCode IN(535) ORDER BY targetFinish LIMIT 1";
						}
						else
						{
							$sql = "SELECT lotNumber, processSection, targetFinish FROM ppic_workschedule WHERE lotNumber IN('".implode("','",$lotNumbersArray)."') AND processCode IN(535) AND status = 0 ORDER BY targetFinish LIMIT 1";
						}
					}
					$queryWorkSchedule = $db->query($sql);
					if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
					{
						$resultWorkSchedule = $queryWorkSchedule->fetch_assoc();
						$lotNumber = $resultWorkSchedule['lotNumber'];
						$processSection = $resultWorkSchedule['processSection'];
						$setAssemblyDate = $resultWorkSchedule['targetFinish'];
						
						if($rescheduleFlag==0 OR $viewDetailFlag == 1)
						{
							$sql = "SELECT lotNumber, processOrder, processSection, targetFinish FROM system_temporaryworkschedule WHERE lotNumber IN('".implode("','",$lotNumbersArray)."') AND lotNumber NOT LIKE '".$lotNumber."' AND idNumber LIKE '".$_SESSION['idNumber']."' AND destination = 0 AND processCode IN(535) AND status = 0 ORDER BY targetFinish";
						}
						else
						{
							if($rescheduleAllFlag==1)
							{
								$sql = "SELECT lotNumber, processOrder, processSection, targetFinish FROM ppic_workschedule WHERE lotNumber IN('".implode("','",$lotNumbersArray)."') AND lotNumber NOT LIKE '".$lotNumber."' AND processCode IN(535) ORDER BY targetFinish";
							}
							else
							{
								$sql = "SELECT lotNumber, processOrder, processSection, targetFinish FROM ppic_workschedule WHERE lotNumber IN('".implode("','",$lotNumbersArray)."') AND lotNumber NOT LIKE '".$lotNumber."' AND processCode IN(535) AND status = 0 ORDER BY targetFinish";
							}
						}
						$queryWorkSched = $db->query($sql);
						if($queryWorkSched AND $queryWorkSched->num_rows > 0)
						{
							while($resultWorkSched = $queryWorkSched->fetch_assoc())
							{
								$lotNumber = $resultWorkSched['lotNumber'];
								$processOrder = $resultWorkSched['processOrder'];
								$processSection = $resultWorkSched['processSection'];
								$targetFinish = $resultWorkSched['targetFinish'];
								
								if(strtotime($targetFinish) > strtotime($setAssemblyDate))
								{
									$gapDays = 0;
									$tempDate = $setAssemblyDate;
									$tempDate = addDays(1,date('Y-m-d',strtotime($tempDate.'-1 days')));
									while(strtotime($tempDate) < strtotime($targetFinish))
									{
										$tempDate = addDays(1,$tempDate);
										$gapDays++;
									}
									
									if($rescheduleFlag==0 OR $viewDetailFlag == 1)
									{
										$sql = "SELECT listId, targetStart, targetFinish, processCode FROM system_temporaryworkschedule WHERE lotNumber LIKE '".$lotNumber."' AND idNumber LIKE '".$_SESSION['idNumber']."' AND destination = 0 AND processOrder <= ".$processOrder." AND status = 0 ORDER BY targetFinish";
									}
									else
									{
										if($rescheduleAllFlag==1)
										{
											$sql = "SELECT id, targetStart, targetFinish, processCode FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processOrder <= ".$processOrder." ORDER BY targetFinish";
										}
										else
										{
											$sql = "SELECT id, targetStart, targetFinish, processCode FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processOrder <= ".$processOrder." AND status = 0 ORDER BY targetFinish";
										}
									}
									$queryWorkS = $db->query($sql);
									if($queryWorkS AND $queryWorkS->num_rows > 0)
									{
										while($resultWorkS = $queryWorkS->fetch_row())
										{
											$id = $resultWorkS[0];
											$targetStart = $resultWorkS[1];
											$targetFinish = $resultWorkS[2];
											
											$newTargetStart = addDays(-$gapDays,$targetStart);
											$newTargetFinish = addDays(-$gapDays,$targetFinish);
											
											if($rescheduleFlag==0 OR $viewDetailFlag == 1)
											{
												$sql = "UPDATE system_temporaryworkschedule SET targetStart = '".$newTargetStart."', targetFinish = '".$newTargetFinish."' WHERE listId = ".$id." LIMIT 1";
												echo "<hr>".$sql;
												echo "<br>".$gapDays." ".$targetStart." ".$targetFinish." ".$lotNumber." ".$resultWorkS[3];
												$queryUpdate = $db->query($sql);
											}
											else
											{
												$sql = "UPDATE ppic_workschedule SET targetStart = '".$newTargetStart."', targetFinish = '".$newTargetFinish."' WHERE id = ".$id." LIMIT 1";
												$queryUpdate = $db->query($sql);
											}
										}
									}
								}
							}
						}
						
					}
				}
				// ------------------------------------------------ Set Assembly same target finish per Part Level ------------------------------------------------ //
			}
			
			//~ if($viewDetailFlag == 1 AND $_SESSION['idNumber']=='0346')
			if($viewDetailFlag == 1 AND in_array($_SESSION['idNumber'],array('*0346','*0280')))
			{
				$lotNumberArray = array();
				$sql = "SELECT DISTINCT lotNumber FROM system_temporaryworkschedule WHERE idNumber LIKE '".$_SESSION['idNumber']."' AND destination = 0";
				$queryTemporaryWorkschedule = $db->query($sql);
				if($queryTemporaryWorkschedule AND $queryTemporaryWorkschedule->num_rows > 0)
				{
					while($resultTemporaryWorkschedule = $queryTemporaryWorkschedule->fetch_assoc())
					{
						$lotNumberArray[] = $resultTemporaryWorkschedule['lotNumber'];
					}
					
					echo "<table><tr><td>";
					
					echo "<table border='1' style='width:65vw;'>";
					
					ksort($loteArray);
					
					//~ $sql = "SELECT lotNumber FROM ppic_lotlist WHERE lotNumber IN('".implode("','",$lotNumberArray)."') ORDER BY partLevel DESC";
					$sql = "SELECT lotNumber FROM ppic_lotlist WHERE lotNumber IN('".implode("','",$loteArray)."') AND identifier = 1 AND workingQuantity > 0 ORDER BY FIELD(lotNumber,'".implode("','",$loteArray)."')";
					$queryLotList = $db->query($sql);
					if($queryLotList AND $queryLotList->num_rows > 0)
					{
						while($resultLotList = $queryLotList->fetch_assoc())
						{
							$lotNumber = $resultLotList['lotNumber'];
							
							$totalSt = 0;
							$previousDate = '';
							
							$sql = "SELECT lotNumber, processOrder, processCode, processSection, targetStart, targetFinish, standardTime, inputST FROM system_temporaryworkschedule WHERE lotNumber LIKE '".$lotNumber."' AND idNumber LIKE '".$_SESSION['idNumber']."' AND destination = 0 ORDER BY processOrder";
							$queryTemporaryWorkschedule = $db->query($sql);
							if($queryTemporaryWorkschedule AND $queryTemporaryWorkschedule->num_rows > 0)
							{
								while($resultTemporaryWorkschedule = $queryTemporaryWorkschedule->fetch_assoc())
								{
									$lotNumber = $resultTemporaryWorkschedule['lotNumber'];
									$processOrder = $resultTemporaryWorkschedule['processOrder'];
									$processCode = $resultTemporaryWorkschedule['processCode'];
									$processSection = $resultTemporaryWorkschedule['processSection'];
									$targetDateStart = $resultTemporaryWorkschedule['targetStart'];
									$targetDateEnd = $resultTemporaryWorkschedule['targetFinish'];
									$st = $resultTemporaryWorkschedule['standardTime'];
									$inputST = $resultTemporaryWorkschedule['inputST'];
									
									if($inputST == 0)	$inputST = '';
									
									$processName = $section = '';
									$sql = "SELECT processName, processSection FROM cadcam_process WHERE processCode = ".$processCode." LIMIT 1";
									$queryProcess = $db->query($sql);
									if($queryProcess AND $queryProcess->num_rows > 0)
									{
										$resultProcess = $queryProcess->fetch_assoc();
										$processName = $resultProcess['processName'];
										$section = $resultProcess['processSection'];
									}
									
									$sectionName = '';
									$sql = "SELECT sectionName, motherSectionId, departmentId FROM ppic_section WHERE sectionId = ".$processSection." LIMIT 1";
									$querySection = $db->query($sql);
									if($querySection AND $querySection->num_rows > 0)
									{
										$resultSection = $querySection->fetch_assoc();
										$sectionName = $resultSection['sectionName'];
									}
									
									$totalStTD = "";
									if($previousDate!=$targetDateEnd)
									{
										if($totalSt > 0)
										{
											echo "
												<tr>
													<td></td>
													<td colspan='8' align='right'>Total ST</td>
													<td>".convertSeconds($totalSt)."</td>
												</tr>
											";
										}
										
										$totalSt = 0;
									}
									
									$totalSt += $st;
									
									if($previousDate!='')
									{
										$current = strtotime($previousDate);
										$last = strtotime($targetDateStart);
										$dates = array();
										while($current <= $last)
										{
											$dateDate = date('Y-m-d',$current);
											
											$sql = "SELECT holidayName FROM hr_holiday WHERE holidayDate = '".$dateDate."' AND holidayType < 6 LIMIT 1";
											$queryHoliday = $db->query($sql);
											if($queryHoliday AND $queryHoliday->num_rows > 0)
											{
												echo "
													<tr bgcolor='pink'>
														<td></td>
														<td colspan='8' align='center'>Holiday (".$dateDate.")</td>
														<td></td>
													</tr>
												";
											}
											else if(date('w',$current)==0)
											{
												echo "
													<tr bgcolor='pink'>
														<td></td>
														<td colspan='8' align='center'>Sunday (".$dateDate.")</td>
														<td></td>
													</tr>
												";
											}
											
											$current = strtotime('+1 day', $current);
										}
									}
									
									$stInput = "";
									if($identifier==1 AND $processCode!=496)
									{
										//~ $stInput = "<input type='number' name='stPostArray[".$lotNumber."|".$processCode."]' value='".$st."' form='inputForm'>";
										$stInput = "<input type='number' name='stPostArray[".$lotNumber."|".$processCode."]' style='background-color:#FFFF99;' value='".$inputST."' form='inputForm'>";
									}
									
									echo "
										<tr ".$bgcolor.">
											<td>".$processOrder."</td>
											<td>".$lotNumber."</td>
											<td>".$processCode."</td>
											<td>".$processName."</td>
											<td>".$sectionName."</td>
											<td>".$stInput."</td>
											<td>".$st."</td>
											<td>".$targetDateStart."</td>
											<td>".$targetDateEnd."</td>
											<td>".convertSeconds($st)."</td>
										</tr>
									";
									
									$previousDate = $targetDateEnd;
								}
							}
						}
					}
					
					echo "</table>";
					
					echo "</td>";
					
					
					$sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId = ".$poId." AND identifier IN(1,2) AND workingQuantity > 0 AND lotNumber NOT IN('".implode("','",$loteArray)."')";
					$queryLotList = $db->query($sql);
					if($queryLotList AND $queryLotList->num_rows > 0)
					{
						while($resultLotList = $queryLotList->fetch_assoc())
						{
							$lotNumber = $resultLotList['lotNumber'];
							
							$totalSt = 0;
							$previousDate = '';							
							
							echo "<td valign='top'>";
							
							echo "<table border='1' style='width:65vw;'>";
							
							$sql = "SELECT lotNumber, processOrder, processCode, processSection, targetStart, targetFinish, standardTime, inputST FROM system_temporaryworkschedule WHERE lotNumber LIKE '".$lotNumber."' AND idNumber LIKE '".$_SESSION['idNumber']."' AND destination = 0 ORDER BY processOrder";
							$queryTemporaryWorkschedule = $db->query($sql);
							if($queryTemporaryWorkschedule AND $queryTemporaryWorkschedule->num_rows > 0)
							{
								while($resultTemporaryWorkschedule = $queryTemporaryWorkschedule->fetch_assoc())
								{
									$lotNumber = $resultTemporaryWorkschedule['lotNumber'];
									$processOrder = $resultTemporaryWorkschedule['processOrder'];
									$processCode = $resultTemporaryWorkschedule['processCode'];
									$processSection = $resultTemporaryWorkschedule['processSection'];
									$targetDateStart = $resultTemporaryWorkschedule['targetStart'];
									$targetDateEnd = $resultTemporaryWorkschedule['targetFinish'];
									$st = $resultTemporaryWorkschedule['standardTime'];
									$inputST = $resultTemporaryWorkschedule['inputST'];
									
									if($inputST == 0)	$inputST = '';
									
									$processName = $section = '';
									$sql = "SELECT processName, processSection FROM cadcam_process WHERE processCode = ".$processCode." LIMIT 1";
									$queryProcess = $db->query($sql);
									if($queryProcess AND $queryProcess->num_rows > 0)
									{
										$resultProcess = $queryProcess->fetch_assoc();
										$processName = $resultProcess['processName'];
										$section = $resultProcess['processSection'];
									}
									
									$sectionName = '';
									$sql = "SELECT sectionName, motherSectionId, departmentId FROM ppic_section WHERE sectionId = ".$processSection." LIMIT 1";
									$querySection = $db->query($sql);
									if($querySection AND $querySection->num_rows > 0)
									{
										$resultSection = $querySection->fetch_assoc();
										$sectionName = $resultSection['sectionName'];
									}
									
									$totalStTD = "";
									if($previousDate!=$targetDateEnd)
									{
										if($totalSt > 0)
										{
											echo "
												<tr>
													<td></td>
													<td colspan='8' align='right'>Total ST</td>
													<td>".convertSeconds($totalSt)."</td>
												</tr>
											";
										}
										
										$totalSt = 0;
									}
									
									$totalSt += $st;
									
									if($previousDate!='')
									{
										$current = strtotime($previousDate);
										$last = strtotime($targetDateStart);
										$dates = array();
										while($current <= $last)
										{
											$dateDate = date('Y-m-d',$current);
											
											$sql = "SELECT holidayName FROM hr_holiday WHERE holidayDate = '".$dateDate."' AND holidayType < 6 LIMIT 1";
											$queryHoliday = $db->query($sql);
											if($queryHoliday AND $queryHoliday->num_rows > 0)
											{
												echo "
													<tr bgcolor='pink'>
														<td></td>
														<td colspan='8' align='center'>Holiday (".$dateDate.")</td>
														<td></td>
													</tr>
												";
											}
											else if(date('w',$current)==0)
											{
												echo "
													<tr bgcolor='pink'>
														<td></td>
														<td colspan='8' align='center'>Sunday (".$dateDate.")</td>
														<td></td>
													</tr>
												";
											}
											
											$current = strtotime('+1 day', $current);
										}
									}
									
									$stInput = "";
									if($identifier==1 AND $processCode!=496)
									{
										//~ $stInput = "<input type='number' name='stPostArray[".$lotNumber."|".$processCode."]' value='".$st."' form='inputForm'>";
										$stInput = "<input type='number' name='stPostArray[".$lotNumber."|".$processCode."]' style='background-color:#FFFF99;' value='".$inputST."' form='inputForm'>";
									}
									
									echo "
										<tr ".$bgcolor.">
											<td>".$processOrder."</td>
											<td>".$lotNumber."</td>
											<td>".$processCode."</td>
											<td>".$processName."</td>
											<td>".$sectionName."</td>
											<td>".$stInput."</td>
											<td>".$st."</td>
											<td>".$targetDateStart."</td>
											<td>".$targetDateEnd."</td>
											<td>".convertSeconds($st)."</td>
										</tr>
									";
									
									$previousDate = $targetDateEnd;
								}
							}
							
							echo "</table>";
							
							echo "</td>";
							
						}
					}
					
					echo "</tr></table>";
				}
			}
		//~ }
		
		/* Activate item handling in japan by sir Ace 2020-06-01
		if($_GET['country']==2)
		{
			if($rescheduleFlag==1 AND $viewDetailFlag==0)
			{
				$lotNumberArray = array();
				$sql = "SELECT lotNumber, partId, parentLot, partLevel, workingQuantity, identifier, patternId FROM ppic_lotlist WHERE poId = ".$poId." AND identifier IN(1,2) AND workingQuantity > 0";
				$queryLotList = $db->query($sql);
				if($queryLotList AND $queryLotList->num_rows > 0)
				{
					while($resultLotList = $queryLotList->fetch_assoc())
					{
						$lotNumberArray[] = $resultLotList['lotNumber'];
					}
				}
				
				$processCode = 496;
				
				$sql = "SELECT DISTINCT lotNumber FROM `ppic_workschedule` WHERE lotNumber IN('".implode("','",$lotNumberArray)."') AND `processCode` = ".$processCode." AND status = 0";
				$queryWorkSchedule = $db->query($sql);
				if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
				{
					while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
					{
						$lote = $resultWorkSchedule['lotNumber'];
					
						$sql = "DELETE FROM ppic_workschedule WHERE lotNumber LIKE '".$lote."' AND processCode = ".$processCode."";
						$queryDelete = $db->query($sql);

						$sql = "SET @newProcessOrder = 0";
						$query = $db->query($sql);

						$sql = "UPDATE `ppic_workschedule` SET processOrder = @newProcessOrder := ( @newProcessOrder +1 ) WHERE lotNumber LIKE '".$lote."' AND processOrder > 0 ORDER BY processOrder";
						$queryUpdate = $db->query($sql);
					}
				}
			}
		}*/
	}
	//************************************************ END Functions for scheduling/rescheduling (2018-06-04) ************************************************//	
	
	//************************************************ START Functions for New scheduling (2019-06-14) ************************************************//	
	function generateSchedule($poId,$rescheduleFlag = 0,$viewDetailFlag = 1)
	{
		include('PHP Modules/mysqliConnection.php');
		
		$startToLastFlag = 0;
		
		$suggestedDeliveryDate = '';
		
		$schedUlit = 1;
		while($schedUlit==1)
		{
			$schedUlit = 0;
			
			if($rescheduleFlag==0 AND $viewDetailFlag==1)
			{
				$sql = "DELETE FROM system_temporaryworkschedule WHERE idNumber LIKE '".$_SESSION['idNumber']."'";
				$queryDelete = $db->query($sql);
			}
			
			if($rescheduleFlag==0)
			{
				insertLotProcess($poId);
			}
			
			$startDate = date('Y-m-d');
			
			$customerId = $customerDeliveryDate = $dueDate = $deliveryType = '';
			$sql = "SELECT poNumber, customerId, customerDeliveryDate, receiveDate FROM sales_polist WHERE poId = ".$poId." LIMIT 1";
			$queryPoList = $db->query($sql);
			if($queryPoList AND $queryPoList->num_rows > 0)
			{
				$resultPoList = $queryPoList->fetch_assoc();
				$customerId = $resultPoList['customerId'];
				$customerDeliveryDate = $resultPoList['customerDeliveryDate'];
				
				$sql = "SELECT dueDate, deliveryType FROM ppic_roreviewdatatemp where poId=".$poId." AND dueDate != '0000-00-00' LIMIT 1";
				$queryRoReviewDataTemp = $db->query($sql);
				if($queryRoReviewDataTemp AND $queryRoReviewDataTemp->num_rows > 0)
				{
					$resultRoReviewDataTemp = $queryRoReviewDataTemp->fetch_assoc();
					$dueDate = $resultRoReviewDataTemp['dueDate'];
					$deliveryType = $resultRoReviewDataTemp['deliveryType'];
				}
			}
			
			if($deliveryType=='')
			{
				$sql = "SELECT deliveryType FROM sales_customer WHERE customerId = ".$customerId." LIMIT 1";
				$queryCustomer = $db->query($sql);
				if($queryCustomer AND $queryCustomer->num_rows > 0)
				{
					$resultCustomer = $queryCustomer->fetch_assoc();
					$deliveryType = $resultCustomer['deliveryType'];
				}
			}
			
			$deliveryInterval = 1;
			if($deliveryType==1)
			{
				$deliveryInterval = 5;//change from 1 day to 5 days 2019-08-02 sir roldan M, ma'am rose
			}
			else if($deliveryType==2)
			{
				$deliveryInterval = 7;
			}
			else if($deliveryType==3)
			{
				$deliveryInterval = 30;
			}
			
			if($dueDate=='')
			{
				$dueDate = date("Y-m-d",strtotime($customerDeliveryDate."-".$deliveryInterval." Days"));
				
				$day =  date('l', strtotime($dueDate));
				
				// -------------------------- Check If Incremented / Decremented Date Is Holiday Or Sunday ----------------------
				if($_GET['country']=='1')//Philippines
				{
					$sql = "SELECT holidayId FROM hr_holiday WHERE holidayDate = '".$dueDate."' AND holidayType < 6 LIMIT 1";
				}
				else if($_GET['country']=='2')//Japan
				{
					$sql = "SELECT holidayId FROM hr_holiday WHERE holidayDate = '".$dueDate."' AND holidayType >= 6 LIMIT 1";
				}
				$dc = $db->query($sql);
				$dcnum = $dc->num_rows;
				// -------------------------- Increment / Decrement Date If Holiday Or Sunday ----------------------
				if($day=='Sunday' OR $dcnum > 0)
				{
					$dueDate = addDays(-1,$dueDate);
				}
			}
			
			$endDate = ($suggestedDeliveryDate!='') ? $suggestedDeliveryDate : $customerDeliveryDate;
			
			$currentTargetFinish = ($startToLastFlag==1) ? $startDate : $endDate;
			
			$desc = ($startToLastFlag==1) ? 'DESC' : '';
			
			$sampleFlag = 0;
			if($sampleFlag == 1)
			{
				$poQuantity = 1000;
			}
			
			$itemScheduleDataArray = $lotNumberDataArray = array();
			$partLevelTemp = '';
			$sql = "SELECT lotNumber, partId, parentLot, partLevel, workingQuantity, identifier, patternId FROM ppic_lotlist WHERE poId = ".$poId." AND identifier IN(1,2) AND workingQuantity > 0 ORDER BY partLevel ".$desc;
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				while($resultLotList = $queryLotList->fetch_assoc())
				{
					$lotNumber = $resultLotList['lotNumber'];
					$partId = $resultLotList['partId'];
					$parentLot = $resultLotList['parentLot'];
					$partLevel = $resultLotList['partLevel'];
					$workingQuantity = $resultLotList['workingQuantity'];
					$identifier = $resultLotList['identifier'];
					$patternId = $resultLotList['patternId'];
					
					if($sampleFlag==1)
					{
						if($partLevel == 1)
						{
							$workingQuantity = $poQuantity;
						}
						else if($partLevel > 1)
						{
							$parentPartId = '';
							$sql = "SELECT partId FROM ppic_lotlist WHERE lotNumber LIKE '".$parentLot."' LIMIT 1";
							$queryParentPartId = $db->query($sql);
							if($queryParentPartId AND $queryParentPartId->num_rows > 0)
							{
								$resultParentPartId = $queryParentPartId->fetch_assoc();
								$parentPartId = $resultParentPartId['partId'];
							}
							
							$quantity = 0;
							$sql = "SELECT quantity FROM cadcam_subparts WHERE parentId = ".$parentPartId." AND childId = ".$partId." AND identifier = ".$identifier." LIMIT 1";
							$querySubparts = $db->query($sql);
							if($querySubparts AND $querySubparts->num_rows > 0)
							{
								$resultSubparts = $querySubparts->fetch_assoc();
								$quantity = $resultSubparts['quantity'];
							}
							
							$workingQuantity = $poQuantity * $quantity;
						}
					}
					
					if($startToLastFlag==1)
					{
						$loteArray = array();
						$sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId = ".$poId." AND identifier IN(1,2) AND workingQuantity > 0 AND parentLot LIKE SUBSTRING_INDEX('".$lotNumber."','-',3)";
						$queryLotListSameLevel = $db->query($sql);
						if($queryLotListSameLevel AND $queryLotListSameLevel->num_rows > 0)
						{
							while($resultLotListSameLevel = $queryLotListSameLevel->fetch_assoc())
							{
								$loteArray[] = $resultLotListSameLevel['lotNumber'];
							}
							
							$sql = "SELECT targetFinish FROM system_temporaryworkschedule WHERE idNumber LIKE '".$_SESSION['idNumber']."' AND destination = 0 AND lotNumber IN('".implode("','",$loteArray)."') ORDER BY targetFinish DESC LIMIT 1";
							$queryLastTargetFinishDate = $db->query($sql);
							if($queryLastTargetFinishDate AND $queryLastTargetFinishDate->num_rows > 0)
							{
								$resultLastTargetFinishDate = $queryLastTargetFinishDate->fetch_assoc();
								$currentTargetFinish = $resultLastTargetFinishDate['targetFinish'];
								$currentTargetFinish = addDays(1,$currentTargetFinish);
							}
						}
					}
					else
					{
						if($partLevelTemp=='')	$partLevelTemp = $partLevel;
						
						if($partLevelTemp!=$partLevel)
						{
							$itemScheduleDataArray = $lotNumberDataArray = array();
							
							$sql = "SELECT targetFinish FROM system_temporaryworkschedule WHERE idNumber LIKE '".$_SESSION['idNumber']."' AND destination = 0 AND lotNumber LIKE '".$parentLot."' ORDER BY targetFinish ASC LIMIT 1";
							$queryLastTargetFinishDate = $db->query($sql);
							if($queryLastTargetFinishDate AND $queryLastTargetFinishDate->num_rows > 0)
							{
								$resultLastTargetFinishDate = $queryLastTargetFinishDate->fetch_assoc();
								$currentTargetFinish = $resultLastTargetFinishDate['targetFinish'];
								$currentTargetFinish = addDays(-1,$currentTargetFinish);
							}
							
							$partLevelTemp = $partLevel;
						}
					}
					
					if($_SESSION['idNumber']=='0412*')
					{
						if($identifier==2)
						{
							$parentPartId = '';
							$sql = "SELECT partId FROM ppic_lotlist WHERE lotNumber LIKE '".$parentLot."' LIMIT 1";
							$queryParentPartId = $db->query($sql);
							if($queryParentPartId AND $queryParentPartId->num_rows > 0)
							{
								$resultParentPartId = $queryParentPartId->fetch_assoc();
								$parentPartId = $resultParentPartId['partId'];
							}
							
							$subpartProcessArray = array();
							$sql = "SELECT processCode FROM engineering_subpartprocesslink WHERE partId = ".$parentPartId." AND childId = ".$partId." AND patternId = ".$patternId."";
							$querySubpartProcessLink = $db->query($sql);
							if($querySubpartProcessLink AND $querySubpartProcessLink->num_rows > 0)
							{
								while($resultSubpartProcessLink = $querySubpartProcessLink->fetch_assoc())
								{
									$subpartProcessArray[] = $resultSubpartProcessLink['processCode'];
								}
							}
							
							if($rescheduleFlag==0)
							{
								$sql = "SELECT targetStart FROM system_temporaryworkschedule WHERE idNumber LIKE '".$_SESSION['idNumber']."' AND destination = 0 AND lotNumber LIKE '".$parentLot."' AND processCode IN(".implode(",",$subpartProcessArray).") ORDER BY processOrder LIMIT 1";
							}
							else
							{
								$sql = "SELECT targetStart FROM ppic_workschedule WHERE lotNumber LIKE '".$parentLot."' AND processCode IN(".implode(",",$subpartProcessArray).") ORDER BY processOrder LIMIT 1";
							}
							$queryWorkSchedule = $db->query($sql);
							if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
							{
								$resultWorkSchedule = $queryWorkSchedule->fetch_assoc();
								$currentTargetFinish = $resultWorkSchedule['targetStart'];
							}
						}
					}
					
					$targetFinish = $currentTargetFinish;
					
					$notInProcess = ($_GET['country']==2) ? "459,324" : "460,459,324";
					
					$scheduleDataArray = array();
					
					$stTotal = 0;
					
					$tableTable = '';
					
					$desc = ($startToLastFlag==1) ? '' : 'desc';
					
					if($viewDetailFlag==1)	echo "<table border='1'>";
					
					$workingTimeLeft = $workingTimeLimit = 30600;//8.5 hours
					if($_SESSION['idNumber']=='0346') $workingTimeLeft = $workingTimeLimit = 57600;//16 hours
					$resetFlag = 0;
					$previousProcessCode = '';
					
					if($rescheduleFlag==0)
					{
						$sql = "SELECT listId, processCode, processSection FROM system_temporaryworkschedule WHERE idNumber LIKE '".$_SESSION['idNumber']."' AND destination = 0 AND lotNumber LIKE '".$lotNumber."' AND processCode NOT IN(".$notInProcess.") AND status = 0 ORDER BY processOrder ".$desc;
					}
					else
					{
						if($identifier==1)	insertItemHandlingProcess($lotNumber);
						
						$sql = "SELECT id, processCode, processSection FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode NOT IN(".$notInProcess.") AND status = 0 ORDER BY processOrder ".$desc;
					}
					$queryWorkSchedule = $db->query($sql);
					if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
					{
						while($resultWorkSchedule = $queryWorkSchedule->fetch_row())
						{
							$id = $resultWorkSchedule[0];
							$processCode = $resultWorkSchedule[1];
							$processSection = $resultWorkSchedule[2];
							
							$st = 0;
							$inputST = '';
							if($identifier==1)
							{
								$st = getStandardTime($partId,$processCode,$workingQuantity,$processSection,$lotNumber);
								
								if($processCode==136) $st = 0;
								
								//~ if(count($simulationData) > 0 AND in_array($_SESSION['idNumber'],array('0346','0280')))
								if(count($simulationData) > 0)
								{
									if(isset($stPostArray[$lotNumber."|".$processCode]) AND $stPostArray[$lotNumber."|".$processCode]!='')
									{
										$inputST = $st = $stPostArray[$lotNumber."|".$processCode];
										$st = $st * $workingQuantity;
									}
								}
								if($_SESSION['idNumber']=='0412*') $st = 0;
								//~ echo $_SESSION['idNumber'];
							}
							
							$processName = $section = '';
							$sql = "SELECT processName, processSection FROM cadcam_process WHERE processCode = ".$processCode." LIMIT 1";
							$queryProcess = $db->query($sql);
							if($queryProcess AND $queryProcess->num_rows > 0)
							{
								$resultProcess = $queryProcess->fetch_assoc();
								$processName = $resultProcess['processName'];
								$section = $resultProcess['processSection'];
							}
							
							$sectionName = '';
							$sql = "SELECT sectionName, motherSectionId, departmentId FROM ppic_section WHERE sectionId = ".$processSection." LIMIT 1";
							$querySection = $db->query($sql);
							if($querySection AND $querySection->num_rows > 0)
							{
								$resultSection = $querySection->fetch_assoc();
								$sectionName = $resultSection['sectionName'];
							}
							
							$pCode = ($startToLastFlag == 1) ? $previousProcessCode : $processCode;
							
							$intervalDay = 0;
							if($pCode==496) $intervalDay = 1;
							if(in_array($pCode,array(312,430,431,432,136))) $intervalDay = 1;
							if(in_array($pCode,array(145,172,228))) $intervalDay = 3;
							
							if($startToLastFlag == 1)
							{
								if($pCode==518) $intervalDay = $deliveryInterval;
							}
							else
							{
								if($pCode==518)
								{
									if($suggestedDeliveryDate!='')
									{
										$intervalDay = $deliveryInterval;
									}
									else
									{
										$targetFinish = $dueDate;
									}
								}
							}
							
							$pCode1 = ($startToLastFlag == 0) ? $previousProcessCode : $processCode;
							
							if($pCode1==162) $intervalDay = 0;
							
							if($intervalDay > 0)
							{
								if($startToLastFlag == 1)
								{
									$targetFinish = addDays(+$intervalDay,$targetFinish);
								}
								else
								{
									$targetFinish = addDays(-$intervalDay,$targetFinish);
								}
								$resetFlag = 0;
							}
							
							if($resetFlag==0 AND !in_array($processCode,array(496,144)))
							{
								$processSectionTemp = $processSection;
								if(in_array($processSectionTemp,array(8,34,36)))	$processSectionTemp = 11;
								$repeatFlag = 1;
								while($repeatFlag==1)
								{
									$repeatFlag = 0;
									
									$idNumberArray = array();
									$employeeCount = 0;
									$sql = "SELECT idNumber FROM hr_employee WHERE sectionId = ".$processSectionTemp." AND status = 1 ";
									$queryEmployeee = $db->query($sql);
									if($queryEmployeee AND $queryEmployeee->num_rows > 0)
									{
										$employeeCount = $queryEmployeee->num_rows;
										while($resultEmployeee = $queryEmployeee->fetch_assoc())
										{
											$idNumberArray[] = $resultEmployeee['idNumber'];
										}
									}
									
									$sql = "SELECT DISTINCT employeeId FROM hr_leave WHERE employeeId IN('".implode("','",$idNumberArray)."') AND leaveDate >= '".$targetFinish."' AND <= '".$targetFinish."' AND employeeId IN('".implode("','",$idNumberArray)."')";
									$queryLeave = $db->query($sql);
									if($queryLeave AND $queryLeave->num_rows > 0)
									{
										$employeeCount -= $queryLeave->num_rows;
									}
									
									$capacity = (7 * $employeeCount) * 3600;
									
									$totalLoad = 0;
									$sql = "SELECT SUM(standardTime) as totalLoad FROM `view_workschedule` WHERE `processSection` = ".$processSectionTemp." AND `targetFinish` = '".$targetFinish."'";
									$queryWorkschedule = $db->query($sql);
									if($queryWorkschedule AND $queryWorkschedule->num_rows > 0)
									{
										$resultWorkschedule = $queryWorkschedule->fetch_assoc();
										$totalLoad = $resultWorkschedule['totalLoad'];
									}
									
									$sql = "SELECT SUM(standardTime) as totalLoad FROM system_temporaryworkschedule WHERE idNumber LIKE '".$_SESSION['idNumber']."' AND destination = 0 `processSection` = ".$processSectionTemp." AND `targetFinish` = '".$targetFinish."'";
									$queryWorkschedule = $db->query($sql);
									if($queryWorkschedule AND $queryWorkschedule->num_rows > 0)
									{
										$resultWorkschedule = $queryWorkschedule->fetch_assoc();
										$totalLoad += $resultWorkschedule['totalLoad'];
									}
									
									if($capacity > 0 AND $totalLoad > 0)
									{
										if($capacity < $totalLoad)
										{
											if($startToLastFlag == 1)
											{
												$targetFinish = addDays(+1,$targetFinish);
											}
											else
											{
												$targetFinish = addDays(-1,$targetFinish);
											}
											$repeatFlag = 1;
										}
										else
										{
											$workingTimeLeft = $workingTimeLimit = ($capacity - $totalLoad);
										}
									}
								}
							}
							
							$targetDateEnd = $targetFinish;
							
							$targetDateStart = $targetFinish = getTargetFinishDate($targetFinish,$st,$workingTimeLimit,$workingTimeLeft,$startToLastFlag);
							
							if($viewDetailFlag==1)
							{
								echo "
									<tr>
										<td>".++$count."</td>
										<td>".$lotNumber."</td>
										<td>".$processCode."</td>
										<td>".$processName."</td>
										<td>".$sectionName."</td>
										<td>".$targetDateStart."</td>
										<td>".$targetDateEnd."</td>
									</tr>
								";
							}
							
							$sql = "UPDATE system_temporaryworkschedule SET targetStart = '".$targetDateStart."', targetFinish = '".$targetDateEnd."', standardTime = '".$st."' , inputST = '".$inputST."' WHERE listId = ".$id." LIMIT 1";
							$queryUpdate = $db->query($sql);
							
							$previousProcessCode = $processCode;
							
							if($processCode!=496)	$resetFlag++;
							
							if($startToLastFlag == 0)
							{
								if(strtotime($targetFinish) < strtotime($startDate) AND $suggestedDeliveryDate=='')
								{
									if($viewDetailFlag==1)	echo "</table><hr>";
									
									$startToLastFlag = 1;
									$schedUlit = 1;
									break 2;
								}
							}
						}
					}
					
					echo "</table>";
					
					
				}
			}
			
			if($startToLastFlag==1 AND $suggestedDeliveryDate=='' AND $schedUlit==0)
			{
				if($viewDetailFlag==1)	echo "<hr><hr>";
				
				$suggestedDeliveryDate = $targetFinish;
				$startToLastFlag = 0;
				$schedUlit = 1;
			}
		}
	}
	//************************************************* END Functions for New scheduling (2019-06-14) *************************************************//	
	
	//************************************************ START Functions for Inserting Process and schedule (2020-07-17) ************************************************//	
	function generateLotProcessSched($poIdTempArray)
	{
		include('PHP Modules/mysqliConnection.php');
		
		$aror = new AutomaticROReview();
		
		$poIdArray = array();
		
		$lotNoArray = $roReviewLotArray = array();
		$sql = "SELECT lotNumber, poId, partId FROM ppic_lotlist WHERE poId IN(".implode(",",$poIdTempArray).") AND partLevel = 1 AND identifier = 1";
		//~ exit(0);
		$queryLotList = $db->query($sql);
		if($queryLotList AND $queryLotList->num_rows > 0)
		{
			while($resultLotList = $queryLotList->fetch_assoc())
			{
				$lotNumber = $resultLotList['lotNumber'];
				$poId = $resultLotList['poId'];
				$partId = $resultLotList['partId'];
				
				$sql = "SELECT id FROM ppic_workschedule WHERE lotNumber LIKE '".$lotNumber."' AND processCode = 324 AND status = 0 LIMIT 1";
				$queryWorkSchedule = $db->query($sql);
				if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
				{
					$sql = "SELECT DISTINCT patternId FROM cadcam_partprocess WHERE partId = ".$partId."";
					$queryPartProcess = $db->query($sql);
					if($queryPartProcess AND $queryPartProcess->num_rows == 1)
					{
						$resultPartProcess = $queryPartProcess->fetch_assoc();
						$patternId = $resultPartProcess['patternId'];
						
						$sql = "UPDATE ppic_lotlist SET patternId = ".$patternId." WHERE lotNumber LIKE '".$lotNumber."' LIMIT 1";
						$queryUpdate = $db->query($sql);
						
						$roReviewFlag = 0;
						
						//~ if($_SESSION['idNumber']=='0346')//Activated 2021-06-17 10:42:45 AM
						//~ {
							//Disable auto review 2021-12-27 by sir roldan with approval of sir ace
							// if($aror->reviewLot($lotNumber)!==FALSE)
							// {
							// 	$roReviewLotArray[] = $lotNumber;
							// 	$roReviewFlag = 1;
							// }
						//~ }
						
						generateScheduleItems($poId,'',0,0);
						
						if($roReviewFlag==1)
						{
							$sql = "SELECT COUNT(listId) as idCount, COUNT(DISTINCT targetFinish) as targetFinishCount FROM `system_temporaryworkschedule` WHERE `idNumber` LIKE '".$_SESSION['idNumber']."' AND poId = ".$poId." AND processCode IN(496,136,312,430,431) GROUP BY lotNumber";
							$queryTemporaryWorkschedule = $db->query($sql);
							if($queryTemporaryWorkschedule AND $queryTemporaryWorkschedule->num_rows > 0)
							{
								while($resultTemporaryWorkschedule = $queryTemporaryWorkschedule->fetch_assoc())
								{
									$idCount = $resultTemporaryWorkschedule['idCount'];
									$targetFinishCount = $resultTemporaryWorkschedule['targetFinishCount'];
									
									if($idCount!=$targetFinishCount)
									{
										if (($key = array_search($lotNumber, $roReviewLotArray)) !== false) {
											unset($roReviewLotArray[$key]);
											$aror->unbook($poId);
										}
										break;
									}
								}
							}							
							
							/*
							$receiveDate = $answerDate = '0000-00-00';
							$sql = "SELECT receiveDate, answerDate FROM system_lotlist WHERE poId = ".$poId." LIMIT 1";
							$queryPoList = $db->query($sql);
							if($queryPoList AND $queryPoList->num_rows > 0)
							{	
								$resultPoList = $queryPoList->fetch_assoc();
								$receiveDate = $resultPoList['receiveDate'];
								$answerDate = $resultPoList['answerDate'];
								
								$highestSt = 0;
								$sql = "SELECT IFNULL(SUM(standardTime),0) as totalST FROM `system_temporaryworkschedule` WHERE `idNumber` LIKE '".$_SESSION['idNumber']."' AND poId = ".$poId." GROUP BY lotNumber ORDER BY totalST DESC LIMIT 1";
								$queryTemporaryWorkschedule = $db->query($sql);
								if($queryTemporaryWorkschedule AND $queryTemporaryWorkschedule->num_rows > 0)
								{
									$resultTemporaryWorkschedule = $queryTemporaryWorkschedule->fetch_assoc();
									$highestSt = $resultTemporaryWorkschedule['totalST'];
								}
								
								$daysCount = $holidayAndSundayCount = 0;
								$tempDate = $receiveDate;
								while(strtotime($tempDate) <= strtotime($answerDate))
								{
									$sql = "SELECT holidayName FROM hr_holiday WHERE holidayDate = '".$tempDate."' AND holidayType < 6 LIMIT 1";
									$queryHoliday = $db->query($sql);
									if($queryHoliday AND $queryHoliday->num_rows > 0 OR date('w',strtotime($tempDate))==0)
									{
										$holidayAndSundayCount++;
									}
									
									$tempDate = date('Y-m-d',strtotime($tempDate.'+1 days'));
									
									$daysCount++;
								}
								
								$workingDays = $daysCount - $holidayAndSundayCount;
								
								if(($highestSt > ($workingDays * 30600)) OR ($workingDays < 30))//2021-06-18 08:37:34 add conditional statement ($workingDays < 30)
								{
									if (($key = array_search($lotNumber, $roReviewLotArray)) !== false) {
										unset($roReviewLotArray[$key]);
										$aror->unbook($poId);
									}
								}
							}*/
						}
						
						$lotNoArray[] = $lotNumber;
						$poIdArray[] = $poId;
					}
				}
			}
			
			//~ exit(0);
			
			$sql = "UPDATE ppic_workschedule SET employeeIdStart = '".$_SESSION['idNumber']."', actualStart = NOW() WHERE lotNumber IN('".implode("','",$lotNoArray)."') AND processCode = 324 AND status = 0";
			$queryUpdate = $db->query($sql);
			
			$lotNumberArray = array();
			$sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId IN(".implode(",",$poIdArray).") AND partLevel > 0";
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				while($resultLotList = $queryLotList->fetch_assoc())
				{
					$lotNumberArray[] = "'".$resultLotList['lotNumber']."'";
				}
			}
			
			// ----- Start ----- Insert Process and Schedule to Workschedule (Transfer data from system_temporaryworkschedule to ppic_workschedule)
			$sql = "
						INSERT INTO `ppic_workschedule`
								(	`poId`, `customerId`, `poNumber`, `lotNumber`, `partNumber`, `revisionId`, `processCode`, `processOrder`, `processSection`, `processRemarks`, `targetStart`, `targetFinish`, `standardTime`, `receiveDate`, `deliveryDate`, `recoveryDate`, `employeeIdStart`, `actualStart`, `actualEnd`, `actualFinish`, `quantity`, `employeeId`, `availability`, `urgentFlag`, `subconFlag`, `partLevelFlag`, `status`)
						SELECT		`poId`, `customerId`, `poNumber`, `lotNumber`, `partNumber`, `revisionId`, `processCode`, `processOrder`, `processSection`, `processRemarks`, `targetStart`, `targetFinish`, `standardTime`, `receiveDate`, `deliveryDate`, `recoveryDate`, `employeeIdStart`, `actualStart`, `actualEnd`, `actualFinish`, `quantity`, `employeeId`, `availability`, `urgentFlag`, `subconFlag`, `partLevelFlag`, `status`
						FROM	`system_temporaryworkschedule`
						WHERE	idNumber LIKE '".$_SESSION['idNumber']."' AND lotNumber IN(".implode(",",$lotNumberArray).") AND destination = 0 ORDER BY lotNumber, processOrder
			";
			$queryInsert = $db->query($sql);
			
			if($queryInsert)
			{
				$sql = "DELETE FROM system_temporaryworkschedule WHERE idNumber LIKE '".$_SESSION['idNumber']."' AND lotNumber IN(".implode(",",$lotNumberArray).")";
				$queryDelete = $db->query($sql);
			}
			// ----- End ----- Insert Process and Schedule to Workschedule (Transfer data from system_temporaryworkschedule to ppic_workschedule)
			
			$lotNoProcessArray = $workscheduleIdArray = $roReviewFinishLotArray = array();
			$sql = "SELECT id, lotNumber FROM ppic_workschedule WHERE lotNumber IN(".implode(",",$lotNumberArray).") AND processCode = 324";
			$queryWorkSchedule = $db->query($sql);
			if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
			{
				while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
				{
					$workscheduleIdArray[] = $resultWorkSchedule['id'];
					$lote = $resultWorkSchedule['lotNumber'];
					$sql = "SELECT id FROM ppic_workschedule WHERE lotNumber LIKE '".$lote."' AND processCode NOT IN(299,298,493,460,530,459,324) LIMIT 1";
					$queryCheckProcess = $db->query($sql);
					if($queryCheckProcess AND $queryCheckProcess->num_rows > 0)
					{
						finishProcess("",$resultWorkSchedule['id'], 0, $_SESSION['idNumber'],'');
						
						$sql = "UPDATE ppic_workschedule SET availability = 0 WHERE lotNumber LIKE '".$lote."' AND processCode = 461 AND status= 0 LIMIT 1";
						$queryUpdate = $db->query($sql);
						
						$sql = "UPDATE view_workschedule SET availability = 0 WHERE lotNumber LIKE '".$lote."' AND processCode = 461 AND status= 0 LIMIT 1";
						$queryUpdate = $db->query($sql);
						
						$sql = "UPDATE ppic_workschedule SET availability = 0 WHERE lotNumber LIKE '".$lote."' AND processCode = 597 AND status= 0 LIMIT 1";
						$queryUpdate = $db->query($sql);
						
						$sql = "UPDATE view_workschedule SET availability = 0 WHERE lotNumber LIKE '".$lote."' AND processCode = 597 AND status= 0 LIMIT 1";
						$queryUpdate = $db->query($sql);
						
						if(in_array($lote,$roReviewLotArray))
						{
							$roReviewFinishLotArray[] = $lote;
						}
					}
					else
					{
						$lotNoProcessArray[] = $lote;
					}
				}
			}
			
			if(count($roReviewFinishLotArray) > 0)
			{
				$sql = "SELECT id FROM ppic_workschedule WHERE lotNumber IN('".implode("','",$roReviewFinishLotArray)."') AND processCode = 459";
				$queryWorkSchedule = $db->query($sql);
				if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
				{
					while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
					{
						finishProcess("",$resultWorkSchedule['id'], 0, $_SESSION['idNumber'],'Auto');
					}
				}
			}
			
			//Activated 2018-09-12
			$sqlFilter = "AND ROUND((LENGTH(lotNumber)-LENGTH(REPLACE(lotNumber,'-','')))/LENGTH('-')) = 2";
			$sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId IN(".implode(",",$poIdArray).") AND partLevel > 0 AND identifier = 1 ".$sqlFilter."";
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				while($resultLotList = $queryLotList->fetch_assoc())
				{
					createTemporaryMaterial($resultLotList['lotNumber']);
				}
			}
			
			//~ $sql = "UPDATE system_lotlist SET recoveryDate = '".$resultSchedule['delivery']."' WHERE lotNumber IN(".implode(",",$lotNumberArray).")";
			//~ $queryUpdate = $db->query($sql);//2017-06-02
			
			//~ $sql = "SELECT targetFinish FROM ppic_workschedule WHERE lotNumber IN(".implode(",",$lotNumberArray).")";
			
			
			// ----- Start ----- Change Recovery Date based on the target finish of Due Date(PH) or Delivery(JP) Process
			if($_GET['country']==2)
			{
				$internalDeliveryDateArray = array();
				$sql = "SELECT lotNumber, targetFinish FROM ppic_workschedule WHERE lotNumber IN(".implode(",",$lotNumberArray).") AND processCode = 144";
				$queryWorkSchedule = $db->query($sql);
				if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
				{
					while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
					{
						$lotNumber = $resultWorkSchedule['lotNumber'];
						$targetFinish = $resultWorkSchedule['targetFinish'];
						
						if(!isset($internalDeliveryDateArray[$targetFinish]))	$internalDeliveryDateArray[$targetFinish] = array();
						
						$internalDeliveryDateArray[$targetFinish][] = $lotNumber;
					}
				}
				
				if(count($internalDeliveryDateArray) > 0)
				{
					foreach($internalDeliveryDateArray as $internalDel => $lotNoArray)
					{
						$sql = "UPDATE system_lotlist SET recoveryDate = '".$internalDel."' WHERE lotNumber IN('".implode("','",$lotNoArray)."')";
						$queryUpdate = $db->query($sql);
					}
				}
			}
			else
			{
				$internalDeliveryDateArray = array();
				$sql = "SELECT lotNumber, targetFinish FROM ppic_workschedule WHERE lotNumber IN(".implode(",",$lotNumberArray).") AND processCode = 518";
				$queryWorkSchedule = $db->query($sql);
				if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
				{
					while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
					{
						$lotNumber = $resultWorkSchedule['lotNumber'];
						$targetFinish = $resultWorkSchedule['targetFinish'];
						
						if(!isset($internalDeliveryDateArray[$targetFinish]))	$internalDeliveryDateArray[$targetFinish] = array();
						
						$internalDeliveryDateArray[$targetFinish][] = $lotNumber;
					}
				}
				
				if(count($internalDeliveryDateArray) > 0)
				{
					foreach($internalDeliveryDateArray as $internalDel => $lotNoArray)
					{
						$sql = "UPDATE system_lotlist SET recoveryDate = '".$internalDel."' WHERE lotNumber IN('".implode("','",$lotNoArray)."')";
						$queryUpdate = $db->query($sql);
					}
				}
			}
			// ----- End ----- Change Recovery Date based on the target finish of Due Date(PH) or Delivery(JP) Process
			
			
			// ******************** Insert DMS Making Per Process for new items 2018-05-12 ******************** //
			$arrayNoDMSMakingProcess = '324,141,174,313,459,403,95,366,367,432,431,430,312,136,227,171,96,94,358,317,228,172,145,144,137,138,229,448,426,424,364,352,346,343,342,242,241,238,230,220,205,197,167,163,92,91,179,162,254,184,496,518,461,299,530,460,533,437,493,539,540,298,597,598,599,600,601,602,603';
			
			$newItemPoIdArray = array();
			$sql = "SELECT DISTINCT poId FROM ppic_lotlist WHERE poId IN(".implode(",",$poIdArray).") AND partLevel > 0 AND identifier = 5";
			$queryLotList = $db->query($sql);
			if($queryLotList AND $queryLotList->num_rows > 0)
			{
				while($resultLotList = $queryLotList->fetch_assoc())
				{
					$newItemPoIdArray[] = $resultLotList['poId'];
				}
				
				$sql = "SELECT lotNumber, poId, partId FROM ppic_lotlist WHERE poId IN(".implode(",",$newItemPoIdArray).") AND partLevel > 0 AND identifier = 1 ".$sqlFilter."";
				$queryLotList = $db->query($sql);
				if($queryLotList AND $queryLotList->num_rows > 0)
				{
					while($resultLotList = $queryLotList->fetch_assoc())
					{
						$lotNumber = $resultLotList['lotNumber'];
						$poId = $resultLotList['poId'];
						$partId = $resultLotList['partId'];
						
						$lot = '';
						$sql = "SELECT lotNumber FROM ppic_lotlist WHERE poId = ".$poId." AND partId = ".$partId." AND identifier = 5 LIMIT 1";
						$queryLot = $db->query($sql);
						if($queryLot AND $queryLot->num_rows > 0)
						{
							$resultLot = $queryLot->fetch_assoc();
							$lot = $resultLot['lotNumber'];
							
							$processOrder = '';
							$sql = "SELECT processOrder FROM ppic_workschedule WHERE lotNumber LIKE '".$lot."' ORDER BY processOrder DESC LIMIT 1";
							$query = $db->query($sql);
							if($query AND $query->num_rows > 0)
							{
								$result = $query->fetch_assoc();
								$processOrder = $result['processOrder'];
							}
							
							$sql = "SET @newProcessOrder = ".$processOrder.";";
							$query = $db->query($sql);
							
							$sql = "SET @newProcessOrder1 = ".($processOrder+1).";";
							$query = $db->query($sql);
							
							$sql = "SET @newProcessOrder2 = ".($processOrder+2).";";
							$query = $db->query($sql);
							
							if($_GET['country']==2)
							{
								$dmsMaking = 564;
								$dmsChecking = 570;
								$inprocessData = 577;
							}//462,519,523
							else
							{
								$dmsMaking = 462;
								$dmsChecking = 519;
								$inprocessData = 523;
							}
							
							$targetFinish = '0000-00-00';
							$sql = "SELECT targetFinish FROM ppic_workschedule WHERE lotNumber LIKE '".$lot."' AND processCode = 463 LIMIT 1";
							$queryWorkSchedule = $db->query($sql);
							if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
							{
								$resultWorkSchedule = $queryWorkSchedule->fetch_assoc();
								$targetFinish = $resultWorkSchedule['targetFinish'];
							}							
							
							$sql = "SELECT id, processRemarks FROM ppic_workschedule WHERE lotNumber LIKE '".$lot."' AND processCode IN(".$dmsMaking.",".$dmsChecking.",".$inprocessData.") LIMIT 1";
							$queryWorkSchedule = $db->query($sql);
							if($queryWorkSchedule AND $queryWorkSchedule->num_rows == 0)
							{
								//Insert DMS Making
								$sql = "
									INSERT INTO `ppic_workschedule`
											(	`poId`, `customerId`, `poNumber`, `lotNumber`, 	`partNumber`, `revisionId`, `processCode`,	`processOrder`, 							`processSection`, 	`processRemarks`, 	`targetStart`, `targetFinish`, `receiveDate`, `deliveryDate`, `recoveryDate`, `urgentFlag`, `subconFlag`, `partLevelFlag`, `status`)
									SELECT		`poId`, `customerId`, `poNumber`, '".$lot."', 	`partNumber`, `revisionId`, '".$dmsMaking."', 			@newProcessOrder := ( @newProcessOrder+3 ), '40', 				`processCode`, 		'".$targetFinish."', '".$targetFinish."', `receiveDate`, `deliveryDate`, `recoveryDate`, `urgentFlag`, `subconFlag`, `partLevelFlag`, 0
									FROM	ppic_workschedule
									WHERE lotNumber LIKE '".$lotNumber."' AND processCode NOT IN(".$arrayNoDMSMakingProcess.") ORDER BY processOrder
								";
								$queryInsert = $db->query($sql);
								
								//Insert DMS Checking
								$sql = "
									INSERT INTO `ppic_workschedule`
											(	`poId`, `customerId`, `poNumber`, `lotNumber`, 	`partNumber`, `revisionId`, `processCode`,	`processOrder`, 							`processSection`, 	`processRemarks`, 	`targetStart`, `targetFinish`, `receiveDate`, `deliveryDate`, `recoveryDate`, `urgentFlag`, `subconFlag`, `partLevelFlag`, `status`)
									SELECT		`poId`, `customerId`, `poNumber`, '".$lot."', 	`partNumber`, `revisionId`, '".$dmsChecking."', 			@newProcessOrder1 := ( @newProcessOrder1+3 ), '40', 			`processCode`, 		'".$targetFinish."', '".$targetFinish."', `receiveDate`, `deliveryDate`, `recoveryDate`, `urgentFlag`, `subconFlag`, `partLevelFlag`, 0
									FROM	ppic_workschedule
									WHERE lotNumber LIKE '".$lotNumber."' AND processCode NOT IN(".$arrayNoDMSMakingProcess.") ORDER BY processOrder
								";
								$queryInsert = $db->query($sql);
								
								//Insert Inprocess Data Input
								$sql = "
									INSERT INTO `ppic_workschedule`
											(	`poId`, `customerId`, `poNumber`, `lotNumber`, 	`partNumber`, `revisionId`, `processCode`,	`processOrder`, 							`processSection`, 	`processRemarks`, 	`targetStart`, `targetFinish`, `receiveDate`, `deliveryDate`, `recoveryDate`, `urgentFlag`, `subconFlag`, `partLevelFlag`, `status`)
									SELECT		`poId`, `customerId`, `poNumber`, '".$lot."', 	`partNumber`, `revisionId`, '".$inprocessData."', 			@newProcessOrder2 := ( @newProcessOrder2+3 ), '34', 			`processCode`, 		'".$targetFinish."', '".$targetFinish."', `receiveDate`, `deliveryDate`, `recoveryDate`, `urgentFlag`, `subconFlag`, `partLevelFlag`, 0
									FROM	ppic_workschedule
									WHERE lotNumber LIKE '".$lotNumber."' AND processCode NOT IN(".$arrayNoDMSMakingProcess.") ORDER BY processOrder
								";
								$queryInsert = $db->query($sql);
								
								$processOrder = '';
								$sql = "SELECT processOrder FROM ppic_workschedule WHERE lotNumber LIKE '".$lot."' ORDER BY processOrder DESC LIMIT 1";
								$query = $db->query($sql);
								if($query AND $query->num_rows > 0)
								{
									$result = $query->fetch_assoc();
									$processOrder = $result['processOrder'];
								}
								
								//Insert Inprocess Data Input
								//~ $sql = "
									//~ INSERT INTO `ppic_workschedule`
											//~ (	`poId`, `customerId`, `poNumber`, `lotNumber`, 	`partNumber`, `revisionId`, `processCode`,	`processOrder`, 	`processSection`, 	`processRemarks`, 	`targetStart`, `targetFinish`, `receiveDate`, `deliveryDate`, `recoveryDate`, `urgentFlag`, `subconFlag`, `partLevelFlag`, `status`)
									//~ SELECT		`poId`, `customerId`, `poNumber`, '".$lot."', 	`partNumber`, `revisionId`, '523', 			'".$processOrder."', '34', 				`processCode`, 		`targetStart`, `targetFinish`, `receiveDate`, `deliveryDate`, `recoveryDate`, `urgentFlag`, `subconFlag`, `partLevelFlag`, 0
									//~ FROM	ppic_workschedule
									//~ WHERE lotNumber LIKE '".$lotNumber."' AND processCode NOT IN(".$arrayNoDMSMakingProcess.") ORDER BY processOrder LIMIT 1
								//~ ";
								//~ $queryInsert = $db->query($sql);
								
								$sql = "SELECT id, processRemarks FROM ppic_workschedule WHERE lotNumber LIKE '".$lot."' AND processCode IN(".$dmsMaking.",".$dmsChecking.",".$inprocessData.")";
								$queryWorkSchedule = $db->query($sql);
								if($queryWorkSchedule AND $queryWorkSchedule->num_rows > 0)
								{
									while($resultWorkSchedule = $queryWorkSchedule->fetch_assoc())
									{
										$id = $resultWorkSchedule['id'];
										$processRemarks = $resultWorkSchedule['processRemarks'];
										
										
										
										$processName = '';
										$sql = "SELECT processName FROM cadcam_process WHERE processCode = ".$processRemarks." LIMIT 1";
										$queryProcess = $db->query($sql);
										if($queryProcess AND $queryProcess->num_rows > 0)
										{
											$resultProcess = $queryProcess->fetch_assoc();
											$processName = $resultProcess['processName'];
										}
										
										$sql = "UPDATE ppic_workschedule SET processRemarks = '".$processName."' WHERE id = ".$id." LIMIT 1";
										$queryUpdate = $db->query($sql);
									}
								}
							}
						}
					}
				}
			}
			// ******************** Insert DMS Making Per Process for new items 2018-05-12 ******************** //				
			
			/* 2020-07-11 gerald
			if($_GET['country']==2)
			{
				foreach($lotNumberArray as $lote)
				{
					$sql = "DELETE FROM ppic_workschedule WHERE lotNumber LIKE ".$lote." AND processCode = 496";
					$queryDelete = $db->query($sql);
					
					$sql = "SET @newProcessOrder = 0";
					$query = $db->query($sql);
					
					$sql = "UPDATE `ppic_workschedule` SET processOrder = @newProcessOrder := ( @newProcessOrder +1 ) WHERE lotNumber LIKE ".$lote." AND processOrder > 0 ORDER BY processOrder";
					$queryUpdate = $db->query($sql);
				}
			}*/
			
			//******************************* Update sales notes (2020-02-28) ******************************* //
			$sql = "SELECT poId, note FROM system_lotlist WHERE poId IN(".implode(",",$poIdArray).") AND note != '' GROUP BY poId";
			$queryNotes = $db->query($sql);
			if($queryNotes AND $queryNotes->num_rows > 0)
			{
				while ($resultNotes = $queryNotes->fetch_assoc()) 
				{
					$poId = $resultNotes['poId'];
					$note = $resultNotes['note'];

					$lotNumberArray = Array ();
					$sql = "SELECT lotNumber FROM ppic_lotlist WHERE identifier IN (1,2) AND poId = ".$poId;
					$queryLotlist = $db->query($sql);
					if($queryLotlist AND $queryLotlist->num_rows > 0)
					{
						while($resultLotlist = $queryLotlist->fetch_assoc())
						{
							$lotNumberArray[] = $resultLotlist['lotNumber'];
						}
					}

					$sql = "UPDATE view_workschedule SET priorityRemarks = '".$db->real_escape_string($note)."' WHERE lotNumber IN ('".implode("', '",$lotNumberArray)."')";
					$queryUpdate = $db->query($sql);
				}
			}		
			//******************************* Update sales notes (2020-02-28) ******************************* //
		
			if(count($lotNoProcessArray) > 0)
			{
				$reviewId = 0;
				$sqlMain = "INSERT INTO `system_noprocess`(`roReviewId`,`lotNumber`) VALUES ";
				$counter = 0;
				$sqlValueArray = array();
				foreach($lotNoProcessArray as $lote)
				{
					$sqlValueArray[] = "('".$reviewId."','".$lote."')";
					
					$counter++;
					
					if($counter==50)
					{
						$sqlInsert = $sqlMain." ".implode(",",$sqlValueArray);
						$queryUpdate = $db->query($sqlInsert);
						$counter = 0;
						$sqlValueArray = array();
					}
				}
				if(count($sqlValueArray) > 0)
				{
					$sqlInsert = $sqlMain." ".implode(",",$sqlValueArray);
					$queryUpdate = $db->query($sqlInsert);
				}
			}
		}
	}	
	//************************************************* END Functions for Inserting Process and schedule (2020-07-17) *************************************************//	
	//Please don't insert another functions beyond here thanks ^_^ 
?>
