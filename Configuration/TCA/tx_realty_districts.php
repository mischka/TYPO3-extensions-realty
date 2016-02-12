<?php

$extPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('realty');
$extRelPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('realty');
$extIconRelPath = $extRelPath . 'icons/';
$ll = 'LLL:EXT:realty/locallang_db.xml:';

return [
    'ctrl' => [
        'title' => $ll . 'tx_realty_districts',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'default_sortby' => 'ORDER BY title',
        'delete' => 'deleted',
        'iconfile' => $extIconRelPath.'icon_tx_realty_districts.gif'
    ],
    'interface' => [
        'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,title,city'
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'special' => 'languages',
                'items' => [
                    [
                        'LLL:EXT:lang/locallang_general.xlf:LGL.allLanguages',
                        -1,
                        'flags-multiple'
                    ],
                ],
                'default' => 0,
            ]
        ],
        'l18n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_realty_districts',
                'foreign_table_where' => 'AND tx_realty_districts.pid=###CURRENT_PID### AND tx_realty_districts.sys_language_uid IN (-1, 0)',
                'showIconTable' => false
            ]
        ],
        'l18n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'title' => [
            'exclude' => 0,
            'label' => $ll . 'tx_realty_districts.title',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'required',
            ],
        ],
        'city' => [
            'exclude' => 0,
            'label' => $ll . 'tx_realty_districts.city',
            'config' => [
                'type' => 'select',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_realty_cities',
                'foreign_table_where' => ' ORDER BY title ASC',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, title;;;;2-2-2, city'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];
