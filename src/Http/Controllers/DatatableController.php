<?php
namespace GustoCoder\LaravelDatatable\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;


class DatatableController extends Controller
{
    private $_model;
    private $_modelNameString;
    private $_data;
    private $_requestedColumns = [];
    private $_total;
    private $_itemLinkRoute;

    //adding extra columns & fields on the fly
    //The options are: 'value', 'link', 'params' (optional array of the link query string params), 
    //and 'attributes' (to be used in styling the table-optional array)
    private $_extraFieldParameters = []; 
    private $_extraColumns = [];
    private $_config = [];



    /**
     * DGZ_Table constructor. Pass it a second parameter which should be the count of the data to be displayed; remember to filter the real count if you have any applicable filters,
     * otherwise the $count will be the total number of records displayed and reflect the number of page links shown in the pagination links, which may not be accurate.
     *
     * For the sorting feature to work, you must send the ordering to the DB query so that the data returns ordered as desired before passing it to DGZ_Table e.g
     *      $letters = $newsletter->selectOnly($columns, null, $order, $sort);
     * $pager = new DGZ_Table($letters);
     *
     * @param $modelNameString the name of the model we need data from
     * @param int $dataRoute the route for creating the link to individual items in case the table records are made clickable 
     * @param string $fields an array of fields to select in case you do not want all the fields in the DB in your table.
     *                  Note that you can pass in a numerically-indexed array for the exact field names, of an associative
     *                  array where the keys are the exact table column names while their values are aliases you would like 
     *                  to use in their places instead. 
     * @param string $config array associative array of values to add to, or override the package config options.
     */
    public function __construct($modelNameString, $dataRoute = '', $fields = [], $config = ['heading' => 'Test data table'])
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

        //merge package config into one
        //TODO: remember that config(...) gets its value from your main app, not your package. 
        //That's why you should have published your package config first (so LV copies your 
        //package config to the main app) before the below code will work
        $this->_config = array_merge(config('laravel-datatable', []), $config);

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
            $this->storeColumns();
        }
        
        $this->_total = $this->_data->total();
    }




    public function getSortingData()
    {
        $orderBy = (isset($_GET['ord']) ? $_GET['ord'] : $this->_config['orderBy']);
        $sortOrder = (isset($_GET['s']) ? $_GET['s'] : $this->_config['sortOrder']);
        return [$orderBy, $sortOrder];
    }



    /**
     * Call this pager class's constructor first passing it your data before calling this method to get the table output
     *
     * The $sortLinkTarget specifies the target back link for the sort links which will be passed to getTable() - do this obviously only
     * 		if you have set 'makeSortable()' to true
     *      Note very carefully that if you have specified '$pager->makeSortable' as true and set the $sortLinkTarget
     * 		variable, then in order for your links and pagination functionality to work well, you MUST call getTable() like so:
     *        $table = $pager->getTable('blog_posts_TableView', $sortLinkTarget);
     *          else you MUST call it like so:
     *        $table = $pager->getTable('newsletter_TableView', ''); (leaving the 2nd argument meant for the sort links blank)
     *
     * @param string $tableTemplateClassName
     * @param $sortLinkTarget string link destib=nation for the sort links on the table head
     * @return string containing the built HTML table
     *
     */
    public function getTable($panelId = '')
    {
        $columns = $this->getColumns();

        //now build the HTML table                    
        $HTMLTable = 
            "<div ".($panelId != ''? "id='$panelId'":"id='datatablePanel'")." 
                class='panel panel-primary'>
                <div class='panel-heading'>
                    <h2 class='panel-title'>".$this->_config['heading']."</h2>
                </div>

                <div class='panel-body'>
                    <div class='table-responsive'>
                        <table class='table'>
                            <thead>
                                <tr>";
                                foreach ($columns as $heading)
                                {
                                    //if a heading is not amongs the requested columns, dont display it
                                    if (!in_array($heading, $this->_requestedColumns))
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
                                        //n add more conditionals like: if ($btnCount == 2) { etc

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
                                        $HTMLTable .= "<tr>";

                                        foreach($dat->getAttributes() as $col => $val) 
                                        {
                                            //we don't want to show 'id' fields if the user did not select them
                                            //though we need that id field to handle clickable records
                                            if (!in_array($col, $this->_requestedColumns))
                                            {
                                                continue;
                                            }

                                            //detect a date field
                                            if ($this->_config['clickableRecs']) {
                                                if (strtolower($this->_config['date_field']) == strtolower($col))
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
                                                if (strtolower($this->_config['date_field']) == strtolower($col))
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

                                        //now for every iteration, loop though the extra columns and insert their values
                                        if (!empty($this->_extraColumns)) {
                                            foreach ($this->_extraColumns as $head => $valuesArray) { 
                                                //$valuesArray is a multidimensional array (which could be one of 'button', 'text'), so loop again
                                                foreach ($valuesArray as $type => $vals) {
                                                    //but because a 'button' type will contain a diff kinda sub-array from a 'text' type
                                                    // we need to check what type it is before looping again, so we know how to loop over each sub-array
                                                    if ($type == 'text') {
                                                        //this is easy, text has only one item in its sub-array, n that's the value of the text
                                                        $HTMLTable .= "<td>" . $vals['value'] . "</td>";
                                                    }
                                                    if ($type == 'button') {
                                                        //its a button, n buttons could have one, or two multidimensional arrays; 'edit', and, or 'delete' with each one having an array of three items
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
                                                                    $link .= '/'.$dat['id'];
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
     * This is a method for dynamically adding extra columns on the fly to the paginated table to be created by this Pager class. Pass it the text for its heading.
     *
     *You need to call this method before finally calling the getTable() method, as this method will have prepared the array that getTable() will use to build final table output.
     * The two parameters you pass to this method are assigned to the $_extraColumns array just as they are, with the first parameter as the key, and the second parameter as its sub array
     *
     * @param $heading
     * @param array $value contains a multidimensional array with a key of 'text', or 'button'
     *  if it is 'text', then this array will hold just on sub array where the value is the value of the text
     *  if is is a button, this array will have 3 sub arrays i) the value (text) of the button ii) the link target, and iii) an array of parameters to pass at the end of the link
     *
     *@return void;
     */
    public function addColumn($heading)
    {
        //The $this->_extraColumns member could take multiple headings depending on how many times addColumn() is called to create multiple columns
        // so each $heading will have a different value and be a separate multidimensional array within the one $this->_extraColumns array
        $this->_extraColumns[$heading] = [];
    }




    /**
     * Call this method if the extra column field you are adding will contain a button.
     *  This method builds up the _extraFieldParameters array property so that we properly deals with cases where buttons, or text column fields are being created
     *  For buttons, it builds up 3 sub arrays
     *      i) the value (text) of the button
     *      ii) the link target, and
     *      iii) an array of parameters to pass at the end of the link
     *
     * @param $heading string this should match the text you gave to the heading when you first created the new column using addColumn()
     *          the system will then know which heading to place this under
     * @param $buttonType string the type of button you want to create. We currently handle two button types; 'Edit', and 'Delete' buttons
     * @param $value string the text to go on the button
     * @param $link string the link where these buttons will take the user to e.g. 'index.phtml?page=blogController&action=editPost'
     * @param array $params array of strings of parameters to stick after the link as a query string. Note that these should match the names of DB table fields where the data is coming from e.g. ['blog_id']
     *
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
     * This is the equivalent of the addFieldButton method because it also builds up the _extraFieldParameters array property so that we properly deal with cases where buttons,
     * or text column fields are being created. It handles placing of text in the extra column field, and not a button, therefore it is thus much simpler.
     *
     * Unlike the case of buttons where we build 3 sub arrays, this array will hold only one sub array where the value is the value of the text
     *
     * @param $heading string this should match the text you gave to the heading when you first created the new column using addColumn()
     *          the system will then know which heading to place this under
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