-----------------------
Plugin: SyntaxChecker
-----------------------
Version: 0.5
Released: June, 2013
Author: Everett Griffiths <everett@fireproofsocks.com>
License: GNU GPLv2 (or later at your option)

This plugin performs validation checks on your Resources, 
Templates, Chunks, and Template Variables and alerts the 
user to any errors that are found in the tag syntax.

Since MODX often fails silently when its parser can't 
make sense of errors, this offers a third-party check
on your content while you save it, immediately letting you
know if a problem was discovered.  This is crucial for 
MODX sites where clients might be butchering tags or when
you just need someone to double-check your tag soup.

If this plugin errantly prevents you from saving your content, 
then please accept my apologies! You can disable the pop-up 
modals by changing the syntaxchecker.prevent_save System Setting
to "No".  This will stop the windows from appearing, but 
error messages will still be logged (sorry, there isn't a 
middle ground due to limitations in the architecture). 

If you are finding lots of false positive error messages in your 
error logs about incorrect syntax, then please file a bug with 
relevant text at https://github.com/fireproofsocks/SyntaxChecker/issues

-------------------------
Installation Instructions
-------------------------
Install this via the MODX package manager.

Manual installation requires the following (e.g. if you have 
downloaded the files from Github):

1. Extract the zipped files to core/components/syntaxchecker 
(create the directory if it does not exist)

2. Paste the contents of the elements/plugins/plugin.syntaxchecker.php
into a new MODX plugin.

3. Check the following System Events:

 *	OnBeforeDocFormSave
 *	OnBeforeChunkFormSave
 *	OnBeforeTVFormSave
 *	OnBeforeTempFormSave

4. Save the plugin.


-----------------------
How to Use
-----------------------

After installation, the plugin will be active when saving MODX documents,
templates, and Chunks.  A modal window will pop up if there are problems 
detected with your tag syntax (see screenshots below).

If saving a page hangs, try clearing out your Log under Reports --> Error Log.
If the saving still hangs, then perhaps it's a bug (sorry!).  Change the 
syntaxchecker.prevent_save System Setting to "No", and the report a bug with
the problematic text that caused the error.  If this is filling up your logs
too much, then you can disable the plugin.

-----------------------
Screenshots
-----------------------

<img src="https://img.skitch.com/20111211-fsdu8521stq2msk11p9876ewt3.jpg"/>
<img src="https://img.skitch.com/20111217-k2h7purk8up8c3q13n3nf2x1wh.jpg"/>
<img src="https://img.skitch.com/20111217-kfdg6y214mffdjj9997ek6bpdk.jpg"/>

