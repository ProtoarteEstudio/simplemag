<?php
/**
 * @package Simple Magazine
 * @version 1.0
 */
/*
Plugin Name: Simple Magazine
Plugin URI: http://christofferok.com/
Description: A simple magazine style plugin 
Author: Christoffer Korvald
Version: 1.0
Author URI: http://christofferok.com/
License: MIT
*/

define('WP_DEBUG', true);

define('SIMPLEMAG_NAME', 		    'Simple Magazine' );
define('SIMPLEMAG_VERSION', 		'1.0' );
define('SIMPLEMAG_URL', 			plugin_dir_url( __FILE__ ) );
define('SIMPLEMAG_PATH', 			plugin_dir_path( __FILE__ ) );
define('SIMPLEMAG_BASENAME', 		plugin_basename( __FILE__ ) );
define('SIMPLEMAG_REL_DIR', 		dirname( SIMPLEMAG_BASENAME ) );


class SimpleMagazine{

    public function __construct(){
        $this->addActions();
    }
    
    public function addActions(){
    
        add_action( 'init', array($this,'create_simplemag') );
        
        if(is_admin()){
            add_action( 'save_post', array($this,'add_simplemag_fields'), 10, 2 );
    
    
            add_action( 'admin_init', array($this,'simplemag_admin') );
            add_action( 'admin_menu', array($this,'simplemag_plugin_menu') );
            
            add_filter('manage_simplemag-article_posts_columns', array($this,'simplemag_article_columns'), 10);  
            add_action('manage_simplemag-article_posts_custom_column', array($this,'simplemag_article_custom_column'), 10, 2); 
            add_filter('name_save_pre', array($this,'save_name'));
            register_deactivation_hook( __FILE__, array($this,'simplemag_deactivate') );
            register_activation_hook( __FILE__, array($this,'simplemag_activate') );
            
            $magSettings = new SimpleMagazineSettings();
            	
        }
    }
    
    
    
    public function add_simplemag_fields( $simplemag_id, $simplemag) {
        // Check post type for movie reviews
        if ( $simplemag->post_type == 'simplemag-article' ) {
            // Store data in post meta table if present in post data
            if ( isset( $_POST['simplemag_issue'] ) && $_POST['simplemag_issue'] != '' ) {
                update_post_meta( $simplemag_id, 'simplemag_issue', $_POST['simplemag_issue'] );
            }
        }
    }
    
    public function simplemag_meta_box() {
        add_meta_box( 'simplemag_meta_box',
            'Article Details',
            'display_simplemag_meta_box',
            'simplemag-article', 'normal', 'high'
        );
    }
    
    public function display_simplemag_meta_box( $simplemag_article ) {
        // Retrieve current name of the Director and Movie Rating based on review ID
        $issue = intval( get_post_meta( $simplemag_article->ID, 'simplemag_issue', true ));
        ?>
        <table>
            <tr>
                <td style="width: 100%">Issue</td>
                <td>
                <select name="simplemag_issue">
                <option value="">- Select issue -</option>
                <?php
                // Get all issues
                $issues = new WP_Query(array('post_type' => 'simplemag-issue'));
                
                while ( $issues->have_posts() ) :
                	$issues->the_post();
                	echo '<option value="' . get_the_ID() . '"';
                	if($issue == get_the_ID()) echo ' selected="selected"';
                	echo '>' . get_the_title() . '</option>';
                endwhile;
                ?>
                </select>
                </td>
            </tr>
        </table>
        <?php
    }
    
    public function create_simplemag() {
        register_post_type( 'simplemag-article',
            array(
                'labels' => array(
                    'name' => 'Articles',
                    'singular_name' => 'Article',
                    'add_new' => 'Add New',
                    'add_new_item' => 'Add New Article',
                    'edit' => 'Edit',
                    'edit_item' => 'Edit Article',
                    'new_item' => 'New Article',
                    'view' => 'View',
                    'view_item' => 'View Article',
                    'search_items' => 'Search Articles',
                    'not_found' => 'No articles found',
                    'not_found_in_trash' => 'No articles found in Trash',
                    'parent' => 'Parent Article'
                ),
                'hierarchical' => false,
                'public' => false,
                'publicly_queryable' => false,
                'show_ui' => true,
                'supports' => array( 'title', 'editor','author',  'thumbnail','page-attributes' ),
                'taxonomies' => array( '' ),
                'menu_icon' => plugin_dir_url( __FILE__ ).'images/icon-16.png',
                'has_archive' => true,
                'show_in_menu' => 'simplemag',
                'register_meta_box_cb' => 'simplemag_meta_box'
            )
        );
        
        register_post_type( 'simplemag-issue',
            array(
                'labels' => array(
                    'name' => 'Issues',
                    'singular_name' => 'Issue',
                    'add_new' => 'Add New',
                    'add_new_item' => 'Add New Issue',
                    'edit' => 'Edit',
                    'edit_item' => 'Edit Issue',
                    'new_item' => 'New Issue',
                    'view' => 'View',
                    'view_item' => 'View Issue',
                    'search_items' => 'Search Issues',
                    'not_found' => 'No issues found',
                    'not_found_in_trash' => 'No issues found in Trash',
                    'parent' => 'Parent Issue'
                ),
                'hierarchical' => false,
                'public' => true,
                'publicly_queryable' => false,
                'supports' => array( 'title', 'excerpt',  'thumbnail'),
                'taxonomies' => array( '' ),
                'menu_icon' => plugin_dir_url( __FILE__ ).'images/icon-16.png',
                'has_archive' => true,
                'show_in_menu' => 'simplemag',
                'rewrite'=>array(
                    'slug'=>'issue',
                    'with_front' => false,
                    'pages' => false
                    )
                
            )
        );
        
        
        
    }
    
    
    public function cleanPermalink($str, $replace=array(), $delimiter='-') {
    	if( !empty($replace) ) {
    		$str = str_replace((array)$replace, ' ', $str);
    	}
    	
    	$clean = str_replace("æ","ae",$clean);
    	$clean = str_replace("ø","oe",$clean);
    	$clean = str_replace("å","a",$clean);
    
    	$clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
    	$clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
    	$clean = strtolower(trim($clean, '-'));
    	$clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);
    
    	return $clean;
    }
    
    
    public function save_name($name) {
    	global $post;
    	if($post->post_type == 'simplemag-article'){
            $post->post_name = cleanPermalink($post->post_title);
              return $post->post_name;
    	}
    	return $name;
    }
    
    
    
    public function simplemag_admin_style_post(){
        $screen = get_current_screen();
        if ($screen && ('simplemag-issue' == $screen->id || 'simplemag-article' == $screen->id )){
    		remove_editor_styles(); //Prevent the current theme from messing up the editor style
    	}
    }
    
    public function simplemag_admin(){
        add_action( 'pre_get_posts', array($this,'simplemag_admin_style_post') );
        wp_enqueue_style( 'simplemag_admin_style', SIMPLEMAG_URL . '/css/simplemag-admin.css');    	
    }
    
    
    public function simplemag_plugin_menu() {
    	add_menu_page(SIMPLEMAG_NAME,SIMPLEMAG_NAME, 'edit_pages', 'simplemag', null,SIMPLEMAG_URL.'/images/icon-16.png',9);
    }    
    
    // Custom column for issue on article
     
    public function simplemag_article_columns($defaults) {  
        $defaults['issue'] = 'Issue';  
        return $defaults;  
    }  
    public function simplemag_article_custom_column($column_name, $post_ID) {  
        if ($column_name == 'issue') {  
            $issueID = get_post_meta($post_ID, 'simplemag_issue', true); 
            $issue = get_post($issueID, ARRAY_A);
            //var_dump($issue);
            echo $issue['post_title'];
        } 
    } 
    
    
    /* This is only done on activation */
    
    public function simplemag_activate() {
        global $wp_rewrite;
        create_simplemag();
        add_rewrite_rule('issue/([0-9A-Za-z-]*)/?([0-9A-Za-z-]*)?/?',substr(SIMPLEMAG_PATH,1).'issue.php?issue=$1&article=$2','top');
        flush_rewrite_rules();
    }
    
    /* This is only done on deactivation */
    
    public function simplemag_deactivate() {
    	flush_rewrite_rules();
    }
        
}





class SimpleMagazineSettings{
    public function __construct(){
        if(is_admin()){
	        add_action('admin_menu', array($this, 'add_plugin_page'));
            add_action('admin_init', array($this, 'page_init'));
        }
    }
	
    public function add_plugin_page(){
        // This page will be under "Settings"
        add_options_page('Settings Admin', SIMPLEMAG_NAME, 'manage_options', 'test-setting-admin', array($this, 'create_admin_page'));
    }

    public function create_admin_page(){
        ?>
    	<div class="wrap">
    	    <?php screen_icon(); ?>
    	    <h2><?php echo SIMPLEMAG_NAME; ?> Settings</h2>			
    	    <form method="post" action="options.php">
    	        <?php
                        // This prints out all hidden setting fields
    		    settings_fields('test_option_group');	
    		    do_settings_sections('test-setting-admin');
    		?>
    	        <?php submit_button(); ?>
    	    </form>
    	</div>
    	<?php
    }
	
    public function page_init(){		
	    register_setting('test_option_group', 'array_key', array($this, 'saveValues'));
		
        add_settings_section(
    	    'setting_section_id',
    	    'Setting',
    	    array($this, 'print_section_info'),
    	    'test-setting-admin'
    	);	
    		
    	add_settings_field(
    	    'simplemag-google-analytics', 
    	    'Google Analytics ID', 
    	    array($this, 'createSettingsField'), 
    	    'test-setting-admin',
    	    'setting_section_id',
    	    array('id'=>'simplemag-google-analytics')				
    	);
    	add_settings_field(
    	    'simplemag-apple-app-id', 
    	    'Apple App ID', 
    	    array($this, 'createSettingsField'), 
    	    'test-setting-admin',
    	    'setting_section_id',
    	    array('id'=>'simplemag-apple-app-id')			
    	);		
    }
	
    public function saveValues($input){
        
        foreach($input as $key => $value){
            if(get_option($key) === FALSE){
    		    add_option($key, $value);
    	    }else{
    		    update_option($key, $value);
    	    }
        }
        return true;
    }
	
    public function print_section_info(){
	    print 'Enter your setting below:';
    }
	
    public function createSettingsField($data){
        ?><input type="text" name="array_key[<?=$data['id']?>]" value="<?=get_option($data['id']);?>" /><?php
    }
}







$simpleMag = new SimpleMagazine();


?>
