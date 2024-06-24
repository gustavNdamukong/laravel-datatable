<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Datatable view Defaults
    |--------------------------------------------------------------------------
    |
    */
    /*
    There are 4 fields that you should have separate entries per model for: 
    The config settings here must be prefixed with the model names in all lowercase
        'date_field'
        'heading'
        'orderBy'
        'panelId'
    */
    
    'blog_date_field' => 'blog_created',
    'contactformmessage_date_field' => 'date',
    'user_date_field' => 'created_at',

    'blog_heading' => 'Blog comments',
    'contactformmessage_heading' => 'Contact form messages',
    'user_heading' => 'User data',

    'blog_orderBy' => 'blog_created',
    'contactformmessage_orderBy' => 'date',
    'user_orderBy' => 'created_at',
    
    'blog_panelId' => '',
    'contactformmessage_panelId' => '',
    'user_panelId' => '',


    'recordsPerpage' => 5,
    'sortable' => true,
    'sortOrder' => 'ASC',
    'clickableRecs' => true,
];