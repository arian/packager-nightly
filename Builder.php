<?php

ini_set('display_errors', 1);

// header('Content-Type: text/plain');

include dirname(__FILE__) . '/packager/packager.php';


// Ugh, safe mode anybody?
function curl_redir_exec($ch, $url){
	static $curl_loops = 0;
	static $curl_max_loops = 20;

	if ($curl_loops++ >= $curl_max_loops){
		$curl_loops = 0;
		return false;
	}

	curl_setopt($ch, CURLOPT_HEADER, true);

	$data = curl_exec($ch);
	list($header, $data) = preg_split('/(\n(\s)*\n)/', $data);
	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	if ($http_code == 301 || $http_code == 302) {
		$matches = array();
		preg_match('/Location:(.*?)\n/', $header . PHP_EOL, $matches);
		$url = trim($matches[1]);

		if (!empty($url)){
			curl_setopt($ch, CURLOPT_URL, $url);
			return curl_redir_exec($ch, $url);
		}
	} else {
		$curl_loops = 0;

		$new_ch = curl_init();
		curl_setopt($new_ch, CURLOPT_URL, $url);
		curl_setopt($new_ch, CURLOPT_RETURNTRANSFER, 1);
		// https
		curl_setopt($new_ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($new_ch, CURLOPT_SSL_VERIFYHOST, 2);
		return curl_exec($new_ch);
	}
}



function download($url, $file, $timeout = 10){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	// https
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	// redirect
	// curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	$file_contents = curl_redir_exec($ch, $url);
	curl_close($ch);
	file_put_contents($file, $file_contents);
}


function deleteDirectory($dir){
	if (!file_exists($dir)) return true;
	if (!is_dir($dir) || is_link($dir)) return unlink($dir);
	foreach (scandir($dir) as $item) {
		if ($item == '.' || $item == '..') continue;
		if (!deleteDirectory($dir . '/' . $item)) {
			chmod($dir . '/' . $item, 0777);
			if (!deleteDirectory($dir . '/' . $item)) return false;
		}
	}
	return rmdir($dir);
}


class Builder {

	public static $downloadTimeout = 10;
	public static $tmp = 'tmp';

	protected $repo;
	protected $url;
	protected $filename;

	public function __construct($repo, $branch = 'master'){
		$this->repo = $repo;
		$this->url = 'https://github.com/' . $repo . '/zipball/' . $branch;
		$this->filename = self::$tmp . '/' . md5($this->url) . '.zip';
	}

	protected function download(){
		download($this->url, $this->filename, self::$downloadTimeout);
	}

	protected function extract(){
		$zip = new ZipArchive;
		if ($zip->open($this->filename) === true){
			$zip->extractTo(self::$tmp);
			$zip->close();
		} else {
			throw new Exception('Could not extract the zip file');
		}
	}

	protected function findDirectory(){
		$dirs = glob(self::$tmp . '/' . str_replace('/', '-', $this->repo) . '*');
		if (!empty($dirs[0])) return $dirs[0];
		throw new Exception('Could not find the extracted directory');
	}

	protected function package($dir){
		$pkg = new Packager($dir);
		$files = $pkg->get_all_files();
		return $pkg->build($files);
	}

	protected function cleanup($dir, $file){
		deleteDirectory($dir);
		unlink($file);
	}

	public function build($file){
		$this->download();
		$this->extract();
		$dir = $this->findDirectory();
		$output = $this->package($dir);
		file_put_contents($file, $output);
		$this->cleanup($dir, $this->filename);
	}

}
