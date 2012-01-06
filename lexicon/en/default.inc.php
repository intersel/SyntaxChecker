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
 * Quip; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * @package syntaxchecker
 */
/**
 * SyntaxChecker English language file
 *
 * @package syntaxchecker
 * @subpackage lexicon
 */
 
$_lang['plugin_name'] = 'Syntax Checker'; 
$_lang['error_prefix'] = 'The following errors were detected:'; 
$_lang['mismatched_brackets'] = 'Mismatched square brackets in %s.';
$_lang['odd_backticks'] = 'Odd number of backticks in %s.';
$_lang['wormholes'] = 'Loop detected: the %1$s field cannot contain the %2$s tag.';
$_lang['missing_ampersands'] = '%1$s\'s %2$s parameter is missing an ampersand.';
$_lang['missing_questionmark'] = '%s tag is missing question mark to separate it from its parameters.';
$_lang['missing_parameter'] = '%s is missing a parameter name.'; // <-- rare.
$_lang['missing_equals'] = '%1$s is missing an equals sign to separate the %2$s parameter from its value.';
$_lang['chunk_does_not_exist'] = 'Chunk does not exist: %s.';
$_lang['snippet_does_not_exist'] = 'Snippet does not exist: %s.';
$_lang['output_filter_does_not_exist'] = 'Output Filter does not exist: %s.';
$_lang['output_filter_args_not_quoted'] = "The '%s' Output Filter's '%s' argument must be quoted with backticks (`).";
$_lang['resource_does_not_exist'] = 'Resource does not exist: %s.';
$_lang['lexicon_does_not_exist'] = 'Lexicon Entry does not exist: %s.';
$_lang['setting_does_not_exist'] = 'Setting does not exist: %s.';
$_lang['propset_does_not_exist'] = 'Property Set does not exist: %s.';
$_lang['props_must_be_quoted'] = '%1$s\'s %2$s property must be quoted with backticks (`), NOT apostrophes!';
$_lang['docvar_does_not_exist'] = 'Document Variable or TV does not exist: %s.';
$_lang['docvar_not_associated'] = 'TV not associated with this template: %s.';
$_lang['void_tag'] = 'Void tags not allowed: %s.';


