<?php

$tables = [
    'document_ranks' => [
        'rankId' => 'VARCHAR(256) NOT NULL PRIMARY KEY',
        'title' => 'VARCHAR(256) NOT NULL'
    ]
];
$indexes = [
    'document_ranks' => [
        'rankId'
    ]
];
$data = [
    'document_ranks' => [
        [
            'rankId' => '1hfwEcHSXULim285JJLsz5h8qMVPxYfn',
            'title' => 'Default'
        ]
    ]
];

return [
    'tables' => $tables,
    'indexes' => $indexes,
    'data' => $data
];

?>