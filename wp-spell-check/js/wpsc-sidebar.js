( function( wp ) {
    //var registerPlugin = wp.plugins.registerPlugin;
    //var PluginSidebar = wp.editPost.PluginSidebar;
    //var el = wp.element.createElement;
	//var Text = wp.components.TextControl;
	//var Btn = wp.components.Button;
	//var url = window.location.href;

    /* registerPlugin( 'wpsc-sidebar', {
        render: function() {
            return el( PluginSidebar,
                {
                    name: 'wpsc-sidebar',
                    icon: 'admin-post',
                    title: 'WP Spell Check',
                },
                el( 'div',
                    { className: 'wpsc-sidebar-content' },
                    el( Text, {
						type: 'button',
                        value: 'Spell Check this page',
						onClick: function( content ) { window.location.href = url+'&wpsc-scan-page=1#wpscmetabox'; },
                    } ),
					el( Text, {
						type: 'button',
                        value: 'View Spelling Errors',
						onClick: function( content ) { wpsc_create_popup(); },
                    } ),
                )
            );
        },
    } ); */
} )( window.wp );