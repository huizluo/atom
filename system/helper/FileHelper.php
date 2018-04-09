<?php
namespace system\helper;

/**
 * FileHelper Class
 *
 * 关于文件一些操作
 *
 * @package		AtomCode
 * @subpackage	helper
 * @author		Eachcan<eachcan@gmail.com>
 * @license		http://digglink.com/doc/license.html
 * @link		http://digglink.com
 * @since		Version 1.0
 * @filesource
 */
class FileHelper {

	/**
	 * 删除文件或目录
	 * 
	 * @param string $path
	 * @param boolean $del_dir
	 * @param integer $level
	 * @return boolean 是否删除成功
	 */
	public static function deleteFiles($path, $del_dir = FALSE, $level = 0) {
		// Trim the trailing slash
		$path = rtrim($path, '\\/');
		
		if (!$current_dir = @opendir($path)) {
			return FALSE;
		}
		
		while (FALSE !== ($filename = @readdir($current_dir))) {
			if ($filename != "." and $filename != "..") {
				if (is_dir($path . DIRECTORY_SEPARATOR . $filename)) {
					// modify: delete all file(s) and folder(s) including hidden one(s).
					self::deleteFiles($path . DIRECTORY_SEPARATOR . $filename, $del_dir, $level + 1);
				} else {
					unlink($path . DIRECTORY_SEPARATOR . $filename);
				}
			}
		}
		
		@closedir($current_dir);
		
		if ($del_dir == TRUE and $level > 0) {
			return @rmdir($path);
		}
		
		return TRUE;
	}

	/**
	 * 取得所有文件名
	 *
	 * 取得目标目录下的所有文件名，包括子目录，但是不包括隐藏文件夹和隐藏文件
	 *
	 * @access	public
	 * @param	string	要查找的目录路径
	 * @param	bool	文件名是否包含路径
	 * @param	bool	递归调用所需要的，不要在调用时传入任何值
	 * @return	array
	 */
	public static function getFilenames($source_dir, $include_path = FALSE, $_recursion = FALSE) {
		static $_filedata = array();
		$fp = @opendir($source_dir);
		
		if ($fp) {
			// reset the array and make sure $source_dir has a trailing slash on the initial call
			if ($_recursion === FALSE) {
				$_filedata = array();
				$source_dir = rtrim(realpath($source_dir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
			}
			
			while (FALSE !== ($file = readdir($fp))) {
				if (@is_dir($source_dir . $file) && strncmp($file, '.', 1) !== 0) {
					self::getFilenames($source_dir . $file . DIRECTORY_SEPARATOR, $include_path, TRUE);
				} elseif (strncmp($file, '.', 1) !== 0) {
					$_filedata[] = ($include_path == TRUE) ? $source_dir . $file : $file;
				}
			}
			return $_filedata;
		} else {
			return FALSE;
		}
	}

	/**
	 * Get File Info
	 *
	 * Given a file and path, returns the name, path, size, date modified
	 * Second parameter allows you to explicitly declare what information you want returned
	 * Options are: name, server_path, size, date, readable, writable, executable, fileperms
	 * Returns FALSE if the file cannot be found.
	 *
	 * @access	public
	 * @param	string	path to file
	 * @param	mixed	array or comma separated string of information returned
	 * @return	array
	 */
	public static function getile_info($file, $returned_values = array('name', 'server_path', 'size', 'date')) {
		
		if (!file_exists($file)) {
			return FALSE;
		}
		
		if (is_string($returned_values)) {
			$returned_values = explode(',', $returned_values);
		}
		
		foreach ($returned_values as $key) {
			switch ($key) {
				case 'name':
					$fileinfo['name'] = substr(strrchr($file, DIRECTORY_SEPARATOR), 1);
					break;
				case 'server_path':
					$fileinfo['server_path'] = $file;
					break;
				case 'size':
					$fileinfo['size'] = filesize($file);
					break;
				case 'date':
					$fileinfo['date'] = filemtime($file);
					break;
				case 'readable':
					$fileinfo['readable'] = is_readable($file);
					break;
				case 'writable':
					$fileinfo['writable'] = is_really_writable($file);
					break;
				case 'executable':
					$fileinfo['executable'] = is_executable($file);
					break;
				case 'fileperms':
					$fileinfo['fileperms'] = fileperms($file);
					break;
			}
		}
		
		return $fileinfo;
	}

	/**
	 * Get Mime by Extension
	 *
	 * Translates a file extension into a mime type based on config/mimes.php.
	 * Returns FALSE if it can't determine the type, or open the mime config file
	 *
	 * Note: this is NOT an accurate way of determining file mime types, and is here strictly as a convenience
	 * It should NOT be trusted, and should certainly NOT be used for security
	 *
	 * @access	public
	 * @param	string	path to file
	 * @return	mixed
	 */
	public static function getMimeType($file) {
		$extension = strtolower(substr(strrchr($file, '.'), 1));
		
		$mimes = load_config('mimes', array());
		
		if (array_key_exists($extension, $mimes)) {
			if (is_array($mimes[$extension])) {
				// Multiple mime types, just give the first one
				return current($mimes[$extension]);
			} else {
				return $mimes[$extension];
			}
		} else {
			return FALSE;
		}
	}

	/**
	 * Symbolic Permissions
	 *
	 * Takes a numeric value representing a file's permissions and returns
	 * standard symbolic notation representing that value
	 *
	 * @access	public
	 * @param	int
	 * @return	string
	 */
	public static function symbolicPermissions($perms) {
		if (($perms & 0xC000) == 0xC000) {
			$symbolic = 's'; // Socket
		} elseif (($perms & 0xA000) == 0xA000) {
			$symbolic = 'l'; // Symbolic Link
		} elseif (($perms & 0x8000) == 0x8000) {
			$symbolic = '-'; // Regular
		} elseif (($perms & 0x6000) == 0x6000) {
			$symbolic = 'b'; // Block special
		} elseif (($perms & 0x4000) == 0x4000) {
			$symbolic = 'd'; // Directory
		} elseif (($perms & 0x2000) == 0x2000) {
			$symbolic = 'c'; // Character special
		} elseif (($perms & 0x1000) == 0x1000) {
			$symbolic = 'p'; // FIFO pipe
		} else {
			$symbolic = 'u'; // Unknown
		}
		
		// Owner
		$symbolic .= (($perms & 0x0100) ? 'r' : '-');
		$symbolic .= (($perms & 0x0080) ? 'w' : '-');
		$symbolic .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x') : (($perms & 0x0800) ? 'S' : '-'));
		
		// Group
		$symbolic .= (($perms & 0x0020) ? 'r' : '-');
		$symbolic .= (($perms & 0x0010) ? 'w' : '-');
		$symbolic .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x') : (($perms & 0x0400) ? 'S' : '-'));
		
		// World
		$symbolic .= (($perms & 0x0004) ? 'r' : '-');
		$symbolic .= (($perms & 0x0002) ? 'w' : '-');
		$symbolic .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x') : (($perms & 0x0200) ? 'T' : '-'));
		
		return $symbolic;
	}

	/**
	 * Octal Permissions
	 *
	 * Takes a numeric value representing a file's permissions and returns
	 * a three character string representing the file's octal permissions
	 *
	 * @access	public
	 * @param	int
	 * @return	string
	 */
	function octalPermissions($perms) {
		return substr(sprintf('%o', $perms), -3);
	}
}