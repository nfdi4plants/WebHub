jQuery(function ($) {
    // get button information
    var buttonAttribute = $('.shibboleth.account').find('button').attr('onclick');

    if (typeof buttonAttribute === 'undefined'){
        return;
    }
    var redirectLocation = buttonAttribute.split("\"")[1];
    // hide parts of the page, if no redirection link is set
    if (redirectLocation === '/login?authenticator=shibboleth&idp=') {
        $('.options').hide();
        $('.or').hide();
    }
});