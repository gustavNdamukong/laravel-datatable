<?php

namespace Gustocoder\LaravelDatatable\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Gustocoder\LaravelDatatable\Exceptions\RedirectException;
use Illuminate\Support\Facades\Route;


/**
 * Class DatatableController
 *
 * @package Gustocoder\LaravelDatatable
 * @author Gustav Ndamukong
 */
class DatatableController extends Controller
{
    private $_model;
    private $_modelNameString;
    private $_data;
    private $_requestedColumns = [];
    private $_total;
    private $_itemLinkRoute;

    private $_extraFieldParameters = []; 
    private $_extraColumns = [];
    private $_config = [];
    private $_joinRealFields = [];


    /**
     * @param $modelNameString the name of the model we need data from
     * @param string $dataRoute the route for creating the link to individual items in case the table records are made clickable 
     * @param string $fields an array of fields to select in case you do not want all the fields in the DB in your table.
     *                  Note that you can pass in a numerically-indexed array for the exact field names, of an associative
     *                  array where the keys are the exact table column names while their values are aliases you would like 
     *                  to use in their places instead. 
     * @param string $config array associative array of values to add to, or override the package config options.
     */
    public function __construct(
        $modelNameString, 
        $dataRoute = '', 
        $fields = [], 
        $config = ['heading' => 'Data table']
    )
    {
        // Check if the provided model class name is valid
        if (!class_exists("App\Models\\".$modelNameString) || !is_subclass_of("App\Models\\".$modelNameString, Model::class)) {
            die('The model class '.$modelNameString.' does not exist');
        }

        $modelName = "App\Models\\".$modelNameString;
        $this->_model = new $modelName();
        $this->_modelNameString = $modelNameString;

        if ($dataRoute != '')
        {
            $this->_itemLinkRoute = $dataRoute;
        }
        else
        {
            $this->_itemLinkRoute = strtolower($modelNameString);
        }

        $this->_config = array_merge(config('laravel-datatable', []), $config);

        if ($this->_config[strtolower($modelNameString).'_heading'] == 'Data')
        {
            $this->_config[strtolower($modelNameString).'_heading'] = ucfirst($modelNameString). ' data';
        }

        [$orderBy, $sortOrder] = $this->getSortingData();

        $recordsPerpage = $this->_config['recordsPerpage'];

        if ($fields)
        {
            [$field_list, $requestedColumns] = $this->getFieldList($fields); 

            //always select the id field even if it was not requested for
            if (!in_array($this->_model->getKeyName(), $field_list))
            {
                array_unshift($field_list, $this->_model->getKeyName());
            }

            //store requested list which may or may not include the id field
            $this->_requestedColumns = $requestedColumns;
            $this->_data = $this->_model::select($field_list)->orderBy($orderBy, $sortOrder)->paginate($recordsPerpage);
        }
        else
        {
            $this->_data = $this->_model::orderBy($orderBy, $sortOrder)->paginate($recordsPerpage); 
            
            if ($this->_data->first() != null)
            { 
                $this->storeColumns(); 
            } 
        }
        $this->_total = $this->_data->total();
    }


    /**
     * @var $joinStrings array of strings each containing the table to join the main table on, & how to link the two
     * @var $selectString array of the fields to select-leave blank to select all fields of all joined tables
     * @var $whereString array for the where clause qualifiers as separate strings. There can be multiple where qualifiers, hence the array
     */
    public function setJoinData($joinStrings, $selectString = [], $whereString = [])
    { 
        $recordsPerpage = $this->_config['recordsPerpage'];

        $query = $this->_model::query();

        // Apply the joins to the query
        $query = $this->applyJoins($query, $joinStrings);

        // Add any other query constraints, e.g., where, orderBy, etc.
        if ($selectString)
        {
            //set real fields from aliases
            $fields = [];
            foreach ($selectString as $string)
            {
                if (preg_match('/as/', $string))
                {
                    $realField = explode('as', $string);
                    $this->_joinRealFields[$realField[1]] = $realField[0];
                }
            }

            $query->select($selectString);
        }
        if ($whereString)
        {
            foreach ($whereString as $where)
            {
                $whereComponents = explode(', ', $where);
                $column = trim($whereComponents[0], "'");
                $value = trim($whereComponents[1], "'");
                $query->where($column, $value);
            }
        }

        [$orderBy, $sortOrder] = $this->getSortingData();
        
        $query->orderBy($orderBy, $sortOrder);

        $this->_data = $query->paginate($recordsPerpage);
        $this->_total = $this->_data->total(); 

        $this->adjustCurrentPage();
        $this->storeColumns();
    }


    public function applyJoins(&$query, array $joins)
    {
        foreach ($joins as $join) {
            // Split the join string into its components
            $joinComponents = explode(', ', $join);
            
            // Trim the components to remove any extra spaces or quotes
            $table = trim($joinComponents[0], "'");
            $first = trim($joinComponents[1], "'");
            $operator = trim($joinComponents[2], "'");
            $second = trim($joinComponents[3], "'");
            
            // Apply the join to the query
            $query = $query->join($table, $first, $operator, $second);
        }
        return $query;
    }


    public function getSortingData()
    {
        $orderBy = (isset($_GET['ord']) ? $this->getRealFieldName($_GET['ord']) : $this->_config[strtolower($this->_modelNameString).'_orderBy']); 
        $sortOrder = (isset($_GET['s']) ? $this->getRealFieldName($_GET['s']) : $this->_config['sortOrder']);
        return [$orderBy, $sortOrder];
    }

    public function getRealFieldName($fieldName)
    {
        $trimmedArray = [];
        foreach ($this->_joinRealFields as $key => $value) {
            $trimmedKey = trim($key);
            $trimmedArray[$trimmedKey] = $value;
        }
        $this->_joinRealFields = $trimmedArray;

        if (isset($this->_joinRealFields[$fieldName]))
        {
            return $this->_joinRealFields[$fieldName];
        }
        else 
        {
            return $fieldName;
        }
    }



    /**
     * @param string $panelId to be used as the id attribute of the panel your table will be wrapped in
     * @return string containing the built HTML table
     */
    public function getTable($panelId = '')
    {
        $this->adjustCurrentPage();
        $columns = $this->getColumns();

        //now build the HTML table                    
        $HTMLTable = 
            "<div ".($panelId != ''? "id='$panelId'":"id='datatablePanel'")." 
                class='panel panel-primary'>
                <div class='panel-heading'>
                    <h2 class='panel-title'>".$this->_config[strtolower($this->_modelNameString).'_heading']."</h2>
                </div>

                <div class='panel-body'>
                    <div class='table-responsive'>
                        <table class='table' id='".strtolower($this->_modelNameString)."_table'>
                            <thead>
                                <tr>";
                                foreach ($columns as $heading)
                                {
                                    //if a heading is not amongs the requested columns, dont display it
                                    if (!in_array($heading, $this->_requestedColumns))
                                    {
                                        continue;
                                    }

                                    //do not create primary_key column 
                                    if ($heading == 'primary_key')
                                    { 
                                        continue;
                                    }

                                    //have they specified that they want the table to be sortable?
                                    if ($this->_config['sortable']) {
                                        if ((isset($_GET['ord'])) && ($_GET['ord'] == $heading)) {
                                            //it means they were already ordering by this column but now want to switch the ordering
                                            if ((isset($_GET['s'])) && (strtolower($_GET['s']) == 'asc')) {
                                                $sortOrder = 'desc';
                                            }
                                            else if ((isset($_GET['s'])) && (strtolower($_GET['s']) == 'desc')) {
                                                $sortOrder = 'asc';
                                            }
                                            else {
                                                $sortOrder = $this->_config['sortOrder'];
                                            }
                                            $HTMLTable .= "<th class='text-center'><a href='$this->_itemLinkRoute?ord=$heading&s=$sortOrder'>" . $heading . " <i class='fa fa-fw fa-sort'></i></a></th>";
                                        }
                                        else {
                                            $HTMLTable .= "<th class='text-center'><a href='$this->_itemLinkRoute?ord=$heading&s=".$this->_config['sortOrder']."'>" . $heading . " <i class='fa fa-fw fa-sort'></i></a></th>";
                                        }
                                    }
                                    else
                                    {
                                        $HTMLTable .= "<th class='text-center'>" . $heading . "</th>";
                                    }
                                }

                                //check if extra columns were specified and add their headings here before you proceed
                                if (!empty($this->_extraColumns)) 
                                {
                                    foreach ($this->_extraColumns as $head => $valueArray)
                                    {
                                        //we need to know if the values of this field will be buttons, n if so, how many btns there are, so we can make the header wide enough to contain the columns
                                        //we are of course assuming below that there will not be more than two btns provided for one column, if u decide to accept more in your app, simply come here
                                        //& add more conditionals like: if ($btnCount == 2) { etc

                                        //we know there're only two types of buttons handled; 'text' & 'button', so let's get the count of total columns added on the fly
                                        $extraColumnsCount = 0;

                                        foreach ($valueArray as $type => $vals) {
                                            if ($type == 'text') {
                                                $extraColumnsCount = count($vals);
                                            }
                                            if ($type == 'button') {
                                                $extraColumnsCount = count($vals);
                                            }
                                        }

                                        if ($extraColumnsCount > 1) {
                                            $HTMLTable .= "<th class='text-center' colspan='$extraColumnsCount'>" . $head . "</th>";
                                        }
                                        else
                                        {
                                            $HTMLTable .= "<th class='text-center'>" . $head . "</th>";
                                        }
                                    }
                                }
                                
                                //close the table heading
                                $HTMLTable .= "</tr></thead><tbody>";
                                $iteration = 0;
                                if ($this->_data)
                                {
                                    foreach ($this->_data as $ref => $dat)
                                    {
                                        $recId = $dat->getKey(); 
                                        if (isset($dat->getAttributes()['primary_key']))
                                        {                                       
                                            $recId = $dat->getAttributes()['primary_key'];
                                        }

                                        $HTMLTable .= "<tr>";

                                        foreach($dat->getAttributes() as $col => $val) 
                                        { 
                                            //only pull from the user-requested fields
                                            if (!in_array($col, $this->_requestedColumns))
                                            {
                                                continue;
                                            }

                                            //do not create primary_key field
                                            if ($col == 'primary_key')
                                            {
                                                continue;
                                            }

                                            if ($this->_config['clickableRecs']) {
                                                if (
                                                    (isset($this->_config[strtolower($this->_modelNameString).'_date_field'])) && 
                                                    (strtolower($this->_config[strtolower($this->_modelNameString).'_date_field']) == strtolower($col))
                                                )
                                                {
                                                    $carbonDate = Carbon::createFromFormat('Y-m-d H:i:s', $val); 
                                                    $formattedDate = $carbonDate->format('d-m-Y');
                                                    $HTMLTable .= "<td id='".$recId."_".$col."'><a href='$this->_itemLinkRoute/$recId'>" . ($val != "" ? $formattedDate : "") . "</a></td>";
                                                }
                                                else {
                                                    $HTMLTable .= "<td id='".$recId."_".$col."'><a href='$this->_itemLinkRoute/$recId'>" . wordwrap($val ?? '', 75, "<br>\n", true) . "</a></td>";
                                                }
                                            }
                                            else
                                            {
                                                if (strtolower($this->_config[strtolower($this->_modelNameString).'_date_field']) == strtolower($col))
                                                {
                                                    $carbonDate = Carbon::createFromFormat('Y-m-d H:i:s', $val);
                                                    $formattedDate = $carbonDate->format('d-m-Y');
                                                    $HTMLTable .= "<td id='".$recId."_".$col."'>" . ($val != "" ? $formattedDate : "") . "</td>";
                                                }
                                                else { 
                                                    $HTMLTable .= "<td id='".$recId."_".$col."'>" . wordwrap($val ?? '', 75, "<br>\n", true) . "</td>";
                                                }
                                            }
                                        }

                                        //handle any extra columns
                                        if (!empty($this->_extraColumns)) {
                                            foreach ($this->_extraColumns as $head => $valuesArray) { 
                                                //$valuesArray is a multidimensional array (which could be either a 'button' or 'text'), so loop again
                                                foreach ($valuesArray as $type => $vals) {
                                                    //distinguish the type
                                                    if ($type == 'text') {
                                                        //this is easy, text has only one item in its sub-array, & that's the value of the text
                                                        $HTMLTable .= "<td>" . $vals['value'] . "</td>";
                                                    }
                                                    if ($type == 'button') {
                                                        //its a button, & buttons could have one, or two multidimensional arrays; 'edit', and, or 'delete' with each one having an array of three items
                                                        // so loop through the buttons
                                                        foreach ($vals as $buttonType => $attributes)
                                                        {
                                                            //Now we need to build the button using its parameters ('vale', 'link', and 'params') from the sub array provided
                                                            //but first, let's prepare the link wh is crucial for the button to be useful
                                                            $link = $attributes['link'];

                                                            //did they provide any parameters for the button link?
                                                            if (!empty($attributes['params']))
                                                            {
                                                                //check if params has an 'id' element, coz this must be added to the link differently
                                                                if (in_array('id', $attributes['params']))
                                                                {
                                                                    $link .= '/'.$recId; 
                                                                    //now get rid of the id from params
                                                                    $idIndex = array_search('id', $attributes['params']);
                                                                    unset($attributes['params'][$idIndex]);
                                                                }

                                                                $count = count($attributes['params']);
                                                                if ($count > 0)
                                                                {
                                                                    $link .= '?';
                                                                    $x = 1;
                                                                    foreach ($attributes['params'] as $param)
                                                                    {
                                                                        //add them to the link
                                                                        if ($x < $count) {
                                                                            //$param will contain the value of the $col from the DB
                                                                            $link .= $param . '='.$dat[$param].'&';
                                                                        }
                                                                        else
                                                                        {
                                                                            $link .= $param . '='.$dat[$param];
                                                                        }
                                                                        $x++;
                                                                    }
                                                                }
                                                                //create a Laravel route from the link
                                                                $link = url($link);
                                                            }

                                                            //Did they provide any attributes for the button link element? If so use them to create the element. These attributes are different from link query strings as is the case
                                                            //with link parameters
                                                            $linkAttributes = '';
                                                            if (!empty($attributes['attributes']))
                                                            {
                                                                $attributeCount = count($attributes['attributes']);
                                                                $i = 1;
                                                                foreach ($attributes['attributes'] as $attrib => $attribVal)
                                                                {
                                                                    //build the attribute string that we will inject into the button element e. g. data-toggle='modal' or data-target='#editNewsletterModal' or id='clickMe' etc
                                                                    if ($i < $attributeCount) {
                                                                        //create the link
                                                                        $linkAttributes .= $attrib .'="'.$attribVal.'" ';
                                                                    }
                                                                    else
                                                                    {
                                                                        $linkAttributes .= $attrib . '="'.$attribVal.'"';
                                                                    }
                                                                    $i++;
                                                                }
                                                            }

                                                            //now create the button - you can optionally check for the $buttonType value and style the button accordingly
                                                            //Note that the $recId variable used in the jQuery custom attribute (data-recid) below has been set above where we create the main table body <td> tags, as we used
                                                            //the record IDs prefixed with an underscore to the DB field names as the IDs of those <td> tags. This is to give you a way to use these btn links to uniquely
                                                            // manipulate the rows of the table
                                                            $btn = '<a data-recid="'.$recId.'" '.$linkAttributes.' href="'.$link.'" class="btn btn-info btn-sm">'.$attributes['value'].'</a>';
                                                            $HTMLTable .= "<td>" . $btn . "</td>";
                                                        }
                                                    }
                                                }
                                            }
                                        }

                                        $iteration++;
                                        if ($iteration != $this->_total) 
                                        {
                                            $HTMLTable .= "</tr>";
                                        }

                                    }
                                }
                                else
                                { ?>
                                    <tr colspan="6" class="text-center">
                                    There are no <?=strtolower($this->_modelNameString)?>s yet. 
                                    </tr>
                                <?php
                                }
                                //close the table
                                $HTMLTable .= "</tbody>";
                                $HTMLTable .= "</table></div></div>";
                                  
                                $HTMLTable .= "<div class='panel-footer'>".
                                    $this->_data->links().
                                    "</div></div>";
        return $HTMLTable;

    }


    public function adjustCurrentPage()
    {
        if ($this->_data->first() == null)
        { 
            $currentPage = request()->query('page', 1);
            
            // Count page records
            $totalRecs = $this->_data->count();
            $configRecsPerpage = $this->_config['recordsPerpage'];

            //if configRecsPerpage is more than totalRecs (which is per page), redirect one page backwards
            if ($configRecsPerpage > $totalRecs) { 
                $newPage = ($currentPage - 1);
                $currentRoute = Route::currentRouteName();
                throw new RedirectException(route($currentRoute, ['page' => $newPage])); 
            } 
        }
    }


    public function getFieldList($fields)
    {
        //determine if this is an associative or numerically-indexed array
        if ($this->isAssociativeArray($fields))
        {
            $queryParts = [];
            $requestedColumns = [];
            //Extract the keys
            foreach ($fields as $field => $alias)
            {
                //also do a non-numeric check on key incase there's a numerically-indexed elem
                if ((!is_numeric($field)) && ($alias != ''))
                {
                    $queryParts[] = $field . ' AS '.$alias;
                    $requestedColumns[] = $alias;
                    $requestedColumns[] = $field;
                }
                else if (!is_numeric($field))
                {
                    $queryParts[] = $field;
                    $requestedColumns[] = $field;
                }
                else if ((is_numeric($field)) && ($alias != ''))
                {
                    $queryParts[] = $alias;
                    $requestedColumns[] = $alias;
                } 
            }
            return [$queryParts, $requestedColumns];
        }
        else 
        {
            return [$fields, $fields];
        }
    }



    function isAssociativeArray($array) {
        // Check if any keys are non-sequential
        return count(array_filter(array_keys($array), 'is_string')) > 0;
    }


    public function storeColumns()
    {
        $firstItem = $this->_data->first();
        //make an associative array of the collection
        $assocArray = $firstItem->getAttributes();

        //extract the keys
        $this->_requestedColumns = array_keys($assocArray);
    }


    public function getColumns()
    {
        $firstItem = $this->_data->first();
        //make an associative array of the collection
        $assocArray = $firstItem->getAttributes();

        //extract the keys
        return array_keys($assocArray);
    }




    /**
     * Dynamically add extra columns on the fly to the paginated table to be created. Pass it the text for its heading.
     *
     * This method must be called before calling getTable(), as it prepares the array that getTable() will use to 
     *  build final table output
     * @param string $heading added to the $_extraColumns array
     *@return void;
     */
    public function addColumn($heading)
    {
        //The $this->_extraColumns member could take multiple headings depending on how many times addColumn() is called to create multiple columns
        // so each $heading will have a different value and be a separate multidimensional array within the one $this->_extraColumns array
        $this->_extraColumns[$heading] = [];
    }




    /**
     * This is for adding new columns to your table on the fly. It handles columns containing buttons.
     * It pushes its value into the _extraFieldParameters array.
     * Call this method if the extra column field you are adding will contain a button.
     *
     *  For buttons, it builds up 3 sub arrays
     *      i) it specifies that the extra field it is adding to the table is a button eg: 
     *          $this->_extraFieldParameters['button']
     *      ii) It then specifies the button type. There are two options you can pass in, 'Edit' or 'Delete'.
     *      iii) Regardless of the button type, it builds four aspects that all buttons need: 
     *          -the link text value
     *          -the actual href URL string to be injected into the button
     *          -any parameters to append to the href value as browser query strings
     *          -any attributes to add to the button element it creates.
     *
     * @param $heading string this should match the text you gave to the heading when you first created the 
     *  new column using addColumn() eg 'Action'. That is how the system will know which column to place the 
     *  $heading value under.
     * @param $buttonType string the type of button you want to create. We currently handle two button types; 
     *  'Edit', and 'Delete'.
     * @param $value string the text to go on the button
     * @param $link string the link where these buttons will take the user to. This will point to one of your routes.
     * @param array $params array of strings of parameters to stick after the link as a query string. 
     * @param array $attributes attributes to pass to the generated button eg 'id', or 'class' etc. This will be 
     *  handy for styling the button or using Javascript to handle the click event on the button.
     * @return void
     */
    public function addFieldButton($heading, $buttonType, $value, $link, $params = [], $attributes = [])
    {
        $this->_extraFieldParameters['button'][$buttonType]['value'] = $value;
        $this->_extraFieldParameters['button'][$buttonType]['link'] = $link;
        $this->_extraFieldParameters['button'][$buttonType]['params'] = $params;
        $this->_extraFieldParameters['button'][$buttonType]['attributes'] = $attributes;

        $this->_extraColumns[$heading] = $this->_extraFieldParameters;
    }




    /**
     * Similar to addFieldButton(), this is for adding new columns to your table on the fly. It handles text columns.
     * It pushes its value into the _extraFieldParameters array. 
     * Call this method if the extra column field you are adding will contain some text instead of a button
     * It handles placing of text in the extra column field, and not a button, therefore it is thus much simpler
     * than addFieldButton(). It appends only one sub array to the _extraFieldParameters array, 'text', where the value 
     * key is the value of the text.
     * 
     * It builds up a simple array
     *      i) it specifies that the extra field it is adding to the table is 'text' eg: 
     *          $this->_extraFieldParameters['text']
     *      ii) It the specifies the value of the text ass passed in by you in $value eg:
     *          $this->_extraFieldParameters['text']['value'] = $value;
     * 
     * @param $heading string this should match the text you gave to the heading when you first created the 
     *  new column using addColumn() eg 'Action'. That is how the system will know which column to place the 
     *  $heading value under.
     * @param $value string to go in the field
     *
     * @return void
     */
    public function addFieldText($heading, $value)
    {
        $this->_extraFieldParameters['text']['value'] = $value;
        $this->_extraColumns[$heading]= $this->_extraFieldParameters;
    }
}