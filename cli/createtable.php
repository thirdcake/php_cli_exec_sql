<?php


// .envファイルを$env連想配列にする
$envstring = file_get_contents(__DIR__.'/../.env');
if ( $envstring === false ) {
    exit('.envファイルが親フォルダに存在しません');
}
$envarray = explode(PHP_EOL, $envstring);
$env = [];
foreach($envarray as $line) {
    if ( false !== $pos = strpos( $line, '#' ) ) {
        $line = substr( $line, 0, $pos );
    }
    $line = trim($line);
    if ( false !== $pos = strpos( $line, '=' ) ) {
        $key = substr($line, 0, $pos);
        $val = substr($line, $pos + 1);
        $key = trim($key);
        $val = trim($val);
        $val = preg_replace('/^\"/', '', $val);
        $val = preg_replace('/\"$/', '', $val);
        $env[$key] = $val;
    }
}

// dsnなどのチェック
if( ! array_key_exists('dsn', $env) || ! array_key_exists('username', $env) || ! array_key_exists('password', $env) ) {
    exit('.envファイルに dsn, username, password が含まれていません。');
}

// sqlファイルを開く
if (count($argv) > 1) {
    $sqlfilename = $argv[1];
    $sqlstring = file_get_contents( __DIR__.'/../sql/'.$sqlfilename );
    if ($sqlstring === false) {
        exit('.sqlファイルがsqlフォルダに存在しません');
    }
} else {
    exit('ファイル名を渡してください');
}

// dbに接続。参考：
// https://qiita.com/te2ji/items/56c194b6cb9898d10f7f
try {
    $pdo = new PDO(
        $env['dsn'],
        $env['username'],
        $env['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
        ]
    );
    $stmt = $pdo->prepare($sqlstring);
//    $stmt->bindValue(':age', (int)$age, PDO::PARAM_INT);
//    $stmt->bindValue(':gender', $gender);
    $stmt->execute();
    $result = $stmt->fetchAll();
} catch (PDOException $e) {
    exit($e->getMessage()); 
}
//var_dump($result);
var_dump($result);
