<?php
use Amp\Success;
function exceptions_error_handler($severity, $message, $filename, $lineno) {
	throw new ErrorException($message, 0, $severity, $filename, $lineno);
}
set_error_handler('exceptions_error_handler');
require_once 'vendor/autoload.php';
use Amp\ByteStream;
class StringStream implements \Amp\ByteStream\WritableStream {
		public $d;
		public $closed;
		public function write(string $data): void {
			$this->d .= $data;
		}
		public function end(): void {
		}											

		public function get() {
			return $this->d;
		}
		public function isWritable(): bool {
			return true;
		}
		public function close(): void {
			$this->closed = true;
		}
		public function isClosed(): bool {
			return $this->closed;
		}
		public function onClose(\Closure $onClose): void {
		}
}

function resize($image, $w, $h) {
	$w = (int) $w;
	$h = (int) $h;
	$oldw = imagesx($image);
	$oldh = imagesy($image);
	$temp = imagecreatetruecolor($w, $h);
	imagecopyresampled($temp, $image, 0, 0, 0, 0, $w, $h, $oldw, $oldh);
	return $temp;
}
try {
	include 'mp.php';
	$user = MP::getUser();
	if(!$user) {
		http_response_code(401);
		die;
	}
	$MP = MP::getMadelineAPI($user);
	$msg = null;
	$di = null;
	$cid = $_GET['c'];
	$info = $MP->getInfo($cid);
	$info = $info['User'] ?? $info['Chat'] ?? $info;
	try {
		$di = $MP->getPropicInfo($info);
	} catch (Exception) {
		header('Content-Type: image/png');
		if((int) $cid < 0) {
			echo file_get_contents('gr.png');
		} else {
			echo file_get_contents('us.png');
		}
		die;
	}
	$p = $_GET['p'] ?? '';
	if(strpos($p, 'r') === 0) {
		header('Cache-Control: private, max-age=86400');
		$p = substr($p, 1);
		$stream = new StringStream();
		$MP->downloadToStream($di, $stream);
		$img = imagecreatefromstring($stream->get());
		$png = false;
		if(strpos($p, 'c') === 0) {
			$png = true;
			$p = substr($p, 1);
			$w = imagesx($img);
			$h = imagesy($img);
			
			$mask = imagecreatetruecolor($w, $h);
			$c = imagecolorallocate($mask, 255, 0, 0);
			imagecolortransparent($mask, $c);
			imagefilledellipse($mask, $w/2, $h/2, $w, $h, $c);
			
			$r = imagecolorallocate($mask, 0, 0, 0);
			imagecopymerge($img, $mask, 0, 0, 0, 0, $w, $h, 100);
			imagecolortransparent($img, $r);
			imagefill($img, 0, 0, $r);
		}
		if(strpos($p, 'p') === 0) {
			$png = true;
			$p = substr($p, 1);
		}
		if($p != 'orig') {
			$s = (int)$p;
			$img = resize($img, $s, $s);
		}
		if($png) {
			header('Content-Type: image/png');
			imagepng($img);
			imagedestroy($img);
			die;
		}
		header('Content-Type: image/jpeg');
		imagejpeg($img, null, 80);
		imagedestroy($img);
	} else {
		$MP->downloadToBrowser($di);
	}
} catch (Exception $e) {
	http_response_code(500);
}
