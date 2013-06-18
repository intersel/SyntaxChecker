<?php
/**
 * To run these tests you must:
 *
 * 1. Download the PHP Unit framework.  If you have installed it via PEAR or Composer, you
 *  can run the tests just by issuing the phpunit command, e.g. phpunit this_file.php
 *  If you have not been able to install PHP Unit locally using Composer or PEAR, then download
 *  the PHAR file, and run the test like this:
 *      php phpunit.phar path/to/this/script.php
 *
 * 2. The paths to MODX assume that this file lives inside assets/components/SyntaxChecker/_build/tests/
 *
 */
 
define('MODX_API_MODE', true);
require_once dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/index.php';
require_once dirname(dirname(dirname(__FILE__))) . '/syntaxchecker.class.php';

class ParserTest extends PHPUnit_Framework_TestCase
{
    public $modx;
    public $Syn;
    
    function __construct() {        
        $this->modx= new modX();
        if (!$this->modx) {
        	print 'MODX not initialized correctly.';
        	exit;
        }
        $this->modx->initialize('mgr');
        $this->Syn = new SyntaxChecker($this->modx);
    }

    /**
     * Test to ensure that there are a matching number of [[square brackets]]
     * and backticks (`)
     */
    public function testMatchingBrackets() {
    
        $resource = $this->modx->newObject('modResource');

        $resource->set('content','[[one]] [[two]] [[three]] [[[[~4]]? &for=`sure`]]');
        $this->Syn->check_integrity('Resource','content',$resource);
        $this->assertEmpty($this->Syn->errors);
        $this->Syn->errors = array(); // reset

        $resource->set('content','[[$one]] [[!two]] [[~3]]');
        $this->Syn->check_integrity('Resource','content',$resource);
        $this->assertEmpty($this->Syn->errors);
        $this->Syn->errors = array(); // reset

        
        $resource->set('content','[[whoops');
        $this->Syn->check_integrity('Resource','content',$resource);
        $this->assertNotEmpty($this->Syn->errors);
        $this->Syn->errors = array(); // reset
        
        $resource->set('content','[ [whoops]]');
        $this->Syn->check_integrity('Resource','content',$resource);
        $this->assertNotEmpty($this->Syn->errors);
        $this->Syn->errors = array(); // reset
        
        $resource->set('content','whoops`');
        $this->Syn->check_integrity('Resource','content',$resource);
        $this->assertNotEmpty($this->Syn->errors);
        $this->Syn->errors = array(); // reset
    }
    
    /**
     * A wormhole exists when you have a [[*content]] tag inside your content.
     */
    public function testWormholes() {
        $resource = $this->modx->newObject('modResource');

        $resource->set('content','[[*pagetitle]] all good');
        $this->Syn->check_wormholes($resource);
        $this->assertEmpty($this->Syn->errors);
        $this->Syn->errors = array(); // reset

        $resource->set('content','[[*content]] wormhole');
        $this->Syn->check_wormholes($resource);
        $this->assertNotEmpty($this->Syn->errors);
        $this->Syn->errors = array(); // reset        
    }

    /**
     * "void" tags include [[]], [[~]], [[$]], [[++]], [[+]], [[%]]
     * Part of the process req's identifying them in a string.
     */
    public function testVoids() {
        $result = $this->Syn->check_voids('[[pagetitle]] no voids.'); // true on error
        $this->assertFalse($result);
        $this->Syn->errors = array(); // reset

        $result = $this->Syn->check_voids('[[! ]] voids!'); // true on error
        $this->assertTrue($result);
        $this->Syn->errors = array(); // reset

        $result = $this->Syn->check_voids('[[$]] voids!'); // true on error
        $this->assertTrue($result);
        $this->Syn->errors = array(); // reset

        $result = $this->Syn->check_voids('[[++]] voids!'); // true on error
        $this->assertTrue($result);
        $this->Syn->errors = array(); // reset

        $result = $this->Syn->check_voids('[[+]] voids!'); // true on error
        $this->assertTrue($result);
        $this->Syn->errors = array(); // reset

        $result = $this->Syn->check_voids('[[%]] voids!'); // true on error
        $this->assertTrue($result);
        $this->Syn->errors = array(); // reset


    }
    
    /**
     * Critical to the entire process is the ability to map tag positions on a valid string
     */
    public function testTagMap() {
        $map = $this->Syn->get_tag_map('[[one]] [[two]]');
        $this->assertTrue($map[0] == 'tag_open');
        $this->assertTrue($map[13] == 'tag_close');
    }
    
    /**
     * Test the ability to split a tag into its component parts
     */
    public function testAtomizeTag() {
        $comps = $this->Syn->atomize_tag('MySnippet@MyPropSet:myfilter? &x=`1` &y=`2`');
        $this->assertEquals($comps['token'], 'MySnippet');
        $this->assertEquals($comps['propset'], 'MyPropSet');
        $this->assertEquals($comps['filters'], 'myfilter');
        $this->assertEquals($comps['params'], '&x=`1` &y=`2`');
    }


    /**
     * Make sure system settings exist
     */
    public function testSystemSettings() {
        $resource = $this->modx->newObject('modResource');
        $this->Syn->check_tag_contents('++site_url', 'content', $resource);
        $this->assertEmpty($this->Syn->errors);

        $this->Syn->check_tag_contents('++does_not_exist', 'content', $resource);
        $this->assertNotEmpty($this->Syn->errors);
    }

    /**
     * Make sure system settings exist
     */
    public function testLinks() {
        $resource = $this->modx->newObject('modResource');
        
        $this->Syn->check_tag_contents('~1', 'content', $resource);
        $this->assertEmpty($this->Syn->errors);
        $this->Syn->errors = array(); // reset        
        
        $this->Syn->check_tag_contents('~1? &x=`123`', 'content', $resource);
        $this->assertEmpty($this->Syn->errors);
        $this->Syn->errors = array(); // reset        
        
        $this->Syn->check_tag_contents('~99090900', 'content', $resource);
        $this->assertNotEmpty($this->Syn->errors);
        $this->Syn->errors = array(); // reset                
    }


    /**
     * Ensure that any ref'd Snippets actually exist (this depends on the database in use)
     */
    public function testSnippets() {
        $resource = $this->modx->newObject('modResource');
        
        // Check an existing Snippet
        $snippets = $this->modx->getCollection('modSnippet');
        if ($snippets) {
            foreach ($snippets as $s) {
                $name = $s->get('name');
                $this->Syn->check_tag_contents($name, 'content', $resource);
                $this->assertEmpty($this->Syn->errors);
                $this->Syn->errors = array(); // reset        

                // Missing Question Mark
                $this->Syn->check_tag_contents("$name &p=`123`", 'content', $resource);
                $this->assertNotEmpty($this->Syn->errors);
                $this->Syn->errors = array(); // reset

                // Improper quoting
                $this->Syn->check_tag_contents("$name &p='dog'", 'content', $resource);
                $this->assertNotEmpty($this->Syn->errors);
                $this->Syn->errors = array(); // reset
                
                // Missing equals
                $this->Syn->check_tag_contents("$name? &p`123`", 'content', $resource);
                $this->assertNotEmpty($this->Syn->errors);
                $this->Syn->errors = array(); // reset
                
                // Output filter does not exist
                $this->Syn->check_tag_contents("$name:asdfasdf &p=`123`", 'content', $resource);
                $this->assertNotEmpty($this->Syn->errors);
                $this->Syn->errors = array(); // reset                
                
            }
        }
        
        // Check a non-existant snippet
        $this->Syn->check_tag_contents('snip'.md5(time()), 'content', $resource);
        $this->assertNotEmpty($this->Syn->errors);
        $this->Syn->errors = array(); // reset        
    }   
    
    /**
     * We can't do too much testing here without a Resource + Template to cross-check
     */
    public function testDocVars() {
        $template = $this->modx->newObject('modTemplate');
        
        $this->Syn->check_tag_contents('*pagetitle', 'content', $template);
        $this->assertEmpty($this->Syn->errors);
        $this->Syn->errors = array(); // reset

        $this->Syn->check_tag_contents('*doesnotexist', 'content', $template);
        $this->assertNotEmpty($this->Syn->errors);
        $this->Syn->errors = array(); // reset
    }

    /**
     * Verify output filters
     */
    public function testFilters() {
        $template = $this->modx->newObject('modTemplate');
        
        $this->Syn->check_tag_contents('*pagetitle:ucase', 'content', $template);
        $this->assertEmpty($this->Syn->errors);
        $this->Syn->errors = array(); // reset

        $this->Syn->check_tag_contents('*pagetitle:doesnotexist', 'content', $template);
        $this->assertNotEmpty($this->Syn->errors);
        $this->Syn->errors = array(); // reset

        $this->Syn->check_tag_contents('*pub_date:date=`%Y`', 'content', $template);
        $this->assertEmpty($this->Syn->errors);
        $this->Syn->errors = array(); // reset

        $this->Syn->check_tag_contents('*pub_date:date=`%Y`:md5', 'content', $template);
        $this->assertEmpty($this->Syn->errors);
        $this->Syn->errors = array(); // reset
        
        $this->Syn->check_tag_contents('*pub_date:date=`%Y`:md5:wordwrap=`80`', 'content', $template);
        $this->assertEmpty($this->Syn->errors);
        $this->Syn->errors = array(); // reset
    }

    
    /**
     * Larger bits
     */
    public function testSyntax() {
        $resource = $this->modx->newObject('modResource');
        
        $content = '[[if?
    &subject=`[[*banner1-img]]`
    &operator=`isempty`
    &then=`[[if? &subject=`[[getResourceField? &id=`1` &field=`banner1-img` &processTV=`1`]]` &operator=`isempty` &then=`` &else=`<div class="banners"><a rel="nofollow" href="[[getResourceField? &id=`1` &field=`banner1-url` &processTV=`1`]]" target="_blank"><img class="banners_img" border="0" src="[[getResourceField? &id=`1` &field=`banner1-img` &processTV=`1`]]" alt="[[getResourceField? &id=`1` &field=`banner1-alt` &processTV=`1`]]"/></a></div>`]]`
    &else=`<div class="banners"><a rel="nofollow" href="[[*banner1-url]]" target="_blank"><img class="banners_img" border="0" src="[[*banner1-img]]" alt="[[*banner1-alt]]"/></a></div>`
  ]]';
        
        $content = '[[test? 
        &x=`<div class="banners">Something [[*pagetitle]]</div>`]]';
        $resource->set('content', $content);
        $this->Syn->check_syntax('Resource','content', $resource);
        $this->assertEmpty($this->Syn->errors);
        $this->Syn->errors = array(); // reset
    }  
    
}
?>