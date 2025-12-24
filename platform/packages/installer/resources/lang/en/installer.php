<?php

return [
    'title' => 'Zarachtech Installation',
    'next' => 'Next Step',
    'forms' => [
        'errorTitle' => 'The following errors occurred:',
    ],
    'welcome' => [
        'title' => 'Welcome',
        'message' => 'Before getting started, we need some information on the database. You will need to know the following items before proceeding.',
        'language' => 'Language',
        'next' => 'Let\'s go',
    ],
    'requirements' => [
        'title' => 'Server Requirements',
        'php_version_required' => 'PHP version :version required',
    ],
    'permissions' => [
        'next' => 'Configure Environment',
    ],
    'environment' => [
        'wizard' => [
            'title' => 'Environment Settings',
            'form' => [
                'name_required' => 'An environment name is required.',
                'app_name_label' => 'Site title',
                'app_url_label' => 'URL',
                'db_connection_label' => 'Database Connection',
                'db_connection_label_mysql' => 'MySQL',
                'db_host_label' => 'Database host',
                'db_port_label' => 'Database port',
                'db_name_label' => 'Database name',
                'db_name_placeholder' => 'Database name',
                'db_username_label' => 'Database username',
                'db_username_placeholder' => 'Database username',
                'db_password_label' => 'Database password',
                'db_password_placeholder' => 'Database password',
                'buttons' => [
                    'install' => 'Install',
                ],
                'db_host_helper' => 'If you use Laravel Sail, just change DB_HOST to DB_HOST=mysql. On some hosting DB_HOST can be localhost instead of 127.0.0.1',
                'db_connections' => [
                    'mysql' => 'MySQL',
                    'sqlite' => 'SQLite',
                    'pgsql' => 'PostgreSQL',
                ],
            ],
        ],
        'success' => 'Your .env file settings have been saved.',
        'errors' => 'Unable to save the .env file, Please create it manually.',
    ],
    'theme' => [
        'title' => 'Choose theme',
        'message' => 'Choose a theme to personalize the appearance of your website. This selection will also import sample data tailored to the chosen theme.',
    ],
    'theme_preset' => [
        'title' => 'Choose theme preset',
        'message' => 'Choose a theme preset to personalize the appearance of your website. This selection will also import sample data tailored to the chosen theme.',
    ],
    'createAccount' => [
        'title' => 'Create account',
        'form' => [
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'username' => 'Username',
            'email' => 'Email',
            'password' => 'Password',
            'password_confirmation' => 'Password confirmation',
            'create' => 'Create',
        ],
    ],
    'final' => [
        'pageTitle' => 'Installation Finished',
        'title' => 'Done',
        'message' => 'Application has been successfully installed.',
        'exit' => 'Go to admin dashboard',
        'website_details' => 'Website Details',
        'database_details' => 'Database Details',
        'website_name' => 'Website Name',
        'website_url' => 'Website URL',
        'database_connection' => 'Connection Type',
        'database_host' => 'Host & Port',
        'database_name' => 'Database Name',
        'database_username' => 'Username',
        'database_password' => 'Password',
        'note' => 'Note',
        'note_message' => 'Please save these details for future reference. You can find them in your .env file.',
        'not_set' => 'Not set',
    ],
    'install_step_title' => 'Installation - Step :step: :title',
];
