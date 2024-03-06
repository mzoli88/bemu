<?php

namespace hws;


class Excel
{

	static function getNameFromNumber($num)
	{
		$numeric = ($num - 1) % 26;
		$letter = chr(65 + $numeric);
		$num2 = intval(($num - 1) / 26);
		if ($num2 > 0) {
			return self::getNameFromNumber($num2) . $letter;
		} else {
			return $letter;
		}
	}

	static function exportWithTabs(array $exports, $fname = "ez_egy_excel_doc_akar_lenni", $save = null)
	{
		set_time_limit(0);
		ini_set("memory_limit", "-1");
		error_reporting(false);

		$objPHPExcel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

		$objPHPExcel->removeSheetByIndex($objPHPExcel->getActiveSheetIndex());

		$sheetIndex = 0;
		foreach ($exports as $tab_name => $query) {
			self::addTab($objPHPExcel, $sheetIndex, $query->getHeaders(), $query->collect('export|default'), $tab_name, []);
			$sheetIndex++;
		}

		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex(0);

		$objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, 'Xlsx');

		if ($save) {
			$objWriter->save($fname);
		} else {
			header("Access-Control-Allow-Origin: *");
			header("Access-Control-Expose-Headers: Content-Disposition");
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="' . urlencode($fname) . '.xlsx"');
			$objWriter->save('php://output');
			exit;
		}
	}

	static function addTab($objPHPExcel, $sheetIndex, $headers = null, $data = [], $tab_name = null, $numeric)
	{
		$objWorkSheet = $objPHPExcel->createSheet($sheetIndex);
		if ($tab_name) $objWorkSheet->setTitle($tab_name);

		if ($headers) {
			$maxcol = self::getNameFromNumber(count($headers));
			$data = array_merge([$headers], $data);
			$objWorkSheet->getStyle('A1:' . $maxcol . '1')->getFont()->setSize(11);
			$objWorkSheet->getStyle('A1:' . $maxcol . '1')->getFill()
				->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
				->getStartColor()->setRGB('D3D3D3');
			$rowIndex = 1;
			foreach ($data as $rowData) {
				$colIndex = 1;
				foreach ($headers as $colKey => $colkexname) {
					if (array_key_exists($colKey, $rowData)) {
						if (!empty($numeric)) {
							if (in_array($colKey, $numeric) && is_numeric($rowData[$colKey])) {
								$objWorkSheet->getCellByColumnAndRow($colIndex, $rowIndex)
									->setValueExplicit($rowData[$colKey], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
							} else {
								$objWorkSheet->getCellByColumnAndRow($colIndex, $rowIndex)
									->setValueExplicit($rowData[$colKey], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
							}
						} else {
							$objWorkSheet->getCellByColumnAndRow($colIndex, $rowIndex)
								->setValueExplicit($rowData[$colKey], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
						}
					}
					$colIndex++;
				}
				$rowIndex++;
			}
		} else {
			$objWorkSheet->fromArray($data, NULL, 'A1');
		}

		$i = 1;
		foreach ($data['0'] as $vvv) {
			$objWorkSheet->getColumnDimension(self::getNameFromNumber($i))->setAutoSize(true);
			$i++;
		}
		$objWorkSheet->getColumnDimension(self::getNameFromNumber($i))->setAutoSize(true);

		return $objPHPExcel;
	}

	static function xls($headers = null, $data = [], $fname = "ez_egy_excel_doc_akar_lenni", $save = null, $numeric = [])
	{
		set_time_limit(0);
		ini_set("memory_limit", "-1");
		error_reporting(false);

		$objPHPExcel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

		self::addTab($objPHPExcel, 0, $headers, $data, null, $numeric);

		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex(0);

		$objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, 'Xlsx');

		if ($save) {
			$objWriter->save($fname);
		} else {
			ob_end_clean();
			header("Access-Control-Allow-Origin: *");
			header("Access-Control-Expose-Headers: Content-Disposition");
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="' . urlencode($fname) . '.xlsx"');
			$objWriter->save('php://output');
			exit;
		}
	}

	static function checkStrings($a, $b)
	{
		$a = mb_strtolower(toUtf(preg_replace('/[^A-Za-z0-9\-]/', '', $a)));
		$b = mb_strtolower(toUtf(preg_replace('/[^A-Za-z0-9\-]/', '', $b)));
		return $a == $b;
	}

	static function import($headers, $file_content, $rowedit, $tab_number = 0, $validator = null,$file_path = null)
	{
		set_time_limit(0);
		ini_set("memory_limit", "-1");

		if(!$file_path){
			//lementjük ideiglenesen a fájlt
			$file_path = tempnam(sys_get_temp_dir(), 'import_');
			file_put_contents($file_path, $file_content);
		}
		try {
			$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file_path);
			$reader->setReadDataOnly(true);
			$spreadsheet = $reader->load($file_path);
		} catch (\Throwable $th) {
			report($th);
			unlink($file_path);
			return response()->json(['message' => 'A fájlt nem sikerült beolvasni.'], 500);
		}

		$spreadsheetname = trim($spreadsheet->getSheetNames()[$tab_number]);
		$data = $spreadsheet->getSheet($tab_number)->toArray();
		$tmpHeader = [];
		foreach ($data[0] as $key => $value) {
			foreach ($headers as $prop => $canbe) {
				if ($prop == '*') {
					if (!preg_match('/\([0-9]+\)$/', $value)) continue;
					foreach ($canbe as $col => $headers2) {
						foreach ($headers2 as $prop2 => $canbe2) {
							$value2 = trim(preg_replace('/\([0-9]+\)$/', '', $value));
							$index = trim(preg_replace('/.*\(?([0-9]+)\)$/', '$1', $value));
							// dd($value2,$canbe2,$index);
							if (self::checkStrings($value2, $canbe2) || self::checkStrings($value2, $prop2)) {
								$tmpHeader[$key] = [$col, $prop2, $index];
							}
						}
					}
					continue;
				}
				if (is_string($canbe)) $canbe = [$canbe];
				if (is_array($canbe)) {
					foreach ($canbe as $can) {
						if (self::checkStrings($value, $can) || self::checkStrings($value, $prop)) {
							$tmpHeader[$key] = $prop;
						}
					}
				}
			}
		}

		$tmpNotFound = [];
		foreach ($headers as $prop => $canbe) {
			if ($prop == '*') {
				continue;
			}
			if (!in_array($prop, $tmpHeader)) $tmpNotFound[] = $prop;
		}
		if (!empty($tmpNotFound)) logger()->error('Nem sikerült minden oszlopot beazonosítani! ' . print_r($tmpNotFound, true));

		if ($validator != null) {
			$validation = self::loopExcel($data, $tmpHeader, $validator, $spreadsheetname, $tab_number);

			if (!empty($validation)) {
				unlink($file_path);

				$objWorkSheet = $spreadsheet->getSheet($tab_number);
				$maxcol = self::getNameFromNumber(count($data[0]) + 1);
				$objWorkSheet->getStyle('A1:' . $maxcol . '1')->getFont()->setSize(11);
				$objWorkSheet->getStyle('A1:' . $maxcol . '1')->getFill()
					->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
					->getStartColor()->setRGB('D3D3D3');
				$validation[1] = 'Hibaüzenet';
				$colIndex = count($data[0]) + 1;
				foreach ($validation as $colKey => $val) {
					$objWorkSheet->getCellByColumnAndRow($colIndex, $colKey)
						->setValueExplicit($val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
				}
				$i = 1;
				foreach ($data['0'] as $vvv) {
					$objWorkSheet->getColumnDimension(self::getNameFromNumber($i))->setAutoSize(true);
					$i++;
				}
				$objWorkSheet->getColumnDimension(self::getNameFromNumber($i))->setAutoSize(true);
				$spreadsheet->getDefaultStyle()->getNumberFormat()->setFormatCode('#'); // # - szám, formázás nélkül

				$objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
				ob_end_clean();
				header("Access-Control-Allow-Origin: *");
				header("Access-Control-Expose-Headers: Content-Disposition");
				header('Content-Type: application/vnd.ms-excel');
				header('Content-Disposition: attachment;filename="' . urlencode('import_hiba_') . date('YmdHis') . '.xlsx"');
				$objWriter->save('php://output');
				exit;
			}
		}
		self::loopExcel($data, $tmpHeader, $rowedit, $spreadsheetname, $tab_number);

		unlink($file_path);
		return ['import_count' => count($data) - 1];
	}

	static function loopExcel(&$data, $tmpHeader, $rowedit, $spreadsheetname, $tab_number)
	{
		$out = [];
		foreach ($data as $line => $rowData) {
			if ($line == 0) continue;
			$tmp = [];
			if (array_key_exists('0', $rowData)) {
				foreach ($tmpHeader as $col => $colprop) {
					if (is_array($colprop)) {
						if (!array_key_exists($colprop['0'], $tmp)) $tmp[$colprop['0']] = [];
						if (!array_key_exists($colprop['2'], $tmp[$colprop['0']])) $tmp[$colprop['0']][$colprop['2']] = [];
						$tmp[$colprop['0']][$colprop['2']][$colprop['1']] = toUtf(trim($rowData[$col]));
						if (empty($tmp[$colprop['0']][$colprop['2']][$colprop['1']])) $tmp[$colprop['0']][$colprop['2']][$colprop['1']] = null;
						continue;
					}
					$tmp[$colprop] = toUtf(trim($rowData[$col]));
					if (empty($tmp[$colprop])) $tmp[$colprop] = null;
				}
			} else {
				$tmp = $rowData;
			}
			$o = $rowedit($tmp, $spreadsheetname, $tab_number + 1, $line + 1);
			$data[$line] = $tmp;
			if (is_string($o)) $out[$line + 1] = $o;
			if (is_array($o)) $data[$line] = $o;
		}
		return $out;
	}
}
