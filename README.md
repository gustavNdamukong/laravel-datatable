# Laravel datatable package

## Instantly generate beautiful tables from your Laravel models

![Example Users table](https://github.com/gustavNdamukong/laravel-datatable/blob/master/public/images/laravel-datatable.png?raw=true)

## How to use it

### Install it

```bash 
    composer require gustocoder/laravel-datatable
```

 After installation
 The configuration file will be created for you in: 
 
      'config/laravel-datatable.php'
 
 The stylesheets should be created for you in:
 
      'public/vendor/datatable.css'
 
 If you have issues, make sure that the LaravelDatatableServiceProvider class is registered in your
 /bootstrap/providers.php

## The main class here is DatatableController

## Use it
```php 
    namespace GustoCoder\LaravelDatatable\Http\Controllers;

    use App\Http\Controllers\Controller;
    use Gustocoder\LaravelDatatable\Http\Controllers\DatatableController;


    class ExampleController extends Controller
    {
        public function showUsers() {
            $dataTableClass = new DatatableController(
                'User', 
                'users', 
                [], 
                ['date_field' => 'created_at', 'orderBy' => 'created_at']
            );

            //give the full route path (NOT the route name) as defined in the route file, eg 'admin/users'
            $deleteRoute = 'deleteUser'; 
            $editRoute = 'editUsers'; 


            //This is optional, & it creates a new column, eg 'Actions'. It accepts the name of the column 
            //and you can call it whatever you want. It only supports adding one column, so only call this 
            //once. You can however, add multiple field buttons under that columns, and the column will be 
            //expanded to contain them all eg Actions.
            //If you do call addColumn, make sure you also call addFieldButton(...) to insert data under 
            //the new column
            $dataTableClass->addColumn('Action');

            //used to add field data to go under the column you added above. Handy for Edit, or Delete buttons.
            $dataTableClass->addFieldButton(
                'Action', 
                'Delete', 
                'x', 
                $deleteRoute, 
                ['id'], 
                ['id' => 'deleteUserBtn', 'class' => 'btn btn-danger btn-sm']
            );

            $dataTableClass->addFieldButton(
                'Action', 
                'Edit', 
                'Edit', 
                $editRoute, 
                ['id'], 
                ['id' => 'editUserBtn', 'class' => 'btn btn-warning btn-sm']
            );

            //add another button if you want. Give it relevant attributes
            $dataTableClass->addFieldButton(
                'Action', 
                'Something', 
                'something', 
                'someRoute', 
                ['id'], 
                ['id' => 'doSomethingBtn', 'class' => 'btn btn-primary btn-sm']
            );

            $panelId = 'usersPanel';
            $usersTable = $dataTableClass->getTable($panelId);
            return view('laravel-datatable::datatable-view', ['usersTable' => $usersTable]);
        }


        /**
         * An example of how you would delete a record from the datatable-see the delete
         * button link and the route that points to this method
        */
        public function deleteUser($userId)
        {
            $userModel = new User();
            $record = $userModel::find($userId);

            if ($record) {
                $record->delete();
                return redirect()->back()->with('success', 'User deleted successfully');
            }
            else 
            {
                return redirect()->back()->with('danger', 'User could not be deleted');
            }
        }
    }
```
    Override the panel id value-the current/default value is 'datatablePanel'. After 
    setting the id, do not forget to edit your panel styling in 
    public/vendor/laravel-datatable/css/datatable.css. Go in there & edit the styling 
    for 'datatablePanel'. The reason for allowing you to set an id attribute on the panel 
    that wraps around the table is to allow you use CSS to customise the look and behaviour 
    of the table, and, or JS to manipulate the table dynamically. If you do assign a 
    panelId, do not forget to go into the CSS stylesheet in your public directory and 
    change the panelId from the default one 'datatablePanel' to the one you have added.

    Do not forget to use the config file 'config/laravel-datatable.php' to enter base 
    settings for your data table. See the 'Your datatable configurations' below.

    You would display the generated table ('usersTable') in your view blade file like so:

```php 
    {!! $usersTable !!}
```

## Description of the arguments passed to DatatableController

* The first argument here (required) must be the exact spelling of the model to use.

* The second argument (optional) is the route path you have defined in your route file for the 
    view file where the fetched data from the model will be displayed. It is optional because you 
    may choose to have the records not clickable and, or, not sortable-in which case there will 
    be no anchor links generated or fetch more data.

* The third argument (optional) is an associative array of specific fields you want to fetch if 
    you do not want to fetch all fields on the model. The keys are the actual DB table field names, 
    while the values which are optional, are strings you want to appear on the generated table as 
    aliases in place of the table field name. 

* The fourth argument (optional) is an array of configuration that you want to override the the 
    base config settings for the current table you are creating. You pass in an associative array 
    of config settings and their values. For example:

    ```php
        ['date_field' => 'created_at', 'orderBy' => 'created_at']
    ```

    * By default, the system assumes that your table has a 'date' field, that is a datetime/timestamp 
      field and its data will be converted for you from the 'Y-m-d' format to the 'd-m-Y' format.  

    * If your table's datetime/timestamp field, is named something other than 'date', and you want 
        this date format conversion, you need to pass a config array to the 4th argument here in 
        order to tell the system the name of your date field. For example, if the name of your date 
        field is 'created_at', pass it like so:
                  
        ```php 
            ['date_field' => 'created_at']
        ```

    * If you do not want this conversion, remove the 'date_field' setting entirely from your config 
      file.

    * By default, the system also orders the date by a 'date' field. If you want ordering to be done 
      using a different field, or if your date field is named something else; pass the config entry 
      here in the 4th argument to specify the field to be ordered on by default like so: 
                
        ```php 
            ['orderBy' => 'created_at'] 
        ```
        
    * Optionally, you can set the heading for your table in this same config 4th argument like so:

        ```php
            ['heading' => 'Users data']
        ```

        Otherwise, the system is going to generate this heading for you using the model name you 
        passed in like in the format: 'Modelname data'.


## A word on the date_field config setting for the orderBy clause
    -You must only use a datetime/timestamp type for this field, and not a 'date' format  
        because the package code expects a time segment in the date string given.


## How to handle actions on the action (manage) buttons
    -When you indicate that you want the table records to be clickable (config file), the system 
     will create anchor links for the individual records and automatically send the record ids 
     with these links. All you then have to do create the routes to match those paths.

    -Also, when you add buttons, the system will add anchor links to them, adding in the record 
     ids as well. Here is an example of how you would create a route to handle deletion of a 
     record:


* routes\web.php

    ```php
        use Illuminate\Support\Facades\Route;
        use Gustocoder\LaravelDatatable\Http\Controllers\ExampleController;

        //This is an example of how you would define routes for the feature
        Route::get('/users', [ExampleController::class, 'showUsers'])->name('show-users');
        Route::get(
            '/deleteUser/{userId}', 
            [ExampleController::class, 'deleteUser']
        )->name('delete-user');
    ```

* Controller

    ```php
        ...
        $dataTableClass = new DatatableController(...);
        ...
        $deleteRoute = 'deleteUser';
        ...
        $dataTableClass->addFieldButton(
            'Action', 
            'Delete', 
            'x', 
            $deleteRoute, 
            ['id'], 
            ['id' => 'deleteUserBtn', 'class' => 'btn btn-danger btn-sm']);
    ```

* The delete button that the getTable() method of DatatableController will create will have an 
    anchor link pointing to '/deleteUser/6' where 6 is the record id against that specific delete 
    button. The defined deleteUser route above will take the request to the deleteUser() method 
    of the ExampleController class.


# Generating a table from more than one model (powered by Eloquent joins)

    This is also possible, but it has some limitations (see 'Limitation to joining models' below). 
    Let us assume you want a table of records from two tables 'blog' and 'blog_comments', and you 
    are going to link them based on a foreign key for example, where 
    blog.id = blog_comments.blog_id. 

    The steps are very similar except for one extra method that you need to call: 'setJoinData()'

    Here is how you would do it. First of all, instantiate the DatatableController class exactly 
    as you would when generating a table for a single model. Pass it the model name of the 
    main (parent) table of the join relationship. A parent table is the table whose primary key 
    is used as foreign keys in other (child) tables. In our case the main table would be 'blog'.
    Also pass in the optional arguments like dataRoute, fields, config exactly as you would do
    for a single model. 

```php 
    $dataTableClass = new DatatableController('Blog', 'blog-comments');
```

    Next, call the setJoinData() method and pass it three arrays eg: 

```php 
    ...
    $dataTableClass->setJoinData(
            ["'blog_comments', 'blog.id', '=', 'blog_comments.blog_id'"],
            [
                'blog.blog_id as post_id', 
                'blog.blog_title', 
                'blog.blog_article', 
                'blog.blog_author as author', 
                'blog_comments.blog_comments_comment as comment', 
                'blog_comments.blog_comments_id as primary_key', 
                'blog_comments.blog_comments_author as commentor'
            ],
            ["'blog_comments.blog_comments_status', 'valid'"]
        );
```
    Once you have passed the main model you want to the constructor, eg:
            
        $dataTableClass = new DatatableController('Blog');
            
        You can now make a join on it like so:
            -call the method $dataTableClass->setJoinData()
                -pass it as first argument as an array of join strings where the 
                    first element is the (child) table to join on the main table eg: 
                    
                    ["'blog_comments', 'blog.id', '=', 'blog_comments.blog_id'"];

                    Here the 'blog_comments' table will be joined on the main 'blog' table
                    on the condition that blog.id is equal to blog_comments.blog_id. 

                -The optional second argument is a select string in case you only want 
                    specific fields-leave it blank to select all fields from all tables joined. 
                    Use aliases to avoid column name conflicts, or use a wildcard on a table 
                    name eg 'tableName.*' to select all fields on that table eg:

                    "'blog.id as post_id', 'blog.blog_title', 'blog.blog_article', 
                    'blog.blog_author as author', 'blog_comments.blog_comments as comment', 
                    'blog_comments.blog_comments_author as commentor'"

                    Note that when it comes to joins like this, if you need the table records 
                    to be clickable, when you call setJoinData(), you MUST create an alias
                    of the primary key. Do it like so:

                        'blog_comments.blog_comments_id as primary_key'

                    This will tell the system what the unique key of the joined records are.

                -The optional second argument is a where string which basically refers to a 
                    where clause if there is any eg:

                    "'blog_comments.blog_comments_status', 'valid'"

    The rest is the steps are exactly the same as with generating a table for a single model:
        -Optionally call the addColumn() and the addFieldButton() methods.
        -Then finally genrate the table and render the table view file like so:

        $panelId = 'blogPanel'; 
        $blogCommentsTable = $dataTableClass->getTable($panelId);
        return view('admin.contactMessages', ['contactMessagesTable' => $blogCommentsTable]);

    Feel free to call $dataTableClass->setJoinData(...) multiple times to join more tables to 
    the main table.

## Here is an example of a model join

```php 
    <?php
    
    namespace App\Http\Controllers;

    use Gustocoder\LaravelDatatable\Http\Controllers\DatatableController;
    ...


    class AdminController extends Controller
    {
        public function blogComments()
        {
            $dataTableClass = new DatatableController('Blog', 'blog-comments');
            
            $dataTableClass->setJoinData(
                ["'blog_comments', 'blog.blog_id', '=', 'blog_comments_blog_id'"],
                [
                    'blog.blog_id as post_id', 
                    'blog.blog_title', 
                    'blog.blog_article', 
                    'blog.blog_author as author', 
                    'blog_comments.blog_comments_comment as comment',
                    //Specify the joined record's primary key field as 'primary_key' 
                    'blog_comments.blog_comments_id as primary_key', 
                    'blog_comments.blog_comments_author as commentor'
                ],
                ["'blog_comments.blog_comments_status', 'valid'"]
            );

            //-------------------Add columns & buttons (optional)-----------------------
            $deleteRoute = 'admin/blog-comments'; 
            $editRoute = 'admin/edit-blog-comments';
            $doSomethingRoute = 'admin/do-something-with-comments';

            $dataTableClass->addColumn('Stuff');

            //used to add field data to go under the column you added above. use this 
            //for Edit, or Delete buttons.
            $dataTableClass->addFieldButton(
                'Stuff', 
                'Delete', 
                'x', 
                $deleteRoute, 
                ['id'], 
                ['id' => 'deleteBlogCommentBtn', 'class' => 'btn btn-danger btn-sm']
            );

            $dataTableClass->addFieldButton(
                'Stuff', 
                'Edit', 
                'Edit', 
                $editRoute, 
                ['id'], 
                ['id' => 'editBlogCommentBtn', 'class' => 'btn btn-warning btn-sm']
            );

            $dataTableClass->addFieldButton(
                'Stuff', 
                'Sometype', 
                'Something', 
                $doSomethingRoute, 
                ['id'], 
                ['id' => 'doSomethingBtn', 'class' => 'btn btn-primary btn-sm']
            );
            //-------------------Add columns & buttons (optional)-------------------------

            $panelId = 'blogPanel'; 
            $blogCommentsTable = $dataTableClass->getTable($panelId);
            return view(
                'admin.contactMessages', 
                ['contactMessagesTable' => $blogCommentsTable]
            );
        }
    }
```

## Limitation to joining models
    The only set back to joining models with this DatatableController class is that the 
    generated table fields of the main table are going to be the only fields you can 
    do sorting on. The fields from the joined (children) tables will only serve as
    display data.

## Your datatable configurations

    Once you have run the command


    The config file will be generated for you and placed in 

        'config/laravel-datatable.php'

    There are 4 fields that you should have separate entries per model for because 
    they are unique to every model's records 
    The config settings here must be prefixed with the model names in all lowercase. 
        
        '..._panelId'       => '',
        '..._heading'       => '',
        '..._date_field'    => '',
        '..._orderBy'       => '',

    These should be prefixed here with the model names-in all lowercase. Here is an
    example entry for these fields for 'blog' and 'blog_comments' tables.

```php
    'blog_panelId'          => '',
    'blog_heading'          => 'Blog',
    'blog_comments_heading' => 'Blog comments',
    'blog_date_field'       => 'blog_created',
    'blog_orderBy'          => 'blog_created',
    'blog_comments_orderBy' => 'blog_comments_created',
```

    There are four other fields that are generic and will apply to all your data 
    tables, so you do not have to set them for every model.

```php
        'recordsPerpage'    => 5,
        'sortable'          => true,
        'sortOrder'         => 'ASC',
        'clickableRecs'     => true,
```
    

## Customising the look of your table

    It is up to you to style the generated table as you wish.
    The table is wrapped in a panel with the id of: 'datatablePanel' or any id you 
    specified for it when you called getTable($panelId)

    The table element itself also has an id generated from the model name like so 
    id='modelname_table'

    You may use the panelId and table id attributes to customise the styling of the 
    table.

* So, remember to reference datatable stylesheet from the path where it lives 
     (in 'vendor/laravel-datatable/css/datatable.css') into your layout file like so:

```css
    <link 
        href="{{ asset('vendor/laravel-datatable/css/datatable.css') }}" 
        rel="stylesheet">
```

* Remember that to style the table, you need to edit the CSS file in 
    'public/vendor/laravel-datatable/css/datatable.css'

  The CSS selectors currently point to the default panelId value which is 
  'datatablePanel'. If you passed in a panelId to 'getTable()' when you called it; 
  you need to go into the CSS file 
  ('public/vendor/laravel-datatable/css/datatable.css') and change the CSS selector 
  references from 'datatablePanel' to the value of the panelid you passed in to 
  getTable().

* Once more, if you cannot find the following files in your project after installing 
    the package:

    * /config/laravel-datatable.php 
    * /vendor/laravel-datatable/css/datatable.css

    Then you need to run this command to fix that:

    ```bash
        php artisan vendor:publish
    ```

    Finally, make sure that the LaravelDatatableServiceProvider class is registered 
    in your
        /bootstrap/providers.php file like so:

```php
    return [
        ...
        Gustocoder\laravelDatatable\LaravelDatatableServiceProvider::class
    ];
```