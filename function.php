<?php

/**
 * Plugin Name: test-plugin
 * Plugin URI: https://www.your-site.com/
 * Description: Test.
 * Version: 0.1
 * Author: your-name
 * Author URI: https://www.your-site.com/
 **/


add_action('admin_enqueue_scripts', 'dropBox_enqueue_scripts');

function dropBox_enqueue_scripts()
{
    wp_enqueue_script('DropBox-main-js', plugins_url('/js/main-script.js', __FILE__));
}



add_action('admin_menu', 'dropBox_plugin_create_menu');

function dropBox_plugin_create_menu()
{
    //create new top-level menu for dropbox
    add_menu_page('DropBox Settings', 'Drop Box Settings', 'administrator', __FILE__, 'dropbox_plugin_settings_page', plugins_url('/images/icon.png', __FILE__));
    //call register settings for fields function
    add_action('admin_init', 'register_DropBox_plugin_settings');
}


function register_DropBox_plugin_settings()
{
    //register our settings
    register_setting('drop-box-settings-group', 'dropBox-app-key');
    register_setting('drop-box-settings-group', 'dropbox-app-secret');
    register_setting('drop-box-settings-group', 'dropbox-app-access-code');
}

function dropbox_plugin_settings_page()
{
?>
    <div class="wrap">
        <h1>Drop Box Setting</h1>

        <form method="post" action="options.php">
            <?php settings_fields('drop-box-settings-group'); ?>
            <?php do_settings_sections('drop-box-settings-group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">App key</th>
                    <td><input type="text" name="dropBox-app-key" value="<?php echo esc_attr(get_option('dropBox-app-key')); ?>" /></td>
                </tr>

                <tr valign="top">
                    <th scope="row">App secret</th>
                    <td><input type="text" name="dropbox-app-secret" value="<?php echo esc_attr(get_option('dropbox-app-secret')); ?>" /></td>
                </tr>

                <tr valign="top">
                    <th scope="row">App secret</th>
                    <td><input type="text" name="dropbox-app-access-code" value="<?php echo esc_attr(get_option('dropbox-app-access-code')); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
<?php
}


add_action('add_meta_boxes', 'dropBox_featured_image_metabox');
function dropBox_featured_image_metabox()
{
    add_meta_box('DropBox_featuredImage', __('DropBox Image', 'dropBox_featured_image'), 'dropBox_featured_image_metabox_callback', 'post', 'side', 'low');
}

function dropBox_featured_image_metabox_callback($post)
{
    $content = '';
    global $content_width, $_wp_additional_image_sizes;

    $image_id = get_post_meta($post->ID, 'dropBox_featured_image', true);
    $show_image = get_post_meta($post->ID, 'dropBox_show_featured_image', true);

    $old_content_width = $content_width;
    $content_width = 254;

    if ($image_id && get_post($image_id)) {

        if (!isset($_wp_additional_image_sizes['post-thumbnail'])) {
            $thumbnail_html = wp_get_attachment_image($image_id, array($content_width, $content_width));
        } else {
            $thumbnail_html = wp_get_attachment_image($image_id, 'post-thumbnail');
        }

        if (!empty($thumbnail_html)) {
            $content = $thumbnail_html;
            $content .= '<p class="hide-if-no-js"><a href="javascript:;" id="remove_dropBox_image_button" >' . esc_html__('Remove DropBox Image', 'dropBox_featured_image') . '</a></p>';
            $content .= '<input type="hidden" id="upload_dropBox_image" name="_dropBox_featured_image" value="' . esc_attr($image_id) . '" />';
        }

        $content_width = $old_content_width;
    } else {

        $content = '<img src="" style="width:' . esc_attr($content_width) . 'px;height:auto;border:0;display:none;" />';
        $content .= '<p class="hide-if-no-js"><a title="' . esc_attr__('Set DropBox Image', 'dropBox_featured_image') . '" href="javascript:;" id="upload_dropBox_image_button" id="set-listing-image" data-uploader_title="' . esc_attr__('Choose an image', 'dropBox_featured_image') . '" data-uploader_button_text="' . esc_attr__('Set DropBox Image', 'dropBox_featured_image') . '">' . esc_html__('Set DropBox Image', 'dropBox_featured_image') . '</a></p>';
        $content .= '<input type="hidden" id="upload_dropBox_image" name="_dropBox_featured_image" value="" />';
    }
    
    $content .= '
        <input type="Checkbox" '.checked( $show_image, 'true' ).' id="dropBox_show_featured_image" name="_dropBox_show_featured_image" value="true" /> Show DropBox featured image
    ';
    echo $content;
}

add_action('save_post', 'listing_image_save', 10, 1);
function listing_image_save($post_id)
{
    if (isset($_POST['_dropBox_featured_image'])) {
        $image_id = (int) $_POST['_dropBox_featured_image'];
        update_post_meta($post_id, 'dropBox_featured_image', $image_id);
    }

    if (isset($_POST['_dropBox_show_featured_image'])) {
        $show_image = (string) $_POST['_dropBox_show_featured_image'];
        update_post_meta($post_id, 'dropBox_show_featured_image', $show_image);
    }else{
        update_post_meta($post_id, 'dropBox_show_featured_image', false);
    }
}


// apply_filters( 'wp_get_attachment_image_src', array|false $image, int $attachment_id, string|int[] $size, bool $icon )
// add_filter( 'wp_get_attachment_image_src', 'dropBox_wp_get_attachment_image_src_fn', 99, 4 );
// function dropBox_wp_get_attachment_image_src_fn( $image, $attachment_id, $size, $icon ) {
//     if ( !is_admin() ) {
//         print_r('sdg');
//         die();
//     }
//     print_r($image);
//     return $image;
// }

add_filter( 'post_thumbnail_html', 'change_featured_image' );
function change_featured_image($attr)
{
    global $wpdb;
    $opt = get_option('s3dcs_status');//My value in `wp_options`
    if(empty($opt)){
        return $attr;
    } 
    $pattern = '~(http.*\.)(jpe?g|png|[tg]iff?|svg)~i';
    $m = preg_match_all($pattern,$attr,$matches);
    $il = $matches[0][0];
    $tail = explode("wp-content", $il)[1];
    $s3dcs_remote_link = 'https://www.shutterstock.com/image-photo/word-demo-appearing-behind-torn-260nw-1782295403.jpg';
    return str_replace($il, $s3dcs_remote_link, $attr) ;
}