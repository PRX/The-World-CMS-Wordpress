<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Autoterms_Schedule_Logs extends WP_List_Table
{

    /** Class constructor */
    public function __construct()
    {

        parent::__construct([
            'singular' => __('autotermsschedulelog', 'taxopress-pro'), //singular name of the listed records
            'plural'   => __('autotermsschedulelogs', 'taxopress-pro'), //plural name of the listed records
            'ajax'     => false //does this table support ajax?
        ]);

    }

    /**
     * Retrieve st_autoterms data from the database
     *
     * @param bool $count_only
     *
     * @return mixed
     */
    public function get_st_autoterms()
    {
        $per_page = $this->get_items_per_page('st_autoterms_schedule_logs_per_page', 20);
        $current_page = $this->get_pagenum();

        $orderby = (!empty($_REQUEST['orderby'])) ? sanitize_text_field($_REQUEST['orderby']) : 'ID'; //If no sort, default to role
        $order   = (!empty($_REQUEST['order'])) ? sanitize_text_field($_REQUEST['order']) : 'desc'; //If no order, default to asc


        return taxopress_autoterms_logs_data($per_page, $current_page, $orderby, $order, true);
    }

    /**
     * Returns the count of records in the database.
     *
     * @return null|string
     */
    public static function record_count()
    {
        return get_st_autoterms()['counts'];
    }

    /**
     * Show single row item
     *
     * @param array $item
     */
    public function single_row($item)
    {
        $class = ['st-autoterm-log-tr'];
        $id    = 'st-autoterm-log-' . md5($item->ID);
        echo sprintf('<tr id="%s" class="%s">', esc_attr($id), esc_attr(implode(' ', $class)));
        $this->single_row_columns($item);
        echo '</tr>';
    }

    /**
     *  Associative array of columns
     *
     * @return array
     */
    function get_columns()
    {
        $columns = [
            'cb' => '<input type="checkbox"/>', //Render a checkbox instead of text
            'title'     => esc_html__( 'Post', 'taxopress-pro' ),
            'post_type'     => esc_html__( 'Post type', 'taxopress-pro' ),
            'taxonomy'     => esc_html__( 'Taxonomy', 'taxopress-pro' ),
            'terms'     => esc_html__( 'Terms added', 'taxopress-pro' ),
            'date'     => esc_html__( 'Date', 'taxopress-pro' )
        ];

        return $columns;
    }

    /**
     * Render a column when no column specific method exist.
     *
     * @param array $item
     * @param string $column_name
     *
     * @return mixed
     */
    public function column_default($item, $column_name)
    {
        return isset($item->$column_name) ? $item->$column_name : '&mdash;';
    }

    /** Text displayed when no stterm data is available */
    public function no_items()
    {
        esc_html_e('Schedule log is empty.', 'taxopress-pro');
    }

    /**
     * The checkbox column
     *
     * @param object $item
     *
     * @return string|void
     */
    protected function column_cb($item)
    {
        return sprintf('<input type="checkbox" name="%1$s[]" value="%2$s" />', 'taxopress_autoterms_schedule_logs', $item->ID);
    }

    /**
     * Get the bulk actions to show in the top page dropdown
     *
     * @return array
     */
    protected function get_bulk_actions()
    {
        $actions = [
            'taxopress-autoterms-delete-logs' => esc_html__('Delete', 'taxopress-pro')
        ];

        return $actions;
    }

    /**
     * Process bulk actions
     */
    public function process_bulk_action()
    {

        $query_arg = '_wpnonce';
        $action = 'bulk-' . $this->_args['plural'];
        $checked = $result = isset($_REQUEST[$query_arg]) ? wp_verify_nonce(sanitize_key($_REQUEST[$query_arg]), $action) : false;

        if (!$checked || !current_user_can('simple_tags')) {
            return;
        }

        if($this->current_action() === 'taxopress-autoterms-delete-logs'){
            $taxopress_autoterms_schedule_logs = array_map('sanitize_text_field', (array)$_REQUEST['taxopress_autoterms_schedule_logs']);
            if (!empty($taxopress_autoterms_schedule_logs)) {
                foreach($taxopress_autoterms_schedule_logs as $taxopress_autoterms_log){
                    wp_delete_post($taxopress_autoterms_log, true);
                }
            }
        }

    }

    /**
     * Columns to make sortable.
     *
     * @return array
     */
    protected function get_sortable_columns()
    {
        $sortable_columns = [
            'title'    => ['title', true],
            'date'    => ['date', true]
        ];

        return $sortable_columns;
    }

    /**
     * Generates and display row actions links for the list table.
     *
     * @param object $item The item being acted upon.
     * @param string $column_name Current column name.
     * @param string $primary Primary column name.
     *
     * @return string The row actions HTML, or an empty string if the current column is the primary column.
     */
    protected function handle_row_actions($item, $column_name, $primary)
    {
        $auto_term_log_post_id = get_post_meta($item->ID, '_taxopress_log_post_id', true);

        //Build row actions
        $actions = [
            'edit'   => sprintf(
                '<a href="%s">%s</a>',
                add_query_arg(
                    [
                        'action' => 'edit',
                        'post' => $auto_term_log_post_id,
                    ],
                    admin_url('post.php')
                ),
                __('Edit Main Post', 'taxopress-pro')
            ),
            'delete' => sprintf(
                '<a href="%s" class="delete-autoterm">%s</a>',
                add_query_arg([
                    'page'                   => 'st_autoterms_schedule',
                    'action'                 => 'taxopress-delete-autoterm-log',
                    'taxopress_autoterms_log'=> esc_attr($item->ID),
                    '_wpnonce'               => wp_create_nonce('autoterm-action-request-nonce')
                ],
                    admin_url('admin.php')),
                __('Delete Log', 'taxopress-pro')
            ),
        ];

        return $column_name === $primary ? $this->row_actions($actions, false) : '';
    }

    /**
     * Method for title column
     *
     * @param array $item
     *
     * @return string
     */
    protected function column_title($item)
    {
        $auto_term_log_post_id = get_post_meta($item->ID, '_taxopress_log_post_id', true);

        $title = sprintf(
            '<a href="%1$s"><strong><span class="row-title">%2$s</span></strong></a>',
            add_query_arg(
                [
                    'action' => 'edit',
                    'post' => $auto_term_log_post_id,
                ],
                admin_url('post.php')
            ),
            esc_html(get_the_title($auto_term_log_post_id))
        );

        return $title;
    }

    /**
     * Method for post_type column
     *
     * @param array $item
     *
     * @return string
     */
    protected function column_post_type($item)
    {
        $auto_term_log_post_id = get_post_meta($item->ID, '_taxopress_log_post_id', true);
        $auto_term_log_posttype = get_post_type_object(get_post_type($auto_term_log_post_id));

        return ($auto_term_log_posttype && !is_wp_error($auto_term_log_posttype)) ? $auto_term_log_posttype->labels->singular_name : '&mdash;';

    }

    /**
     * Method for taxonomy column
     *
     * @param array $item
     *
     * @return string
     */
    protected function column_taxonomy($item)
    {
        $taxopress_log_taxonomy = get_post_meta($item->ID, '_taxopress_log_taxonomy', true);
        $taxopress_log_taxonomy_data = get_taxonomy($taxopress_log_taxonomy);

        return ($taxopress_log_taxonomy_data && !is_wp_error($taxopress_log_taxonomy_data)) ? $taxopress_log_taxonomy_data->labels->singular_name : '&mdash;';

    }

    /**
     * Method for terms column
     *
     * @param array $item
     *
     * @return string
     */
    protected function column_terms($item)
    {
        $taxopress_log_terms = get_post_meta($item->ID, '_taxopress_log_terms', true);

        if($taxopress_log_terms && !empty(trim($taxopress_log_terms))){
            return '<font color="green"> '.esc_html(ucwords($taxopress_log_terms)).' </font>';
        }else{
            return '<font color="red"> '. esc_html__('None', 'taxopress-pro') .' </font>';
        }
    }

    /**
     * Method for date column
     *
     * @param array $item
     *
     * @return string
     */
    protected function column_date($item)
    {
        
        return get_the_date('l F j, Y h:i A', $item->ID);

    }

    /**
     * Sets up the items (roles) to list.
     */
    public function prepare_items()
    {

        $this->_column_headers = $this->get_column_info();
        $this->process_bulk_action();

        /**
         * First, lets decide how many records per page to show
         */
        $per_page = $this->get_items_per_page('st_autoterms_schedule_logs_per_page', 20);

        /**
         * Fetch the data
         */
        $results = $this->get_st_autoterms();
        $data = $results['posts'];
        $total_items  = $results['counts'];
        $current_page = $this->get_pagenum();


        /**
         * Now we can add the data to the items property, where it can be used by the rest of the class.
         */
        $this->items = $data;

        /**
         * We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args([
            'total_items' => $total_items,                      //calculate the total number of items
            'per_page'    => $per_page,                         //determine how many items to show on a page
            'total_pages' => ceil($total_items / $per_page)   //calculate the total number of pages
        ]);
    }


}
