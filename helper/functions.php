<?php

function createEnvArray(string $jsonPathString): array {
    // pathからjsonを取得
    if (! file_exists($jsonPathString)) {
        return ['errorMessage'=>'env.jsonファイルがありません'];
    }
    $jsonString = file_get_contents($jsonPathString);

    $env = json_decode($jsonString, true);    
    // json_decodeは、失敗したときnullを返す
    if(is_null($env)) {
        return ['errorMessage' => json_last_error_msg()];
    }

    // 必要なキーが附則しているとき
    $checkKeys = ['dbname', 'host', 'username', 'password'];
    $hasAllKeys = array_reduce($checkKeys, function ($c, $i) use ($env) {
        $hasKey = array_key_exists($i, $env);
        return $c && $hasKey;
    }, true);
    if (! $hasAllKeys) {
        return ['errorMessage' => 'env.jsonに必要な情報が不足しています'];
    }

    // 成功したらdsn情報を追加して$envを返す
    $env['dsn'] = 'mysql:dbname='.$env['dbname'].';host='.$env['host'].';charset=utf8mb4';
    return $env;
}

function createSqlString(array $args, string $sqlCommand): array {
    // $args(=$argv)に、参照先のsqlファイルが無かったら
    if (! isset($args[1])) {
        return ['errorMessage' => 'sqlファイル名を渡してください'];
    }
    $sqlfilename = $args[1];
    // 参照先のsqlが、sqlコマンドと一致しないとき
    if ( 1 !== preg_match( '/^'.$sqlCommand.'/', $sqlfilename ) ) {
        return ['errorMessage' => 'sqlファイル名は、'.$sqlCommand.'から始めてください' ];
    }

    // ファイルが存在しないとき
    if (! file_exists(__DIR__.'/../sql/'.$sqlfilename)) {
        return ['errorMessage'=>'sqlファイルがありません'];
    }
    $sqlString = file_get_contents(__DIR__.'/../sql/'.$sqlfilename);
    
    return ['string' => $sqlString];
}
