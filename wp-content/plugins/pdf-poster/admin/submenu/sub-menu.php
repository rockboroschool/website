<?php
//-----------------------------------------------
// Helps 
//-----------------------------------------------

add_action('admin_menu', 'pdfp_support_page');
function pdfp_support_page()
{
    add_submenu_page('edit.php?post_type=pdfposter', 'Help ', 'Help', 'manage_options', 'pdfp-support', 'pdfp_support_page_callback');
}

function pdfp_support_page_callback()
{
    ?>
    <div class="bplugins-container">
        <div class="row">
            <div class="bplugins-features">
                <div class="col col-12">
                    <div class="bplugins-feature center">
                        <h1>Helpful Links</h1>
                    </div>
                </div>
            </div>
        </div>
    </div>
<div class="bplugins-container">
    <div class="row">
        <div class="bplugins-features">
            <div class="col col-4">
                <div class="bplugins-feature center">
                    <i class="fa fa-life-ring"></i>
                    <h3>Need any Assistance?</h3>
                    <p>Our Expert Support Team is always ready to help you out promptly.</p>
                    <a href="https://bplugins.com/support/" target="_blank" class="button
                    button-primary">Contact Support</a>
                </div>
            </div>
            <div class="col col-4">
                <div class="bplugins-feature center">
                    <i class="fa fa-file-text"></i>
                    <h3>Looking for Documentation?</h3>
                    <p>We have detailed documentation on every aspects of the plugin.</p>
                    <a href="https://pdfposter.com/docs/" target="_blank" class="button button-primary">Documentation</a>
                </div>
            </div>

            <div class="col col-4">
                <div class="bplugins-feature center">
                    <i class="fa fa-thumbs-up"></i>
                    <h3>Liked This Plugin?</h3>
                    <p>Glad to know that, you can support us by leaving a 5 &#11088; rating.</p>
                    <a href="https://wordpress.org/support/plugin/pdf-poster/reviews/#new-post" target="_blank" class="button
                    button-primary">Rate the Plugin</a>
                </div>
            </div>            
        </div>
    </div>
</div>

<div class="bplugins-container">
    <div class="row">
        <div class="bplugins-features">
            <div class="col col-12">
                <div class="bplugins-feature center">
                    <h1>Video Tutorials</h1><br/>
                    <div class="embed-container"><iframe width="100%" height="700px" src="https://www.youtube.com/embed/PcYaAw7gX7w" frameborder="0"
                    allowfullscreen></iframe></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
}

//-------------------------------------------------------------------
// Pro Plugin List
//------------------------------------------------------------------
// add_action('admin_menu', 'pdfp_pro_plugin_page');

// function pdfp_pro_plugin_page() {
// 	add_submenu_page( 'edit.php?post_type=pdfposter', 'Our PRO Plugins', 'Our PRO Plugins', 'manage_options', 'pdfp-pro-plugins', 'pdfp_proplugin_page_cb' );
// }

// function pdfp_proplugin_page_cb() {
// 	 $plugins = wp_remote_get('https://office-viewer.bplugins.com/premium-plugins-of-bplugins-llc/');

//  echo $plugins['body']; 
// }

//$table->display();
if (!class_exists('bPlugins_pdfp_free_plugins')) {
    class bPlugins_pdfp_free_plugins
    {

        public function __construct()
        {
            add_action('admin_menu', array($this, 'bPlugins_pdfp_free_plugins_menu'));
        }
        public function bPlugins_pdfp_free_plugins_menu()
        {
            add_submenu_page(
                'edit.php?post_type=pdfposter',
                'bPlugins',
                'Our Free Plugins',
                'manage_options',
                'plugin-install.php?s=abuhayat&tab=search&type=author'
            );
        }
    }
}
new bPlugins_pdfp_free_plugins();