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


createBucket = function (bucket) {
    var url = window.location.href;
    if (url.includes('?')){
        url = url.split('?')[0];
    }
    var endpoint = url + '/createBucket';
    var parts =  {bucket: bucket};
    console.log(parts);
    $.ajax({
        type: 'POST',
        url: endpoint,
        data: parts,
        cache: false,
        success: function (response) {
            if (response !== 'undefined' && response != "") {
                response = JSON.parse(response);
                if (response["success"] == "false") {
                    var code = response.code;
                    var error = response.error != null ? response.error : "";
                    if (error == "") {
                        switch (code) {
                            case 403:
                                error = "Access Denied";
                                break;
                            case 409:
                                error = "Bucket already exists";
                                break;
                            default:
                                error = "Unexpected Error";
                        }
                    }
                    var message = code + ' - ' + error;
                    alert("Could not create Bucket " + bucket + " : " + message);
                    return;
                }
            }
            window.location.reload();
        }
    });
}

deleteItem = function (item) {
    // extract arguments from item url
    var parts = extractArgs(item);

    if (typeof parts["bucket"] !== "undefined" && typeof parts["prefix"] !== "undefined") {
        var url = window.location.href;
        if (url.includes("?")) {
            url = url.split("?")[0];
        }
        if (typeof parts["object"] !== "undefined" && parts["object"] != "") {
            confirmDelete = confirm("Are you sure you want to delete " + decodeURI(parts["object"]) + " ?");
            if (!confirmDelete) {
                return;
            }
        }
        else {
            var prefixParts = decodeURI(parts["prefix"]).split('/');
            var name = prefixParts[prefixParts.length - 1];
            confirmDelete = confirm("Are you sure you want to delete " + name + " and all files contained within ?");
            if (!confirmDelete) {
                return;
            }
        }

        const endpoint = url + "/delete";
        $.ajax({
            type: 'POST',
            url: endpoint,
            data: parts,
            cache: false,
            success: function (response) {
                if (response !== 'undefined' && response != "") {
                    response = JSON.parse(response);
                    if (response["success"] == "false") {
                        if (Array.isArray(response)) {
                            var messages = [];
                            response.forEach(entry => {
                                var code = entry.code;
                                var error = entry.error != null ? entry.error : "";
                                if (error == "") {
                                    switch (code) {
                                        case 403:
                                            error = "Access Denied";
                                            break;
                                        default:
                                            error = "Unexpected Error";
                                    }
                                }
                                var message = code + ' - ' + error;
                                messages.append(message);
                            })
                            var prefixParts = decodeURI(parts["prefix"]).split('/');
                            var name = prefixParts[prefixParts.length - 1];
                            alert("Could not delete one or multiple files in " + name + " : " + messages.join("\n"));
                        }
                        else {
                            var code = response.code;
                            var error = response.error != null ? response.error : "";
                            if (error == "") {
                                switch (code) {
                                    case 403:
                                        error = "Access Denied";
                                        break;
                                    default:
                                        error = "Unexpected Error";
                                }
                            }
                            var message = code + ' - ' + error;
                            alert("Could not delete file " + decodeURI(parts['object']) + " : " + message);
                            return;
                        }
                    }
                }
                window.location.reload();
            }
        });
    }

}

downloadItem = function (item) {
    const url = item.parentNode.previousSibling.href;
    window.open(url, '_blank');
}

extractArgs = function (item) {
    var previous = item.parentNode.previousSibling;
    if (previous === null) {
        previous = item.previousSibling;
    }
    var url = previous.href;
    var args = url.split("?")[1];
    var parts = {};
    args.split("&").forEach((arg) => { var arg = arg.split("="); parts[arg[0]] = arg[1] });
    return parts;
}

itemInfo = function (item) {
    // extract arguments from item url
    var parts = extractArgs(item);

    if (item.title.length == 0 && typeof parts["bucket"] !== "undefined" && typeof parts["prefix"] !== "undefined") {
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
                if (response === 'undefined' || response == "") {
                    return;
                }
                response = JSON.parse(response);
                if (response["success"] == "false") {
                    var code = response.code;
                    var error = response.error != null ? response.error : "";
                    if (error == "") {
                        switch (code) {
                            case 403:
                                error = "Access Denied";
                                break;
                            default:
                                error = "Unexpected Error";
                        }
                    }
                    var message = code + ' - ' + error;
                    item.title = "An error occured: " + message;
                }
                else {
                    item.title = "File size: " + response["File size"];
                }
            }
        });
    }

}

handleFiles = function (files) {
    for (var i = 0; i < files.length; i++) {
        var file = files.item(i);
        upload(file);
    }
}

upload = function (file) {
    var formdata = new FormData();
    formdata.set("file", file);
    // TODO: handle correctly if this doesn't exist
    if (file.webkitRelativePath !== "undefined") {
        formdata.set("path", file.webkitRelativePath);
    }

    var url = window.location.href;
    parts = url.split("?");
    url = parts[0];
    const endpoint = url + "/upload";

    // get current bucket and path from args
    parts = parts[1].split("&");
    formdata.set("bucket", parts[0].split("=")[1]);
    if (parts.length > 1) {
        formdata.set("prefix", parts[1].split("=")[1]);
    }
    if (parts.length > 2) {
        formdata.set("object", parts[2].split("=")[1]);
    }

    $.ajax({
        type: 'POST',
        url: endpoint,
        data: formdata,
        cache: false,
        contentType: false,
        processData: false,
        success: function (response) {
            if (response !== 'undefined' && response != "") {
                response = JSON.parse(response);
                if (response["success"] == "false") {
                    var code = response.code;
                    var error = response.error != null ? response.error : "";
                    if (error == "") {
                        switch (code) {
                            case 403:
                                error = "Access Denied";
                                break;
                            default:
                                error = "Unexpected Error";
                        }
                    }
                    var message = code + ' - ' + error;
                    alert("Could not upload file " + file.name + " : " + message);
                    return;
                }
            }
            $("progress").trigger("change");
        }
    });
}

HUB.Plugins.ObjectStorage = {
    jQuery: jq,

    initialize: function (e) {
        // clear input on page reload
        if ($("form").length > 0) {
            $("form")[0].reset();
        }
        $("#bucket-creation").submit(function(event){
            event.preventDefault();
            var bucket = $("#bucket-name")[0].value;
            createBucket(bucket);
        });

        // attach functionality to buttons
        $("#upload").click(this.prepareFileUpload);
        $("#uploadFiles").on("change", function () {
            var count = $(this).prop("files").length;
            if (count > 0) {
                var label = $("label[for='" + $(this).attr("id") + "']");
                if (count === 1) {
                    label.text('File selected');
                }
                else {
                    label.text(count + ' files selected');
                }
            }
        });
        $("#uploadFolder").on("change", function () {
            var label = $("label[for='" + $(this).attr("id") + "']");
            label.text('Folder selected');
        });
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
            $(".actions").append('<label for="file">Upload progress:</label><progress value="0">0</progress>');
            $("progress").on("change", function () {
                var progress = 1 / total + parseFloat($("progress").attr("value"));
                $("progress").attr("value", progress);
                $("progress").text(progress + " %");
                if (0.99 <= progress && progress <= 1.01) {
                    // reload page
                    setTimeout(function () { window.location.reload() }, 500);
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