			var status;
			var fields=new Array();
			var values=new Array();
			var keys=new Array();
			var i=0;
			var j=0;
			var max=0;
			$.ajaxSetup({ cache: false });
			$.getJSON(source).done(function(data){
				//alert(data);
				var items = [];
				$.each( data,
					function(key,val)
					{
						//alert (key);
						items.push( "<li id='" + key + "'>" + val + "</li>" );
						fields[i]=key;
						keys[i]=new Array;
						values[i]=new Array;
						j=0;
						$.each(val, function (valkey,valval)
						{
							keys[i][j]=valkey;
							values[i][j]=valval;
							if (valval>max)
								max=valval;
							j++;
						});
						//alert(values[i]);
						i++;
					}
				);
				//$("<ul/>", {"class": "my-new-list",	html: items.join( "" )}).appendTo( "body" );
				output();
			});
			var tt = document.createElement('div'),
				leftOffset = -(~~$('html').css('padding-left').replace('px', '') + ~~$('body').css('margin-left').replace('px', '')),
					topOffset = -32;
				tt.className = 'ex-tooltip';
				document.body.appendChild(tt);
			function output()
			{
				var v=new Array();
				var vm=new Array();
				var vc=new Array();
					
				for (i=0; i<values[0].length; i++)
				{
					v[i]=new Array();
					for (j=0; j<fields.length; j++)
					{
						v[i][j]={"x": fields[j], "y": Math.round(values[j][i])};
					}
				}
				var data = {
					"main": [
					{
					  "label": "Studiensemester",
					  "data": v[0],
					  "className": ".foo"
					}
					],
					"xScale": "ordinal",
					"yScale": "linear",
					"comp": [
					{
					  "label": "Foo Target",
					  "data": v[1],
					  "className": ".comp.comp_foo",
					  "type": "line-dotted"
					},{
					  "label": "Foo2",
					  "data": v[2],
					  "className": ".comp.foo",
					  "type": "line-dotted"
					}
					]
				};
				var opts = {
					"mouseover": function (d, i) {
						var pos = $(this).offset();
						$(tt).text(d.x + ': ' + d.y)
						  .css({top: topOffset + pos.top, left: pos.left + leftOffset})
						  .show();
					  },
					  "mouseout": function (x) {
						$(tt).hide();
					  }
					};
				chart = new xChart('bar', data, '#xChart1',opts);				
			}	
