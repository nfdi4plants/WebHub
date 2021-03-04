jQuery(function ($) {
	// get necessary elements from dropdownmenu
	var optionContainer = $('.shibboleth.account'),
		selection = $('<select name="idp"">');
	var li = optionContainer.find('li');

	// add idp options from list
	li.each(function (_, li) {
		li = $(li);
		selection.append($('<option>')
			.data('content', li.data('content'))
			.val(li.data('entityid'))
			.text(li.text())
		);
	});
	optionContainer.empty().append(selection);

	// unset selection, so event is only fired after choosing IDP
	selection.val("");
	// submit the form when a selection is made
	selection
		.change(function (event) {
			// setTimeout makes the UI slightly nicer in that it gives a chance for
			// the selectpicker to update the selection before the browser starts
			// to wait on the form submission
			// TODO: this seems to be buggy for multiple options
			setTimeout(function () {
				var eid = selection.val();
				location = location.protocol + '//' + location.host + (location.port ? ':' + location.port : '') + '/login?authenticator=shibboleth&idp=' + encodeURIComponent(eid);
			}, 1);
		});
});