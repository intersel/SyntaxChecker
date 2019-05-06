<?php
/**
 * SyntaxChecker
 *
 * Copyright 2011 by Everett Griffiths <everett@fireproofsocks.com>
 *
 * This is a plugin for MODX 2.2.x, designed to check the tag syntax of MODX
 * documents, templates, and chunks.
 *
 * SyntaxChecker is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * SyntaxChecker is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * SyntaxChecker; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * @package syntaxchecker
 */
class SyntaxChecker {

	// for log file
	public $errors = array();
	// For modal window (less specific because the user already knows what page they're on)
	public $simple_errors = array(); 
	
	public $modx;
	
	// Harvested from core/model/modx/filters/modoutputfilter.class.php
	public $built_in_output_filters = array(
	'input','if','eq','is','equals','equalto','isequal','isequalto','ne','neq','isnot','isnt',
	'notequals','notequalto','gte','isgte','eg','ge','equalorgreaterthan','greaterthanorequalto',
	'lte','islte','le','el','lessthanorequalto','equaltoorlessthan','gt','isgt','greaterthan',
	'isgreaterthan','lt','islt','lessthan','lowerthan','islessthan','islowerthan','ismember',
	'memberof','mo','or','and','hide','show','then','else','select','cat','lcase','lowercase',
	'strtolower','ucase','uppercase','strtoupper','ucwords','ucfirst','htmlent','htmlentities',
	'esc','escape','strip','stripString','replace','notags','striptags','stripTags','strip_tags',
	'length','len','strlen','reverse','strrev','wordwrap','wordwrapcut','limit','ellipsis',
	'tag','math','add','increment','incr','subtract','decrement','decr','multiply','mpy',
	'divide','div','modulus','mod','default','ifempty','isempty','empty','ifnotempty',
	'isnotempty','notempty','!empty','nl2br','date','strtotime','fuzzydate','ago','md5','cdata',
	'userinfo','isloggedin','isnotloggedin','urlencode','urldecode','toPlaceholder','cssToHead',
	'htmlToHead','htmlToBottom','jsToHead','jsToBottom'	);

	// What [[*doc_vars]] are available by default?
	public $resource_fields = array(
	'id','type','contentType','pagetitle','longtitle','description','alias','link_attributes',
	'published','pub_date','unpub_date','parent','isfolder','introtext','content','richtext',
	'template','menuindex','searchable','cacheable','createdby','createdon','editedby','editedon',
	'deleted','deletedon','deletedby','publishedon','publishedby','menutitle','donthit',
	'content_dispo','hidemenu','context_key','content_type','uri_override','hide_children_in_tree',
	'show_in_tree','articles_container_settings','articles_container'
	);
	
	//------------------------------------------------------------------------------
	/**
	 * 
	 */
	public function __construct($modx) {
		$this->modx 	=& $modx;
	}

	//------------------------------------------------------------------------------
	//! Private Functions
	//------------------------------------------------------------------------------
	// Just for debugging
	private function _log($str) {
		$myFile = "/tmp/modx.txt";
		$fh = fopen($myFile, 'a') or die("can't open file");
		fwrite($fh, $str . "\n");
		fclose($fh);	
	}
	
	/**
	 * Generates string of length $len that is only spaces.  We use this to white-out
	 * tags after we've checked them.
	 * @param	integer	$len -- the length of the string needed.
	 * @return	string	a series of $len spaces.
	 */
	private function _generate_whitespace($len) {
		$str = '';
		$i = 0;
		while ($i < $len) {
			$str .= ' ';
			$i++;
		}
		return $str;
	}


	//------------------------------------------------------------------------------
	/**
	 * Check the parameters to see if they are correct.
	 *
	 * Sets error messages if there are problems.
	 *
	 * @param	string	$str 
	 */
	private function _validate_chunk($str) {
		$chunk = $this->modx->getObject('modChunk', array('name'=>$str));
		if (!$chunk) {			
			$this->errors[] = sprintf( $this->modx->lexicon('chunk_does_not_exist'), '[[$'.$str.']]');
			$this->simple_errors[] = sprintf( $this->modx->lexicon('chunk_does_not_exist'), '[[$'.$str.']]');
		}
	}

	//------------------------------------------------------------------------------
	/**
	 * Check the parameters to see if they are correct.  Params and TVs...
	 * the problem here is that the [[*docvar]] instances could appear 
	 * ANYWHERE (e.g. in a Chunk), so we have to adjust the checking depending
	 * on where it appears.
	 *
	 * Sets error messages if there are problems.
	 *
	 * @param	string	$str 
	 */
	private function _validate_docvar($str, $field, &$obj) {
	
		$class = get_parent_class($obj);
		//$this->modx->log(xPDO::LOG_LEVEL_ERROR, "[SyntaxChecker] class: $class");
		//$this->modx->log(xPDO::LOG_LEVEL_ERROR, '[SyntaxChecker]'. print_r( get_class_vars($class), true));

//		return;
		switch ($class) {
			// is there any var or TV by that name?
			case 'modChunk':
				if (!in_array($str, $this->resource_fields)) {
/*
					// 
					$TV = $this->modx->getObject('modTemplateVar', array('name'=>$str));
					if (!$TV) {
						$this->errors[] = sprintf( $this->modx->lexicon('docvar_does_not_exist'), $str);
						$this->simple_errors[] = sprintf( $this->modx->lexicon('docvar_does_not_exist'), $str);			
					}
*/

					$TV = $this->modx->getObject('modTemplateVar', array('name'=>$str));
					if (!$TV) {
						$this->errors[] = sprintf( $this->modx->lexicon('docvar_does_not_exist'), $str);
						$this->simple_errors[] = sprintf( $this->modx->lexicon('docvar_does_not_exist'), $str);			
					}
				}			
				break;
				
			// is there any var or TV assoc'd to THIS template?
			case 'modTemplate':
				if (!in_array($str, $this->resource_fields)) {
					// modTemplateVarResource
					$TV = $this->modx->getObject('modTemplateVar', array('name'=>$str));
					if (!$TV) {
						$this->errors[] = sprintf( $this->modx->lexicon('docvar_does_not_exist'), '[[*'.$str.']]');
						$this->simple_errors[] = sprintf( $this->modx->lexicon('docvar_does_not_exist'), '[[*'.$str.']]');
					}
					else {
						$TVT = $this->modx->getObject('modTemplateVarTemplate'
						, array('templateid'=>$obj->get('id'), 'tmplvarid'=> $TV->get('id') ));
						if (!$TVT) {
							$this->errors[] = sprintf( $this->modx->lexicon('docvar_not_associated'), '[[*'.$str.']]');
							$this->simple_errors[] = sprintf( $this->modx->lexicon('docvar_not_associated'), '[[*'.$str.']]');
						}					
					}
				}			

				break;
				
			// Any vars associated with this resource?	
			case 'modDocument':
				if (!in_array($str, $this->resource_fields)) {
					// modTemplateVarResource
					$TV = $this->modx->getObject('modTemplateVar', array('name'=>$str));
					if (!$TV) {
						$this->errors[] = sprintf( $this->modx->lexicon('docvar_does_not_exist'), '[[*'.$str.']]');
						$this->simple_errors[] = sprintf( $this->modx->lexicon('docvar_does_not_exist'), '[[*'.$str.']]');
					}
					else {
						$TVT = $this->modx->getObject('modTemplateVarTemplate'
						, array('templateid'=>$obj->get('id'), 'tmplvarid'=> $TV->get('id') ));
						if (!$TVT) {
							$this->errors[] = sprintf( $this->modx->lexicon('docvar_not_associated'), '[[*'.$str.']]');
							$this->simple_errors[] = sprintf( $this->modx->lexicon('docvar_not_associated'), '[[*'.$str.']]');
						}					
					}
				}			

				break;
				
			// TVs.  Can you put document variables in a TV?
			default:
				// Check on a $field basis?
				//$this->errors[] = 'You cannot put document variables in a TV.'.$class.'<---';
				//$this->simple_errors[] = 'You cannot put document variables in a TV.'.$class.'<---';
		}
	}


	//------------------------------------------------------------------------------
	/**
	 * Check the parameters to see if they are correct.
	 *
	 * Sets error messages if there are problems.
	 *
	 * @param	string	$str 
	 */
	private function _validate_link($str) {
		if (empty($str)) {
			return;
		}
		$resource = $this->modx->getObject('modResource', $str);
		if (!$resource) {
			$this->errors[] = sprintf( $this->modx->lexicon('resource_does_not_exist'), '[[~'.$str.']]');
			$this->simple_errors[] = sprintf( $this->modx->lexicon('resource_does_not_exist'), '[[~'.$str.']]');			
		}	
	}

	//------------------------------------------------------------------------------
	/**
	 * Check the parameters to see if they are correct.
	 *
	 * Sets error messages if there are problems.
	 *
	 * @param	string	$str 
	 */
	private function _validate_lexicon($str) {	
		// TODO: how to check this?
		$lexicon = $this->modx->getObject('modLexiconEntry', array('name' => $str));
		if (!$lexicon) {
		//	$this->errors[] = sprintf( $this->modx->lexicon('lexicon_does_not_exist'), '[[%'.$str.']]');
		//	$this->simple_errors[] = sprintf( $this->modx->lexicon('lexicon_does_not_exist'), '[[%'.$str.']]');			
		}	
	}

	//------------------------------------------------------------------------------
	/**
	 * Check the parameters to see if they are correct.
	 *
	 * Sets error messages if there are problems.
	 *  
	 * @param	string	$str e.g. '&elementClass=`modSnippet` &element=`getResources`'
	 */
	private function _validate_params($parts) {
		
		
		$str = $parts['params'];
		
		while($str) {
		
			$str = trim($str);
			
			$parameter_name = $str; // e.g. the "tpl" in &tpl=`something`, but here we give it a default value
			
			$first_char = substr($str, 0, 1);
			if ($first_char != '&') {
				// get the parameter name (everything up to the = or `)
				preg_match('/^[^=`]+/i', $str, $matches);
 
				if (isset($matches[0])) {
					$parameter_name = $matches[0];
				}
				$this->errors[] = sprintf( $this->modx->lexicon('missing_ampersands'), $parts['token'], $parameter_name);
				$this->simple_errors[] = sprintf( $this->modx->lexicon('missing_ampersands'), $parts['token'], $parameter_name);	
			}
			else {
				$str = trim(substr($str, 1)); // shift off &
			}

			// Everything before the equals (or to the backtick)
			preg_match('/^[^=`]+/i', $str, $matches);
			if (isset($matches[0])) {
				$parameter_name = $matches[0];
				// TODO: check if this is a valid parameter name, e.g.based on Snippet profile/params
				$str = preg_replace('/^'.$matches[0].'/','', $str); // shave off the param
			}
			// No parameter name?!?  e.g. [[Snippet? =`arg`]] (just checking...)
			else {
				$this->errors[] = sprintf($this->modx->lexicon('missing_parameter'), $parts['token']);
				$this->simple_errors[] = sprintf($this->modx->lexicon('missing_parameter'), $parts['token']);	
			}
			
			// Missing equals sing?
			$first_char = substr($str, 0, 1);
			if ($first_char != '=') {
				$this->errors[] = sprintf( $this->modx->lexicon('missing_equals'), $parts['token'], $parameter_name);
				$this->simple_errors[] = sprintf( $this->modx->lexicon('missing_equals'), $parts['token'], $parameter_name);	
			}
			else {
				$str = trim(substr($str, 1)); // shift off the equals sign
			}
			
			
			// Check parameter value
			$first_char = substr($str, 0, 1);
			if ($first_char != '`') {
				// Integers can be passed w/o backticks
				preg_match('/^[^\s]+/i', $str, $matches);				
				if (isset($matches[0])) {
					if(!is_numeric($matches[0])) {
						$this->errors[] = sprintf( $this->modx->lexicon('props_must_be_quoted'), $parts['token'], $parameter_name);
						$this->simple_errors[] = sprintf( $this->modx->lexicon('props_must_be_quoted'), $parts['token'], $parameter_name);			
					}
					$str = preg_replace('/^'.$matches[0].'/','', $str); // shave off the param
				}
				// ???
				return;
			}
			// Check for empty parameter strings, e.g. &param=``
			elseif (substr($str, 0 ,2) == '``') {
				$str = substr($str, 2); // shift off the empty backticks
			}
			else {
				$str = substr($str, 1); // shift off the backtick
				
				preg_match('/^[^`]+/i', $str, $matches); // get everything til the closing backtick.
				
				if (isset($matches[0])) {
					// The parameter is matched here
					//$matches[0];
					$str = preg_replace('/^'.preg_quote($matches[0],'/').'/','', $str); // shave off the param val
					$str = substr($str, 1); // shift off the closing backtick
				}
			}
		}
	
	}

	//------------------------------------------------------------------------------
	/**
	 * Looks up the propset to see if it exists.
	 *
	 * Sets error messages if there are problems.
	 *
	 * @param	string	$str
	 */
	private function _validate_propset($str) {
		//modPropertySet
		$propset = $this->modx->getObject('modPropertySet', array('name'=>$str));
		if (!$propset) {
			$this->errors[] = sprintf( $this->modx->lexicon('propset_does_not_exist'), $str);
			$this->simple_errors[] = sprintf( $this->modx->lexicon('propset_does_not_exist'), $str);
		}
	}

	//------------------------------------------------------------------------------
	/**
	 * Input should be something like this: 
	 *		filter=`H:i:s`:otherfilter
	 *
	 * Note that we can't just do an "explode" operation because the filters may
	 * use colons as input.
	 *
	 * Sets error messages if there are problems.
	 *
	 * @param	string	$str
	 */
	private function _validate_filters($str) {
		$filters = array();
		
		
		while($str) {
			preg_match('/^[^=:]+/i', $str, $matches);
			if (isset($matches[0])) {
				
				$this_filter = trim($matches[0]); // store this for messaging.
				$filters[] = $this_filter;
				$str = preg_replace('/^'.$matches[0].'/', '', $str);
				
				$first_char = substr($str, 0, 1);
				$str = substr($str, 1); // shift off first char
				
				if ($first_char == ':') {
					continue;  // next filter
				}
				// any Arguments?, e.g. 
				elseif ($first_char == '=') {
					$first_char = substr($str, 0, 1);
					if ($first_char != '`') {
						// Integers can be passed w/o backticks
						preg_match('/^[^\s]+/i', $str, $matches);				
						if (isset($matches[0])) {
							if(!is_numeric($matches[0])) {
								$this->errors[] = sprintf( $this->modx->lexicon('output_filter_args_not_quoted'), $this_filter, $matches[0]);
								$this->simple_errors[] = sprintf( $this->modx->lexicon('output_filter_args_not_quoted'), $this_filter, $matches[0]);			
							}
							$str = preg_replace('/^'.$matches[0].'/','', $str); // shave off the param
						}
					}
					// Check for empty parameter strings, e.g. &param=``
					elseif (substr($str, 0 ,2) == '``') {
						$str = substr($str, 2); // shift off the empty backticks
					}
					else {
						$str = substr($str, 1); // shift off the backtick
						
						preg_match('/^[^`]+/i', $str, $matches); // get everything til the closing backtick.
						
						if (isset($matches[0])) {
							// The parameter is matched here
							//$matches[0];
							$str = preg_replace('/^'.preg_quote($matches[0],'/').'/','', $str); // shave off the param val
							$str = substr($str, 1); // shift off the closing backtick
						}
					}
				}
			}
			// No filter?!?
			else {
				// $this->errors[]
				return;
			}
		}
		
		foreach ($filters as $f) {
			if (!in_array($f, $this->built_in_output_filters)) {
				// Is is a custom snippet?
				$Snippet = $this->modx->getObject('modSnippet', array('name'=>$f));
				if (!$Snippet) {
					$this->errors[] = sprintf( $this->modx->lexicon('output_filter_does_not_exist'), $f);
					$this->simple_errors[] = sprintf( $this->modx->lexicon('output_filter_does_not_exist'), $f);
				}
			}
		}
	}

	//------------------------------------------------------------------------------
	/**
	 * Looks up the propset to see if it exists.
	 *
	 * Sets error messages if there are problems.
	 * getOption won't work: e.g. YOUR user may not have this setting, but the user 
	 * who views it might have it.
	 *
	 * @param	string	$str
	 */
	private function _validate_setting($str) {
		
		// This will get global settings and any hard-coded ones, e.g. site_url					
		$Setting = $this->modx->getOption($str); 
		//$Setting = $this->modx->getObject('modSystemSetting', array('name'=>$str));
		if (!$Setting) {
			$Setting = $this->modx->getObject('modContextSetting', array('key'=>$str));
			if (!$Setting) {			
				$Setting = $this->modx->getObject('modUserSetting', array('key'=>$str));
				if (!$Setting) {
					$this->errors[] = sprintf( $this->modx->lexicon('setting_does_not_exist'), '[[++'.$str.']]');
					$this->simple_errors[] = sprintf( $this->modx->lexicon('setting_does_not_exist'), '[[++'.$str.']]');
				}
			}
		}
	}


	//------------------------------------------------------------------------------
	/**
	 * Looks up the propset to see if it exists.
	 *
	 * Sets error messages if there are problems.
	 *
	 * @param	string	$str
	 */
	private function _validate_snippet($str) {
		$Snippet = $this->modx->getObject('modSnippet', array('name'=>$str));
		if (!$Snippet) {
			$this->errors[] = sprintf( $this->modx->lexicon('snippet_does_not_exist'), '[['.$str.']]');
			$this->simple_errors[] = sprintf( $this->modx->lexicon('snippet_does_not_exist'), '[['.$str.']]');
		}
	}

	//------------------------------------------------------------------------------
	//! Public functions
	//------------------------------------------------------------------------------

	/**
	 * Break down a tag into its component parts.
	 *
	 * array(
	 *		[token]		=>
	 *		[propset]	=>
	 *		[filters]	=>
	 *		[params]	=> 
	 * )
	 *
	 * @param	string contents of a tag without an !, e.g. "pagetitle" or "MySnippet? &arg=`one`"
	 * @param	array
	 */
	public function atomize_tag($tag) {
		$tag = trim($tag);
		
		$parts = array(
			'token' => '',
			'propset' => '',
			'filters' => '',
			'params' => ''
		);
		
		// Get token
		preg_match('/^[^@?:&`]+/i', $tag, $matches);
		if (!empty($matches)){
			$parts['token'] = trim($matches[0]);
			$tag = trim(preg_replace('/^'.$matches[0].'/', '', $tag));
		}
		else {
			// ERROR!! No Token!!
			return;
		}
		
		// Get Propset
		$first_char = substr($tag, 0, 1);
		if ($first_char == '@') {
			$tag = substr($tag, 1); // shift off first char
			preg_match('/^[^?:&`]+/i', $tag, $matches);
			if (isset($matches[0])) {
				$parts['propset'] = trim($matches[0]);
				$this->_validate_propset($parts['propset']);
				$tag = trim(preg_replace('/^'.$matches[0].'/', '', $tag));
			}
		}
		
		// Get Filters
		$first_char = substr($tag, 0, 1);
		if ($first_char == ':') {
			$tag = substr($tag, 1); // shift off first char
			preg_match('/^[^?&]+/i', $tag, $matches);
			//print_r($matches);
			if (isset($matches[0])) {
				$parts['filters'] = trim($matches[0]);
				$this->_validate_filters($parts['filters']);
				$tag = trim(preg_replace('/^'.preg_quote($matches[0],'/').'/', '', $tag));
			}
		}
		if (!$tag) {
			return $parts;
		}
		// Get Params
		$first_char = substr($tag, 0, 1);
		if ($first_char == '?') {
			$tag = substr($tag, 1); // shift off first char
			$parts['params'] = trim($tag);			
		}
		else {
			// ERROR!!! Missing Question Mark!!!
			$this->errors[] = sprintf($this->modx->lexicon('missing_questionmark'), $parts['token']);
			$this->simple_errors[] = sprintf($this->modx->lexicon('missing_questionmark'), $parts['token']);
			
			$parts['params'] = $tag;
		}
		$this->_validate_params($parts);
		
		return $parts;
	}

	/**
	 * Basic integrity check: look for mismatched square-brackets.  if ($backticks & 1)... odd number.
	 *
	 * @param	string	$type		Resource|Chunk|Template|TV (used for messaging)
	 * @param	string	$field		the field being checked (so we know what content to load)
	 * @param	object	$obj		either $resource, $template, $tv, or $chunk, depending.
	 */
	public function check_integrity($type, $field, &$obj) {
		
		$content 	= $obj->get($field);
		$id 		= $obj->get('id');
		
		$left_brackets	= substr_count($content, '[[');
		$right_brackets	= substr_count($content, ']]');
		$backticks		= substr_count($content, '`');
		if ($left_brackets != $right_brackets) {
			$this->errors[] = sprintf( $this->modx->lexicon('mismatched_brackets'), $field) . " $type $id";
			$this->simple_errors[] = sprintf( $this->modx->lexicon('mismatched_brackets'), $field);
		}
		
		if($backticks&1) {
			$this->errors[] = sprintf( $this->modx->lexicon('odd_backticks'), $field) . " $type $id";
			$this->simple_errors[] = sprintf( $this->modx->lexicon('odd_backticks'), $field);
		}
	}

	//------------------------------------------------------------------------------
	/**
	 * THIS IS THE MAIN EVENT!!!
	 *
	 * Phase 2 checker: evaluate the tags for their syntax. You MUST run check_integrity
	 * first before you can run this check_syntax() function.
	 *
	 * @param	string	$type
	 * @param	string	$field the field to be checked.
	 * @param	object	$obj
	 */
	public function check_syntax($type, $field, &$obj) {
	
		// Gotta strip out those nasty "space-like" characters.
		$content = str_replace(array("\r","\r\n","\n","\t",chr(202),chr(173),chr(0xC2),chr(0xA0) ), ' ', $obj->get($field));
	
		
		$id = $obj->get('id');
		$map = $this->get_tag_map($content);
		
		if (empty($map)) {
			return; // No tags!
		}
	
		// Check for any "persona non grata", i.e. "void" tags
		// e.g. [[~]], [[$]], [[++]], [[+]], [[++]], [[%]]
		// This is important because we blank them out as we check them.  After this point,
		// those "void" tags will be considered valid.
		if ($this->check_voids($content, $id)) {
			return;
		}
		
		$raw_map = $map; // save a copy
		
		// We gotta loop through this.  First pass grabs only the non-nested bits.
		// We keep going through until we've handled all the bits in the $map.
		while (count($map)) { 		// Loop start
			$indices = array_keys($map);
			$count = count($indices);
			$this_index = $map[$indices[0]];
			for ( $i = 1; $i < $count; $i++ ) {
				$next_index = $map[$indices[$i]];
				if ($this_index == 'tag_open' && $next_index == 'tag_close') {
					$tag_len = $indices[$i] - $indices[$i-1] - 2; // 2 for the width of the tag
					$full_tag_len = $tag_len + 4; // additional 4 characters for framing brackets
					$tag = substr ($content , $indices[$i-1] + 2, $tag_len );

					$this->check_tag_contents($tag, $field, $obj); // <-- the magic happens
				
					// Update the map: check these ones off our list
					unset($map[$indices[$i-1]]);
					unset($map[$indices[$i]]);
					
					// blank out that tag with spaces.  This is a cheap trick so we can test nested tags.
					$whiteout = $this->_generate_whitespace($full_tag_len);
					$content = substr_replace($content, $whiteout , $indices[$i-1], $full_tag_len );
				}
				
				$this_index = $next_index;
				
			}
		}// Loop end	
	}

	//------------------------------------------------------------------------------
	/**
	 * Check a single tag contents (i.e. everything inside square brackets, and without 
	 * any nested tags).  
	 * Input can look like any of the following:
	 *
	 *	mySnippet? &param=`something`
	 *	$myChunk
	 *	*doc_var:with:output:filters
	 *	~123
	 * 	someTag:filters? &params=`xyz`
	 *	Token@propertySet:filter? &param=`123`
	 *
	 * See http://rtfm.modx.com/display/revolution20/Tag+Syntax
	 *
	 * The way this is called should ensure that any nested tags are "whited out"
	 * before they get here. 
	 *
	 * @param string $content of a tag, without the framing square brackets.
	 * @param field e.g. content
	 * @param object e.g. modResource
	 */
	public function check_tag_contents($content, $field, &$obj) {
			
		// Strip the exclamation point 
		$content = preg_replace('/^!/','', $content);

		$first_char = substr($content, 0, 1);
		
		switch ($first_char) {
			// Comment tag
			case '-':
				return; // do nothing.
				break;				
			// Lexicon tag
			case '%':
				$content = substr($content, 1); // shift off first char
				$parts = $this->atomize_tag($content);
				$this->_validate_lexicon($parts['token']);
				break;				
			// Chunk
			case '$':
				$content = substr($content, 1); // shift off first char
				$parts = $this->atomize_tag($content);
				$this->_validate_chunk($parts['token']);
				break;
			// Link
			case '~':
				$content = substr($content, 1); // shift off first char
				$parts = $this->atomize_tag($content);
				$this->_validate_link($parts['token']);
				break;
			// Doc var
			case '*':
				$content = substr($content, 1); // shift off first char
				$parts = $this->atomize_tag($content);
				$this->_validate_docvar($parts['token'], $field, $obj);
				break;
			// Placeholder or System Setting
			case '+':
				$content = substr($content, 1); // shift off first char
				// ++ System Setting
				if (substr($content, 0, 1) == '+') {
					$content = substr($content, 1);
					$parts = $this->atomize_tag($content);
					$this->_validate_setting($parts['token']);
				}
				// Placeholder
				else {
					$parts = $this->atomize_tag($content);
					// we don't check the token, 'cuz who knows
				}
				break;
			
			// Snippet
			default:
				$parts = $this->atomize_tag($content);
				$this->_validate_snippet($parts['token']);
		}
		
	}

	//------------------------------------------------------------------------------
	/**
	 * Check for "void" tags, e.g. [[~]], [[$]], [[++]], [[+]], [[%]]
	 * We have to do this check up front, not later on.  This is important because we 
	 * blank them out tags as we check them from the most deeply nested tag outwards.  
	 * After this point, those "void" tags will be considered valid.
	 *
	 * For example:  [[~[[*id]]]] is a valid tag. First pass reduces it to [[~    ]]
	 * (i.e. it becomes void).  That's ok when WE do it.  It's not ok if it arrives
	 * on scene as [[~]].
	 *
	 * @param string $content
	 * @param integer (optional) page id for reporting
	 * @return true on error
	 */
	public function check_voids($content, $id=0) {

		// Enter just the contents of the tag, omit [[ and ]]
		$void_tags = array('','~','+','++','$','%');
		$error_flag = false;
		
		foreach ($void_tags as $v) {
			$v = preg_quote($v);
			if (preg_match('/\[\[!?'.$v.'\s*\]\]/', $content, $matches)) {
				$t = $matches[0];
				$this->errors[] = sprintf($this->modx->lexicon('void_tag'), $t). " Resource $id";
				$this->simple_errors[] = sprintf($this->modx->lexicon('void_tag'), $t);
				$error_flag = true;
			}
		}
		return $error_flag;
	}

	//------------------------------------------------------------------------------
	/**
	 * The parser can go into an endless loop if the content field contains the 
	 * [[*content]] placeholder.  So we check for these nasty guys explicitly. 
	 * 
	 * @param	object	$resource	 the current page object
	 */
	public function check_wormholes(&$resource) {
		
		$id = $resource->get('id');

		$tags = array_keys($resource->toArray());
		
		foreach ($tags as $t) {
			$content = $resource->get($t);
			$tag = '[[*'.$t;
			$uncached_tag = '[[!*'.$t;
			if (substr_count($content, $tag) || substr_count($content, $uncached_tag) ) {
				$this->errors[] = sprintf($this->modx->lexicon('wormholes'), $t, "[[*$t]]"). " Resource $id";
				$this->simple_errors[] = sprintf($this->modx->lexicon('wormholes'), $t, "[[*$t]]");
			}
		}
	}
	

	//------------------------------------------------------------------------------
	/**
	 * Get formatted HTML error message for the user.
	 */
	public function get_error_msg() {
	
		$error_msg = '<ul>';		
		foreach ($this->simple_errors as $e) {
			$error_msg .= "<li>$e</li> 
			";
		}
		$error_msg .= '</ul>';
		
		// See http://bugs.modx.com/issues/6294
		return strip_tags($error_msg);
	}

	/**
	 * We gotta get a map of all opening and closing tags ('[[' and ']]');
	 *
	 * Goal is something like this:
	 * Array(
	 *	[12]	=> 'tag_open',
	 *  [20]	=> 'tag_close',
	 * )
	 *
	 * @param string
	 * @return array
	 */
	public function get_tag_map($str) {
		$map = array();
		
		$strlen = strlen ($str);
		
		
		// Find starting tags;
		$offset = 0;
		while($offset !== false) {
			$offset = strpos($str,'[[',$offset);
			if ($offset === false) {
				break;
			}
			$map[$offset] = 'tag_open';
			$offset++; // advance the pointer
		}

		// Find closing tags;
		$offset = 0;
		while($offset !== false) {
			$offset = strpos($str,']]',$offset);
			if ($offset === false) {
				break;
			}
			$map[$offset] = 'tag_close';
			$offset++; // advance the pointer
		}
		
		ksort($map);
		return $map;
	}

}
/*EOF*/
