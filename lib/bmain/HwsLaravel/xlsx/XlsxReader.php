<?php

namespace hws\xlsx;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use ZipArchive;

class XlsxReader
{

    use CacheTrait;

    public $tmpPathName;

    public $baseName;

    public $sharedStrings;

    public $sharedStringsXML = [];

    static $file;

    public $total;


    public function __construct(String $file_path = null)
    {
        set_time_limit(0);
        if ($this->canWriteCache()) {
            $this->writeCache('(Előkészítés)');
        }

        if (!$file_path) {
            if (!isset($_FILES['import_file'])) sendError("Fájl nem található");
            $file_path = $_FILES['import_file']['tmp_name'];
        }

        $file_name = explode(DIRECTORY_SEPARATOR, $file_path);
        $file_name = array_pop($file_name);

        $this->tmpPathName = Storage::path('') . $file_name;
        mkdir($this->tmpPathName);

        try {
            $zip = new ZipArchive();
            $zip->open($file_path);
            $zip->extractTo($this->tmpPathName);
        } catch (\Throwable $th) {
            sendError("A fájlt nem lehet beolvasni");
        }
        $this->checkFormat($zip);

        $this->sharedStrings = $this->tmpPathName . DIRECTORY_SEPARATOR . 'xl' . DIRECTORY_SEPARATOR . 'sharedStrings.xml';

        if (file_exists($this->sharedStrings)) {
            $sharedStringsXML = simplexml_load_file(
                $this->sharedStrings,
                'SimpleXMLElement'
            );

            $i = 0;
            foreach ($sharedStringsXML->children() as $value) {
                $this->sharedStringsXML[$i] = (string)$value->t;
                $i++;
            }

            unset($sharedStringsXML);
        }
    }

    public function getRow($number)
    {
        return $this->sharedStringsXML[$number];
    }

    static function checkStrings($a, $b)
    {
        $a = mb_strtolower(toUtf(preg_replace('/[^A-Za-z0-9\-]/', '', $a)));
        $b = mb_strtolower(toUtf(preg_replace('/[^A-Za-z0-9\-]/', '', $b)));
        return $a == $b;
    }

    public function import(callable $rowEdit, array $headers = null, callable $validateCallback = null)
    {

        $total = 0;
        $tmpHeader = [];
        self::ReadRowByTag($this->baseName, 'row', function ($i, $row) use (&$total, &$tmpHeader, $headers) {
            if ($i == 0) {
                //első sor olvasása
                $rowData = $this->getRowData($row, null, $i, true);
                foreach ($rowData as $key => $value) {
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
            } else {
                $total++;
            }
        });

        $this->total = $total;

        //oszlop ellenőrzés, hiányzik-e adat
        $tmpNotFound = [];
        foreach ($headers as $prop => $canbe) {
            if ($prop == '*') {
                continue;
            }
            if (!in_array($prop, $tmpHeader)) $tmpNotFound[] = $prop;
        }
        if (!empty($tmpNotFound)) {
            Config::set('error', 'Az import fájl hibás. Nem sikerült minden oszlopot beazonosítani.');
            return ['success' => false, 'message' => 'Nem sikerült minden oszlopot beazonosítani! (' . implode(', ', $tmpNotFound) . ')'];
        }

        if ($validateCallback) {

            $has_error = false;

            $tmp_file_path = tempnam(sys_get_temp_dir(), 'import_');

            $xlsx = new XlsxWriter(array_merge($headers, [
                'err' => 'Hibaüzenet',
            ]));

            $fhandler = fopen($tmp_file_path, 'w');

            self::ReadRowByTag($this->baseName, 'row', function ($line, $row) use ($tmpHeader, $validateCallback, $xlsx, $fhandler, &$has_error) {
                if ($line == 0) return;
                $rowData = $this->getRowData($row, $tmpHeader, $line, true);
                if (!$rowData) return;

                $out = $validateCallback($rowData, $line);
                if (is_string($out)) {
                    $has_error = true;
                    $xlsx->addRow(array_merge($rowData, [
                        'err' => $out,
                    ]), 3);
                } else {
                    $xlsx->addRow(array_merge($rowData, [
                        'err' => '',
                    ]));
                }
                if (is_array($out)) {
                    $rowData = $out;
                }
                if (!$has_error) fwrite($fhandler, json_encode($rowData) . "\n");
            });

            fclose($fhandler);

            if ($has_error) {
                Config::set('error', 'Az import fájl hibás. Az adatok nem megfelelőek.');
                return $xlsx->download('import_hiba_' . date('YmdHis') . '.xlsx');
            } else {
                $this->readJsonFile($tmp_file_path, function ($rowData, $i) use ($rowEdit) {
                    $rowEdit($rowData, $i);
                    if ($this->canWriteCache()) {
                        $percent = round(($i / $this->total) * 100);
                        $this->writeCache(' (Adatok rögzítése ' .  $percent . ' % kész)');
                    }
                });
            }
            unlink($tmp_file_path);
        } else {
            self::ReadRowByTag($this->baseName, 'row', function ($i, $row) use ($tmpHeader, $rowEdit) {
                if ($i == 0) return;
                $rowData = $this->getRowData($row, $tmpHeader, $i);
                if ($rowData) $rowEdit($rowData, $i);
            });
        }
    }

    public function readCell($c)
    {
        switch ($c['t']) {
            case 's':
                if ((string) $c->v != '') {
                    $value = $this->getRow((int) $c->v);
                } else {
                    $value = '';
                }
                break;
            case 'b':
                if (!isset($c->f)) {
                    $value = isset($c->v) ? (string) $c->v : null;
                    if ($value == '0') {
                        $value = false;
                    } elseif ($value == '1') {
                        $value = true;
                    } else {
                        $value = (bool) $c->v;
                    }
                }
                break;
            case 'inlineStr':
                if (!isset($c->f)) $value = (string)$c->is->t;
                break;
            case 'e':
                if (!isset($c->f)) $value = isset($c->v) ? (string) $c->v : null;
                break;
            default:
                if (!isset($c->f)) {
                    if (property_exists($c, 'v')) {
                        $value = (string)$c->v;
                    } else {
                        $value = '';
                    }
                }
                break;
        }
        return $value;
    }

    public function getRowData($row, $tmpHeader = null, $line, $validation = null)
    {
        $row = preg_replace('/[a-zA-Z]+:([a-zA-Z]+[=>])/', '$1', $row);
        $row = simplexml_load_string($row);
        $rowData = [];
        foreach ($row as $col) {
            $key = preg_replace('/[0-9]*$/', '', (string)$col['r']);

            //gyorsítás, ne olvassa ki a tartalmat, ha nem kell
            if ($tmpHeader && !array_key_exists($key, $tmpHeader)) continue;

            $rowData[$key] = $this->readCell($col);
        }

        $isempty = empty(array_filter($rowData, function ($r) {
            return !empty($r);
        }));

        if ($this->total && $this->canWriteCache()) {
            $percent = round(($line / $this->total) * 100);
            if ($validation) {
                $this->writeCache(' (Adatok ellenőrzése ' .  $percent . ' % kész)');
            } else {
                $this->writeCache(' (Adatok rögzítése ' .  $percent . ' % kész)');
            }
        }

        if ($isempty) return null;

        if (!$tmpHeader) return $rowData;
        $rowData2 = [];

        foreach ($tmpHeader as $key => $value) {
            if (array_key_exists($key, $rowData)) {
                $rowData2[$value] = $rowData[$key];
            } else {
                $rowData2[$value] = null;
            }
        }
        return $rowData2;
    }

    static function ReadRowByTag(String $file_path, String $tag, callable $callback)
    {
        if (!is_file($file_path)) sendError('Fájl nem létezik: ' . $file_path);
        $handle = @fopen($file_path, "r");
        if (!$handle) sendError('Fájl olvasása sikertelen: ' . $file_path);
        $preg_start_tag = '<' . str_replace(['/', '\\', '<', '>'], '', $tag);
        $preg_end_tag = '</' . str_replace(['/', '\\', '<', '>'], '', $tag) . '>';
        $i = 0;
        $pre_buffer = '';
        $buffer_size = 1024 * 1024;
        while (!feof($handle)) {
            $buffer = $pre_buffer . fgets($handle, $buffer_size);
            if (preg_match($preg_end_tag, $buffer)) {
                $explode_buffer = explode($preg_end_tag, $buffer);
                $pre_buffer = array_pop($explode_buffer);
                foreach ($explode_buffer as $row) {
                    $row = preg_replace("/\r|\n/", "", $row);
                    if ($i == 0) $row = preg_replace('/.*' . $preg_start_tag . '/', $preg_start_tag, $row);
                    $callback($i,  $row . $preg_end_tag);
                    $i++;
                }
            } else {
                $pre_buffer = $buffer;
            }
        }
        fclose($handle);
    }

    private function checkFormat(ZipArchive $zip)
    {

        $rels = $this->tmpPathName . DIRECTORY_SEPARATOR . '_rels' . DIRECTORY_SEPARATOR . '.rels';

        if (!file_exists($rels)) sendError("A fájlt nem lehet beolvasni");

        $this->baseName = $this->tmpPathName . DIRECTORY_SEPARATOR . 'xl' . DIRECTORY_SEPARATOR . 'worksheets' . DIRECTORY_SEPARATOR . 'sheet1.xml';
        if (!file_exists($this->baseName)) sendError("A fájlt nem lehet beolvasni", 1);
    }

    public function readJsonFile($file_path, callable $callback)
    {
        if (!is_file($file_path)) sendError('Fájl nem létezik: ' . $file_path);
        $handle = @fopen($file_path, "r");
        if (!$handle) sendError('Fájl olvasása sikertelen: ' . $file_path);

        $i = 0;
        while (!feof($handle)) {
            $i++;
            $buffer = fgets($handle);
            if (!empty($buffer)) $callback(json_decode($buffer, true), $i);
        }
        fclose($handle);
    }

    public function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object))
                        $this->rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                    else
                        unlink($dir . DIRECTORY_SEPARATOR . $object);
                }
            }
            rmdir($dir);
        }
    }

    public function __destruct()
    {
        $this->rrmdir($this->tmpPathName);
    }
}
