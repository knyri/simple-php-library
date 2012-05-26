<?php
/**
 * @author Kenneth Pierce kcpiercejr@gmail.com
 */

/**
 *
 * Aids in the creation of a WAP page.
 * @author Kenneth Pierce
 */
class WML {
	const MEOL = "<br/>\n"; //mark-up EOL
	private static $base_href = '';
	public static function echo_header() {
		echo '<?xml version="1.0"?>' . "\n" .
		'<!DOCTYPE wml PUBLIC "-//WAPFORUM//DTD WML 1.1//EN" "http://www.wapforum.org/DTD/wml_1.1.xml">' . "\n" .
		'<wml>' . "\n";
	}
	public static function echo_footer() {
		echo '</wml>';
	}
	public static function anchor($text, $link) {
		return "<anchor>$text<go href=\"".WML::$base_href."$link\"/></anchor>";
	}
	public static function anchor_ext($pre, $text, $post, $link) {
		return "$pre<anchor>$text<go href=\"".WML::$base_href."$link\"/></anchor>$post";
	}
	public static function anchor_back($text) {
		return "<anchor>$text<prev/></anchor>";
	}
	/**
	 * Enter description here ...
	 * @param unknown_type $link
	 */
	public static function set_base_href($link) {
		WML::$base_href = $link;
	}
	public static function bold($text) {
		return '<b>' . $text . '</b>';
	}
	public static function italic($text) {
		return '<i>' .$text. '</i>';
	}
	public static function underline($text) {
		return '<u>' .$text. '</u>';
	}
	/** Creates and prints a form.
	 * @param string $name name of the form(not used)
	 * @param string $target link to the target page
	 * @param array $input array of wanted input.
	 *		Formatted like so: array( array(<type>, <text>, <name>, <value>[, <inline>])[, ...] )
	 *		<type> = 'TEXT', 'SELECT', 'HIDDEN', 'MASK', 'LITERAL'
	 *		<inline> = true or false. defaults to false.
	 *		LITERAL prints the text and value, and adds a hidden field.
	 *		For 'HIDDEN' the format is array('HIDDEN', <name>, <value>)
	 *		For 'SELECT' the format is array('SELECT', <text>, <name>, <value>, <default>[, <inline>])
	 *		and <value> is either array(<value>[, ...]) or array( array(<value>, <text>)[, ...] ).
	 *		if the prior it used then <value> will be used for <text>.
	 *		For 'MASK' the internal array is formatted array('MASK',<text>, <name>, <value>, <mask>[,<inline>])
	 *		where <mask> is:
	 *			'A' = uppercase alphabetic or punctuation characters
	 *			'a' = lowercase alphabetic or punctuation characters
	 *			'N' = numeric characters
	 *			'X' = uppercase characters
	 *			'x' = lowercase characters
	 *			'M' = all characters
	 *			'm' = all characters
	 *			'*f' = Any number of characters. Replace the f with one of the letters above to specify what characters the user can enter
	 *			'nf' = Replace the n with a number from 1 to 9 to specify the number of characters the user can enter. Replace the f with one of the letters above to specify what characters the user can enter
	 * @param string $button Text for the button. Default: submit
	 * @param string $method Either 'GET' or 'post'. Default: GET
	 */
	public static function form($name, $target, $input, $button = 'submit', $method = 'GET') {
		$postdata = array();
		$cElements = 0;
		$form = '';
		//$reset = "<nativemarkup targetLocation=\"wml.card.onevent\">\n\t<onevent type=\"onenterforward\">\n\t\t<refresh>\n";
		foreach($input as $ele) {
			$cElements = count($ele);
			if ($ele[0] == 'HIDDEN' || $ele[0]=='LITERAL') {
				if ($cElements == 3)
					$postdata[] = array($ele[1], $ele[2]);
				else {
					$postdata[] = array($ele[2], $ele[3]);
					$form .= $ele[1].' '.$ele[3].($cElements==5?($ele[4]?'':WML::MEOL):WML::MEOL);
				}
				continue;
			} else
				$postdata[] = array($ele[2], '$('.$ele[2].')');
			$form .= $ele[1] . ' ';
			if ($ele[0] == 'SELECT') {
				$form .= "\t<select name=\"$ele[2]\" value=\"$ele[4]\">\n";
			//	$reset .= "\t\t<setvar name=\"$ele[2]\" value=\"$ele[4]\"/>\n";
				foreach ($ele[3] as $opt) {
					if (is_array($opt)) {
						$form .= "\t\t<option value=\"$opt[0]\">$opt[1]</option>\n";
					} else {
						$form .= "\t\t<option value=\"$opt\">$opt</option>\n";
					}
				}
				$form .= "\t</select>".($cElements==6?($ele[5]?'':WML::MEOL):WML::MEOL);
			} else if ($ele[0] == 'TEXT') {
				$form .= "\t<input name=\"$ele[2]\" value=\"$ele[3]\"/>".($cElements==5?($ele[4]?'':WML::MEOL):WML::MEOL);
				//$reset .= "\t\t<setvar name=\"$ele[2]\" value=\"$ele[3]\"/>\n";
			} else if ($ele[0] == 'MASK') {
				$form .= "\t<input name=\"$ele[2]\" value=\"$ele[3]\" format=\"$ele[4]\"/>".($cElements==6?($ele[5]?'':WML::MEOL):WML::MEOL);
				//$reset .= "\t\t<setvar name=\"$ele[2]\" value=\"$ele[3]\"/>\n";
			}
		}
		//$reset .= "\n\t\t</refresh>\n\t</onevent>\n</nativemarkup>\n";
		//echo $reset . $form;
		echo $form;
		echo "<anchor>\n\t<go method=\"$method\" href=\"$target\">\n";
		foreach ($postdata as $data) {
			echo "\t\t<postfield name=\"$data[0]\" value=\"$data[1]\"/>\n";
		}
		echo "\t</go>\n\t$button\n</anchor>";
	}
	function embed_refresh($location, $deci) {
	echo "<card ontimer=\"$location;\"><br />
		<timer value=\"$deci\"/><br />
		<p><i>If your browser does not refresh in ".($deci/10.0)." seconds, click <anchor>here<go href=\"$location;\"/></anchor>.</i></p>
	</card>";
	}
	public static function remove_special($string) {
		$return = '';
		for($i = 0; $i < strlen($string); $i++) {
			switch($string[$i]) {
				case '\'':
				case '"':
				case '<':
				case '>':
					break;
				default:
					$return .= $string[$i];
			}
		}
		return $return;
	}
	public static function escape_special($string) {
		$return = '';
		for($i = 0; $i < strlen($string); $i++) {
			switch($string[$i]) {
				case '\'':
					$return .= '&apos;';
					break;
				case '"':
					$return .= '&quote;';
					break;
				case '<':
					$return .= '&lt;';
					break;
				case '>':
					$return .= '&gt;';
					break;
				default:
					$return .= $string[$i];
			}
		}
		return $return;
	}
	public static function escape($string) {
		$return = '';
		for($i = 0; $i < strlen($string); $i++) {
			switch($string[$i]) {
				case ' ':
					$return .= '&nbsp;';
					break;
				case '\'':
					$return .= '&apos;';
					break;
				case '"':
					$return .= '&quote;';
					break;
				case '<':
					$return .= '&lt;';
					break;
				case '>':
					$return .= '&gt;';
					break;
				default:
					$return .= $string[$i];
			}
		}
		return $return;
	}
	public static function unescape($string) {
		$return = '';
		for ($i = 0; $i < strlen($string); $i++) {
			if ($string[$i] != '&') {
				$return .= $string[$i];
			} else {
				switch($string[++$i]) {
					case 'n':
						if ($string[++$i]=='b') {
							if ($string[++$i]=='s') {
								if ($string[++$i]=='p') {
									if ($string[++$i]==';') {
										$return .= ' ';
									} else {
										$return .= '&nbsp';
										$i--;
									}
								} else {
									$return .= '&nbs';
									$i--;
								}
							} else {
								$return .= '&nb';
								$i--;
							}
						} else {
							$return .= '&n';
							$i--;
						}
						break;
					case 'a':
						if ($string[++$i]=='p') {
							if ($string[++$i]=='o') {
								if ($string[++$i]=='s') {
									if ($string[++$i]==';') {
										$return .= '\'';
									} else {
										$return .= '&apos';
										$i--;
									}
								} else {
									$return .= '&apo';
									$i--;
								}
							} else {
								$return .= '&ap';
								$i--;
							}
						} else {
							$return .= '&a';
							$i--;
						}
						break;
					case 'q':
						if ($string[++$i]=='u') {
							if ($string[++$i]=='o') {
								if ($string[++$i]=='t') {
									if ($string[++$i]=='t') {
										if ($string[++$i]==';') {
											$return .= '"';
										} else {
											$return .= '&quote';
											$i--;
										}
									} else {
										$return .= '&quot';
										$i--;
									}
								} else {
									$return .= '&quo';
									$i--;
								}
							} else {
								$return .= '&qu';
								$i--;
							}
						} else {
							$return .= '&q';
							$i--;
						}
						break;
					case 'l':
						if ($string[++$i]=='t') {
							if ($string[++$i]==';') {
								$return .= '<';
							} else {
								$return .= '&lt';
								$i--;
							}
						} else {
							$return .= '&l';
							$i--;
						}
						break;
					case 'g':
						if ($string[++$i]=='t') {
							if ($string[++$i]==';') {
								$return .= '>';
							} else {
								$return .= '&gt';
								$i--;
							}
						} else {
							$return .= '&g';
							$i--;
						}
						break;
					default:
						$return .= '&';
						$i--;
				}
			}
		} // end for
		return $return;
	}//unescape
}
?>