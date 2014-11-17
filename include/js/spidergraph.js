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
			function output()
			{
				$('#spidergraphcontainer').spidergraph({
					'fields': fields,
					'gridcolor': 'rgba(20,20,20,1)'   
				});
				for (i=0; i<keys[0].length; i++)
				{
					var v=new Array();
					for (j=0; j<keys.length; j++)
					{
						v[j]=values[j][i]*10/max;
					}
					$('#spidergraphcontainer').spidergraph('addlayer', { 
						'strokecolor': strokecolors[i],
						'fillcolor': fillcolors[i],
						'data': v
					});
				}
				
			}
