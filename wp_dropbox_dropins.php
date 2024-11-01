<?php
/**
 * Plugin Name: WP Dropbox Dropins
 * Plugin URI: http://redwanhilali.net
 * Description: Embed WordPress Dropins: Saver & Chooser.
 * Version: 1.0
 * Author: Redwan Hilali
 * Author URI: http://www.redwanhilali.net
 * License: GPL2
 */
 
if(is_admin()){
	add_action('admin_menu','load_menu');
	add_action( 'admin_init', 'wp_dropins_dropbox_setting' );
	add_action( 'admin_notices', 'wp_check_appkey' );
} 
function wp_add_dropbox_dropins_js() {
    $appkey = get_option('dropins_appkey');
	
    echo '<script type="text/javascript" src="https://www.dropbox.com/static/api/1/dropins.js" id="dropboxjs" data-app-key="'.$appkey.'"></script>';
}
// Add hook for admin <head></head>
add_action('admin_head', 'wp_add_dropbox_dropins_js');
// Add hook for front-end <head></head>
add_action('wp_head', 'wp_add_dropbox_dropins_js');
function load_menu(){
	add_menu_page( 'Dropbox Dropins', 'DropinsDB', 'manage_options', 'dbdropins-admin', 'wp_dropins_configure', WP_PLUGIN_URL . '/wp_dropbox_dropins/icon.png');
}
function wp_dropins_dropbox_setting() { // whitelist options
  register_setting( 'dbdropins-options', 'dropins_appkey' );
}
function wp_dropins_configure(){
	include("admin-dropins.php");
}
function wp_check_appkey(){
	 $appkey = get_option('dropins_appkey');
	 if($appkey == null || $appkey == ''){
		?>
		 <div class="error">
			<p><?php _e( 'Dropbox Dropins App Key is not set yet, please set the App Key before using it.' ); ?></p>
		</div>
		<?php 
			
	 }
}
/*----------------------------------------------
*  SAVER [dropbox_saver url='URL_FILE' filename='OPTIONAL_filename'] or dropbox_dropin_saver($url, $filanme)
*-----------------------------------------------*/
function dropbox_dropin_saver($url, $filename=''){
	if($url == '' || $url==null){
		echo "<b style='color:red'>You need to specify a URL for the Saver to work</b>";
		die();
	}
	$dataFileName = $filename != '' ? "data-filename='$filename'": '';
	echo "<a href='".$url."' ".$dataFileName." class='dropbox-saver'></a>";
}

function dropbox_dropin_saver_shortcode($atts){
	if(!isset($atts['url']) || $atts['url'] == '' || $atts['url']==null){
		echo "<b style='color:red'>You need to specify a URL for the Saver to work</b>";
		die();
	}
	$dataFileName = $atts['filename'] != '' ? "data-filename='".$atts['filename']."'" : '';
	return "<a href='".$atts['url']."' ".$dataFileName." class='dropbox-saver'></a>";
}
add_shortcode('dropbox_saver', 'dropbox_dropin_saver_shortcode');
 /*----------------------------------------------------------
 *   Chooser
 *-----------------------------------------------------------*/
 add_action('wp_ajax_chooser_upload', 'wp_dropins_upload_file');
 add_action('wp_ajax_nopriv_chooser_upload', 'wp_dropins_upload_file');
 function wp_dropin_download($url, $path)
{
  $dirname = dirname($path);
	if (!is_dir($dirname))
	{
		mkdir($dirname, 0755, true);
	}	
  # open file to write
  $fp = fopen ($path, 'w+');
  # start curl
  $ch = curl_init();
  curl_setopt( $ch, CURLOPT_URL, $url );
  # set return transfer to false
  curl_setopt( $ch, CURLOPT_RETURNTRANSFER, false );
  curl_setopt( $ch, CURLOPT_BINARYTRANSFER, true );
  curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
  # increase timeout to download big file
  curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 10 );
  # write data to local file
  curl_setopt( $ch, CURLOPT_FILE, $fp );
  # execute curl
  curl_exec( $ch );
  # close curl
  curl_close( $ch );
  # close local file
  fclose( $fp );

  if (filesize($path) > 0) return true;
}
 function wp_dropins_upload_file(){
    $_POST = $_POST['data'];
	if(!isset($_POST['filename']) || !isset($_POST['url'])){
		die();
	}
	
    	
	$url = $_POST['url'];
	$filename = $_POST['filename'];
	
	$upload_dir = wp_upload_dir();
	
    $fp = wp_dropin_download($url, $upload_dir['path'].'/'.$filename);
	
	if($fp){
		wp_send_json_success();
	}
	else{
		wp_send_json_error();
	}
 }
 add_action( 'wp_enqueue_scripts', 'wp_dropins_script' );
 function wp_dropins_script(){
	wp_register_script( 'db_dropins', plugins_url('/wp_dropbox_dropins/js/main.js') );
	wp_enqueue_script( 'db_dropins' );
	 $translation_array = array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) );
    wp_localize_script( 'db_dropins', 'db_dropins', $translation_array );
 }
 
 function dropbox_dropin_chooser($options = array()){
	
	extract( shortcode_atts( array(
				'name' => 'dropbox-chooser-files',
				'linktype' => 'direct',
				'multiple'=> false,
				'extensions' => '',
				'autoupload'=>false,
				'savepath'=>'',
				'id'=>'db-dropins-chooser'
				
			), $options) );
	$upload_dir = wp_upload_dir();
	if($savepath !== ''){
		$savepath = $savepath.'/';
	}
	
	echo '<input type="dropbox-chooser" id="'.$id.'" name="'.$name.'" data-link-type="'.$linktype.'" data-multiselect="'.$multiple.'" data-extensions="'.$extensions.'" style="visibility: hidden;"/>';
	echo '<div class="'.$id.'dropbox-files-container"></div>';
	echo '<a href="#" id="'.$id.'-btn">Upload</a>';
	
	?>
	<script>
		 // add an event listener to a Chooser button
		 var files = [];
		document.getElementById("<?php echo $id; ?>").addEventListener("DbxChooserSuccess",
			function(e) {
				files = e.files;
				jQuery('.<?php echo $id; ?>dropbox-files-container').html('');
				for(var f in files){
					jQuery('.<?php echo $id; ?>dropbox-files-container').append(files[f].name+"<br/>");
				}	
			}, false);
		jQuery('#<?php echo $id; ?>-btn').live('click', function(ev){
			for(var f in files){
				
				jQuery.post(
					db_dropins.ajaxurl, 
					{
						'action': 'chooser_upload',
						'data':   {filename: '<?php echo $savepath; ?>'+files[f].name, url:files[f].link}
					}, 
					function(response){
						if(response.success){
							alert("File saved successfully");
						} else {
							alert("Error while saving the selected file, please try again");
						}
					}
				);
			}
		})
	</script>
	<?php
 }
 
 function dropbox_dropin_chooser_shortcode($atts){
	ob_start();
	dropbox_dropin_chooser($atts);
	$output_string=ob_get_contents();;
ob_end_clean();

return $output_string;
 }
 add_shortcode('dropbox_chooser', 'dropbox_dropin_chooser_shortcode');
