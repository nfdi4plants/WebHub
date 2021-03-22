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
        // add click listeners to buckets
        var buckets = $(".bucket");
        for (var i = 0; i < buckets.length; i++) {
            buckets[i].addEventListener('click', this.chooseBucket, false);
        }
        // add click listeners to folders
        var buckets = $(".folder");
        for (var i = 0; i < buckets.length; i++) {
            buckets[i].addEventListener('click', this.chooseFolder, false);
        }
        // add click listeners to files
        var buckets = $(".file");
        for (var i = 0; i < buckets.length; i++) {
            buckets[i].addEventListener('click', this.chooseFile, false);
        }
    },

    chooseBucket: function(e) {
        $.ajax({
            type: "POST",
            url: "objectstorage",
            data: { "bucket" : $(this).html()},
            success: function() {
                location.reload();
            }
        });
    },

    chooseFolder: function(e) {
        $.ajax({
            type: "POST",
            url: "objectstorage",
            data: { "folder" : $(this).html()},
            success: function() {
                location.reload();
            }
        });
    },

    chooseFile: function(e) {
        $.ajax({
            type: "POST",
            url: "objectstorage",
            data: { "file" : $(this).html()},
            success: function() {}
        });
    }

}

jQuery(document).ready(function ($) {
    HUB.Plugins.ObjectStorage.initialize();
});