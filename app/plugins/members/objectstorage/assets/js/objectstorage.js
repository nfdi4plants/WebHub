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


// aliases for later use
const chunk_size = 500*1024*1024*1024;

handleFiles = function(files){
    for (var i = 0; i < files.length; i++){
        var file = files.item(i);
        if(file.size > 0)
        {
            presign(file);
        }
    }
}

presign = function(file) {
    var name = file.name;
    var data = {};
    var path = $("#up").attr("href").split('?')[1];
    path.split("&").forEach(function(arg){
        if (arg.includes("=")){
            var parts = arg.split("=");
            if (parts.length == 2){
                data[parts[0]] = parts[1];
            }
        }
    })
    data["name"] = name;
    var url = window.location.href;
    if (url.includes("?")){
        url = url.split("?")[0];
    }

    const endpoint = url + "/sign";
    $.ajax({
        type: 'GET',
        dataType: 'json',
        url: endpoint,
        data: data,
        success: function(url){
            upload(url, file);
        },
        error: function(jqxhr, exception){
            console.log(exception);
        }
        
    })
}

upload = function(url, file){
    var formdata = new FormData();
    formdata.set('file', file);

    var request = new XMLHttpRequest();
    request.open("PUT", url);
    request.send(formdata);
}

HUB.Plugins.ObjectStorage = {
    jQuery: jq,

    initialize: function(e){
        // attach upload functionality to button
        $("#upload").click(this.prepareFileUpload);
    },

    prepareFileUpload: function(e) {
        // collect input for files and folder upload, both a FileList
        var files = $("input[name=uploadFiles]").prop('files');
        var folder = $("input[name=uploadFolder]").prop('files');

		if (typeof files !== 'undefined'){
            handleFiles(files);
        }
        if (typeof folder !== 'undefined'){
            handleFiles(folder);
        }
        e.preventDefault();
    },
}

jQuery(document).ready(function ($) {
    HUB.Plugins.ObjectStorage.initialize();
});