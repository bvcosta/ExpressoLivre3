/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Tinebase.container');

/**
 * Tinebase container class
 * 
 * @todo move container model here
 * 
 * @namespace   Tine.Tinebase.container
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Tinebase.container = {
    /**
     * type for personal containers
     * 
     * @constant TYPE_PERSONAL
     * @type String
     */
    TYPE_PERSONAL: 'personal',
    
    /**
     * type for shared container
     * 
     * @constant TYPE_SHARED
     * @type String
     */
    TYPE_SHARED: 'shared',
    
    /**
     * @private
     * 
     * @property isLeafRegExp
     * @type RegExp
     */
    isLeafRegExp: /^\/personal\/[0-9a-z_\-]+\/|^\/shared\/[a-f0-9]+/i,
    
    /**
     * @private
     * 
     * @property isPersonalNodeRegExp
     * @type RegExp
     */
    isPersonalNodeRegExp: /^\/personal\/([0-9a-z_\-]+)$/i,
    
    /**
     * returns the path of the 'my ...' node
     * 
     * @return {String}
     */
    getMyNodePath: function() {
        return '/personal/' + Tine.Tinebase.registry.get('currentAccount').accountId;
    },
    
    /**
     * returns the file node path of the 'my ...' node
     * 
     * @return {String}
     */
    getMyFileNodePath: function() {
        return '/personal/' + Tine.Tinebase.registry.get('currentAccount').accountLoginName;
    },
    
    /**
     * returns true if given path represents a (single) container
     * 
     * NOTE: if path could only be undefined when server send container without path.
     *       This happens only in server json classes which only could return containers
     * 
     * @static
     * @param {String} path
     * @return {Boolean}
     */
    pathIsContainer: function(path) {
        return !Ext.isString(path) || !!path.match(Tine.Tinebase.container.isLeafRegExp);
    },
    
    /**
     * returns true if given path represents a personal container of current user
     * 
     * @param {String} path
     * @return {Boolean}
     */
    pathIsMyPersonalContainer: function(path) {
        var regExp = new RegExp('^' + Tine.Tinebase.container.getMyNodePath() + '\/([0-9a-z_\-]+)$');
        var matches = path.match(regExp);
        
        return !!matches;
    },
    
    /**
     * returns owner id if given path represents an personal _node_
     * 
     * @static
     * @param {String} path
     * @return {String/Boolean}
     */
    pathIsPersonalNode: function(path) {
        if (! Ext.isString(path)) {
            return false;
        }
        var matches = path.match(Tine.Tinebase.container.isPersonalNodeRegExp);
        
        return matches ? matches[1] : false;
    },
    
    /**
     * gets translated container name by path
     * 
     * @static
     * @param {String} path
     * @param {String} containerName
     * @param {String} containersName
     * @return {String}
     */
    path2name: function(path, containerName, containersName) {
        switch (path) {
            case '/':           return String.format(_('All {0}'), _(containersName));
            case '/shared':     return String.format(_('Shared {0}'), '');
            case '/personal':   return String.format(_('Other Users {0}'), '');
        }
        
        if (path === Tine.Tinebase.container.getMyNodePath()
                || path === Tine.Tinebase.container.getMyFileNodePath()) {
            return String.format(_('My {0}'),_(containersName));
        }
        
        return path;
    },
    
    /**
     * returns container type (personal/shared) of given path
     * 
     * @static
     * @param {String} path
     * @return {String}
     */
    path2type: function(path) {
        var pathParts = Ext.isArray(path) ? path : path.split('/');
        
        return pathParts[1]; 
    }
    
};
