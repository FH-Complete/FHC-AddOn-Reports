/**
 * javascript file for managing systemfilters
 */

//set JS events for systemfilters (Ansichten) block
function setSysFilterEvents(get_params)
{
	$("#systemfilter").change(

		function() {
			get_params.systemfilter_id = $("#systemfilter").val();

/*			if (!$.isNumeric(get_params.systemfilter_id))
				return;*/

			getSysFilterPreferences(get_params, function () {
				showMsg("Fehler beim Setzten der Ansicht!", "text-danger");
			});
		}
	);

	if ($("#standardsysfilter").length)
	{
		$("#standardsysfilter").change(
			function() {
				var checked = $(this).prop("checked");
				var systemfilter_id = $("#systemfilter").val();

				setDefaultSysFilter(systemfilter_id, checked, function () {
					showMsg("Fehler beim Setzten der Standardansicht!", "text-danger");
				});
			}
		);
	}

	$("#addprivatesysfilterbtn").click(
		function() {
			if (validateFilterName())
			{
				saveSysFilter(get_params, function () {
					showMsg("Fehler beim Speichern der Ansicht!", "text-danger");
				}, false);
			}
			else
			{
				$("#addprvfiltergroup").addClass("has-error");
				showMsg("Ansichtname muss alphanumerisch, unverwendet und mind. 1 Zeichen sein!", "text-danger");
			}
		}
	);

	$("#updateprivatesysfilterbtn").click(
		function() {
			saveSysFilter(get_params, function () {
				showMsg("Fehler beim Speichern der Ansicht!", "text-danger");
			}, true);
		}
	);

	$("#deleteprivatesysfilterbtn").click(
		function() {
			get_params.systemfilter_id = $("#systemfilter").val();
			deleteSysFilter(get_params, function(){
				showMsg("Fehler beim Löschen der Ansicht!", "text-danger")
			});
		}
	);
	//Each time events are set, also set correct systemfilter_id in url
	appendSystemfilterToUrl(get_params);
}

/*--------------------------------------------- Systemfilters AJAX calls ---------------------------------------------*/

function getSysFilterPreferences(get_params, errorcallback)
{
	$.ajax(
		{
			url: './systemfilter.php',
			type: 'GET',
			datatype: 'json',
			data: {
				"action": "getPreferences",
				"statistik_kurzbz": get_params.statistik_kurzbz,
				"systemfilter_id": get_params.systemfilter_id,
			},
			success: function (data) {
				if (typeof data === "string")
				{
					var preferences = null;
					try
					{
						if (data !== '')
						{
							preferences = JSON.parse(data);
						}
						drawPivotUI(preferences);
						loadSysFilterBlock(get_params);
					}
					catch(e)
					{
						errorcallback();
					}
				} else
					errorcallback();
			},
			error:
			errorcallback
		}
	);
}

function saveSysFilter(get_params, errorcallback, update)
{
	var data = getSysFilterSaveData(get_params.statistik_kurzbz, update);

	$.ajax(
		{
			url: './systemfilter.php',
			type: 'POST',
			datatype: 'json',
			data: data,
			success: function(data) {
				try
				{
					var systemfilter_id = JSON.parse(data);
					if ($.isNumeric(systemfilter_id))
					{
						get_params.systemfilter_id = systemfilter_id;
						loadSysFilterBlock(get_params, "Ansicht erfolgreich gespeichert!");
					} else
						errorcallback();
				}
				catch(e)
				{
					errorcallback();
				}
			},
			error: errorcallback
		}
	);
}

function setDefaultSysFilter(systemfilter_id, checked, errorcallback)
{
	$.ajax(
		{
			url: './systemfilter.php',
			type: 'POST',
			datatype: 'json',
			data: {
				"action": "setDefault",
				"systemfilter_id": systemfilter_id,
				"default_filter": checked
			},
			success: function (data) {
				if (data === 'true')
				{
					if (checked)
					{
						$("#standardsysfilterlabel").css("font-weight", 700);
					}
					else
					{
						$("#standardsysfilterlabel").css("font-weight", "normal");
					}

				} else
					errorcallback();
			},
			error:
			errorcallback
		}
	);
}

function deleteSysFilter(get_params, errorcallback)
{
	$.ajax(
		{
			url: './systemfilter.php',
			type: 'POST',
			datatype: 'json',
			data: {
				"action": "deletePrivate",
				"systemfilter_id": get_params.systemfilter_id
			},
			success: function (data) {
				if (data === 'true')
				{
					get_params.systemfilter_id = 'undefined';
					getSysFilterPreferences(get_params, errorcallback);

				} else
					errorcallback();
			},
			error: errorcallback
		}
	);
}

function loadSysFilterBlock(get_params, message)
{
	$("#sysfilterblock").load(
		'systemfilter_block_view.php',
		{
			statistik_kurzbz: get_params.statistik_kurzbz,
			systemfilter_id: get_params.systemfilter_id
		},
		function (response){
			setSysFilterEvents(get_params);
			if (typeof message == 'string')
			{
				setTimeout(
					function () {
						showMsg(message, 'text-success')
					},
					50
				);
			}
		}
	);
}

/*--------------------------------------------- Systemfilters AJAX calls Ende---------------------------------------------*/

//Get data for saving a systemfilter (Ansicht)
function getSysFilterSaveData(statistik_kurzbz, update)
{
	if (GLOBAL_OPTIONS_STORAGE == null)
		return null;

	var filtername = $("#privatesysfiltername").val();

	var filter = JSON.stringify({"name" : filtername, "preferences": GLOBAL_OPTIONS_STORAGE});

	var data = {
		"action": "savePrivate",
		"filter": filter,
		"statistik_kurzbz": statistik_kurzbz
	};

	var filter_id = $("#systemfilter").val();

	//überschreiben des gewählten filters.
	if (update === true && $.isNumeric(filter_id))
	{
		data.systemfilter_id = filter_id;
	}

	return data;
}

//filtername should not be empty, only alphanumeric and not already used!
function validateFilterName()
{
	var filtername = $("#privatesysfiltername").val();
	var filtername_priv = filtername + " (p)";
	var filternamefound = false;
	var regex = new RegExp(/^[a-z0-9]+$/i);

	$("#systemfilter").children("option").each(
		function () {
			if ($(this).text() === filtername_priv)
				filternamefound = true;
		}
	);

	return regex.test(filtername) && !filternamefound;
}

//append systemfilter_id of currently selected systemfilter to url so statistik with certain filter applied can be bookmarked
function appendSystemfilterToUrl(get_params)
{
	var sysfilterurl = window.location.toString();
	if (sysfilterurl.indexOf("vorschau.php") > -1)
	{
		if (typeof get_params.systemfilter_id === 'undefined' || get_params.systemfilter_id === 'undefined')
		{
			get_params.systemfilter_id = $("#systemfilter").val();
		}

		if ($.isNumeric(get_params.systemfilter_id))
		{
			var regex = new RegExp(/systemfilter_id=[a-z0-9]+/i);
			if (regex.test(sysfilterurl))
			{
				sysfilterurl = sysfilterurl.replace(regex, "systemfilter_id=" + get_params.systemfilter_id);
			}
			else
			{
				var separator = sysfilterurl.indexOf("?") > -1 ? "&" : "?";
				sysfilterurl += separator + "systemfilter_id=" + get_params.systemfilter_id
			}
		}
		else
		{
			sysfilterurl = sysfilterurl.replace(new RegExp(/(\?|&)systemfilter_id=[a-z0-9]+/i), "");
		}
		window.history.pushState({systemfilter_id: get_params.systemfilter_id}, "", sysfilterurl);
	}
}

function showMsg(message, cssclass)
{
	$("#sysfiltermsg").html("<br><span class='" + cssclass + "'>" + message + "</span>");
}
