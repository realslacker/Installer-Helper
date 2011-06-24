<?php
/**
 * The Installer Helper extends Record to provide an easy interface for adding
 * initial tables and creating permissions.
 *
 * This helper provides the following capabilities:
 * - table creation and removal with verification
 * - permission creation, removal, renaming, assignment and revokation
 * - role creation, removal and renaming
 * - driver identification
 * - error generation specific to installations
 *
 * Note: This file must be placed in /wolf/helpers and can be called like
 *       any helper. All methods are static and should be called directly.
 *
 * @package Helpers
 * @subpackage Installer
 *
 * @author Shannon Brooks <shannon@brooksworks.com>
 * @copyright Shannon Brooks, 2011
 * @license http://www.gnu.org/licenses/gpl.html GPLv3 license
 */



class Installer extends Record {

	public static $LAST_ERROR = null;
	
	//	createTable
	//	usage:		Installer::createTable(<table name>,<create statement>);
	//	returns:	true on success
	//				false on failure, sets self::$LAST_ERROR on failure
	final public static function createTable($table,$query) {
		
		if ( self::$__CONN__->query("SELECT COUNT(*) FROM ".$table ) !== false ) return self::__ERROR( __('Table exists.') );
		
		self::$__CONN__->exec($query);
		
		if ( self::$__CONN__->query("SELECT COUNT(*) FROM ".$table ) === false ) return self::__ERROR( __('Table not created!') );

		return true;
		
	}
	
	//	updateTable
	//	usage:		Installer::updateTable(<table name>,<update statement>);
	//	returns:	true
	//				false if table doesn't exist, sets self::$LAST_ERROR on failure
	final public static function updateTable($table,$query) {
	
		if ( self::$__CONN__->query("SELECT COUNT(*) FROM ".$table ) === false ) return self::__ERROR( __('Table doesn\'t exist!') );
		
		self::$__CONN__->exec($query);
		
		return true;
		
	}
	
	//	removeTable
	//	usage:		Installer::removeTable(<table name>);
	//	returns:	true on success
	//				false on failure, sets self::$LAST_ERROR on failure
	final public static function removeTable($table) {
	
		self::$__CONN__->exec('DROP TABLE IF EXISTS '.$table);
		
		if ( self::$__CONN__->query("SELECT COUNT(*) FROM ".$table ) !== false ) return self::__ERROR( __('Table not removed!') );
		
		return true;
	}
	
	//	createPermissions
	//	usage:		Installer::createPermissions(<comma separated list of permissions>);
	//	returns:	true on success, skips existing permissions
	//				false on first failure, sets self::$LAST_ERROR on failure
	final public static function createPermissions($permissions) {

		foreach (explode(',', $permissions) as $permission) {
			
			$permission = trim($permission);
			
			if ( ! Permission::findByName($permission) )
				if ( ! Record::insert('Permission',array('name'=>$permission)) ) return self::__ERROR( __('Could not create Permission') . ': ' . $permission );
		
		}

		return true;
	}
	
	//	removePermissions
	//	usage:		Installer::removePermissions(<comma separated list of permissions>);
	//	returns:	true on success, skips non-existent permissions
	//				false on first failure, sets self::$LAST_ERROR on failure
	final public static function removePermissions($permissions) {
		
		foreach (explode(',', $permissions) as $permission) {
			
			$permission = trim($permission);
			
			if ( $p = Permission::findByName($permission)) {
			
				Record::deleteWhere('RolePermission','permission_id='.$p->id);
				if ( Record::countFrom('RolePermission','permission_id='.$p->id) > 0 ) return self::__ERROR( __('Could not remove Role->Permission link') . ': ' . $permission );
				
				Record::deleteWhere('Permission','id='.$p->id);
				if ( Record::countFrom('Permission','id='.$p->id) > 0 ) return  self::__ERROR( __('Could not remove Permission') . ': ' . $permission );
			
			}
		
		}
		
		return true;
	}
	
	//	renamePermission
	//	usage:		Installer::renamePermission(<permission name>,<new name>);
	//	returns:	true on success
	//				false on failure, sets self::$LAST_ERROR on failure
	final public static function renamePermission($permission,$name) {
		
		$permission = trim($permission);
		$name = trim($name);
	
		if ( ! $p = Permission::findByName($permission) ) return self::__ERROR( __('Permission does not exist!') );
		
		$p->name = $name;
		$p->save();
		
		if ( ! Permission::findByName($name) ) return self::__ERROR( __('Could not rename Permission!') );
		
		return true;
	}
	
	//	createRoles
	//	usage:		Installer::createRoles(<comma separated list of roles>);
	//	returns:	true on success, skips existing roles
	//				false on first failue, sets self::$LAST_ERROR on failure
	final public static function createRoles($roles) {
	
		foreach (explode(',',$roles) as $role) {
			
			$role = trim($role);
			
			if ( ! Role::findByName($role) )
				if ( ! Record::insert('Role',array('name'=>$role)) ) return self::__ERROR( __('Could not add Role') . ': ' . $role );
		
		}
		
		return true;
	}
	
	//	removeRoles
	//	usage:		Installer::removeRoles(<comma separated list of roles>);
	//	returns:	true on success, skips non-existant roles
	//				false on first failue, sets self::$LAST_ERROR on failure
	final public static function removeRoles($roles) {
		
		foreach (explode(',',$roles) as $role) {
			
			$role = trim($role);
			
			if ( $r = Role::findByName($role)) {
			
				Record::deleteWhere('UserRole','role_id='.$r->id);
				if ( Record::countFrom('UserRole','role_id='.$r->id) > 0 ) return self::__ERROR( __('Could not remove User->Role link') . ': ' . $role );
				
				Record::deleteWhere('RolePermission','role_id='.$r->id);
				if ( Record::countFrom('RolePermission','role_id='.$r->id) > 0 ) return self::__ERROR( __('Could not remove Role->Permission link') . ': ' . $role );
				
				Record::deleteWhere('Role','id='.$r->id);
				if ( Record::countFrom('Role','id='.$r->id) > 0 ) return  self::__ERROR( __('Could not remove Role') . ': ' . $role );
			
			}
		
		}
		
		return true;
	}
	
	//	renameRole
	//	usage:		Installer::renameRole(<role name>,<new name>);
	//	returns:	true on success
	//				false on failure, sets self::$LAST_ERROR on failure
	final public static function renameRole($role,$name) {
		
		$role = trim($role);
		$name = trim($name);
	
		if ( ! $r = Role::findByName($role) ) return self::__ERROR( __('Role does not exist!') );
		
		$r->name = $name;
		$r->save();
		
		if ( ! Role::findByName($name) ) return self::__ERROR( __('Could not rename Role!') );
		
		return true;
	}

	//	assignPermissions
	//	usage:		Installer::assignPermissions(<role name>,<comma separated list of permissions>);
	//	returns:	true on success
	//				false on first failure, sets self::$LAST_ERROR on failure
	final public static function assignPermissions($role,$permissions) {
	
		$role = trim($role);
	
		if ( ! $r = Role::findByName($role)) return self::__ERROR( __('Role doesn\'t exist!') );
		
		foreach (explode(',', $permissions) as $permission) {
			
			$permission = trim($permission);
			
			if ( ! $r->hasPermission($permission) ) {
			
				if ( ! $p = Permission::findByName($permission) ) return self::__ERROR( __('Permission doesn\'t exist!') );
			
				if ( ! Record::insert('RolePermission',array('role_id'=>$r->id,'permission_id'=>$p->id)) ) return self::__ERROR( __('Could not assign Permission to Role!') );
			
			}
		
		}

		return true;
	}
	
	//	revokePermissions
	//	usage:		Installer::revokePermissions(<role name>,<comma separated list of permissions>);
	//	returns:	true on success
	//				false on first failure, sets self::$LAST_ERROR on failure
	final public static function revokePermissions($role,$permissions) {
	
		$role = trim($role);
	
		if ( ! $r = Role::findByName($role)) return self::__ERROR( __('Role doesn\'t exist!') );
		
		foreach (explode(',', $permissions) as $permission) {
			
			$permission = trim($permission);
			
			if ( $r->hasPermission($permission) ) {
			
				if ( ! $p = Permission::findByName($permission) ) return self::__ERROR( __('Permission doesn\'t exist!') );
				
				Record::deleteWhere('RolePermission','role_id='.$r->id.', permission_id='.$p->id);
				if ( Record::countFrom('RolePermission','role_id='.$r->id.', permission_id='.$p->id) > 0 ) return  self::__ERROR( __('Could not remove Role->Permission link!') );
			
			}
		
		}

		return true;
	}
	
	//	getDriver
	//	description:
	//	returns SQL driver in use
	//	usage:		Installer::getDriver();
	//	returns:	driver as string
	final public static function getDriver() {
		return strtolower(self::$__CONN__->getAttribute(Record::ATTR_DRIVER_NAME));
	}
	
	//	failInstall
	//	description:
	//	When plugin is not installed successfully you can call this function to set an
	//	error message and remove the plugin from the installed table. If failing without
	//	calling this function plugin will show as installed even when it failed to install
	//	usage:		Installer::failInstall(<plugin name>[,<message>]);
	final public static function failInstall($plugin,$message=false) {
		if ( $message === false ) $message = self::$LAST_ERROR;
		unset(Plugin::$plugins[$plugin]);
		Plugin::save();
		Flash::set('error',$message);
		exit;
	}
	
	//	failUninstall
	//	description:
	//	When plugin is not uninstalled successfully you can call this function to set an
	//	error message.
	//	usage:		Installer::faileUninstall(<plugin name>[,<message>]);
	final public static function failUninstall($plugin,$message=false) {
		if ( $message === false ) $message = self::$LAST_ERROR;
		Flash::set('error',$message);
		redirect(get_url('setting'));
	}
	
	//	__ERROR
	//	description:
	//	Internal function to set self::$LAST_ERROR and return false
	final private static function __ERROR($message) {
		self::$LAST_ERROR = $message;
		return false;
	}

}

// EOF