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
// const chunk_size = 500*1024*1024*1024;

deleteItem = function(item){
    // extract arguments from item url
    var args = item.previousSibling.href.split("?")[1];
    var parts = {};
    args.split("&").forEach((arg) => {var arg = arg.split("="); parts[arg[0]] = arg[1]});
    
    if (typeof parts["bucket"] !== "undefined" && typeof parts["prefix"] !== "undefined"){
        var url = window.location.href;
        if (url.includes("?")){
            url = url.split("?")[0];
        }
    
        const endpoint = url + "/delete";
        $.ajax({
            type: 'POST',
            url: endpoint,
            data: parts,
            cache: false,
            success: function(){
                window.location.reload();
            }
        });
    }

}

handleFiles = function(files){
    for (var i = 0; i < files.length; i++){
        var file = files.item(i);
        if(file.size > 0)
        {
            upload(file);
        }
    }
}

// presign = function(file) {
//     var name = file.name;
//     var data = {};
//     var path = window.location.href.split('?')[1];
//     path.split("&").forEach(function(arg){
//         if (arg.includes("=")){
//             var parts = arg.split("=");
//             if (parts.length == 2){
//                 data[parts[0]] = parts[1];
//             }
//         }
//     })
//     data["name"] = name;
//     var url = window.location.href;
//     if (url.includes("?")){
//         url = url.split("?")[0];
//     }

//     const endpoint = url + "/sign";
//     $.ajax({
//         type: 'GET',
//         dataType: 'json',
//         url: endpoint,
//         data: data,
//         success: function(url){
//             upload(url, file);
//         },
//         error: function(jqxhr, exception){
//             console.log(exception);
//         }
        
//     })
// }

upload = function(file){
    var formdata = new FormData();
    formdata.set("file", file);
    if (file.webkitRelativePath !== "undefined"){
        formdata.set("path", file.webkitRelativePath);
    }

    var url = window.location.href;
    parts = url.split("?");
    url = parts[0];
    const endpoint = url + "/upload";

    // get current bucket and path
    parts = parts[1].split("&");
    formdata.set("bucket", parts[0].split("=")[1]);
    formdata.set("prefix", parts[1].split("=")[1]);

    $.ajax({
        type: 'POST',
        url: endpoint,
        data: formdata,
        cache: false,
        contentType: false,
        processData: false,
        success: function(){
            $("progress").trigger("change");
        }
    });
}

HUB.Plugins.ObjectStorage = {
    jQuery: jq,

    initialize: function(e){
        // attach upload functionality to button
        $("#upload").click(this.prepareFileUpload);
        $(".delete").click(this.deleteFiles);
    },

    prepareFileUpload: function(e) {
        // collect input for files and folder upload, both a FileList
        var files = $("input[name=uploadFiles]").prop('files');
        var folder = $("input[name=uploadFolder]").prop('files');

        $(".actions").append('<label for="file">Upload progress:</label><progress max="100" value="0">0</progress>');
        $("progress").on("change", function(){
            var progress = 1/total*100 + parseInt($("progress").attr("value"));
            $("progress").attr("value", progress);
            $("progress").text(progress + " %");
        });
        $("progress").on("click", function(){
            if ( 99.5 <= parseFloat($("progress").attr("value")) <= 100){
                window.location.reload(), 3000;
            }
        })

        var total = 0;
        if (typeof files !== 'undefined'){
            total += files.length;
        }
        if (typeof folder !== 'undefined'){
            total += folder.length;
        }
        
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