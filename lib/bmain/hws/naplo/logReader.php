<?php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $limit = false;
    $perpage = 20;
    $total = 0;

    if (isset($_GET['total']) && isset($_GET['page']) && $_GET['page'] != 1) {
        $limit = (int)$_GET['total'] - (((int)$_GET['page'] - 1) * (int)$perpage);
        // $total = (int)$_GET['total'];
    }

    $naploClass = new LogReader($dir);

    $log_type = 'debug';
    if (isset($_POST['log_type'])) $log_type = $_POST['log_type'];

    $search = [];

    foreach ($_POST as $key => $value) {
        if ($key == 'log_type') continue;
        $search[$key] = $value;
    }


    $data = $naploClass->getLog($log_type, $search, $total, $perpage, $limit);
    echo json_encode([
        'total' => isset($_GET['total']) && $limit ? $_GET['total'] : $total,
        'data' => $data,
    ]);
    exit;
}

class LogReader
{

    static $log_folder = "/tmp/";

    public function  __construct($folder)
    {
        self::$log_folder = $folder;
    }


    static function search_row(array $preg_tmb, $buffer)
    {
        foreach ($preg_tmb as $preg) {
            if (preg_match($preg, $buffer)) continue;
            return false;
        }
        return true;
    }

    public function getLog($file_name = 'debug', $search = [], &$total_find = 0, $perPage = 25, $limit = false)
    {
        $matches = [];

        //keresési reguláris kifejezések előállítása
        $search_pregs = false;
        $days = [];
        if (!empty($search)) {

            if (array_key_exists('date', $search) && !empty($search['date']) && is_string($search['date'])) {
                $days[] = $search['date'];
            }

            $search_pregs = [];

            foreach ($search as $key => $value) {
                if ($key == 'date') continue;
                $search_pregs[] = '/"' . $key . '":"[^"]*' . $value . '[^"]*"/i';
            }
        }
        if (empty($days)) $days[] = date('Y-m-d');


        foreach ($days as $date) {

            if (empty($search_pregs) && !$limit) {

                //ha nincs keresés akkor ez egy gyorsabb meoldás a total kiszámítására

                if (!is_file(self::$log_folder . $file_name . "-" . $date . '.log')) continue;
                $handle = new \SplFileObject(self::$log_folder . $file_name . "-" . $date . '.log', 'r');
                if (!$handle) continue;

                $handle->seek(PHP_INT_MAX);
                $total_find = $handle->key();

                $to_line = $total_find - $perPage - 1;

                if ($to_line < 1) $to_line = 0;

                $handle->seek($to_line);

                while (!$handle->eof()) {
                    $buffer = $handle->fgets();
                    if (empty($buffer)) continue;
                    $matches[] = ['date' => $date, 'ct' => $buffer];
                }
            } else {
                // print_r(self::$log_folder . $file_name . "-" . $date . '.log');
                if (!is_file(self::$log_folder . $file_name . "-" . $date . '.log')) continue;
                $handle = @fopen(self::$log_folder . $file_name . "-" . $date . '.log', "r");
                if (!$handle) continue;
                while (!feof($handle)) {
                    $buffer = fgets($handle);
                    if (empty($buffer) || ($search_pregs && !self::search_row($search_pregs, $buffer))) continue;
                    $matches[] = ['date' => $date, 'ct' => $buffer];
                    $total_find++;
                    if ($perPage && $total_find > $perPage) array_shift($matches);
                    if ($limit && $limit <= $total_find) break;
                }
                fclose($handle);
            }

            //foreach-ből is ki kell léptetni, ha már meglett a limit
            if ($limit && $limit <= $total_find) break;
        }

        $out = [];
        if (!empty($matches)) {
            $matches = array_reverse($matches);
            foreach ($matches as $key => $row) {
                $tmp = json_decode($row['ct'], true);
                $tmp['message'] = htmlentities($tmp['message'], ENT_QUOTES, "UTF-8");
                $out[] = $tmp;
            }
        }
        return $out;
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>LogReader</title>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
    <style>
        body,
        html,
        #app {
            height: 100vh;
            padding: 0;
            margin: 0;
        }

        * {
            color: #BFD2FF;
            font-size: 18px;
        }

        a {
            text-decoration: none;
        }

        .noselect {
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            -khtml-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        input {
            border-style: none;
            background: transparent;
            outline: none;
        }

        button {
            padding: 0;
            background: none;
            border: none;
            outline: none;
            width: 500px;
        }

        body {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            background-image: radial-gradient(circle at 0% 0%, #373b52, #252736 51%, #1d1e26);
        }

        table {
            border-collapse: collapse;
        }

        .vflex,
        .hflex,
        .cflex {
            display: flex;
        }

        .hflex {
            flex-direction: column;
        }

        .vflex {
            flex-direction: row;
        }

        .cflex {
            justify-content: center;
            align-items: center;
        }

        .vflex>*,
        .hflex>*,
        .cflex>* {
            flex-grow: 0;
            flex-shrink: 0;
        }

        .cflex>* {
            /* legördülőlőkhöz böngésző  hiba javítás */
            margin: auto;
        }

        .fit {
            flex: 1 1 auto;
            min-height: 10px;
            min-width: 10px;
        }

        .webflow-style-input {
            color: #BFD2FF;
            padding: 10px;
            border-bottom: 2px solid #ddd;
        }

        input {
            color: #fff;
        }

        .webflow-style-input input::-webkit-input-placeholder {
            color: #7881A1;
        }

        .webflow-style-input button {
            color: #7881A1;
            vertical-align: middle;
            transition: color 0.25s;
        }

        .webflow-style-input button:hover {
            color: #BFD2FF;
        }

        #table_list {
            overflow: auto;
            min-height: 10px;
            min-width: 10px;
            padding: 10px;
        }

        td {
            font-size: 14px;
            padding: 2px 8px;
            border-bottom: 2px solid #ddd;
        }

        th,
        td.date_col {
            white-space: nowrap;
            text-align: left;
            border-bottom: 2px solid #ddd;
        }

        .uri_data {
            font-size: 14px;
            margin: 5px;
        }

        td>div.message {

            font-size: 14px;
            color: black;
            cursor: pointer;
            height: 150px;
            overflow: hidden;
            margin: 5px;
            border: 2px solid #eef0f6;
            border-radius: 4px;
            background-color: #fff;
            padding: 4px;
            display: block;
            unicode-bidi: embed;
            font-family: monospace;
            white-space: -moz-pre-wrap;
            white-space: -pre-wrap;
            white-space: -o-pre-wrap;
            white-space: pre-wrap;
            overflow-wrap: break-word;
            word-wrap: break-word;
            -ms-word-break: break-all;
            word-break: break-word;
            -ms-hyphens: auto;
            -moz-hyphens: auto;
            -webkit-hyphens: auto;
            hyphens: auto;
        }

        td>div.message:focus {
            border: 2px solid rgba(56, 102, 193, 0.6);
            overflow: auto;
            width: auto;
            height: auto;
            cursor: initial;
            min-height: 150px;
            max-height: 800px;
        }

        .small_table td {
            border: none;
            padding: 0 6px;
        }

        .error_level .message {
            background-color: #ff5a5a;
        }

        .info_level .message {
            background-color: greenyellow;
        }

        ::-webkit-calendar-picker-indicator {
            filter: invert(1);
        }

        .osszesen {
            padding: 10px;
        }

        #loading,
        #refresh {
            font-size: 50px;
            cursor: pointer;
        }

        .lds-hourglass {
            display: inline-block;
            position: relative;
            width: 80px;
            height: 80px;
        }

        .lds-hourglass:after {
            content: " ";
            display: block;
            border-radius: 50%;
            width: 0;
            height: 0;
            margin: 8px;
            box-sizing: border-box;
            border: 32px solid #fff;
            border-color: #fff transparent #fff transparent;
            animation: lds-hourglass 1.2s infinite;
        }

        @keyframes lds-hourglass {
            0% {
                transform: rotate(0);
                animation-timing-function: cubic-bezier(0.55, 0.055, 0.675, 0.19);
            }

            50% {
                transform: rotate(900deg);
                animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
            }

            100% {
                transform: rotate(1800deg);
            }
        }
    </style>
</head>

<body>
    <div id="app" class="vflex">
        <div id="table_list" class="hflex fit">
        </div>
        <div class="hflex noselect">
            <div class="webflow-style-input">
                <input type="radio" id="type_debug" name="log_type" value="debug" class="search_field" checked>
                <label for="type_debug">Debug</label>
                <input type="radio" id="type_audit" name="log_type" value="audit" class="search_field">
                <label for="type_audit">Audit</label>
                <input type="radio" id="type_security" name="log_type" value="security" class="search_field">
                <label for="type_security">Security</label>
                <input type="radio" id="type_system" name="log_type" value="system" class="search_field">
                <label for="type_system">System</label>
            </div>
            <div class="webflow-style-input vflex">
                <label>date:</label>
                <input class="search_field fit" type="date" name="date"></input>
            </div>
            <div class="webflow-style-input vflex">
                <label>level:</label>
                <input class="search_field fit" type="text" name="level"></input>
            </div>
            <div class="webflow-style-input vflex">
                <label>software_id:</label>
                <input class="search_field fit" type="text" name="software_id"></input>
            </div>
            <div class="webflow-style-input vflex">
                <label>request_id:</label>
                <input class="search_field fit" type="text" name="request_id"></input>
            </div>
            <div class="webflow-style-input vflex">
                <label>user_name:</label>
                <input class="search_field fit" type="text" name="user_name"></input>
            </div>
            <div class="webflow-style-input vflex">
                <label>uuid:</label>
                <input class="search_field fit" type="text" name="uuid"></input>
            </div>
            <div class="webflow-style-input vflex">
                <label>Method:</label>
                <input class="search_field fit" type="text" name="method"></input>
            </div>
            <div class="webflow-style-input vflex">
                <label>URI:</label>
                <input class="search_field fit" type="text" name="uri"></input>
            </div>
            <div class="webflow-style-input vflex">
                <label>event:</label>
                <input class="search_field fit" type="text" name="event"></input>
            </div>
            <div class="webflow-style-input vflex">
                <label>Message:</label>
                <input class="search_field fit" type="text" name="message"></input>
            </div>
            <div class="fit cflex">
                <div id="refresh">⟳</div>
                <div id="loading" class="lds-hourglass"></div>
            </div>
            <div class="osszesen">Összesen: <span id="osszesen_data">0</span> db</div>
        </div>

    </div>
</body>

<script>
    var pageNumber = 1;
    var total = 0;
    var running = false;

    function load(data) {
        if (running) return;
        running = true;
        document.getElementById('loading').style.display = 'block';
        document.getElementById('refresh').style.display = 'none';
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4) {
                running = false;
                document.getElementById('loading').style.display = 'none';
                document.getElementById('refresh').style.display = 'block';
            }
            if (this.readyState == 4 && this.status == 200) {
                refreshList(JSON.parse(this.response));
            };
        }

        data = data || {};
        var tosend = [];

        for (var index in data) {
            tosend.push(encodeURIComponent(index) + '=' + encodeURIComponent(data[index]))
        }

        xhttp.open("POST", window.location.origin + window.location.pathname + '?page=' + pageNumber + '&total=' + total, true);
        xhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhttp.send(tosend.join('&'));
    }

    function refreshList(data) {
        if (pageNumber > 1) {
            var cmpText = '';
        } else {
            var cmpText = '<table id="dataTable"><tr><th>Dátum idő</th><th>Adatok</th><th>Szöveg</th></tr>';
        }
        if (data.total) {
            total = data.total;
            document.getElementById('osszesen_data').innerHTML = data.total;
        } else {
            total = 0;
            document.getElementById('osszesen_data').innerHTML = '0';
        }
        for (var i in data.data) {
            let tmp_data = data.data[i];
            $class = 'default_level';

            if (tmp_data.level == 'error') $class = 'error_level';
            if (tmp_data.level == 'info') $class = 'info_level';

            cmpText += '<tr class="' + $class + '">';

            cmpText += '<td class="date_col">' + tmp_data.datetime + '</td>';

            cmpText += '<td><table class="small_table">';

            for (var j in tmp_data) {
                if (j == 'datetime') continue;
                if (j == 'message') continue;
                if (j == 'uri') continue;
                if (j == 'method') continue;
                cmpText += '<tr><td>' + j + ':</td><td>' + tmp_data[j] + '</td></tr>';
            }

            cmpText += '</table></td>';
            cmpText += '<td><div class="uri_data">' + tmp_data.method + ': ' + tmp_data.uri + '</div><div class="message" tabindex="0" >' + tmp_data.message + '</div></td>';
            cmpText += '</tr>';
        }
        if (pageNumber > 1) {
            var Parent = document.getElementById('dataTable');
            Parent.innerHTML = Parent.innerHTML.replace(/^<tbody>/, '').replace(/<\/tbody>$/, '') + cmpText;
            return;
        } else {
            cmpText += '</table>'
            var Parent = document.getElementById('table_list');
            Parent.innerHTML = cmpText;
        }
    }

    function search(page) {
        if (page) {
            pageNumber++;
        } else {
            pageNumber = 1;
            total = 0;
        }
        var inputs = document.getElementsByClassName("search_field");
        var values = {};
        for (let i = 0; i < inputs.length; i++) {
            if (inputs[i].name == 'log_type' && !inputs[i].checked) continue;
            if (inputs[i].value && inputs[i].value.length) values[inputs[i].name] = inputs[i].value;
        }
        load(values);
        window.location.hash = values.log_type;
    }

    var TMPtimout;
    document.addEventListener('input', function(e) {
        document.getElementById('loading').style.display = 'block';
        document.getElementById('refresh').style.display = 'none';
        clearTimeout(TMPtimout);
        if (e.target.type == 'radio') return search();
        TMPtimout = setTimeout(search, 1500);
    });

    if (window.location.hash.length < 2) window.location.hash = 'debug';
    document.getElementsByName('log_type').forEach(function(rec) {
        if (rec.value == window.location.hash.substr(1)) rec.checked = true;
        else rec.checked = false;
    });

    document.addEventListener("keypress", function(event) {
        if (event.key !== "Enter") return;
        event.preventDefault();
        clearTimeout(TMPtimout);
        return search();
    });

    var ScrollTimout;
    var tableList = document.getElementById('table_list');
    tableList.addEventListener('scroll', function(event) {
        clearTimeout(ScrollTimout);
        ScrollTimout = setTimeout(function() {
            if ((tableList.offsetHeight + tableList.scrollTop + 50) >= tableList.scrollHeight) {
                clearTimeout(TMPtimout);
                search(true);
            }
        }, 200);
    });

    document.getElementById('refresh').addEventListener("click", function(event) {
        event.preventDefault();
        clearTimeout(TMPtimout);
        return search();
    });

    load({
        log_type: window.location.hash.substr(1)
    });
</script>

</html>