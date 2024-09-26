<?php

require_once __DIR__.'/../helper/functions.php';

// env.jsonファイルを$env連想配列にする
$env = createEnvArray(__DIR__.'/../env.json');
if (array_key_exists('errorMessage', $env)) {
    exit($env['errorMessage'].PHP_EOL);
}

// sqlファイルを開く
$sql = createSqlString($argv, 'select');
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
    $stmt = $pdo->prepare( $sql['string'] );
    $stmt->execute();
    $result = $stmt->fetchAll();
} catch (PDOException $e) {
    exit($e->getMessage()); 
}

// 結果を表示
var_dump($result);
