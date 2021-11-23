<?php
if ( !class_exists('STRACK_PLU_Auto_Plugin_Updater') ):

class STRACK_PLU_Auto_Plugin_Updater
{
    /**
     * The plugin current version
     * @var string
     */
    public $current_version;
 
    /**
     * The plugin remote update path
     * @var string
     */
    public $api_url;
 
    /**
     * Plugin Slug (plugin_directory/plugin_file.php)
     * @var string
     */
    public $plugin_slug;
	
	/**
     * Plugin License 
     * @var string
     */
    public $plugin_license;
	
	
	/**
     * Envato item ID 
     * @var string
     */
	public $envato_item_id;
 
    /**
     * Plugin name (plugin_file)
     * @var string
     */
    public $slug;
 
    /**
     * Initialize a new instance of the WordPress Auto-Update class
     * @param string $current_version
     * @param string $update_path
     * @param string $plugin_slug
     */
    function __construct($current_version, $api_url, $plugin_slug, $plugin_license = '', $envato_item_id = '')
    {
        // Set the class public variables
        $this->current_version = $current_version;
        $this->api_url = $api_url;
        $this->plugin_slug = $plugin_slug;
		$this->license = $plugin_license;
		$this->envato_item_id = $envato_item_id;
        list ($t1, $t2) = explode('/', $plugin_slug);
        //$this->slug = str_replace('.php', '', $t2);
        $this->slug = $t1;
        
        // define the alternative API for updating checking
        add_filter('pre_set_site_transient_update_plugins', array(&$this, 'check_update'));
 
        // Define the alternative response for information checking
        add_filter('plugins_api', array(&$this, 'plu_check_info'), 10, 3);
        
        //add_action( 'in_plugin_update_message-'.$plugin_slug, array(&$this,'plu_upgrade_message_link'), 10, 2 );
    }
 
    /**
     * Add our self-hosted autoupdate plugin to the filter transient
     *
     * @param $transient
     * @return object $ transient
     */
    public function check_update($transient)
    {
        if (empty($transient->checked)) {
            return $transient;
        }
 
        // Get the remote version
        $remote_version = $this->getRemote_version();
 
        // If a newer version is available, add the update
        if (version_compare($this->current_version, $remote_version, '<')) {
            $obj = new stdClass();
            $obj->slug = $this->slug;
            $obj->new_version = $remote_version;
            $obj->url = $this->api_url;
            $obj->package = $this->getPackage();
            $transient->response[$this->plugin_slug] = $obj;
        }
        //var_dump($transient);
        return $transient;
    }
 
    /**
     * Add our self-hosted description to the filter
     *
     * @param boolean $false
     * @param array $action
     * @param object $arg
     * @return bool|object
     */
    public function plu_check_info($false, $action, $arg)
    {
        if ($arg->slug === $this->slug) {
            $information = $this->getRemote_information();

            $array_pattern = array(
                '/^([\*\s])*([^\*]\d\.\d\.\d[^\n]*)/m',
                '/^\n+|^[\t\s]*\n+/m',
                '/\n/',
            );
            $array_replace = array(
                '<h4>$2</h4>',
                '</div><div>',
                '</div><div>',
            );
            $information->sections['changelog'] = '<div>' . preg_replace( $array_pattern, $array_replace, $information->sections['changelog'] ) . '</div>';

            return $information;
        }
		// http://stackoverflow.com/questions/7074616/wordpress-plugin-self-hosted-update
        return $false;
    }
 
    /**
     * Return the remote version
     * @return string $remote_version
     */
    public function getRemote_version()
    {
		global $wp_version;
		
		$request_string = array(
			'body' => array(
				'action' => 'version', 
				'slug' => $this->slug,
				'api-key' => md5(get_bloginfo('url')),
			),
			'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo('url')
		);
		
        $request = wp_remote_post($this->api_url, $request_string);
        if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {
            return $request['body'];
        }
        return false;
    }
 
    /**
     * Get information about the remote version
     * @return bool|object
     */
    public function getRemote_information()
    {
		global $wp_version;
		
		$request_string = array(
			'body' => array(
				'action' => 'info',
				'slug' => $this->slug, 
				'api-key' => md5(get_bloginfo('url')),
			),
			'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo('url')
		);
		
        $request = wp_remote_post($this->api_url, $request_string);
        if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {
            return unserialize($request['body']);
        }
        return false;
    }
 
    /**
     * Return the status of the plugin licensing
     * @return boolean $remote_license
     */
    public function getRemote_license()
    {
        $request = wp_remote_post($this->api_url, array('body' => array('action' => 'license')));
        if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {
            return $request['body'];
        }
        return false;
    }
	
	
	
	public function getPackage()
    {
        global $wp_version;
        
        //if( !empty($this->license)){
            $request_string = array(
                'body' => array(
                    'download'        => 1,
                    'slug'            => $this->slug,
                    'api-key'         => md5(get_bloginfo('url')),
                    'license'         => $this->license,
                    'envato_item_id'  => $this->envato_item_id
                ),
                'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo('url')
            );
            
            $request = wp_remote_post($this->api_url, $request_string);
            if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {
                //return unserialize($request['body']);
                return $request['body'];
            }
        //}
        //return false;
    }



    /**
	 * Shows message on Wp plugins page with a link for updating from envato.
	 */
    public function plu_upgrade_message_link($plugin_data, $response) 
    {
		$is_activated = !empty( $this->license );
		if ( ! $is_activated ) {
			$url = esc_url( 'admin.php?page=adning-updates' );
            $redirect = sprintf( '<a href="%s">%s</a>', $url, __( 'settings', 'adn' ) );
            
            printf(
                '<strong>%s</strong>',
                sprintf( ' ' . __( 'To receive automatic updates please visit %s to activate your %s license.', 'adn' ), $redirect, $plugin_data['Name'] )
            );
		}
	}

}

endif;