<?php
/**
 * @package markup_language
 */

/**
 *
 * @author Ken
 *
 */
class HTML_form {
	const METHOD_GET=0;
	const METHOD_POST=1;
	const INPUT_TEXT=0;
	const INPUT_SELECT=1;
	const INPUT_CHECKBOX=2;
	const INPUT_LITERAL=3;
	const INPUT_HIDDEN=4;
	const INPUT_FILE=5;
	private $data=array();
	private $name='';
	private $action='';
	private $method='GET';
	private $submit=array('submit', 'submit');
	function __destruct(){
		unset($this->data);
	}
	/**
	 * Sets the name and value of the submit button.
	 * @param string $display The value
	 * @param string $name [optional] The name
	 */
	public function setSubmit($display, $name=null){
		$this->submit[0]=$display;
		$this->submit[1]=$name;
	}
	/**
	 * Sets the target page for this form.
	 * @param string $page The target page for this form.
	 */
	public function setAction($page){
		$this->action=$page;
	}
	/**
	 * Sets the name of the form.
	 * @param string $name The name of the form.
	 */
	public function setName($name){
		$this->name=$name;
	}
	/**
	 * Sets the form submission method.
	 * @param int $method METHOD_POST or METHOD_GET
	 */
	public function setMethod($method){
		switch ($method){
			case HTML_form::METHOD_GET:
				$this->method='GET';
				break;
			case HTML_form::METHOD_POST:
				$this->method='POST';
				break;
			default:
				throw new InvalidArgumentException('Method is not one of the prefedined values.', 0);
		}
	}
	/**
	 * Prints the form to stdout.
	 */
	public function output(){
		echo "<form name=\"$this->name\" action=\"$this->action\" method=\"$this->method\">\n";
		foreach($this->data as $element)
			echo $element;
		echo '<input type="submit" '. ((!empty($this->submit[1]))?("name=\"{$this->submit[1]}\" "):'') ."value=\"{$this->submit[0]}\">";
		echo '</form>';
	}
	/**
	 * Convenience method for adding lots of hidden data to the form.
	 * @param array $data
	 */
	public function addAllHidden(array $data){
		foreach ($data as $key => $value)
			$this->addItem(array(HTML_form::INPUT_HIDDEN, $key, $value));
	}
	/**
	 * Adds an element to the form.<br>
	 *		Formatted like so: array(type, text, name, value[, inline])<br>
	 *		type=one of HTML_form::INPUT_*<br>
	 *		inline=true or false. defaults to false.<br>
	 *		LITERAL prints the text and value and adds a hidden field.<br>
	 *		For 'CHECKBOX' the format is array('CHECKBOX', text, name, value, selected[, inline])<br>
	 *		For 'HIDDEN' the format is array('HIDDEN', name, value)<br>
	 *		For 'SELECT' the format is array('SELECT', text, name, value, default[, inline])<br>
	 *		and value is either array(value[, ...]) or array( array(value, text)[, ...] ).<br>
	 *		if the prior it used then value will be used for text.
	 * @param array $item
	 */
	public function addItem(array $item){
		$cElements=count($item);
		switch ($item[0]){
			case HTML_form::INPUT_TEXT:
				if($cElements < 4) throw new InvalidArgumentException('Number of elements is too low for text.', 0);
				$this->data[]="\t$item[1] <input type=\"text\" name=\"$item[2]\" value=\"$item[3]\"/>".($cElements==5?($item[4]?'':HTML::MEOL):HTML::MEOL);
				break;
			case HTML_form::INPUT_HIDDEN:
				if($cElements != 3) throw new InvalidArgumentException('Number of elements is incorrect for hidden.', 0);
				$this->data[]="\t<input type=\"hidden\" name=\"$item[1]\" value=\"$item[2]\" />\n";
				break;
			case HTML_form::INPUT_SELECT:
				if($cElements < 5) throw new InvalidArgumentException('Number of elements is too low for hidden.', 0);
				$element='';
				$element=$element . "\t$item[1] <select name=\"$item[2]\" value=\"$item[4]\">\n";
				if(is_array($item[3])){
					foreach ($item[3] as $opt){
						if(is_array($opt)){
							$element=$element . "\t\t<option value=\"$opt[0]\" ".($item[4]==$opt[0]?'selected="selected"':'').">$opt[1]</option>\n";
						} else {
							$element=$element . "\t\t<option value=\"$opt\"".($item[4]==$opt?'selected="selected"':'').">$opt</option>\n";
						}
					}
				} else {
					if(mysql_num_fields($item[3]) == 1){
						while ($row=mysql_fetch_row($item[3])){
							$element=$element . "\t\t<option value=\"$row[0]\" ".($item[4]==$row[0]?'selected="selected"':'').">$row[0]</option>\n";
						}
					} else {
						while ($row=mysql_fetch_row($item[3])){
							$element=$element . "\t\t<option value=\"$row[0]\" ".($item[4]==$row[0]?'selected="selected"':'').">$row[1]</option>\n";
						}
					}
				}
				$element=$element . "\t</select>".($cElements==6?($item[5]?'':HTML::MEOL):HTML::MEOL);
				$this->data[]=$element;
				break;
			case HTML_form::INPUT_FILE:
				if($cElements < 4) throw new InvalidArgumentException('Number of elements is too low for file.', 0);
				$this->data[]="\t$item[1] <input type=\"file\" name=\"$item[2]\" value=\"$item[3]\"/>".($cElements==5?($item[4]?'':HTML::MEOL):HTML::MEOL);
				break;
			case HTML_form::INPUT_CHECKBOX:
				if($cElements < 5) throw new InvalidArgumentException('Number of elements is too low for checkbox.', 0);
				$this->data="\t$item[1] <input type=\"checkbox\" name=\"$item[2]\" value=\"$item[3]\"".($item[4]?"checked=\"checked\"":"")."/>".($cElements==6?($item[5]?'':HTML::MEOL):HTML::MEOL);
				break;
			case HTML_form::INPUT_LITERAL:
				if($cElements < 4) throw new InvalidArgumentException('Number of elements is too low for literal.', 0);
				$this->data="\t<input type=\"hidden\" name=\"$item[2]\" value=\"$item[3]\" />\n" .
					$item[1].' '.$item[3].($cElements==5?($item[4]?'':HTML::MEOL):HTML::MEOL);
				break;
			default:
				throw new InvalidArgumentException('Input type is not one of the predefined types.', 0);
		}
	}
}