/**
 * @package    
 * @copyright  
 * @license    
 */

//-----------------------------------------------------------
//  Ensure we have our namespace
//-----------------------------------------------------------
if (!HUB) {
    var HUB = {};
}
if (!HUB.Plugins) {
    HUB.Plugins = {};
}
if (!jq) {
    var jq = $;
}

HUB.Plugins.ObjectStorage = {
    jQuery: jq,

    initialize: function () {
        // Set up all click event listeners
        // add click listeners to file list title
        var titles = $(".item-title");
        for (var i = 0; i < titles.length; i++) {
            titles[i].addEventListener('click', this.toggle, false);
        }
    },

    toggle: function (e) {
        // get p toggle element to set correct text
        var p = $($(this).find(".toggle-visibility"));
        // get container div, i.e. parent of event source
        var itemTitle = $($(this).parent());
        // file list is the second child
        var fileList = $(itemTitle.children()[1]);
        if (fileList.is(":hidden")) {
            fileList.show();
            p.text("Hide");
        } else {
            fileList.hide();
            p.text("Show");
        }
    }

}

jQuery(document).ready(function ($) {
    HUB.Plugins.ObjectStorage.initialize();
});