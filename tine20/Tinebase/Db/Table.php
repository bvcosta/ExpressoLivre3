<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Db
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * the class provides functions to handle applications
 *
 * @package     Tinebase
 * @subpackage  Db
 */
class Tinebase_Db_Table extends Zend_Db_Table_Abstract
{

    public function __construct($config = array())
    {
            /**
             * tries to read metadata table generated during installation from setup.xml
             * to avoid metadata table generated during installation from setup.xml
             */
            $ds = DIRECTORY_SEPARATOR;
            $path = realpath(__DIR__ . "{$ds}..{$ds}..{$ds}data{$ds}metadata{$ds}table");
            $filename = $path . $ds . $config['name'] . '.php';
            $filename = str_replace(SQL_TABLE_PREFIX, '', $filename);

            if (file_exists($filename))	$this->_metadata = require $filename;
            else
            {
               if($_REQUEST['method'])
               {
                   $app = $_REQUEST['method'];
               }
               else
               {
                   $app = 'Tinebase';
               }

               $xml =  Setup_Controller::getInstance()->getSetupXml($app);
               if (isset($xml->tables))
               {
                    foreach ($xml->tables[0] as $tableXML)
                    {
                        Setup_Controller::getInstance()->saveTableMetadata($tableXML);
                    }
               }

               $this->_metadata = require $filename;
            }

            parent::__construct($config);
    }

    /**
     * wrapper around Zend_Db_Table_Abstract::fetchAll
     *
     * @param strin|array $_where OPTIONAL
     * @param string $_order OPTIONAL
     * @param string $_dir OPTIONAL
     * @param int $_count OPTIONAL
     * @param int $_offset OPTIONAL
     * @throws Tinebase_Exception_InvalidArgument if $_dir is not ASC or DESC
     * @return the row results per the Zend_Db_Adapter fetch mode.
     */
    public function fetchAll($_where = NULL, $_order = NULL, $_dir = 'ASC', $_count = NULL, $_offset = NULL)
    {
        if($_dir != 'ASC' && $_dir != 'DESC') {
            throw new Tinebase_Exception_InvalidArgument('$_dir can be only ASC or DESC');
        }

        $order = NULL;
        if($_order !== NULL) {
            $order = $_order . ' ' . $_dir;
        }

        $rowSet = parent::fetchAll($_where, $order, $_count, $_offset);

        return $rowSet;
    }

    /**
     * get total count of rows
     *
     * @param string|array|Zend_Db_Select $_where
     */
    public function getTotalCount($_where)
    {
        $tableInfo = $this->info();

        if (is_array($_where) || is_string($_where)) {
            $select = $this->getAdapter()->select();
            foreach((array)$_where as $where) {
                $select->where($where);
            }
        } elseif ($_where instanceof Zend_Db_Select ) {
            $select = $_where;
        }

        $select->from($tableInfo['name'], array('count' => 'COUNT(*)'));

        $stmt = $this->getAdapter()->query($select);
        $result = $stmt->fetch(Zend_Db::FETCH_ASSOC);

        return $result['count'];
    }

}
