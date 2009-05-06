<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Json
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    Tinebase_JsonTest::main();
}

/**
 * Test class for Tinebase_Group
 */
class Tinebase_JsonTest extends PHPUnit_Framework_TestCase
{
    /**
     * unit under test (UIT)
     * @var Tinebase_Frontend_Json
     */
    protected $_instance;

    /**
     * @var array test objects
     */
    protected $_objects = array();
    
    /**
     * Runs the test methods of this class.
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_JsonTest');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * set up tests
     *
     */
    public function setUp()
    {
        $this->_instance = new Tinebase_Frontend_Json();
        
        $this->_objects['record'] = array(
            'id'        => 1,
            'model'     => 'Addressbook_Model_Contact',
            'backend'    => 'Sql',
        );        

        $this->_objects['note'] = new Tinebase_Model_Note(array(
            'note_type_id'      => 1,
            'note'              => 'phpunit test note',    
            'record_model'      => $this->_objects['record']['model'],
            'record_backend'    => $this->_objects['record']['backend'],       
            'record_id'         => $this->_objects['record']['id']
        ));        
    }
    
    /**
     * try to add a note type
     *
     */
    public function testSearchNotes()
    {
        Tinebase_Notes::getInstance()->addNote($this->_objects['note']);

        $filter = array(array(
            'field' => 'query',
            'operator' => 'contains',
            'value' => 'phpunit test note'
        ));
        $paging = array();
        
        $notes = $this->_instance->searchNotes(Zend_Json::encode($filter), Zend_Json::encode($paging));
        
        $this->assertGreaterThan(0, $notes['totalcount']);        
        $this->assertEquals($this->_objects['note']->note, $notes['results'][0]['note']);
        
        // delete note
        Tinebase_Notes::getInstance()->deleteNotesOfRecord(
            $this->_objects['record']['model'], 
            $this->_objects['record']['backend'], 
            $this->_objects['record']['id']
        );        
    }
    
    /**
     * test getCountryList
     *
     */
    public function testGetCountryList()
    {
        $list = $this->_instance->getCountryList();
        $this->assertTrue(count($list['results']) > 200);
    }
    
    /**
     * test get translations
     *
     */
    public function testGetAvailableTranslations()
    {
        $list = $this->_instance->getAvailableTranslations();
        $this->assertTrue(count($list['results']) > 3);
    }
    
    /**
     * tests locale fallback
     */
    public function testSetLocaleFallback()
    {
        // de_LU -> de
        $this->_instance->setLocale('de_LU', FALSE, FALSE);
        $this->assertEquals('de', (string)Zend_Registry::get('locale'), 'Fallback to generic german did not succseed');
        
        $this->_instance->setLocale('zh', FALSE, FALSE);
        $this->assertEquals('zh_CN', (string)Zend_Registry::get('locale'), 'Fallback to simplified chinese did not succseed');
        
        $this->_instance->setLocale('foo_bar', FALSE, FALSE);
        $this->assertEquals('en', (string)Zend_Registry::get('locale'), 'Exception fallback to english did not succseed');
    }
    
    /**
     * test set locale and save it in db
     */
    public function testSetLocaleAsPreference()
    {
        $oldPreference = Tinebase_Core::getPreference()->{Tinebase_Preference::LOCALE};
        
        $locale = 'de';
        $result = $this->_instance->setLocale($locale, TRUE, FALSE);
        
        // get config setting from db
        $preference = Tinebase_Core::getPreference()->{Tinebase_Preference::LOCALE};
        $this->assertEquals($locale, $preference, "Didn't get right locale preference.");
        
        // restore old setting
        Tinebase_Core::getPreference()->{Tinebase_Preference::LOCALE} = $oldPreference;
    }

    /**
     * test set timezone and save it in db
     */
    public function testSetTimezoneAsPreference()
    {
        $oldPreference = Tinebase_Core::getPreference()->{Tinebase_Preference::TIMEZONE};
        
        $timezone = 'America/Vancouver';
        $result = $this->_instance->setTimezone($timezone, true);        
        
        // check json result
        $this->assertEquals($timezone, $result);
        
        // get config setting from db
        $preference = Tinebase_Core::getPreference()->{Tinebase_Preference::TIMEZONE};
        $this->assertEquals($timezone, $preference, "Didn't get right timezone preference.");
        
        // restore old settings
        Tinebase_Core::set('userTimeZone', $oldPreference);
        Tinebase_Core::getPreference()->{Tinebase_Preference::TIMEZONE} = $oldPreference;
    }
    
    /**
     * get notes types
     */
    public function testGetNotesTypes()
    {
        $noteTypes = $this->_instance->getNoteTypes();
        $this->assertTrue($noteTypes['totalcount'] >= 5);
    }
    
    /**
     * search preferences by application
     *
     */
    public function testSearchPreferences()
    {
        // search prefs
        $result = $this->_instance->searchPreferencesForApplication('Tinebase', Zend_Json::encode($this->_getPreferenceFilter()));
        
        // check results
        $this->assertTrue(isset($result['results']));
        $this->assertGreaterThan(2, $result['totalcount']);
        
        //check locale/timezones options
        foreach ($result['results'] as $pref) {
            switch($pref['name']) {
                case Tinebase_Preference::LOCALE:
                    $this->assertGreaterThan(10, count($pref['options']));
                    break;
                case Tinebase_Preference::TIMEZONE:
                    $this->assertGreaterThan(500, count($pref['options']));
                    break;
            }
            // check label and description
            $this->assertTrue(isset($pref['label']) && !empty($pref['label']));
            $this->assertTrue(isset($pref['description']) && !empty($pref['description']));
        }
    }

    /**
     * search preferences by application
     *
     */
    public function testSearchPreferencesWithOptions()
    {
        // add new default pref
        $pref = $this->_getPreferenceWithOptions();
        $pref = Tinebase_Core::getPreference()->create($pref);        
        
        // search prefs
        $results = $this->_instance->searchPreferencesForApplication('Tinebase', Zend_Json::encode($this->_getPreferenceFilter()));
        
        // check results
        $this->assertTrue(isset($results['results']));
        $this->assertGreaterThan(3, $results['totalcount']);
        
        foreach ($results['results'] as $result) {
            if ($result['name'] == 'testPref') {
                $this->assertEquals($pref->value, $result['value']);
                $this->assertTrue(is_array($result['options']));
                $this->assertEquals(2, count($result['options']));
            }
        }
        
        Tinebase_Core::getPreference()->delete($pref);
    }
    
    /**
     * search preferences of another user
     *
     * @todo add check for the case that searching user has no admin rights
     */
    public function testSearchPreferencesOfOtherUsers()
    {
        // add new default pref
        $pref = $this->_getPreferenceWithOptions();
        $pref->account_id = 2;
        $pref->account_type = Tinebase_Acl_Rights::ACCOUNT_TYPE_USER;
        $pref = Tinebase_Core::getPreference()->create($pref);        
        
        // search prefs
        $results = $this->_instance->searchPreferencesForApplication('Tinebase', Zend_Json::encode($this->_getPreferenceFilter(TRUE, FALSE, 2)));
        
        // check results
        $this->assertTrue(isset($results['results']));
        $this->assertEquals(1, $results['totalcount']);
        
        Tinebase_Core::getPreference()->delete($pref);
    }
    
    /**
     * save preferences for user
     *
     * @todo add test for saving of other users prefs and acl check
     */
    public function testSavePreferences()
    {
        $prefData = $this->_getUserPreferenceData();
        $this->_instance->savePreferences(Zend_Json::encode($prefData), false);

        // search saved prefs
        $results = $this->_instance->searchPreferencesForApplication('Tinebase', Zend_Json::encode($this->_getPreferenceFilter(FALSE)));
        
        //print_r($results);
        
        // check results
        $this->assertTrue(isset($results['results']));
        $this->assertGreaterThan(2, $results['totalcount']);
        
        $savedPrefData = array();
        foreach ($results['results'] as $result) {
            if ($result['name'] == 'timezone') {
                $savedPrefData['Tinebase'][$result['name']] = array('value' => $result['value']);
            
                $this->assertTrue(is_array($result['options']), 'options missing');
                $this->assertGreaterThan(500, count($result['options']));
                // cleanup
                Tinebase_Core::getPreference()->delete($result['id']);
            }            
        }
        $this->assertEquals($prefData, $savedPrefData);
    }

    /**
     * save admin prefs
     *
     */
    public function testSaveAdminPreferences()
    {
        // add new default pref
        $pref = $this->_getPreferenceWithOptions();
        $pref = Tinebase_Core::getPreference()->create($pref);        
        
        $prefData = array();
        $prefData['Tinebase'][$pref->getId()] = array('value' => 'test', 'type' => 'forced');
        $this->_instance->savePreferences(Zend_Json::encode($prefData), true);

        // search saved prefs
        $results = $this->_instance->searchPreferencesForApplication('Tinebase', Zend_Json::encode($this->_getPreferenceFilter(TRUE)));

        // check results
        $this->assertTrue(isset($results['results']));
        $this->assertEquals(1, $results['totalcount']);
        $this->assertEquals($prefData['Tinebase'][$pref->getId()]['value'], $results['results'][0]['value']);
        $this->assertEquals($prefData['Tinebase'][$pref->getId()]['type'], $results['results'][0]['type']);
                
        // cleanup
        Tinebase_Core::getPreference()->delete($pref);
    }
    
    /******************** protected helper funcs ************************/
    
    /**
     * get preference filter
     *
     * @param bool $_savedPrefs
     * @return array
     */
    protected function _getPreferenceFilter($_savedPrefs = FALSE, $_adminPrefs = FALSE, $_userId = NULL)
    {
        if ($_userId === NULL) {
            $_userId = Tinebase_Core::getUser()->getId();
        }
        
        $result = array(
            array(
                'field' => 'account', 
                'operator' => 'equals', 
                'value' => array(
                    'accountId'     => ($_adminPrefs) ? 0 : $_userId,
                    'accountType'   => ($_adminPrefs) 
                        ? Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE 
                        : Tinebase_Acl_Rights::ACCOUNT_TYPE_USER
                )
            )
        );

        if ($_savedPrefs) {
            $result[] = array(
                'field' => 'name', 
                'operator' => 'contains', 
                'value' => 'testPref'
            );
        }
        
        return $result;
    }

    /**
     * get preference data for testSavePreferences()
     *
     * @return array
     */
    protected function _getUserPreferenceData()
    {
        return array(
            'Tinebase' => array(
                'timezone' => array('value' => 'Europe/Amsterdam'),
            )
        );        
    }
    
    /**
     * get preference with options
     *
     * @return Tinebase_Model_Preference
     */
    protected function _getPreferenceWithOptions()
    {
        return new Tinebase_Model_Preference(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Tinebase')->getId(),
            'name'              => 'testPref',
            'value'             => 'value1',
            'account_id'        => 0,
            'account_type'      => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
            'type'              => Tinebase_Model_Preference::TYPE_DEFAULT,
            'options'           => '<?xml version="1.0" encoding="UTF-8"?>
                <options>
                    <option>
                        <label>option1</label>
                        <value>value1</value>
                    </option>
                    <option>
                        <label>option2</label>
                        <value>value2</value>
                    </option>
                </options>'
        ));
    }
}
