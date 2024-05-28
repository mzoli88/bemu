<?php

namespace hws\xlsx;

use hws\Downloader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class XlsxWriter
{

    use CacheTrait;

    public $headers = [];

    public $currentRows = [1 => 0];

    private $closed = false;

    public $tmp_output_path;

    public $sheetXml_paths = [];
    public $sheetXml_filehandlers = [];

    public $template = [];

    public $activeSheet = 1;
    public $SheetNames = [1 => 'Munka1'];


    public function __construct($headers = null)
    {
        set_time_limit(0);
        if ($this->canWriteCache()) {
            $this->writeCache('(Előkészítés)');
        }

        $this->sheetXml_paths[1] = tempnam(Storage::path(''), 'export_');
        $this->sheetXml_filehandlers[1] = fopen($this->sheetXml_paths[1], 'w');
        fwrite($this->sheetXml_filehandlers[1], file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'SheetStart.txt'));

        $this->template['_rels/.rels'] = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'rels.xml');
        $this->template['[Content_Types].xml'] = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'ContentTypes.xml');
        $this->template['xl/workbook.xml'] = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'workbook.xml');
        $this->template['xl/styles.xml'] = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'styles.xml');
        $this->template['xl/worksheets/_rels/sheet1.xml.rels'] = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'relsSheet.xml');
        $this->template['xl/_rels/workbook.xml.rels'] = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'relsWorkbook.xml');
        $this->template['docProps/core.xml'] = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'core.xml');
        $this->template['docProps/app.xml'] = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'app.xml');
        if (isset($_GET['hiddenHeaders'])) {
            try {
                $hiddenHeaders = json_decode($_GET['hiddenHeaders']);
                if (!empty($hiddenHeaders)) {
                    foreach ($hiddenHeaders as $keyheader) {
                        if (array_key_exists($keyheader, $headers)) unset($headers[$keyheader]);
                    }
                }
            } catch (\Throwable $th) {
                //throw $th;
            }
        }
        if ($headers) $this->addHeaders($headers);
    }

    public function addHeaders($headers)
    {
        $this->headers[$this->activeSheet] = [];
        $i = 0;
        collect($headers)->each(function ($text, $key) use (&$i) {
            $i++;
            $this->headers[$this->activeSheet][$key] = $this->getNameFromNumber($i);
        });
        $this->addRow($headers, 1);
    }

    public function setActiveSheet($activeSheet = 1)
    {
        $this->activeSheet = $activeSheet;
        if (!array_key_exists($activeSheet, $this->SheetNames)) {
            $this->currentRows[$activeSheet] = 0;
            $this->SheetNames[$activeSheet] = 'Munka' . $activeSheet;
            $this->sheetXml_paths[$activeSheet] = tempnam(Storage::path(''), 'export_');
            $this->sheetXml_filehandlers[$activeSheet] = fopen($this->sheetXml_paths[$activeSheet], 'w');
            fwrite($this->sheetXml_filehandlers[$activeSheet], str_replace('tabSelected="1" ', '', file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'SheetStart.txt')));
        }
    }

    public function setActiveSheetName($name)
    {
        $this->SheetNames[$this->activeSheet] = $name;
    }

    public function query(Builder $query, $headers = [], $collection = 'export|default', $export_chunk_size = 2000, $noOrderBy = false, $noPage = false)
    {
        // $this->addHeaders($query->getHeaders($headers));
        if ($headers) $this->addHeaders($headers);

        if ($noPage) {
            $query->get()->each(function ($record) use ($collection) {
                $this->addRow($record->collect($collection));
            });
        } else {
            $total = 0;
            if (config('callq')) $total = $query->toBase()->getCountForPagination();
            //rendezés törlés, sorrend beégetés
            $perPage = config('export_chunk_size', $export_chunk_size);
            if ($noOrderBy == false) {
                $query->getQuery()->orders = null;
                $query->orderBy($query->getModel()->getTable() . '.' . $query->getModel()->getKeyName());
            }
            $page = 0;
            $query->chunk($perPage, function ($records) use ($collection, $total, $perPage, &$page) {
                $records->each(function ($record) use ($collection) {
                    $this->addRow($record->collect($collection));
                });

                if ($this->canWriteCache()) {
                    $percent = round(($page * $perPage) / $total * 100);
                    $this->writeCache('(' . $percent . ' % kész)');
                }
                $page++;
            });
        }
    }

    public function addRow(array $rowData, $background = false, &$datas = false)
    {
        $this->currentRows[$this->activeSheet]++;

        $out = '<row r="' . $this->currentRows[$this->activeSheet] . '">';
        foreach ($this->headers[$this->activeSheet] as $name => $key) {
            if (array_key_exists($name, $rowData) && (!empty($rowData[$name]) || $rowData[$name] == 0)) {
                if (is_int($rowData[$name]) || is_double($rowData[$name])) {
                    $out .= '<c r="' . $key . $this->currentRows[$this->activeSheet] . '"' . ($background ? ' s="' . $background . '"' : '') . '><v>' . $rowData[$name] . '</v></c>';
                } else {
                    $rowData[$name] = htmlspecialchars($rowData[$name], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8');
                    $out .= '<c r="' . $key . $this->currentRows[$this->activeSheet] . '" t="inlineStr"' . ($background ? ' s="' . $background . '"' : '') . '><is><t>' . $rowData[$name] . '</t></is></c>';
                }
            } else {
                if ($background) $out .= '<c r="' . $key . $this->currentRows[$this->activeSheet] . '"' . ($background ? ' s="' . $background . '"' : '') . '><v></v></c>';
            }
        }
        $out .= '</row>';
        fwrite($this->sheetXml_filehandlers[$this->activeSheet], $out);
    }

    public function close()
    {
        if ($this->closed) return;
        $this->closed = true;
        if ($this->canWriteCache()) $this->writeCache('(Véglegesítés)');

        $write_to = '</sheetData></worksheet>';

        foreach ($this->sheetXml_filehandlers as $handler) {
            fwrite($handler, $write_to);
            fclose($handler);
        }


        //workbook írása
        $wb_add = '';
        $wb2_add = '';
        $wb3_add = '';
        foreach ($this->SheetNames as $key => $name) {
            $wb_add .= '<sheet name="' . $name . '" sheetId="' . $key . '" r:id="rId' . ($key + 5) . '" />';
            $wb2_add .= '<Relationship Id="rId' . ($key + 5) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $key . '.xml" />';
            $wb3_add .= '<Override PartName="/xl/worksheets/sheet' . $key . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml" />';
        }
        $wbTmp = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'workbook.xml');
        $this->template['xl/workbook.xml'] = str_replace('<sheet name="Munka1" sheetId="1" r:id="rId1" />', $wb_add, $wbTmp);
        // dd($this->template['xl/workbook.xml']);

        $wb2Tmp = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'relsWorkbook.xml');
        $this->template['xl/_rels/workbook.xml.rels'] = str_replace('<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml" />', $wb2_add, $wb2Tmp);
        // dd($this->template['xl/_rels/workbook.xml.rels']);

        $wb3Tmp = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'ContentTypes.xml');
        $this->template['[Content_Types].xml'] = str_replace('<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml" />', $wb3_add, $wb3Tmp);
        // dd($this->template['[Content_Types].xml']);

        $zip = new ZipArchive;

        $this->tmp_output_path =  tempnam(Storage::path(''), 'export_');

        $res = $zip->open($this->tmp_output_path . '_ready.xlsx', ZipArchive::CREATE);
        if ($res === TRUE) {
            foreach ($this->template as $key => $template) {
                if ($key == 'docProps/core.xml') {
                    $template = str_replace('{DATE}', gmdate('Y-m-d\TH:i:s\Z'), $template);
                }
                $zip->addFromString($key, sprintf($template));
            }
            foreach ($this->sheetXml_paths as $key => $path) {
                $zip->addFile($path, 'xl/worksheets/sheet' . $key . '.xml');
            }
        }
        $zip->close();
    }

    public function save($path)
    {
        $this->close();
        copy($this->tmp_output_path . '_ready.xlsx', $path);
    }

    public function store($dir, $name)
    {
        $this->close();
        Storage::putFileAs($dir, $this->tmp_output_path . '_ready.xlsx', $name);
    }

    private function saveToQueque($file_name)
    {
        $this->close();
        global $Callq_file;
        $Callq_file = [
            "download" => config('error') ? 'Hibákat tartalmazó import fájl letöltése' : 'Export fájl letöltése',
            "file" => Storage::putFile('export', $this->tmp_output_path . '_ready.xlsx'),
            "name" => $file_name,
        ];
        return $Callq_file;
    }

    public function download($file_name)
    {
        if (!preg_match('/\.xlsx$/', $file_name)) $file_name .= '.xlsx';
        hwslog(null, null, "Elkészült lista: " . $file_name);
        if (app()->runningInConsole()) return $this->saveToQueque($file_name);
        $this->close();
        Downloader::local($this->tmp_output_path . '_ready.xlsx', $file_name);
    }

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

    public function __destruct()
    {
        if (file_exists($this->tmp_output_path)) unlink($this->tmp_output_path);
        if (file_exists($this->tmp_output_path . '_ready.xlsx')) unlink($this->tmp_output_path . '_ready.xlsx');
        foreach ($this->sheetXml_paths as $key => $path) {
            if (file_exists($path)) unlink($path);
        }
    }
}
