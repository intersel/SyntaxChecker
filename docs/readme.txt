-----------------------
Plugin: SyntaxChecker
-----------------------
Version: 0.2
First Released: December 12, 2012
Author: Everett Griffiths <everett@fireproofsocks.com>
License: GNU GPLv2 (or later at your option)

This plugin performs validation checks on your Resources, 
Templates, Chunks, and Template Variables and alerts the 
user to any errors that are found in the tag syntax.

This is still a BETA release! If this plugin errantly prevents 
you from saving your content, then please accept my 
apologies! Disable the plugin and report a bug with 
the tag that caused it to choke!  Thanks! 

This plugin is currently heavy-handed: if an error is 
detected, you will not be able to save your content until
the error is corrected.

Since MODX often fails silently when its parser can't 
make sense of errors, this offers a third-party check
on your content while you save it, immediately letting you
know if a problem was discovered.  This is crucial for 
MODX sites where clients might be butchering tags or when
you just need someone to double-check your tag soup.

-----------------------
How to Use
-----------------------

Simply install the Addon: the SyntaxChecker plugin will be activated.
Now when you save Page, Template, or Chunk, any syntax errors will be 
reported to you (see screenshots below)

If the saving hangs, try clearing out your Log under Reports --> Error Log.
If the saving still hangs, then perhaps it's a bug (sorry!).  Disable the 
plugin and report a bug with the problematic text that caused the error.

-----------------------
Screenshots
-----------------------

<img src="https://img.skitch.com/20111211-fsdu8521stq2msk11p9876ewt3.jpg"/>
<img src="https://img.skitch.com/20111217-k2h7purk8up8c3q13n3nf2x1wh.jpg"/>
<img src="https://img.skitch.com/20111217-kfdg6y214mffdjj9997ek6bpdk.jpg"/>


The following checks are performed when you save a Resource,
Template, Chunk, or TV:

1. Basic integrity check: equal number of '[[' and ']]'
2. No looping conditions (e.g. where you put [[*content]] inside your content).
3. Snippets exist?  e.g. [[Waaaayfinder]]
4. Chunks exist? e.g. [[$mispelled]]
5. Resources exist?  e.g. [[~123]]
6. Settings exist? e.g. [[++site_url]]
7. Document variables exist?  e.g. [[*kontent]]
8. Property sets exist?  e.g. [[Snippet@myPropSet]]
9. Output filters exist? e.g. [[Snippet:my_filter]]
10. Parameters are prefixed with an ampersand?  e.g. [[Snippet? whoops=`xyz`]]
11. Parameters delineated from the token via a question mark, e.g. [[Snippet &no=`question`]]
12. Parameter names and values are separated by an equals sign, e.g. [[Snippet &not`equal`]]
13. When saving a template, ensure that the TVs are assigned to the current template.
