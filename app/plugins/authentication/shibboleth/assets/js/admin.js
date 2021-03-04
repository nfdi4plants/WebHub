// auth link invalidation form
jQuery(function ($) {
	var form = $('.shibboleth')
	serialized = form.children('.serialized'),
		val = JSON.parse(serialized.val());
	form.find('li').each(function (_, li) {
		li = $(li);
		li.append(
			$('<button>Invalidate</button>')
				.attr('title', 'Remove this association so that the domain/email combination in question can be linked to a different account')
				.click(function () {
					val.push(li.data('id'));
					serialized.val(JSON.stringify(val));
					li.remove();
				})
		);
	});
});

// institution management form
jQuery(function ($) {
	$('#jform_params_institutions-lbl').hide();

	var prnt = $('.shibboleth'),
		// control values are stored in a JSON string so they fit in the extensions table
		serialized = $('.shibboleth input.serialized'),
		// initialize from existing params
		val = JSON.parse(serialized.val()),
		// update hidden input to reflect form state
		update = function () {
			serialized.val(JSON.stringify(val));
		},
		// update active idp list state
		updateIdps = function () {
			val.activeIdps = [];
			var anyInvalid = false;
			prnt.find('ul.active li').each(function (_, li) {
				addedEntities = {}
				var idp = {}, thisInvalid = false;
				// copy form data to 'val'
				$(li).find('input').each(function (_, inp) {
					inp = $(inp);
					var name = inp.attr('name');
					idp[name] = inp.val();
					// Only entity_id and label need to be set and not empty 
					thisInvalid = thisInvalid || name == 'entity_id' && !idp[name].replace(/\s/g, '') || name == 'label' && !idp[name].replace(/\s/g, '');
					anyInvalid = anyInvalid || thisInvalid;
				});
				if (!thisInvalid) {
					val.activeIdps.push(idp);
				}
			});
			if (anyInvalid) {
				idpWarning.show();
			}
			else {
				idpWarning.hide();
			}
			// propagate to JSON representation
			update();
		},
		idpWarning = $('<p class="warning">Not all ID providers will be saved! Each entry must have an entity ID and a label.</p>').hide();

	// make idp attribute keys slightly more presentable
	var keyToLabel = function (str) {
		return str[0].toUpperCase() + str.substr(1).replace('_', ' ') + ': ';
	};

	// make a new entry in the idp list
	var newActiveIdp = function (idp, before) {
		var li = $('<li>')
			.append($('<span class="ui-icon ui-icon-arrowthick-2-n-s">'))
			.append($('<span class="remove icon">').click(function () {
				li.remove();
				updateIdps();
			}))
		[before === true ? 'prependTo' : 'appendTo'](existing);
		if (before) {
			li.animate('pulsate', 'slow');
		}
		for (var k in idp) {
			if (k === 'logo_data' || k === 'logoData') {
				continue;
			}
			var control = mkInp(k, idp[k]);
			if (k == 'logo') {

			}
			else {
				control.input.change(updateIdps);
			}
			li.append(control.label);
		}
	};

	var addedEntities = {};
	if (val.activeIdps) {
		val.activeIdps.forEach(function (idp) {
			addedEntities[idp.entity_id] = 1;
		});
	}

	prnt.append($('<hr>'));
	var mkInp = function (lbl, val) {
		var inp = $('<input>').val(val).attr('name', lbl).data('orig', val);
		return {
			'label': $('<p>').append($('<label>').append($('<span>').text(keyToLabel(lbl))).append(inp)),
			'input': inp
		};
	};

	// new idp entry form
	var addNew = $('<div class="new idp">');
	['entity_id', 'label'].forEach(function (key) {
		var inp = mkInp(key);
		addNew.append(inp.label);
	});
	addNew.append($('<button><span class="add icon"></span> Add ID provider</button>').click(function (evt) {
		evt.stopPropagation();
		var idp = {};
		addNew.find('input').each(function (_, inp) {
			inp = $(inp);
			idp[inp.attr('name')] = inp.val().replace(/^\s+|\s+$/g, '');
			inp.val('');
		});
		newActiveIdp(idp, true);
		updateIdps();
		return false;
	}));

	// append existing active providers
	prnt
		.append($('<h4>Active ID providers</h4>'))
		.append(idpWarning)
		.append(addNew);
	var existing = $('<ul class="active">').sortable({ 'stop': updateIdps }).appendTo(prnt);
	val.activeIdps.forEach(newActiveIdp);
	$('<button>Sort</button>').appendTo(prnt).click(function (evt) {
		evt.stopPropagation();
		existing.children('li').sort(function (a, b) {
			var lblA = $(a).find('input[name=label]').val();
			var lblB = $(b).find('input[name=label]').val();
			return normalizeUniversity(lblA) > normalizeUniversity(lblB) ? 1 : -1;
		}).appendTo(existing);
		return false;
	});
});
