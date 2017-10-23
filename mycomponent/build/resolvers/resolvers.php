<?php
return [
    'file' => [
        [
            'source' => $this->config['source_core'],
            'target' => "return MODX_CORE_PATH.'components/';",
        ],
        [
            'source' => $this->config['source_assets'],
            'target' => "return MODX_ASSETS_PATH.'components/';",
        ]
    ],
    'php' => [
        [
            'source' => $this->config['resolvers'].'resolver.tables.php'
        ]
    ]
];