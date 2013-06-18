<?php
/**
 * SyntaxChecker
 *
 * Copyright 2011 by Everett Griffiths <everett@fireproofsocks.com>
 *
 * This is a plugin for MODX 2.x.  It checks the tag syntax of MODX
 * documents, chunks, templates, and template variables as they are saved
 * and alerts the user on errors.
 *
 * System Events:
 *	OnBeforeDocFormSave
 *	OnBeforeChunkFormSave
 *	OnBeforeTVFormSave
 *	OnBeforeTempFormSave
 * 
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

if (empty($modx)) {
	return; 
}

// TODO: Check cached version?  If the field hasn't changed, no reason to validate it.

require_once MODX_CORE_PATH . 'components/syntaxchecker/syntaxchecker.class.php';

$SyntaxChecker = new SyntaxChecker($modx);

$modx->lexicon->load('syntaxchecker:default');
$modx->log(xPDO::LOG_LEVEL_DEBUG, '[SyntaxChecker] Event:'.$modx->event->name);

// Phase 1: Basic Integrity
switch ($modx->event->name) {

	// Documents
	case 'OnBeforeDocFormSave':		
		$SyntaxChecker->check_integrity('Resource','content', $resource);
		$SyntaxChecker->check_integrity('Resource','pagetitle', $resource);
		$SyntaxChecker->check_integrity('Resource','longtitle', $resource);
		$SyntaxChecker->check_integrity('Resource','description', $resource);
		$SyntaxChecker->check_integrity('Resource','introtext', $resource);
		$SyntaxChecker->check_integrity('Resource','alias', $resource);
		$SyntaxChecker->check_wormholes($resource);
		break;
		
	// Chunks
	case 'OnBeforeChunkFormSave':	
		$SyntaxChecker->check_integrity('Chunk','snippet', $chunk);
		break;
	
	// TVs: TODO
/*
	case 'OnBeforeTVFormSave':
		$SyntaxChecker->check_integrity('TV','properties', $tv);
		$SyntaxChecker->check_integrity('TV','input_properties', $tv);
		break;
*/
	
	// Templates
	case 'OnBeforeTempFormSave':
		$SyntaxChecker->check_integrity('Template','content', $template);
		$SyntaxChecker->check_integrity('Template','description', $template);
		$SyntaxChecker->check_integrity('Template','templatename', $template);
		break;
}

// Log Phase 1 Errors
if (!empty($SyntaxChecker->errors)) {
    if ($modx->getOption('syntaxchecker.prevent_save','',1)) {
	   $modx->event->output($SyntaxChecker->get_error_msg()); // to modal window
    }
	return '[SyntaxChecker] '. implode("\n", $SyntaxChecker->errors); 	// to the logs
}


// Phase 2: Now that we have basic integrity, we can check inside the tags
switch ($modx->event->name) {

	// Documents
	case 'OnBeforeDocFormSave':
		$SyntaxChecker->check_syntax('Resource','content', $resource);
		$SyntaxChecker->check_syntax('Resource','pagetitle', $resource);
		$SyntaxChecker->check_syntax('Resource','longtitle', $resource);
		$SyntaxChecker->check_syntax('Resource','description', $resource);
		$SyntaxChecker->check_syntax('Resource','introtext', $resource);
		$SyntaxChecker->check_syntax('Resource','alias', $resource);
		break;
		
	// Chunks
	case 'OnBeforeChunkFormSave':
		$SyntaxChecker->check_syntax('Chunk','snippet', $chunk);
		break;
	
	// TVs
/*
	case 'OnBeforeTVFormSave':
		$SyntaxChecker->check_syntax('TV','properties', $tv);
		$SyntaxChecker->check_syntax('TV','input_properties', $tv);	
		break;
*/
	
	// Templates
	case 'OnBeforeTempFormSave':
		$SyntaxChecker->check_syntax('Template','content', $template);
		$SyntaxChecker->check_syntax('Template','description', $template);
		$SyntaxChecker->check_syntax('Template','templatename', $template);
		break;
}


// Log Phase 2 Errors
if (!empty($SyntaxChecker->errors)) {
    if ($modx->getOption('syntaxchecker.prevent_save','',1)) {
	   $modx->event->output($SyntaxChecker->get_error_msg()); // to modal window
	}
	return '[SyntaxChecker] '. implode("\n", $SyntaxChecker->errors); // to the logs
}

return; // null = no problems


/*EOF*/