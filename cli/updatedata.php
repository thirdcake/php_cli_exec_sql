<?php

require_once __DIR__.'/../helper/functions.php';

// env.jsonファイルを$env連想配列にする
$env = createEnvArray(__DIR__.'/../env.json');
if (array_key_exists('errorMessage', $env)) {
    exit($env['errorMessage'].PHP_EOL);
}

// sqlファイルを開く
$sql = createSqlString($argv, 'update');
if (array_key_exists('errorMessage', $sql)) {
    exit($sql['errorMessage'].PHP_EOL);
}

// 実行文のチェック
echo $sql['string'];
echo 'このSQLを実行します。よろしいですか？[Y/n]'.PHP_EOL;
$not_exec = (trim(fgets(STDIN)) !== 'Y');
if ($not_exec) {
    exit('処理を中断しました。'.PHP_EOL);
}

// 処理後のcheck
$checkarray = [];
$sqlarray = explode( PHP_EOL, $sql['string'] );

foreach( $sqlarray as $line ) {
    $line = trim( $line );
    if ( str_contains($line, 'UPDATE') ) {
        $tmparray = explode(' ', $line);
        foreach($tmparray as $k => $v) {
            if ($v === 'UPDATE') {
                $checkarray['table_name'] = $tmparray[$k + 1];
            }
        }
    }
    if ( 1 === preg_match( '/^WHERE/', $line ) ) {
        $line = str_replace(['WHERE ', ';'], '', $line);
        $checkarray['where_condition'] = $line;
    }
}
var_dump($checkarray);
$checkselect_bool = false;
if ( array_key_exists('table_name', $checkarray) && array_key_exists('where_condition', $checkarray) ) {
    $checkselect = <<<EOL
    SELECT * FROM {$checkarray['table_name']}
    WHERE {$checkarray['where_condition']}
    ;
    EOL;
    echo $checkselect.PHP_EOL;
    echo 'UPDATE の処理後、この SELECT 文を実行しますか？[Y/n]'.PHP_EOL;
    $checkselect_bool = (trim(fgets(STDIN)) === 'Y');    
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
    $pdo->exec($sql['string']);

    if ( $checkselect_bool ) {
        $stmt = $pdo->prepare( $checkselect );
        $stmt->execute();
        $result = $stmt->fetchAll();
    }

} catch (PDOException $e) {
    exit($e->getMessage()); 
}

if ( $checkselect_bool ) {
    echo '処理が完了しました。結果を再確認してください。'.PHP_EOL;
    var_dump($result);
    echo PHP_EOL;
} else {
    echo '処理が完了しました'.PHP_EOL;
}