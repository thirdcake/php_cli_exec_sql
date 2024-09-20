<?php


// .envファイルを$env連想配列にする
$envfile = @fopen(
        __DIR__.'/../.env',
        'r'
        ) or exit( '.envファイルが親フォルダにありません' );

// 各行実行
$env = [];
while ( false !== $line = fgets($envfile, 1024) ) {
        if ( false !== $eqpos = strpos( $line, '=' ) ) {
                if ( false !== $hapos = strpos( $line, '#') ) {
                        $line = substr($line, 0, $hapos);
                }
                $line = trim( $line );
                $key = substr( $line, 0, $eqpos );
                $key = trim($key);
                $val = substr( $line, $eqpos + 1 );
                $val = trim($val);
                $val = preg_replace('/^\"/', '', $val);
                $val = preg_replace('/\"$/', '', $val);
                $env[$key] = $val;
        }
}

// dbname などのチェック
if( 
    ! array_key_exists('dbname', $env) 
    || ! array_key_exists('host', $env) 
    || ! array_key_exists('username', $env) 
    || ! array_key_exists('password', $env) 
) {
    exit('.envファイルに dbname, host, username, password が含まれていません。');
}

// sqlファイルを開く
if ( count($argv) < 2 ) {
    exit( 'ファイル名を渡してください' );
}
$sqlfilename = $argv[1];
if ( 1 !== preg_match( '/^insert/', $sqlfilename ) ) {
    exit( '.sqlファイル名は、insertから始めてください' );
}
$sqlstring = @file_get_contents(
    __DIR__.'/../sql/'.$sqlfilename
) or exit( '.sqlファイルがsqlフォルダにありません' );

// 実行文のチェック
echo $sqlstring;
echo 'このSQLを実行します。よろしいですか？[Y/n]'.PHP_EOL;
$exec_bool = trim(fgets(STDIN));
if ( $exec_bool !== 'Y' ) {
    exit('処理を中断しました。'.PHP_EOL);
}

// dbに接続。参考：
// https://qiita.com/te2ji/items/56c194b6cb9898d10f7f
try {
    $pdo = new PDO(
        'mysql:dbname='.$env['dbname'].';host='.$env['host'].';charset=utf8mb4',
        $env['username'],
        $env['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
        ]
    );
    $pdo->exec($sqlstring);
} catch (PDOException $e) {
    exit($e->getMessage()); 
}

echo 'insertされました。'.PHP_EOL;
