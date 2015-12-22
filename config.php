<?php

/**
 * AmForms Default Configuration.
 */
return array(
    'general' => array(
        array(
            'name' => 'Plugin name',
            'value' => '',
        ),
        array(
            'name' => 'Quiet errors',
            'value' => false,
        ),
        array(
            'name' => 'Fields per set',
            'value' => 8,
        ),
        array(
            'name' => 'Use Mandrill for email',
            'value' => false,
        ),
        array(
            'name' => 'Bcc email address',
            'value' => '',
        ),
    ),
    'export' => array(
        array(
            'name' => 'Delimiter',
            'value' => ';',
        ),
        array(
            'name' => 'Export rows per set',
            'value' => 50,
        ),
        array(
            'name' => 'Ignore Matrix field and block names',
            'value' => false,
        ),
    ),
    'antispam' => array(
        array(
            'name' => 'Honeypot enabled',
            'value' => true,
        ),
        array(
            'name' => 'Honeypot name',
            'value' => 'yourssince1615',
        ),
        array(
            'name' => 'Time check enabled',
            'value' => true,
        ),
        array(
            'name' => 'Minimum time in seconds',
            'value' => 3,
        ),
        array(
            'name' => 'Duplicate check enabled',
            'value' => true,
        ),
        array(
            'name' => 'Origin check enabled',
            'value' => true,
        ),
    ),
    'recaptcha' => array(
        array(
            'name' => 'Google reCAPTCHA enabled',
            'value' => false,
        ),
        array(
            'name' => 'Site key',
            'value' => '',
        ),
        array(
            'name' => 'Secret key',
            'value' => '',
        ),
    ),
    'templates' => array(
        array(
            'name' => 'Form template',
            'value' => '',
        ),
        array(
            'name' => 'Tab template',
            'value' => '',
        ),
        array(
            'name' => 'Field template',
            'value' => '',
        ),
        array(
            'name' => 'Notification template',
            'value' => '',
        ),
    ),
    'fields' => array(
        array(
            'name' => 'Name',
            'type' => 'PlainText',
        ),
        array(
            'name' => 'First name',
            'type' => 'PlainText',
        ),
        array(
            'name' => 'Last name',
            'type' => 'PlainText',
        ),
        array(
            'name' => 'Website',
            'type' => 'PlainText',
        ),
        array(
            'name' => 'Email address',
            'type' => 'AmForms_Email',
        ),
        array(
            'name' => 'Telephone number',
            'type' => 'PlainText',
        ),
        array(
            'name' => 'Mobile number',
            'type' => 'PlainText',
        ),
        array(
            'name' => 'Comment',
            'type' => 'PlainText',
            'settings' => array(
                'multiline'   => 1,
                'initialRows' => 4,
            ),
        ),
        array(
            'name' => 'Reaction',
            'type' => 'PlainText',
            'settings' => array(
                'multiline'   => 1,
                'initialRows' => 4,
            ),
        ),
        array(
            'name' => 'Image',
            'type' => 'Assets',
            'translatable' => false,
            'settings' => array(
                'restrictFiles' => 1,
                'allowedKinds' => array('image'),
                'sources' => array('folder:1'),
                'singleUploadLocationSource' => '1',
                'defaultUploadLocationSource' => '1',
                'limit' => 1,
            ),
        ),
        array(
            'name' => 'File',
            'type' => 'Assets',
            'translatable' => false,
            'settings' => array(
                'sources' => array('folder:1'),
                'singleUploadLocationSource' => '1',
                'defaultUploadLocationSource' => '1',
                'limit' => 1,
            ),
        ),
    ),
);
