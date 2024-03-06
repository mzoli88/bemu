<?php

namespace hws;

class Sablon
{
	static function create($template, array $data, $doempty = true)
	{
		$template = html_entity_decode($template, ENT_QUOTES, 'UTF-8');
		if (empty(trim($template))) {
			if ($doempty) {
				$out = "Sablon üres, lehetséges változók:<br>\n";
				self::outputData($data, $out);
				return $out;
			}
			return '';
		}

		if (!preg_match('/(@if)|(@each)/', $template)) {
			$data = self::twoDarr($data);
			return self::insertToRow($template, $data);
		}

		$template = explode("\n", $template);
		$tmp = [];
		$level = 0;
		foreach ($template as $i => $line) {
			$line = str_replace("\r", '', $line);
			if (preg_match('/^\@each/', $line)) {
				$tmp[] = [
					'level' => $level,
					'ct' => $line,
					'cm' => 'each'
				];
				$level++;
			} else if (preg_match('/^\@endeach/', $line)) {
				$level--;
				$tmp[] = [
					'level' => $level,
					'ct' => $line,
					'cm' => 'endeach'
				];
			} else if (preg_match('/^\@if/', $line)) {
				$tmp[] = [
					'level' => $level,
					'ct' => $line,
					'cm' => 'if'
				];
				$level++;
			} else if (preg_match('/^\@endif/', $line)) {
				$level--;
				$tmp[] = [
					'level' => $level,
					'ct' => $line,
					'cm' => 'endif'
				];
			} else {
				if ($i != 0 && $tmp[array_key_last($tmp)]['level'] == $level && !array_key_exists('cm', $tmp[array_key_last($tmp)])) {
					$tmp[array_key_last($tmp)]['ct'] .= "\n" . $line;
				} else {
					$tmp[] = [
						'level' => $level,
						'ct' => $line,
					];
				}
			}
		}

		$template = self::eachline($tmp);
		unset($tmp);
		return preg_replace("/^\n/", "", self::inserAll($template, $data));
	}

	static function outputData($data, &$out, $level = 1)
	{
		if ($level > 20) return;
		if (!is_array($data)) return;
		$data = self::twoDarr($data);
		foreach ($data as $key => $row) {
			if (is_array($row)) {
				$out .= "@each{" . $key . "}<br>\n";
				foreach ($row as $tmp) {
					self::outputData($tmp, $out, $level + 1);
				}
				$out .= "@endeach{" . $key . "<br>\n";
			} else {
				$out .= str_repeat("\t&emsp;", $level) . '{' . $key . '} - ' . $row . "<br>\n";
			}
		}
	}

	static function eachline(array $inputArray, int $level = 0)
	{
		$out = [];
		foreach ($inputArray as $key => $line) {
			if ($line['level'] != $level) continue;
			if (array_key_exists('cm', $line)) {
				if ($line['cm'] == 'endif' || $line['cm'] == 'endeach') continue;
				$tmp_array = [];
				for ($i = ($key + 1); $i != count($inputArray); $i++) {
					if ($line['level'] > $inputArray[$i]['level']) break;
					if (array_key_exists('cm', $inputArray[$i]) && $line['level'] == $inputArray[$i]['level'] && (($line['cm'] == 'if' && $inputArray[$i]['cm'] == 'endif') || ($line['cm'] == 'each' && $inputArray[$i]['cm'] == 'endeach'))) break;
					$tmp_array[] = $inputArray[$i];
				}
				$out[] = [
					'level' => $line['level'],
					'ct' => $line['ct'],
					'cm' => $line['cm'],
					'ch' => self::eachline($tmp_array, $line['level'] + 1),
				];
				unset($tmp_array);
			} else {
				$out[] = $line;
			}
		}
		return $out;
	}

	static function insertToRow($szoveg, $data, $insert_before = '', $insert_after = '')
	{
		foreach ($data as $key => $value) {
			if (is_array($value)) continue;
			$szoveg = str_replace('{' . $key . '}',  $insert_before . $value . $insert_after, $szoveg);
		}
		return preg_replace('/\{.*\}/', '', $szoveg);
	}

	static function isKeysNumeric(array $arr)
	{
		if (array() === $arr) return false;
		if (empty($arr)) return true;
		// return array_keys($arr) !== range(0, count($arr) - 1);

		foreach ($arr as $key => $val) {
			if (!is_numeric($key)) return false;
		}

		return true;
	}

	static function twoDarr(array $data)
	{
		if (!empty($data)) {
			foreach ($data as $key => $value) {
				if (is_array($value) && !self::isKeysNumeric($value)) {
					$data[$key] = self::twoDarr($data[$key]);
					foreach ($data[$key] as $key2 => $value2) {
						$data[$key . '.' . $key2] = $value2;
					}
					unset($data[$key]);
				}
			}
		}
		return $data;
	}

	static function inserAll($input, $data)
	{
		$out = '';
		if (empty($input)) return $out;
		$data = self::twoDarr($data);
		// dd ($data);
		foreach ($input as $line) {
			if (array_key_exists('cm', $line)) {
				if ($line['cm'] == 'if') {
					$prop = trim(preg_replace(['/^@if/'], '', $line['ct']));
					if (
						preg_match('/\{.*\}/', $prop)
					) {
						// kód biztonság szempontjából ki kell törölni a zárójeleket (nem futnak függvények)
						$cmd = str_replace(["(", ")", "[", "]", "{", "}", ";", "$", "echo"], "", self::insertToRow($prop, $data, '"', '"'));
						$is_true = false;
						try {
							$is_true = eval('return ' . $cmd . ';');
						} catch (\Throwable $th) {
						}
						if ($is_true) {
							$out .= self::inserAll($line['ch'], $data);
						}
					} else {
						$prop = trim(preg_replace(['/\{/', '/\}/', '/\(/', '/\)/'], '', $prop));
						if (array_key_exists($prop, $data) && !empty($data[$prop]) && $data[$prop] != false && $data[$prop] !== 0) {
							$out .= self::inserAll($line['ch'], $data);
						}
					}
				} else if ($line['cm'] == 'each') {
					$prop = trim(preg_replace(['/^@each/', '/\{/', '/\}/', '/\(/', '/\)/'], '', $line['ct']));
					if (array_key_exists($prop, $data) && is_array($data[$prop]) && !empty($data[$prop])) {
						foreach ($data[$prop] as $data_part) {
							$tmp_data = array_merge($data, $data_part);
							$out .= self::inserAll($line['ch'], $tmp_data);
							unset($tmp_data);
						}
					}
				}
			} else {
				$out .= "\n";
				$out .= self::insertToRow($line['ct'], $data);
			}
		}
		return $out;
	}
}
