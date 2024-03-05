<?php
require_once('../lib/config.class.php');
emu_user_init();
class MZ_adminer
{

	//iframeben is meg lehet nyitni
	function headers()
	{
		header("X-Frame-Options: SameOrigin");
	}

	public function loginForm(): void
    {
        echo script('
            if (document.querySelector(\'#content > div.error\') == null) {
                document.addEventListener(\'DOMContentLoaded\', function () {
                    document.forms[0].submit()
                })
            }
        ');
    }

	public function head()
    {
        ?>
        <script <?php echo nonce(); ?>>
            (function(document) {
                "use strict";
                document.addEventListener("DOMContentLoaded", init, false);
                function init() {
                    var selects = document.querySelectorAll("select[name='Collation'], select[name*='collation']");
                    for (var i = 0; i < selects.length; i++) {
                        selects[i].innerHTML = '<option selected="selected">utf8_hungarian_ci</option>';
                    }
                }
            })(document);
        </script>
        <?php
    }

	//automata belépés
	function credentials()
	{
		return array(BORDER_DB_HOST, BORDER_DB_USER, BORDER_DB_PASSWORD);
	}

	function login($login, $password)
	{
		return true;
	}
}

class AdminerTablesFilter {
	function tablesPrint($tables) { ?>
<script<?php echo nonce(); ?>>
var tablesFilterTimeout = null;
var tablesFilterValue = '';

function tablesFilter(){
	var value = qs('#filter-field').value.toLowerCase();
	if (value == tablesFilterValue) {
		return;
	}
	tablesFilterValue = value;
	if (value != '') {
		var reg = (value + '').replace(/([\\\.\+\*\?\[\^\]\$\(\)\{\}\=\!\<\>\|\:])/g, '\\$1');
		reg = new RegExp('('+ reg + ')', 'gi');
	}
	if (sessionStorage) {
		sessionStorage.setItem('adminer_tables_filter', value);
	}
	var tables = qsa('li', qs('#tables'));
	for (var i = 0; i < tables.length; i++) {
		var a = null;
		var text = tables[i].getAttribute('data-table-name');
		if (text == null) {
			a = qsa('a', tables[i])[1];
			text = a.innerHTML.trim();

			tables[i].setAttribute('data-table-name', text);
			a.setAttribute('data-link', 'main');
		} else {
			a = qs('a[data-link="main"]', tables[i]);
		}
		if (value == '') {
			tables[i].className = '';
			a.innerHTML = text;
		} else {
			tables[i].className = (text.toLowerCase().indexOf(value) == -1 ? 'hidden' : '');
			a.innerHTML = text.replace(reg, '<strong>$1</strong>');
		}
	}
}

function tablesFilterInput() {
	window.clearTimeout(tablesFilterTimeout);
	tablesFilterTimeout = window.setTimeout(tablesFilter, 200);
}

sessionStorage && document.addEventListener('DOMContentLoaded', function () {
	var db = qs('#dbs').querySelector('select');
	db = db.options[db.selectedIndex].text;
	if (db == sessionStorage.getItem('adminer_tables_filter_db') && sessionStorage.getItem('adminer_tables_filter')){
		qs('#filter-field').value = sessionStorage.getItem('adminer_tables_filter');
		tablesFilter();
	}
	sessionStorage.setItem('adminer_tables_filter_db', db);
});
</script>
<p class="jsonly"><input id="filter-field" autocomplete="off"><?php echo script("qs('#filter-field').oninput = tablesFilterInput;"); ?>
<?php
	}
}

class AdminerEditForeign {
	var $_limit;
	
	function __construct($limit = 0) {
		$this->_limit = $limit;
	}
	
	function editInput($table, $field, $attrs, $value) {
		static $foreignTables = array();
		static $values = array();
		$foreignKeys = &$foreignTables[$table];
		if ($foreignKeys === null) {
			$foreignKeys = column_foreign_keys($table);
		}
		foreach ((array) $foreignKeys[$field["field"]] as $foreignKey) {
			if (count($foreignKey["source"]) == 1) {
				$target = $foreignKey["table"];
				$id = $foreignKey["target"][0];
				$options = &$values[$target][$id];
				if (!$options) {
					$column = idf_escape($id);
					if (preg_match('~binary~', $field["type"])) {
						$column = "HEX($column)";
					}
					$options = array("" => "") + get_vals("SELECT $column FROM " . table($target) . " ORDER BY 1" . ($this->_limit ? " LIMIT " . ($this->_limit + 1) : ""));
					if ($this->_limit && count($options) - 1 > $this->_limit) {
						return;
					}
				}
				return "<select$attrs>" . optionlist($options, $value) . "</select>";
			}
		}
	}
	
}

function adminer_object()
{
	$plugins = array(
		new AdminerEditForeign,
		new AdminerTablesFilter,
		new MZ_adminer,
	);

	class AdminerPlugin extends Adminer
	{
		/** @access protected */
		var $plugins;

		function _findRootClass($class)
		{ // is_subclass_of(string, string) is available since PHP 5.0.3
			do {
				$return = $class;
			} while ($class = get_parent_class($class));
			return $return;
		}

		/** Register plugins
		 * @param array object instances or null to register all classes starting by 'Adminer'
		 */
		function __construct($plugins)
		{
			if ($plugins === null) {
				$plugins = array();
				foreach (get_declared_classes() as $class) {
					if (preg_match('~^Adminer.~i', $class) && strcasecmp($this->_findRootClass($class), 'Adminer')) { //! can use interface
						$plugins[$class] = new $class;
					}
				}
			}
			$this->plugins = $plugins;
			//! it is possible to use ReflectionObject to find out which plugins defines which methods at once
		}

		function _callParent($function, $args)
		{
			return call_user_func_array(array('parent', $function), $args);
		}

		function _applyPlugin($function, $args)
		{
			foreach ($this->plugins as $plugin) {
				if (method_exists($plugin, $function)) {
					switch (count($args)) { // call_user_func_array() doesn't work well with references
						case 0:
							$return = $plugin->$function();
							break;
						case 1:
							$return = $plugin->$function($args[0]);
							break;
						case 2:
							$return = $plugin->$function($args[0], $args[1]);
							break;
						case 3:
							$return = $plugin->$function($args[0], $args[1], $args[2]);
							break;
						case 4:
							$return = $plugin->$function($args[0], $args[1], $args[2], $args[3]);
							break;
						case 5:
							$return = $plugin->$function($args[0], $args[1], $args[2], $args[3], $args[4]);
							break;
						case 6:
							$return = $plugin->$function($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
							break;
						default:
							trigger_error('Too many parameters.', E_USER_WARNING);
					}
					if ($return !== null) {
						return $return;
					}
				}
			}
			return $this->_callParent($function, $args);
		}

		function _appendPlugin($function, $args)
		{
			$return = $this->_callParent($function, $args);
			foreach ($this->plugins as $plugin) {
				if (method_exists($plugin, $function)) {
					$value = call_user_func_array(array($plugin, $function), $args);
					if ($value) {
						$return += $value;
					}
				}
			}
			return $return;
		}

		// appendPlugin

		function dumpFormat()
		{
			$args = func_get_args();
			return $this->_appendPlugin(__FUNCTION__, $args);
		}

		function dumpOutput()
		{
			$args = func_get_args();
			return $this->_appendPlugin(__FUNCTION__, $args);
		}

		function editRowPrint($table, $fields, $row, $update)
		{
			$args = func_get_args();
			return $this->_appendPlugin(__FUNCTION__, $args);
		}

		function editFunctions($field)
		{
			$args = func_get_args();
			return $this->_appendPlugin(__FUNCTION__, $args);
		}

		// applyPlugin

		function name()
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function credentials()
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function connectSsl()
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function permanentLogin($create = false)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function bruteForceKey()
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function serverName($server)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function database()
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function schemas()
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function databases($flush = true)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function queryTimeout()
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function headers()
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function csp()
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function head()
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function css()
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function loginForm()
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function loginFormField($name, $heading, $value)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function login($login, $password)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function tableName($tableStatus)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function fieldName($field, $order = 0)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function selectLinks($tableStatus, $set = "")
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function foreignKeys($table)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function backwardKeys($table, $tableName)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function backwardKeysPrint($backwardKeys, $row)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function selectQuery($query, $start, $failed = false)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function sqlCommandQuery($query)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function rowDescription($table)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function rowDescriptions($rows, $foreignKeys)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function selectLink($val, $field)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function selectVal($val, $link, $field, $original)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function editVal($val, $field)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function tableStructurePrint($fields)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function tableIndexesPrint($indexes)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function selectColumnsPrint($select, $columns)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function selectSearchPrint($where, $columns, $indexes)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function selectOrderPrint($order, $columns, $indexes)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function selectLimitPrint($limit)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function selectLengthPrint($text_length)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function selectActionPrint($indexes)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function selectCommandPrint()
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function selectImportPrint()
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function selectEmailPrint($emailFields, $columns)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function selectColumnsProcess($columns, $indexes)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function selectSearchProcess($fields, $indexes)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function selectOrderProcess($fields, $indexes)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function selectLimitProcess()
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function selectLengthProcess()
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function selectEmailProcess($where, $foreignKeys)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function selectQueryBuild($select, $where, $group, $order, $limit, $page)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function messageQuery($query, $time, $failed = false)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function editInput($table, $field, $attrs, $value)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function editHint($table, $field, $value)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function processInput($field, $value, $function = "")
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function dumpDatabase($db)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function dumpTable($table, $style, $is_view = 0)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function dumpData($table, $style, $query)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function dumpFilename($identifier)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function dumpHeaders($identifier, $multi_table = false)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function importServerPath()
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function homepage()
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function navigation($missing)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function databasesPrint($missing)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}

		function tablesPrint($tables)
		{
			$args = func_get_args();
			return $this->_applyPlugin(__FUNCTION__, $args);
		}
	}

	return new AdminerPlugin($plugins);
}

include "adminer-4.8.1-en.php";
