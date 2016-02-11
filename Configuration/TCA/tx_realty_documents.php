<?php

$extPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('realty');
$extRelPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('realty');
$extIconRelPath = $extRelPath . 'icons/';
$ll = 'LLL:EXT:realty/locallang_db.xml';

return [
    'ctrl' => [
        'title' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_documents',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'default_sortby' => 'ORDER BY object',
        'delete' => 'deleted',
        'hideTable' => TRUE,
        'enablecolumns' => [],
        'iconfile' => $extIconRelPath . 'icon_tx_realty_documents.gif'
    ],
    'interface' => [
        'showRecordFieldList' => 'sys_language_uid,l18n_parent,l18n_diffsource,title,filename'
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
                'foreign_table' => 'tx_realty_documents',
                'foreign_table_where' => 'AND tx_realty_documents.pid=###CURRENT_PID### AND tx_realty_documents.sys_language_uid IN (-1, 0)',
                'showIconTable' => false
            ]
        ],
        'l18n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'object' => [
            'exclude' => 0,
            'label' => '',
            'config' => [
                'type' => 'select',
                'foreign_table' => 'tx_realty_objects',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
        'title' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_documents.title',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'required',
            ],
        ],
        'filename' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:realty/locallang_db.xml:tx_realty_documents.filename',
            'config' => [
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => 'pdf',
                'max_size' => 2000,
                'uploadfolder' => 'uploads/tx_realty',
                'show_thumbs' => 0,
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, title, filename'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];
