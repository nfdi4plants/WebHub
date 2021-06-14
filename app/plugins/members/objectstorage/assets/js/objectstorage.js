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

deleteItem = function (item) {
    // extract arguments from item url
    var parts = extractArgs(item);

    if (typeof parts["bucket"] !== "undefined" && typeof parts["prefix"] !== "undefined") {
        var url = window.location.href;
        if (url.includes("?")) {
            url = url.split("?")[0];
        }

        const endpoint = url + "/delete";
        $.ajax({
            type: 'POST',
            url: endpoint,
            data: parts,
            cache: false,
            success: function () {
                window.location.reload();
            }
        });
    }

}

downloadItem = function (item){
    const url = item.parentNode.previousSibling.href;
    window.open(url, '_blank');
}

extractArgs = function(item){
    var args = item.parentNode.previousSibling.href.split("?")[1];
    var parts = {};
    args.split("&").forEach((arg) => { var arg = arg.split("="); parts[arg[0]] = arg[1] });
    return parts;
}

itemInfo = function (item) {
    // extract arguments from item url
    var parts = extractArgs(item);

    if (typeof parts["bucket"] !== "undefined" && typeof parts["prefix"] !== "undefined") {
        var url = window.location.href;
        if (url.includes("?")) {
            url = url.split("?")[0];
        }

        const endpoint = url + "/info";
        $.ajax({
            type: 'POST',
            url: endpoint,
            data: parts,
            cache: false,
            success: function (response) {
                console.log(response);
            }
        });
    }

}

handleFiles = function (files) {
    for (var i = 0; i < files.length; i++) {
        var file = files.item(i);
        if (file.size > 0) {
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

upload = function (file) {
    var formdata = new FormData();
    formdata.set("file", file);
    if (file.webkitRelativePath !== "undefined") {
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
        success: function () {
            $("progress").trigger("change");
        }
    });
}

HUB.Plugins.ObjectStorage = {
    jQuery: jq,

    initialize: function (e) {
        // clear input on page reload
        
        // attach functionality to buttons
        $("#upload").click(this.prepareFileUpload);
        $(".delete").click(this.deleteFiles);
    },

    prepareFileUpload: function (e) {
        // collect input for files and folder upload, both a FileList
        var files = $("input[name=uploadFiles]").prop("files");
        var folder = $("input[name=uploadFolder]").prop("files");

        var total = 0;
        if (typeof files !== 'undefined') {
            total += files.length;
        }
        if (typeof folder !== 'undefined') {
            total += folder.length;
        }

        if (total > 0) {
            // add progress bar and change listeners for upload
            $(".actions").append('<label for="file">Upload progress:</label><progress max="100" value="0">0</progress>');
            $("progress").on("change", function () {
                var progress = 1 / total * 100 + parseFloat($("progress").attr("value"));
                $("progress").attr("value", progress);
                $("progress").text(progress + " %");
                if (99.9 <= parseFloat($("progress").attr("value")) <= 100.1) {
                    setTimeout(function(){window.location.reload()}, 500);
                }
            });
        }
        if (typeof files !== 'undefined') {
            handleFiles(files);
        }
        if (typeof folder !== 'undefined') {
            handleFiles(folder);
        }

        e.preventDefault();
    },
}

jQuery(document).ready(function ($) {
    HUB.Plugins.ObjectStorage.initialize();
});