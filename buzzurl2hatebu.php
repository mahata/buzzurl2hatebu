<?php

require_once('HTTP/Request.php');

main();

function main() {
	$conf = parse_ini_file('pass.ini');
	$bookmark_arr = json_decode(file_get_contents('http://api.buzzurl.jp/api/articles/v1/json/' . $conf['buzzurl_id']));
	foreach($bookmark_arr as $bookmark) {
		$tags = '';
		foreach ($bookmark->keywords as $keyword) {
			$tags .= "[{$keyword}]";
		}
		hatena_bookmark($conf['hatena_id'], $conf['hatena_pass'], $bookmark->url, $tags . $bookmark->comment);
	}
}

function hatena_bookmark($user, $pass, $url, $body) {
	echo "bookimarking ... {$url}\n";
	$created = date('Y-m-d\TH:i:s\Z');
	$nonce = pack('H*', sha1(md5(time())));
	$pass_digest = base64_encode(pack('H*', sha1($nonce.$created.$pass)));
	$wsse = 'UsernameToken Username="' . $user . '", PasswordDigest="' . $pass_digest . '", Created="' . $created . '", Nonce="' . base64_encode($nonce) . '"';
	$rawdata = '<entry xmlns="http://purl.org/atom/ns#"><title>dummy</title><link rel="related" type="text/html" href="' . $url . '" /><summary type="text/plain">' . $body . '</summary></entry>';
	$url = 'http://b.hatena.ne.jp/atom/post';
	$req = new HTTP_Request();
	$req->addHeader('Accept','application/x.atom+xml, application/xml, text/xml, */*');
	$req->addHeader('Authorization', 'WSSE profile="UsernameToken"');
	$req->addHeader('X-WSSE', $wsse);
	$req->addHeader('Content-Type', 'application/x.atom+xml');
	$req->setMethod(HTTP_REQUEST_METHOD_POST);
	$req->setURL($url);
	$req->addRawPostData($rawdata);
	$res = $req->sendRequest();
}

?>
